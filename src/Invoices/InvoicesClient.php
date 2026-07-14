<?php

declare(strict_types=1);

namespace Xingen\Sdk\Invoices;

use Generator;
use Xingen\Sdk\Http\ResponseHandler;
use Xingen\Sdk\Internal\FileUpload;
use Xingen\Sdk\Internal\HttpClient;
use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Internal\Wire;
use Xingen\Sdk\Models\AutoFilledField;
use Xingen\Sdk\Models\ExtractionModelTier;
use Xingen\Sdk\Models\ValidationProfile;
use Xingen\Sdk\Paging\Page;

final class InvoicesClient
{
    private const BASE_PATH = '/v1/invoices';
    private const VALIDATE_PATH = self::BASE_PATH . '/validate';
    private const VALIDATE_IDOC_PATH = self::BASE_PATH . '/validate/idoc';
    private const VALIDATE_ODATA_PATH = self::BASE_PATH . '/validate/odata';
    private const EXTRACT_PATH = self::BASE_PATH . '/extract';
    private const AUTO_FILLED_FIELDS_PATH = self::BASE_PATH . '/auto-filled-fields';

    /** @var array<string, string> */
    private const CONTENT_TYPES = ['.xml' => 'application/xml', '.pdf' => 'application/pdf'];

    public function __construct(private readonly HttpClient $http)
    {
    }

    public function submit(InvoiceSubmission $submission): InvoiceSubmissionResult
    {
        $response = $this->http->request(
            'POST',
            self::BASE_PATH,
            jsonBody: Json::encode($submission->toArray()),
        );

        return ResponseHandler::decodeOrRaise($response, InvoiceSubmissionResult::fromWire(...));
    }

    public function get(string $invoiceId): InvoiceRecord
    {
        $response = $this->http->request('GET', self::BASE_PATH . '/' . $invoiceId);

        return ResponseHandler::decodeOrRaise($response, InvoiceRecord::fromWire(...));
    }

    /** Applies a JSON merge-patch (RFC 7386) to the invoice's canonical fields -- e.g. to fill in
     * fields an AI extraction missed, or fix a value flagged by validation -- and re-validates
     * synchronously. Array fields (lines, paymentMeans, allowanceCharges, taxBreakdowns) are
     * replaced wholesale when present in the patch; submit the complete corrected array, not a
     * single element. Only invoices that have finished processing (VALIDATED or
     * FAILED_VALIDATION) can be corrected.
     * @param string|array<string, mixed> $patch either a raw JSON merge-patch document, or a
     *   plain array of canonical invoice fields to encode */
    public function patchInvoice(string $invoiceId, string|array $patch): InvoiceRecord
    {
        $body = is_string($patch) ? $patch : Json::encode($patch);
        $response = $this->http->request(
            'PATCH',
            self::BASE_PATH . '/' . $invoiceId,
            jsonBody: $body,
        );

        return ResponseHandler::decodeOrRaise($response, InvoiceRecord::fromWire(...));
    }

    public function list(int $page, int $size, ?string $sort = null): Page
    {
        $response = $this->http->request('GET', self::BASE_PATH, query: [
            'page' => $page,
            'size' => $size,
            'sort' => $sort,
        ]);

        return ResponseHandler::decodeOrRaise(
            $response,
            fn (mixed $raw): Page => Page::fromWire($raw, InvoiceRecord::fromWire(...)),
        );
    }

    /** Lazily iterates every invoice, fetching the next page only once the current page's
     * content is exhausted.
     * @return Generator<InvoiceRecord> */
    public function listAll(int $pageSize): Generator
    {
        $pageIndex = 0;
        while (true) {
            $page = $this->list($pageIndex, $pageSize, 'createdAt,desc');
            foreach ($page->content as $record) {
                yield $record;
            }
            if ($page->last) {
                return;
            }
            $pageIndex++;
        }
    }

    /** Returns, per validation profile, the invoice fields the JSON create/PATCH/AI-extraction
     * endpoints backfill automatically (e.g. invoice type code, specification identifier) -- so
     * a caller can tell the user rather than leave the gap unexplained.
     * @return array<string, list<AutoFilledField>> */
    public function getAutoFilledFields(): array
    {
        $response = $this->http->request('GET', self::AUTO_FILLED_FIELDS_PATH);

        return ResponseHandler::decodeOrRaise(
            $response,
            fn (mixed $raw): array => Wire::arrMap(Wire::asWire($raw), AutoFilledField::fromWire(...)),
        );
    }

    /** Either a filesystem path, or an in-memory [filename, content] pair.
     * @param string|array{0: string, 1: string} $file */
    private function resolveFile(string|array $file): FileUpload
    {
        if (is_array($file)) {
            [$filename, $content] = $file;
        } else {
            $filename = basename($file);
            $content = file_get_contents($file);
            if ($content === false) {
                throw new \RuntimeException("Could not read file: {$file}");
            }
        }

        return new FileUpload($filename, $content, self::guessContentType($filename));
    }

    private static function guessContentType(string $filename): string
    {
        $extension = strtolower((string) strrchr($filename, '.'));

        return self::CONTENT_TYPES[$extension] ?? 'application/octet-stream';
    }

    /** @param string|array{0: string, 1: string} $file */
    private function validate(string $path, string|array $file, ValidationProfile $profile): InvoiceSubmissionResult
    {
        // `profile` must be a query parameter, never a form field, even though this is a
        // multipart/form-data endpoint -- the backend's Spring @RequestParam binds it from
        // the query string, not the multipart body.
        $response = $this->http->request(
            'POST',
            $path,
            query: ['profile' => $profile->value],
            file: $this->resolveFile($file),
        );

        return ResponseHandler::decodeOrRaise($response, InvoiceSubmissionResult::fromWire(...));
    }

    /** @param string|array{0: string, 1: string} $file */
    public function validateFile(string|array $file, ValidationProfile $profile): InvoiceSubmissionResult
    {
        return $this->validate(self::VALIDATE_PATH, $file, $profile);
    }

    /** @param string|array{0: string, 1: string} $file */
    public function validateIdoc(string|array $file, ValidationProfile $profile): InvoiceSubmissionResult
    {
        return $this->validate(self::VALIDATE_IDOC_PATH, $file, $profile);
    }

    /** Uploads a plain (non-XRechnung/UBL/CII) invoice PDF -- including scanned/image-based PDFs
     * -- for AI-based field extraction. The extracted invoice is validated and converted to a
     * ZUGFeRD/Factur-X hybrid PDF, downloadable via {@see self::downloadPdf()} once processing
     * completes. Processing is asynchronous -- poll {@see self::get()} or use
     * {@see self::extractInvoiceAndWait()}. `$tier === ExtractionModelTier::ACCURATE` requires a
     * Pro subscription.
     * @param string|array{0: string, 1: string} $file */
    public function extractInvoice(string|array $file, ValidationProfile $profile, ExtractionModelTier $tier): InvoiceSubmissionResult
    {
        // profile/tier must be query parameters, never form fields, even though this is a
        // multipart/form-data endpoint -- same gotcha as validate()/validateIdoc() above.
        $response = $this->http->request(
            'POST',
            self::EXTRACT_PATH,
            query: ['profile' => $profile->value, 'tier' => $tier->value],
            file: $this->resolveFile($file),
        );

        return ResponseHandler::decodeOrRaise($response, InvoiceSubmissionResult::fromWire(...));
    }

    /** Thin, deliberately untyped passthrough for SAP OData payloads -- these are large and
     * integration-specific, so no typed model is provided.
     * @param string|array<string, mixed> $payload */
    public function submitOdata(string|array $payload, ValidationProfile $profile): InvoiceSubmissionResult
    {
        $body = is_string($payload) ? $payload : Json::encode($payload);
        $response = $this->http->request(
            'POST',
            self::VALIDATE_ODATA_PATH,
            query: ['profile' => $profile->value],
            jsonBody: $body,
        );

        return ResponseHandler::decodeOrRaise($response, InvoiceSubmissionResult::fromWire(...));
    }

    /** Downloads the ZUGFeRD PDF (with embedded XML) for a validated invoice. */
    public function downloadPdf(string $invoiceId): string
    {
        $response = $this->http->request(
            'GET',
            self::BASE_PATH . '/' . $invoiceId . '/download',
            headers: ['Accept' => 'application/pdf'],
        );

        return ResponseHandler::bytesOrRaise($response);
    }

    public function downloadIdocXml(string $invoiceId): string
    {
        $response = $this->http->request(
            'GET',
            self::BASE_PATH . '/' . $invoiceId . '/download/idoc',
            headers: ['Accept' => 'application/xml'],
        );

        return ResponseHandler::bytesOrRaise($response);
    }

    public function submitAndWait(InvoiceSubmission $submission, ?PollOptions $options = null): InvoiceRecord
    {
        $result = $this->submit($submission);

        return Polling::pollUntilTerminal(fn () => $this->get($result->id), $result->id, $options ?? new PollOptions());
    }

    /** @param string|array{0: string, 1: string} $file */
    public function validateFileAndWait(
        string|array $file,
        ValidationProfile $profile,
        ?PollOptions $options = null,
    ): InvoiceRecord {
        $result = $this->validateFile($file, $profile);

        return Polling::pollUntilTerminal(fn () => $this->get($result->id), $result->id, $options ?? new PollOptions());
    }

    /** @param string|array{0: string, 1: string} $file */
    public function validateIdocAndWait(
        string|array $file,
        ValidationProfile $profile,
        ?PollOptions $options = null,
    ): InvoiceRecord {
        $result = $this->validateIdoc($file, $profile);

        return Polling::pollUntilTerminal(fn () => $this->get($result->id), $result->id, $options ?? new PollOptions());
    }

    /** Uploads `$file` for AI extraction and polls {@see self::get()} until validation reaches a
     * terminal status.
     * @param string|array{0: string, 1: string} $file */
    public function extractInvoiceAndWait(
        string|array $file,
        ValidationProfile $profile,
        ExtractionModelTier $tier,
        ?PollOptions $options = null,
    ): InvoiceRecord {
        $result = $this->extractInvoice($file, $profile, $tier);

        return Polling::pollUntilTerminal(fn () => $this->get($result->id), $result->id, $options ?? new PollOptions());
    }
}
