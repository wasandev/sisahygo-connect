# Staging Readiness

Sprint 8 prepares Sisahygo Connect for a future staging deployment. This document is a readiness guide only; do not deploy from this sprint without a separate deployment approval.

## Required Environment Variables

| Variable | Required | Purpose |
| --- | --- | --- |
| `SISAHYGO_API_ENVIRONMENT` | Yes | `sandbox` or `production`. Staging should normally use `sandbox` until production credentials are approved. |
| `SISAHYGO_API_BASE_URL` | Optional | Explicit override for the selected environment base URL. Leave blank unless the deployment platform manages one canonical URL. |
| `SISAHYGO_API_SANDBOX_URL` | Required for sandbox | Sisahygo Core Client API sandbox base URL. |
| `SISAHYGO_API_PRODUCTION_URL` | Required for production | Sisahygo Core Client API production base URL. Production cannot use a host containing `sandbox`. |
| `SISAHYGO_API_CONNECT_TIMEOUT` | Yes | Connection timeout in seconds. |
| `SISAHYGO_API_TIMEOUT` | Yes | Total request timeout in seconds. |
| `SISAHYGO_API_RETRY_TIMES` | Yes | Retry count for safe read-only GET requests. |
| `SISAHYGO_API_RETRY_SLEEP_MS` | Yes | Delay between read-only retries. |
| `SISAHYGO_API_USER_AGENT` | Yes | User agent sent to Core API. |
| `SISAHYGO_API_LIVE_SMOKE_TESTS` | Optional | Enables explicit live smoke tests only when intentionally set. |

API credentials are stored per Client Account through encrypted credential records. Do not place real API keys in `.env.example`, docs, fixtures, logs, screenshots, or committed files.

## Secret Handling Rules

- Never expose `X-Api-Key`, authorization headers, encrypted credentials, passwords, or private Core response bodies in UI or logs.
- Store real API keys only through the credential management command/workflow.
- Use placeholder values in tracked examples.
- Rotate staging credentials before wider user testing.
- Review logs after smoke testing for accidental secrets before retaining or sharing them.

## Configuration Safety

- Missing base URLs now fail clearly instead of silently choosing a fallback.
- Production configuration is rejected if it points to a sandbox host.
- API base URLs must be HTTPS.
- POST creation calls are not automatically retried. Automatic POST retries may create duplicate orders without a confirmed idempotency contract.

## Local Connectivity Procedure

1. Configure the required `SISAHYGO_API_*` variables locally.
2. Create or rotate a Client Account credential using the existing credential workflow.
3. Sign in as a user for that Client Account with `settings.manage` capability.
4. Open Settings and review the Sisahygo API connectivity card.
5. Confirm it shows configuration present, credential present, connected/unavailable state, response duration, and last checked time.
6. Confirm no API key, authorization header, private response body, or stack trace appears.

## Staging Smoke-Test Checklist

- Login and Client Account selection work.
- Settings API connectivity card reports expected status.
- Dashboard loads summary, recent records, pending actions, and mock notification preview.
- Universal Search resolves only records visible to the selected Client Account.
- Order Detail opens from History and does not expose records outside the selected Client Account.
- Shipment list/detail and Tracking lookup use Core Client API only.
- Payment Center respects existing sender/receiver visibility rules.
- Single Order Checking and Bulk Order Checking submit through Core Client API only.
- Core API failures show safe user messages without raw exceptions or secrets.
- Logs include safe context: user ID, Client Account ID, endpoint, method, status, duration, retry count, and correlation ID.

## Rollback Preparation Checklist

- Record the deployed commit SHA before deployment.
- Keep the previous known-good environment variable set available.
- Confirm database backups and credential records are restorable.
- Prepare a route/cache/config cache rollback command sequence.
- Keep staging credentials revocable independently from production credentials.
- Define who can approve rollback and who can rotate credentials.

## Known Limitations

- Notification Center remains Phase 1 mock-only. There is no polling, push notification delivery, or persisted read state.
- API connectivity status uses `/units` as a safe existing read-only endpoint; it does not validate every Core feature endpoint.
- Live smoke tests remain opt-in.
- Automatic POST retries remain disabled for order creation and bulk creation.
