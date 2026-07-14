# Sisahygo API Error Handling

The integration layer maps transport and API failures into safe exceptions:

- `SisahygoConnectionException`
- `SisahygoAuthenticationException`
- `SisahygoAuthorizationException`
- `SisahygoValidationException`
- `SisahygoRateLimitException`
- `SisahygoNotFoundException`
- `SisahygoServerException`
- `SisahygoUnexpectedResponseException`

Exception context must be safe for logs and diagnostics. It may include status code, endpoint name, correlation ID, Client Account ID, credential ID and fingerprint. It must not include `X-Api-Key`, decrypted credentials, full payloads, passwords, full addresses, or unnecessary customer personal data.

User-facing Thai error copy should be mapped later at the application boundary.