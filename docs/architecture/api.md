# API

Sisahygo Connect will integrate with Sisahygo through the Sisahygo API.

Boundary rules:

- Do not query the core Sisahygo production database directly.
- Treat Sisahygo customer IDs as external identifiers.
- Future API client services must run inside the current Client Account context.
- Future APIs must call the same domain services, policies, and authorized query objects as Livewire and web controllers.
- API handlers must not query Sisahygo transaction tables directly.

Sprint 1.5 implements the reusable integration foundation only. Business API modules and UI workflows remain out of scope.

## Sprint 1.5 Foundation

The Sisahygo integration foundation uses `app/Integrations/Sisahygo` for API transport, exceptions, DTOs, endpoint classes, logging, and context objects.

API credentials are encrypted per Client Account and environment. They are authentication credentials only, not customer identity. Sender and receiver customer scope comes from active `client_account_customers` links.

Receiver-only integration has a documented Core API compatibility dependency until the Core API confirms authorization based on `order_headers.customer_rec_id` and receiver payment rules.