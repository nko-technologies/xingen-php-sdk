<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests\Invoices;

use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Invoices\AddressInput;
use Xingen\Sdk\Invoices\AllowanceChargeInput;
use Xingen\Sdk\Invoices\ContactInput;
use Xingen\Sdk\Invoices\DeliveryInput;
use Xingen\Sdk\Invoices\InvoicePeriodInput;
use Xingen\Sdk\Invoices\InvoiceSubmission;
use Xingen\Sdk\Invoices\ItemAttributeInput;
use Xingen\Sdk\Invoices\ItemClassificationInput;
use Xingen\Sdk\Invoices\LineInput;
use Xingen\Sdk\Invoices\PartyIdentifierInput;
use Xingen\Sdk\Invoices\PartyInput;
use Xingen\Sdk\Invoices\PaymentMeansInput;
use Xingen\Sdk\Invoices\PrecedingInvoiceReferenceInput;
use Xingen\Sdk\Invoices\SupportingDocumentInput;
use Xingen\Sdk\Models\ExtractionModelTier;
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
            supplier: new PartyInput(
                name: 'Acme GmbH',
                vatId: 'DE123456789',
                address: new AddressInput(city: 'Berlin', countryCode: 'DE'),
            ),
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
        $this->assertSame('Berlin', $body['supplier']['address']['city']);
        $this->assertSame('DE', $body['supplier']['address']['countryCode']);
        $this->assertSame('Software License Q1', $body['lines'][0]['description']);
    }

    public function testSubmitSendsFullDomainModelFieldsWhenPresent(): void
    {
        $this->server->route('/v1/invoices', 202, '{"id":"inv_full","status":"processing"}');

        $submission = new InvoiceSubmission(
            invoiceNumber: 'INV-2024-0099',
            issueDate: '2024-03-15',
            currency: 'EUR',
            validationProfile: ValidationProfile::EN16931,
            supplier: new PartyInput(
                name: 'Acme GmbH',
                registrationName: 'Acme GmbH Legal',
                vatId: 'DE123456789',
                address: new AddressInput(city: 'Berlin', countryCode: 'DE'),
                contact: new ContactInput(name: 'Jane Doe', email: 'jane@acme.example'),
                identifiers: [
                    new PartyIdentifierInput(id: 'DE98ZZZ09999999999', schemeId: 'SEPA'),
                ],
            ),
            buyer: new PartyInput(
                name: 'Buyer Co',
                address: new AddressInput(countryCode: 'DE'),
            ),
            dueDate: '2024-04-14',
            paymentTermsNote: 'Net 30',
            orderReference: 'PO-1',
            notes: ['Thank you for your business'],
            precedingInvoiceReferences: [
                new PrecedingInvoiceReferenceInput(id: 'INV-2024-0001', issueDate: '2024-01-01'),
            ],
            supportingDocuments: [
                new SupportingDocumentInput(id: 'DOC-1', typeCode: '50', description: 'Delivery note'),
            ],
            invoicePeriod: new InvoicePeriodInput(startDate: '2024-03-01', endDate: '2024-03-31'),
            delivery: new DeliveryInput(
                partyName: 'Warehouse Co',
                address: new AddressInput(city: 'Hamburg', countryCode: 'DE'),
            ),
            payee: new PartyInput(name: 'Payee GmbH', address: new AddressInput(countryCode: 'DE')),
            lines: [
                new LineInput(
                    description: 'Consulting services',
                    quantity: '1',
                    unit: 'C62',
                    price: '500.00',
                    taxRate: '19',
                    itemName: 'Consulting',
                    classifications: [new ItemClassificationInput(code: '1234')],
                    attributes: [new ItemAttributeInput(name: 'Color', value: 'Blue')],
                ),
                new LineInput(
                    description: 'Export sale',
                    quantity: '1',
                    unit: 'C62',
                    price: '100.00',
                    taxRate: '0',
                    taxCategoryCode: 'G',
                    exemptionReason: 'Export outside the EU',
                    exemptionReasonCode: 'VATEX-EU-G',
                ),
            ],
            paymentMeans: [
                new PaymentMeansInput(typeCode: '58', creditTransferAccountId: 'DE89370400440532013000'),
            ],
            allowanceCharges: [
                new AllowanceChargeInput(charge: true, amount: '5.00', vatCategoryCode: 'S', vatRate: '19'),
            ],
        );

        $result = $this->client->invoices->submit($submission);

        $this->assertSame('inv_full', $result->id);

        $body = Json::decode($this->server->recordedRequestsFor('/v1/invoices')[0]['body']);

        $this->assertSame('2024-04-14', $body['dueDate']);
        $this->assertSame('Net 30', $body['paymentTermsNote']);
        $this->assertSame('PO-1', $body['orderReference']);
        $this->assertSame(['Thank you for your business'], $body['notes']);
        $this->assertSame('INV-2024-0001', $body['precedingInvoiceReferences'][0]['id']);
        $this->assertSame('DOC-1', $body['supportingDocuments'][0]['id']);
        $this->assertSame('2024-03-01', $body['invoicePeriod']['startDate']);
        $this->assertSame('Warehouse Co', $body['delivery']['partyName']);
        $this->assertSame('Acme GmbH Legal', $body['supplier']['registrationName']);
        $this->assertSame('Jane Doe', $body['supplier']['contact']['name']);
        $this->assertSame('DE98ZZZ09999999999', $body['supplier']['identifiers'][0]['id']);
        $this->assertSame('SEPA', $body['supplier']['identifiers'][0]['schemeId']);
        $this->assertSame('Payee GmbH', $body['payee']['name']);
        $this->assertSame('1234', $body['lines'][0]['classifications'][0]['code']);
        $this->assertSame('Blue', $body['lines'][0]['attributes'][0]['value']);
        $this->assertSame('G', $body['lines'][1]['taxCategoryCode']);
        $this->assertSame('Export outside the EU', $body['lines'][1]['exemptionReason']);
        $this->assertSame('VATEX-EU-G', $body['lines'][1]['exemptionReasonCode']);
        $this->assertSame('58', $body['paymentMeans'][0]['typeCode']);
        $this->assertSame('DE89370400440532013000', $body['paymentMeans'][0]['creditTransferAccountId']);
        $this->assertSame(true, $body['allowanceCharges'][0]['charge']);
        $this->assertSame('5.00', $body['allowanceCharges'][0]['amount']);
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

    public function testExtractInvoiceSendsProfileAndTierAsQueryParamsAndFileAsMultipartField(): void
    {
        $this->server->route('/v1/invoices/extract', 202, '{"id":"inv_789","status":"processing"}');

        $result = $this->client->invoices->extractInvoice(
            ['invoice.pdf', '%PDF-1.4'],
            ValidationProfile::EN16931,
            ExtractionModelTier::ACCURATE,
        );

        $this->assertSame('inv_789', $result->id);

        $request = $this->server->recordedRequestsFor('/v1/invoices/extract')[0];
        $this->assertSame('profile=EN16931&tier=ACCURATE', $request['query']);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $request['headers']['CONTENT-TYPE']);

        $body = $request['body'];
        $this->assertStringContainsString('name="file"; filename="invoice.pdf"', $body);
        $this->assertStringContainsString('Content-Type: application/pdf', $body);
        // the gotcha this test guards against: profile/tier must never be sent as form fields
        $this->assertStringNotContainsString('name="profile"', $body);
        $this->assertStringNotContainsString('name="tier"', $body);
    }

    public function testPatchInvoiceSendsMergePatchAndDecodesUpdatedRecord(): void
    {
        $this->server->route('/v1/invoices/inv_01HXYZ', 200, self::FIXTURE);

        $record = $this->client->invoices->patchInvoice('inv_01HXYZ', ['currency' => 'USD']);

        $request = $this->server->recordedRequestsFor('/v1/invoices/inv_01HXYZ')[0];
        $this->assertSame('PATCH', $request['method']);
        $this->assertSame('{"currency":"USD"}', $request['body']);
        $this->assertSame('inv_01HXYZ', $record->id);
    }

    public function testPatchInvoiceAcceptsRawJsonStringPatch(): void
    {
        $this->server->route('/v1/invoices/inv_01HXYZ', 200, self::FIXTURE);

        $this->client->invoices->patchInvoice('inv_01HXYZ', '{"buyerReference":"991-12345-06"}');

        $request = $this->server->recordedRequestsFor('/v1/invoices/inv_01HXYZ')[0];
        $this->assertSame('PATCH', $request['method']);
        $this->assertSame('{"buyerReference":"991-12345-06"}', $request['body']);
    }

    public function testGetAutoFilledFieldsDecodesMapByProfile(): void
    {
        $this->server->route('/v1/invoices/auto-filled-fields', 200,
            '{"EN16931":[{"field":"typeCode","value":"380","reason":"Defaults to a commercial invoice."}]}');

        $fields = $this->client->invoices->getAutoFilledFields();

        $this->assertCount(1, $fields['EN16931']);
        $this->assertSame('typeCode', $fields['EN16931'][0]->field);
        $this->assertSame('380', $fields['EN16931'][0]->value);
        $this->assertSame('Defaults to a commercial invoice.', $fields['EN16931'][0]->reason);
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
