# Sandbox E2E Checklist

Run this checklist locally or on future staging only with approved sandbox credentials.

## Command Checks

- `php artisan sisahygo:diagnostics`
- `php artisan sisahygo:integration-status --account=<id>`
- `php artisan sisahygo:smoke-test --account=<id>`
- Optional search: `php artisan sisahygo:smoke-test --account=<id> --search=<tracking-or-reference>`
- Controlled write only with explicit approval and fixture IDs.

## Browser Workflow

1. Login.
2. Select Client Account.
3. Open Dashboard.
4. Run Universal Search.
5. Open Single Order Checking.
6. Open Bulk Order Checking.
7. Open Order History.
8. Open Order Detail.
9. Open Shipment Tracking.
10. Open Payment Center.
11. Open Settings API Status.
12. Switch Client Account and confirm data changes.

## Failure Scenarios

Verify safe handling for:

- Empty results.
- Unauthorized or invalid credentials.
- Validation errors.
- Rate limits.
- Timeout or Core unavailable.
- Malformed responses.
- Account switching and stale state prevention.

## Security Checks

- No API key or Authorization header appears in UI or command output.
- No private Core payload or stack trace appears to users.
- Logs use safe context only.
- Order-creation POST requests are not retried automatically.
