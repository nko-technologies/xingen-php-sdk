<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Invoices\InvoiceRecord;
use Xingen\Sdk\Models\InvoiceStatus;
use Xingen\Sdk\Models\Severity;
use Xingen\Sdk\Models\ValidationLayer;

final class InvoiceRecordDeserializationTest extends TestCase
{
    private static function load(string $name): InvoiceRecord
    {
        $path = __DIR__ . '/Fixtures/' . $name;

        return InvoiceRecord::fromWire(Json::decode((string) file_get_contents($path)));
    }

    public function testValidatedRecordDecodesFullInvoiceAndValidationResult(): void
    {
        $record = self::load('invoice-record.json');

        $this->assertSame('inv_01HXYZ', $record->id);
        $this->assertSame(InvoiceStatus::VALIDATED, $record->status);
        $this->assertTrue($record->status->isTerminal());
        $this->assertTrue($record->sandbox);
        $this->assertSame('3fa85f64-5717-4562-b3fc-2c963f66afa6', $record->apiKeyId);

        $this->assertNotNull($record->invoice);
        $this->assertSame('INV-2024-0042', $record->invoice->invoiceNumber);
        $this->assertSame('EUR', $record->invoice->currency);
        $this->assertNotNull($record->invoice->supplier);
        $this->assertSame('Acme GmbH', $record->invoice->supplier->name);
        $this->assertNotNull($record->invoice->supplier->address);
        $this->assertSame('Berlin', $record->invoice->supplier->address->city);
        $this->assertNotNull($record->invoice->buyer);
        $this->assertSame('991-12345-06', $record->invoice->buyer->leitwegId);

        $line = $record->invoice->lines[0];
        $this->assertSame('199.00', $line->price);
        $this->assertSame('995.00', $line->lineNetAmount);

        $breakdown = $record->invoice->taxBreakdowns[0];
        $this->assertSame('S', $breakdown->categoryCode);
        $this->assertSame('19', $breakdown->rate);

        $this->assertNotNull($record->validationResult);
        $this->assertTrue($record->validationResult->valid);
        $this->assertSame([], $record->validationResult->errors);
        $this->assertNotNull($record->validationResult->kositResult);
        $this->assertTrue($record->validationResult->kositResult->valid);
    }

    public function testProcessingRecordHasNoInvoiceOrValidationResultYet(): void
    {
        $record = self::load('invoice-record-processing.json');

        $this->assertSame(InvoiceStatus::PROCESSING, $record->status);
        $this->assertFalse($record->status->isTerminal());
        $this->assertNull($record->invoice);
        $this->assertNull($record->validationResult);
    }

    public function testFailedRecordDecodesValidationErrors(): void
    {
        $record = self::load('invoice-record-failed.json');

        $this->assertSame(InvoiceStatus::FAILED_VALIDATION, $record->status);
        $this->assertTrue($record->status->isTerminal());
        $this->assertNotNull($record->validationResult);
        $this->assertFalse($record->validationResult->valid);
        $this->assertNull($record->validationResult->kositResult);

        $error = $record->validationResult->errors[0];
        $this->assertSame('BR-01', $error->code);
        $this->assertSame('specificationId', $error->field);
        $this->assertSame(ValidationLayer::CORE, $error->layer);
        $this->assertSame(Severity::ERROR, $error->severity);
    }

    public function testDecimalPrecisionIsPreservedExactlyNotRoundedThroughFloat(): void
    {
        // A regression guard for PHP's native json_decode(), which otherwise routes
        // numeric literals through a lossy float64 intermediate (see Internal\Json).
        $record = self::load('invoice-record.json');

        $this->assertNotNull($record->invoice);
        $this->assertSame('199.00', $record->invoice->lines[0]->price);
        $this->assertSame('189.05', $record->invoice->taxAmount);
    }

    public function testNullListFieldsAreCoercedToEmptyLists(): void
    {
        // A regression guard: the real backend serializes some never-populated list fields
        // (notes, supportingDocuments, identifiers, ...) as JSON null rather than [].
        // Wire::arr()/Wire::strArr() already treat null as [] regardless of what the
        // backend sends -- discovered by running the SDK against the production API, not
        // by any fixture in this suite.
        $json = <<<'JSON'
        {
          "id": "inv_nulls", "status": "validated",
          "createdAt": "2026-07-09T00:00:00Z", "validationProfile": "EN16931",
          "invoiceFormat": "JSON", "uploadedBy": "user", "sandbox": true, "apiKeyId": null,
          "canonicalJson": {
            "invoiceNumber": "INV-1", "currency": "EUR",
            "notes": null, "precedingInvoiceReferences": null, "supportingDocuments": null,
            "lines": [{"lineId": "1", "classifications": null, "attributes": null, "allowanceCharges": null}],
            "taxBreakdowns": null, "allowanceCharges": null, "paymentMeans": null,
            "supplier": {"name": "Acme", "identifiers": null}
          },
          "validationResult": {"valid": true, "errors": null, "kositResult": null}
        }
        JSON;

        $record = InvoiceRecord::fromWire(Json::decode($json));

        $this->assertNotNull($record->invoice);
        $this->assertSame([], $record->invoice->notes);
        $this->assertSame([], $record->invoice->precedingInvoiceReferences);
        $this->assertSame([], $record->invoice->supportingDocuments);
        $this->assertSame([], $record->invoice->taxBreakdowns);
        $this->assertSame([], $record->invoice->allowanceCharges);
        $this->assertSame([], $record->invoice->paymentMeans);
        $this->assertSame([], $record->invoice->lines[0]->classifications);
        $this->assertSame([], $record->invoice->lines[0]->attributes);
        $this->assertSame([], $record->invoice->lines[0]->allowanceCharges);
        $this->assertNotNull($record->invoice->supplier);
        $this->assertSame([], $record->invoice->supplier->identifiers);
        $this->assertNotNull($record->validationResult);
        $this->assertSame([], $record->validationResult->errors);
    }
}
