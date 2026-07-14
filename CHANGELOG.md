# Changelog

## 0.2.0

- AI-based PDF invoice extraction: `extractInvoice`/`extractInvoiceAndWait` (`POST /v1/invoices/extract`),
  new `ExtractionModelTier` enum (`FAST`/`ACCURATE`) and `InvoiceRecord::$extractionTier`.
- `patchInvoice` — correct invoice fields via JSON merge-patch and re-validate synchronously
  (`PATCH /v1/invoices/{id}`).
- `getAutoFilledFields` — list the invoice fields the backend fills in automatically, per
  validation profile (`GET /v1/invoices/auto-filled-fields`), via the new `AutoFilledField` model.
- `PartyInput` gained an `address` field (new `AddressInput` class: `streetName`/`city`/
  `postalZone`/`countryCode`) -- the backend now rejects a party with no postal address on every
  profile, and `submit()` had no way to supply one.
- `submit()` reaches full parity with the backend's domain model, so every invoice type it can
  validate can now also be submitted as structured JSON. New on `InvoiceSubmission`: `dueDate`,
  `taxPointDate`, `taxCurrencyCode`, `paymentTermsNote`, the BT-11..BT-19 reference fields, `notes`,
  `precedingInvoiceReferences`, `supportingDocuments`, `deliveryPeriodStart`/`deliveryPeriodEnd`,
  `invoicePeriod`, `delivery`, `payee`, `taxRepresentative`, `paymentMeans`, and
  `allowanceCharges`. New on `PartyInput`: `registrationName`, `taxRegistrationId`,
  `legalRegistrationId`/`legalRegistrationSchemeId`, `additionalLegalInfo`, `contact` (new
  `ContactInput` class), `endpointId`/`endpointSchemeId`, `identifiers` (new
  `PartyIdentifierInput` class). New on `AddressInput`: `additionalStreetName`, `addressLine3`,
  `countrySubdivision`. New on `LineInput`: `itemName`, `note`, object/order/accounting
  references, seller/buyer/standard item ids, `originCountryCode`, `classifications` (new
  `ItemClassificationInput` class), `attributes` (new `ItemAttributeInput` class), `grossPrice`,
  `priceDiscount`, `priceBaseQuantity`/`priceBaseQuantityUnit`, `taxCategoryCode`,
  `exemptionReason`/`exemptionReasonCode`, `period` (new `InvoicePeriodInput` class), and
  `allowanceCharges` (new `LineAllowanceChargeInput` class). New top-level `PaymentMeansInput` and
  `AllowanceChargeInput` classes.

## 0.1.0

Initial release, ported 1:1 from the
[Java SDK](https://github.com/nko-technologies/xingen-java-sdk) (via the Python/TypeScript ports):

- `XingenClient` exposing `->invoices` and `->apiKeys`.
- Invoice submission (structured JSON, multipart UBL/CII/ZUGFeRD, SAP IDoc XML, SAP OData),
  retrieval, pagination (`list` / `listAll`), and PDF/IDoc-XML downloads.
- `*AndWait` polling helpers with exponential backoff, timeout, and cancellation support.
- API key CRUD (`create` / `list` / `revoke`).
- Typed exception hierarchy for 400/401/403/404/429 responses plus a generic `ApiException`
  fallback.
- Plain readonly value objects for the full invoice/validation domain, hydrated via explicit
  `fromWire()` factories, with string-exact monetary fields and forward-compatible unknown-field
  handling.
- No third-party runtime dependencies: HTTP via `ext-curl`, JSON decoding via a hand-rolled
  lossless parser (`Xingen\Sdk\Internal\Json`) that preserves numeric literal precision.
- Published to Packagist, MIT licensed.
