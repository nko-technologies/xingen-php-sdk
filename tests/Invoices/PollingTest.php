<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\Invoices;

use Xingen\Sdk\Error\XingenCancellationException;
use Xingen\Sdk\Error\XingenTimeoutException;
use Xingen\Sdk\Invoices\InvoiceSubmission;
use Xingen\Sdk\Invoices\LineInput;
use Xingen\Sdk\Invoices\PartyInput;
use Xingen\Sdk\Invoices\PollOptions;
use Xingen\Sdk\Invoices\Polling;
use Xingen\Sdk\Models\InvoiceStatus;
use Xingen\Sdk\Models\ValidationProfile;
use Xingen\Sdk\Tests\Support\LoopbackTestCase;

final class PollingTest extends LoopbackTestCase
{
    private static function minimalSubmission(): InvoiceSubmission
    {
        return new InvoiceSubmission(
            invoiceNumber: 'INV-1',
            issueDate: '2026-01-01',
            currency: 'EUR',
            validationProfile: ValidationProfile::EN16931,
            supplier: new PartyInput(name: 'Seller'),
            buyer: new PartyInput(name: 'Buyer'),
            lines: [new LineInput(description: 'Item', quantity: '1', unit: 'C62', price: '10', taxRate: '0')],
        );
    }

    private static function recordJson(string $id, string $status, bool $valid): string
    {
        $processing = $status === 'processing';
        $canonical = $processing ? 'null' : '{"invoiceNumber":"INV-1","currency":"EUR","lines":[],"notes":[]}';
        $validationResult = $processing ? 'null' : '{"valid":' . ($valid ? 'true' : 'false') . ',"errors":[],"kositResult":null}';

        return '{"id":"' . $id . '","status":"' . $status . '","createdAt":"2026-07-08T09:30:00Z",'
            . '"validationProfile":"EN16931","invoiceFormat":"UBL","uploadedBy":"user_abc",'
            . '"sandbox":false,"apiKeyId":"3fa85f64-5717-4562-b3fc-2c963f66afa6",'
            . '"canonicalJson":' . $canonical . ',"validationResult":' . $validationResult . '}';
    }

    public function testSubmitAndWaitPollsUntilValidatedApplyingExponentialBackoff(): void
    {
        $this->server->route('/v1/invoices', 202, '{"id":"inv_1","status":"processing"}');
        $this->server->routeSequence('/v1/invoices/inv_1', [
            ['status' => 200, 'body' => self::recordJson('inv_1', 'processing', true)],
            ['status' => 200, 'body' => self::recordJson('inv_1', 'processing', true)],
            ['status' => 200, 'body' => self::recordJson('inv_1', 'validated', true)],
        ]);

        $result = $this->client->invoices->submit(self::minimalSubmission());

        $recordedSleeps = [];
        $record = Polling::pollUntilTerminal(
            fn () => $this->client->invoices->get($result->id),
            $result->id,
            new PollOptions(),
            sleep: function (float $seconds) use (&$recordedSleeps): void {
                $recordedSleeps[] = $seconds;
            },
        );

        $this->assertSame(InvoiceStatus::VALIDATED, $record->status);
        $this->assertNotNull($record->validationResult);
        $this->assertTrue($record->validationResult->valid);
        // 2 processing responses observed -> 2 sleeps before the 3rd (terminal) poll.
        $this->assertSame([0.5, 0.75], $recordedSleeps);
    }

    public function testValidateFileAndWaitReturnsNormallyOnFailedValidation(): void
    {
        $this->server->route('/v1/invoices/validate', 202, '{"id":"inv_2","status":"processing"}');
        $this->server->route('/v1/invoices/inv_2', 200, self::recordJson('inv_2', 'failed_validation', false));

        $record = $this->client->invoices->validateFileAndWait(['invoice.xml', '<x/>'], ValidationProfile::EN16931);

        $this->assertSame(InvoiceStatus::FAILED_VALIDATION, $record->status);
        $this->assertNotNull($record->validationResult);
        $this->assertFalse($record->validationResult->valid);
    }

    public function testTimesOutWithPartialResultWhenDeadlineElapses(): void
    {
        $this->server->route('/v1/invoices', 202, '{"id":"inv_3","status":"processing"}');
        $this->server->route('/v1/invoices/inv_3', 200, self::recordJson('inv_3', 'processing', true));

        try {
            $this->client->invoices->submitAndWait(self::minimalSubmission(), new PollOptions(timeout: 0.0));
            $this->fail('expected XingenTimeoutException');
        } catch (XingenTimeoutException $e) {
            $this->assertSame('inv_3', $e->partialResult->id);
            $this->assertSame(InvoiceStatus::PROCESSING, $e->partialResult->status);
        }
    }

    public function testCancellationCheckAbortsPolling(): void
    {
        $this->server->route('/v1/invoices', 202, '{"id":"inv_4","status":"processing"}');
        $this->server->route('/v1/invoices/inv_4', 200, self::recordJson('inv_4', 'processing', true));

        $this->expectException(XingenCancellationException::class);
        $this->client->invoices->submitAndWait(
            self::minimalSubmission(),
            new PollOptions(cancellationCheck: fn () => true),
        );
    }
}
