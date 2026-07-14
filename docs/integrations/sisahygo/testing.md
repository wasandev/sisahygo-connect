# Sisahygo API Testing

Normal automated tests must use `Http::fake()` and deterministic fixtures from `tests/Fixtures/Sisahygo/V1/`.

The test suite covers:

- configuration resolution and invalid environments
- encrypted credential storage and serialization safety
- credential resolution, revocation, rotation, and last-used timestamp
- tenant-safe context building
- sender and receiver customer scopes
- HTTP headers, correlation IDs, and user agent
- retry behavior for GET requests
- no blind retry for POST requests
- API error mapping
- malformed JSON handling
- DTO mapping and harmless extra fields

Live sandbox smoke tests are disabled by default and require explicit opt-in plus a locally supplied credential. They must not run in CI by default.