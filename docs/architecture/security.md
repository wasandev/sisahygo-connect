# Security

Every future shipment or payment query must begin with an authorized Client Account scope. Security must not rely on Blade filtering. Policies and query objects must prevent horizontal privilege escalation and direct URL access to unauthorized records.
## Sisahygo API Integration Security

External API calls must be made with a validated `SisahygoIntegrationContext`. The context separates authorized sender customer IDs from authorized receiver customer IDs. Arbitrary customer IDs from callers are rejected unless they match the selected Client Account scope.

Operational logs may include metadata such as Client Account ID, credential ID, fingerprint, endpoint, method, status, duration, retry count and correlation ID. Logs must never include decrypted API keys or full sensitive payloads.