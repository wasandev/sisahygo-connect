# API Connectivity Operations

Sisahygo Connect checks Core API reachability from the protected Client Account settings page.

## What The Status Card Shows

- API configuration exists or is missing.
- Active Client Account credential exists or is missing.
- Connected or unavailable status.
- Approximate response duration in milliseconds.
- Last checked time.

The card never displays API keys, authorization headers, private response bodies, stack traces, or raw Core exception text.

## Probe Endpoint

The connectivity check reuses the existing Core Client API `/units` endpoint through the normal stack:

Client Account middleware/resolver -> API Client / Endpoint -> DTO / Mapper -> Application Service -> Livewire -> Connect Blade components

No fake production endpoint is introduced.

## Required Access

The user must have access to the selected Client Account and the `settings.manage` capability to run the operational check. The check uses the selected Client Account credential for the configured environment.

## Timeout And Retry Policy

- `SISAHYGO_API_CONNECT_TIMEOUT` controls connection timeout.
- `SISAHYGO_API_TIMEOUT` controls total request timeout.
- GET requests may retry limited 429/5xx failures according to `SISAHYGO_API_RETRY_TIMES` and `SISAHYGO_API_RETRY_SLEEP_MS`.
- POST creation requests are not automatically retried because duplicate orders are possible without a confirmed idempotency contract.

## Safe Failure Categories

The API client maps Core and transport failures into user-safe categories:

- 401 authentication failure
- 403 authorization failure
- 404 not found
- 422 validation failure
- 429 rate limit
- Core 5xx server failure
- connection failure or timeout
- malformed response
- unexpected response

Detailed context is kept in safe application logs without secrets.
