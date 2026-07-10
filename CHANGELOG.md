# Changelog

## Unreleased

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
