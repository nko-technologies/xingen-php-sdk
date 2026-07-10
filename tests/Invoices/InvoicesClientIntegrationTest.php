<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\Invoices;

use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Invoices\InvoiceSubmission;
use Xingen\Sdk\Invoices\LineInput;
use Xingen\Sdk\Invoices\PartyInput;
use Xingen\Sdk\Models\InvoiceStatus;
use Xingen\Sdk\Models\ValidationProfile;
use Xingen\Sdk\Tests\Support\LoopbackTestCase;

final class InvoicesClientIntegrationTest extends LoopbackTestCase
{
    private const FIXTURE = '{"id":"inv_01HXYZ","status":"validated",'
        . '"createdAt":"2026-07-08T09:30:00Z","validationProfile":"XRECHNUNG","invoiceFormat":"UBL",'
        . '"uploadedBy":"user_abc123","sandbox":false,"apiKeyId":"3fa85f64-5717-4562-b3fc-2c963f66afa6",'
        . '"canonicalJson":{"invoiceNumber":"INV-2024-0042","currency":"EUR","lines":[],"notes":[]},'
        . '"validationResult":{"valid":true,"errors":[],"kositResult":null}}';

    private static function singlePage(string $recordJson, bool $last): string
    {
        return '{"content":[' . $recordJson . '],"totalElements":2,"totalPages":2,"number":'
            . ($last ? '1' : '0') . ',"size":1,"first":' . ($last ? 'false' : 'true')
            . ',"last":' . ($last ? 'true' : 'false') . ',"numberOfElements":1,"empty":false}';
    }

    public function testSubmitSendsExactBackendRequestShapeAndDecodes202(): void
    {
        $this->server->route('/v1/invoices', 202, '{"id":"inv_123","status":"processing"}');

        $submission = new InvoiceSubmission(
            invoiceNumber: 'INV-2024-0042',
            issueDate: '2024-03-15',
            currency: 'EUR',
            validationProfile: ValidationProfile::XRECHNUNG,
            supplier: new PartyInput(name: 'Acme GmbH', vatId: 'DE123456789'),
            buyer: new PartyInput(name: 'Buyer Co', leitwegId: '991-12345-06'),
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

        $result = $this->client->invoices->submit($submission);

        $this->assertSame('inv_123', $result->id);
        $this->assertSame(InvoiceStatus::PROCESSING, $result->status);

        $body = Json::decode($this->server->recordedRequestsFor('/v1/invoices')[0]['body']);
        $this->assertSame('INV-2024-0042', $body['invoiceNumber']);
        $this->assertSame('XRECHNUNG', $body['validationProfile']);
        $this->assertSame('DE123456789', $body['supplier']['vatId']);
        $this->assertSame('Software License Q1', $body['lines'][0]['description']);
    }

    public function testValidateFileSendsProfileAsQueryParamAndFileAsMultipartField(): void
    {
        $this->server->route('/v1/invoices/validate', 202, '{"id":"inv_456","status":"processing"}');

        $result = $this->client->invoices->validateFile(['invoice.xml', '<Invoice/>'], ValidationProfile::EN16931);

        $this->assertSame('inv_456', $result->id);

        $request = $this->server->recordedRequestsFor('/v1/invoices/validate')[0];
        $this->assertSame('profile=EN16931', $request['query']);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $request['headers']['CONTENT-TYPE']);

        $body = $request['body'];
        $this->assertStringContainsString('name="file"; filename="invoice.xml"', $body);
        $this->assertStringContainsString('Content-Type: application/xml', $body);
        $this->assertStringContainsString('<Invoice/>', $body);
        // the gotcha this test guards against: profile must never be sent as a form field
        $this->assertStringNotContainsString('name="profile"', $body);
    }

    public function testGetDecodesInvoiceRecordEnvelope(): void
    {
        $this->server->route('/v1/invoices/inv_01HXYZ', 200, self::FIXTURE);

        $record = $this->client->invoices->get('inv_01HXYZ');

        $this->assertSame('inv_01HXYZ', $record->id);
        $this->assertSame(InvoiceStatus::VALIDATED, $record->status);
        $this->assertNotNull($record->invoice);
        $this->assertSame('INV-2024-0042', $record->invoice->invoiceNumber);
    }

    public function testListSendsPageSizeAndSortAsQueryParams(): void
    {
        $this->server->route('/v1/invoices', 200, self::singlePage(self::FIXTURE, true));

        $this->client->invoices->list(2, 10, 'createdAt,desc');

        $query = $this->server->recordedRequestsFor('/v1/invoices')[0]['query'];
        $this->assertStringContainsString('page=2', $query);
        $this->assertStringContainsString('size=10', $query);
        $this->assertStringContainsString('sort=createdAt', $query);
    }

    public function testSubmitOdataSendsProfileAsQueryParamAndRawJsonAsBody(): void
    {
        $this->server->route('/v1/invoices/validate/odata', 202, '{"id":"inv_odata","status":"processing"}');

        $result = $this->client->invoices->submitOdata('{"SupplierInvoice":"raw-payload"}', ValidationProfile::EN16931);

        $this->assertSame('inv_odata', $result->id);
        $request = $this->server->recordedRequestsFor('/v1/invoices/validate/odata')[0];
        $this->assertSame('profile=EN16931', $request['query']);
        $this->assertSame('{"SupplierInvoice":"raw-payload"}', $request['body']);
    }

    public function testDownloadPdfReturnsRawBytesWithPdfAccept(): void
    {
        $pdfBytes = "\x25\x50\x44\x46"; // "%PDF"
        $this->server->route(
            '/v1/invoices/inv_01HXYZ/download',
            200,
            $pdfBytes,
            ['Content-Type' => 'application/pdf'],
        );

        $result = $this->client->invoices->downloadPdf('inv_01HXYZ');

        $this->assertSame($pdfBytes, $result);
        $request = $this->server->recordedRequestsFor('/v1/invoices/inv_01HXYZ/download')[0];
        $this->assertSame('application/pdf', $request['headers']['ACCEPT']);
    }

    public function testDownloadIdocXmlReturnsRawBytesWithXmlAccept(): void
    {
        $xmlBytes = '<IDOC/>';
        $this->server->route(
            '/v1/invoices/inv_01HXYZ/download/idoc',
            200,
            $xmlBytes,
            ['Content-Type' => 'application/xml'],
        );

        $result = $this->client->invoices->downloadIdocXml('inv_01HXYZ');

        $this->assertSame($xmlBytes, $result);
        $request = $this->server->recordedRequestsFor('/v1/invoices/inv_01HXYZ/download/idoc')[0];
        $this->assertSame('application/xml', $request['headers']['ACCEPT']);
    }

    public function testListAllLazilyWalksMultiplePages(): void
    {
        $this->server->routeSequence('/v1/invoices', [
            ['status' => 200, 'body' => self::singlePage(self::FIXTURE, false)],
            ['status' => 200, 'body' => self::singlePage(self::FIXTURE, true)],
        ]);

        $records = iterator_to_array($this->client->invoices->listAll(1));

        $this->assertCount(2, $records);
    }
}
