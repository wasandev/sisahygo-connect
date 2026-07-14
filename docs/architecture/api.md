# API

Sisahygo Connect will integrate with Sisahygo through the Sisahygo API.

Boundary rules:

- Do not query the core Sisahygo production database directly.
- Treat Sisahygo customer IDs as external identifiers.
- Future API client services must run inside the current Client Account context.
- Future APIs must call the same domain services, policies, and authorized query objects as Livewire and web controllers.
- API handlers must not query Sisahygo transaction tables directly.

No Sprint 1.5 API client code is implemented in the current baseline.
