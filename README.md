# xingen-sdk

PHP client SDK for the [Xingen](https://xingen.de) e-invoice validation API — submit UBL, CII,
ZUGFeRD, and SAP IDoc/OData invoices for validation against EN16931, XRechnung, and Peppol.

Requires PHP 8.1+ with the `curl` and `json` extensions (both bundled with PHP by default). No
third-party runtime dependencies.

> Status: v1, covering invoice submission/validation and API key management. Contacts and
> dashboard/user endpoints are not exposed (they're Firebase-auth-only on the backend).

## Install

```bash
composer require xingen/xingen-sdk
```

## Authentication

Every request needs an API key (`xgn_live_...` for production, `xgn_test_...` for sandbox —
sandbox requests never count toward quota). Create one from the Xingen dashboard or via
`$client->apiKeys`.

```php
use Xingen\Sdk\XingenClient;

$client = new XingenClient(apiKey: getenv('XINGEN_API_KEY'));
```

`XingenClient` holds one connection-pooled curl handle — construct it once and reuse it, don't
rebuild it per request. `baseUrl:` overrides the default `https://app.xingen.de/api`, useful for
self-hosted or local (`./gradlew bootRun`, port 10001) testing.

## Validate a file

Every validate/submit endpoint is asynchronous — the backend queues the invoice and returns
immediately. Use a `*AndWait` helper to submit and poll for the result in one call:

```php
use Xingen\Sdk\Models\InvoiceStatus;
use Xingen\Sdk\Models\ValidationProfile;

$result = $client->invoices->validateFileAndWait('invoice.xml', ValidationProfile::XRECHNUNG);

if ($result->status === InvoiceStatus::VALIDATED && $result->validationResult->valid) {
    echo "Valid!\n";
} else {
    foreach ($result->validationResult->errors as $error) {
        echo "{$error->severity->value}: {$error->message} ({$error->field})\n";
    }
}
```

`PollOptions` controls the backoff (`initialInterval`, `maxInterval`, `backoffMultiplier`), the
overall `timeout`, and an optional `cancellationCheck`. A **failed validation is not an
exception** — it's a completed API call that found the invoice invalid, so `*AndWait` returns
normally with `validationResult->valid === false`. Only a transport failure, cancellation, or
timeout throws.

```php
use Xingen\Sdk\Invoices\PollOptions;

$options = new PollOptions(initialInterval: 0.3, maxInterval: 3.0, timeout: 30.0);
$result = $client->invoices->validateFileAndWait('invoice.xml', ValidationProfile::XRECHNUNG, $options);
```

If you'd rather manage polling yourself, use the low-level pair:

```php
$submitted = $client->invoices->validateFile('invoice.xml', ValidationProfile::EN16931);
// ... later ...
$record = $client->invoices->get($submitted->id);
```

`validateIdoc` / `validateIdocAndWait` work the same way for SAP IDoc XML files. Both, and
`validateFile`/`validateFileAndWait`, also accept a `[$filename, $content]` array if you already
hold the file bytes in memory instead of a path.

## Submit a structured invoice (JSON)

```php
use Xingen\Sdk\Invoices\AddressInput;
use Xingen\Sdk\Invoices\InvoiceSubmission;
use Xingen\Sdk\Invoices\LineInput;
use Xingen\Sdk\Invoices\PartyInput;

$submission = new InvoiceSubmission(
    invoiceNumber: 'INV-2024-0042',
    issueDate: '2024-03-15',
    currency: 'EUR',
    validationProfile: ValidationProfile::XRECHNUNG,
    supplier: new PartyInput(
        name: 'Acme GmbH',
        vatId: 'DE123456789',
        address: new AddressInput(city: 'Berlin', countryCode: 'DE'),
    ),
    buyer: new PartyInput(
        name: 'Buyer Co',
        leitwegId: '991-12345-06',
        address: new AddressInput(countryCode: 'DE'),
    ),
    buyerReference: '991-12345-06',
    lines: [
        new LineInput(
            description: 'Software License Q1',
            quantity: '5',
            unit: 'C62',
            price: '199.00',
            taxRate: '19',
        ),
    ],
);

$result = $client->invoices->submitAndWait($submission);
```

SAP S/4HANA OData supplier-invoice payloads are supported as a thin passthrough — pass a raw JSON
string or a plain array rather than a fully typed model:

```php
$client->invoices->submitOdata($rawOdataJson, ValidationProfile::EN16931);
```

## Extract an invoice from a PDF (AI)

Upload a plain invoice PDF — including scanned/image-based PDFs — and let the backend extract
structured fields with Claude. Works exactly like the other submit endpoints: async, so use
`extractInvoiceAndWait` or the low-level `extractInvoice`/`get` pair.

```php
use Xingen\Sdk\Models\ExtractionModelTier;

$result = $client->invoices->extractInvoiceAndWait(
    'scanned-invoice.pdf',
    ValidationProfile::EN16931,
    ExtractionModelTier::FAST,   // or ACCURATE -- higher accuracy, Pro subscription required
);
```

If the extraction missed a field or validation flagged something, correct it with a JSON
merge-patch (RFC 7386) and re-validate synchronously — only invoices that finished processing
(`VALIDATED` or `FAILED_VALIDATION`) can be corrected. Array fields (`lines`, `paymentMeans`,
`allowanceCharges`, `taxBreakdowns`) are replaced wholesale when present in the patch:

```php
$corrected = $client->invoices->patchInvoice($result->id, [
    'currency' => 'EUR',
    'buyerReference' => '991-12345-06',
]);
```

To find out which fields the backend fills in automatically per profile (so you know what *not*
to prompt the user for):

```php
$autoFilled = $client->invoices->getAutoFilledFields();
// ['EN16931' => [AutoFilledField, ...], 'PEPPOL' => [...], ...]
```

## List and retrieve invoices

```php
$page = $client->invoices->list(0, 20, 'createdAt,desc');

// or, to walk every invoice without managing page indices yourself:
foreach ($client->invoices->listAll(50) as $record) {
    echo "{$record->id} -> {$record->status->value}\n";
}

$one = $client->invoices->get('inv_01HXYZ');
```

## Download results

```php
$pdf = $client->invoices->downloadPdf($id);          // ZUGFeRD PDF with embedded XML
$idocXml = $client->invoices->downloadIdocXml($id);   // SAP IDoc XML
```

## API keys

```php
use Xingen\Sdk\ApiKeys\CreateApiKeyRequest;

$created = $client->apiKeys->create(new CreateApiKeyRequest(name: 'Production CI', sandbox: false));
echo "Store this now, it's shown only once: {$created->rawKey}\n";

$keys = $client->apiKeys->list();
$client->apiKeys->revoke($created->id);
```

## Error handling

All SDK exceptions extend `Xingen\Sdk\Error\XingenException`. HTTP errors map to typed subclasses
of `ApiException`:

| Exception | Status | Notes |
|---|---|---|
| `AuthenticationException` | 401 | Missing or invalid API key |
| `PermissionException` | 403 | Resource exists but isn't owned by the caller |
| `NotFoundException` | 404 | |
| `ValidationRequestException` | 400 | `->fieldErrors` has details for request-body validation failures |
| `QuotaExceededException` | 429 | Monthly request quota exhausted |
| `ApiException` | other 4xx/5xx | Fallback; `->statusCode` / `->rawBody` always available |

```php
use Xingen\Sdk\Error\QuotaExceededException;
use Xingen\Sdk\Error\ValidationRequestException;
use Xingen\Sdk\Error\XingenException;

try {
    $client->invoices->submit($submission);
} catch (ValidationRequestException $e) {
    foreach ($e->fieldErrors as $field => $message) {
        echo "{$field}: {$message}\n";
    }
} catch (QuotaExceededException $e) {
    echo "Quota exceeded — upgrade or wait for the next billing period\n";
} catch (XingenException $e) {
    echo "Request failed: {$e->getMessage()}\n";
}
```

## Design notes

- **No automatic retries.** Retrying a `submit()` after a client-side timeout is unsafe without
  idempotency keys, which the API doesn't support yet — a retried submit could create a duplicate
  invoice. Handle retries at the call site if you need them.
- **Monetary and quantity fields are `string`, never `float`.** PHP has no native
  arbitrary-precision decimal type, so `float` would silently lose precision on large or
  exact-decimal amounts. Response bodies are parsed through a small dependency-free JSON decoder
  (`Xingen\Sdk\Internal\Json`) that preserves every numeric literal as its exact source-text
  string instead of routing it through PHP's `json_decode()` (which, like most JSON parsers,
  converts numbers through a float64 intermediate). Request bodies send these same fields as plain
  PHP strings, which the backend's Jackson-based deserializer accepts for its `BigDecimal` fields
  without issue.
- **No third-party dependencies.** HTTP is handled via `ext-curl` (bundled with PHP), reusing a
  single connection-pooled handle across requests rather than a Composer HTTP client package.

## Contributing

```bash
composer install
vendor/bin/phpunit
```

Tests run against a real (loopback) `php -S` server subprocess, not a mocking framework — no
network calls leave the machine, and no external test-server dependency is required.

## License

MIT — see [LICENSE](LICENSE).
