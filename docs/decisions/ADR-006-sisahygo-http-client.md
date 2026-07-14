# ADR-006: Sisahygo HTTP Client

Status: Accepted

## Context

Sisahygo Connect needs a reusable API foundation for the external Sisahygo Client API without direct database access.

## Decision

Use Laravel HTTP Client behind `app/Integrations/Sisahygo/V1/SisahygoApiClient`. Requests use trusted configured base URLs, `X-Api-Key`, JSON headers, correlation IDs, configured timeouts, conservative GET retry, and safe exception mapping.

POST requests are not retried blindly in Sprint 1.5.

## Consequences

Future modules can reuse a single transport boundary while keeping secrets out of logs and UI state.