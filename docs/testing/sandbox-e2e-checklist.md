# Sandbox And Staging E2E Checklist

Use this checklist on local or staging with approved sandbox credentials only. During Sprint 10 staging, the application URL is `https://connect.sisahygo.online` and Core API traffic goes to `https://sandbox-api.sisahygo.online/api/v1/client`.

## Command Checks

- `php artisan sisahygo:diagnostics`
- `php artisan sisahygo:integration-status --account=<client-account-id>`
- `php artisan sisahygo:smoke-test --account=<client-account-id> --search=<known-sandbox-reference>`
- Optional controlled write only: add `--include-write --confirm-write --receiver-id=<id> --product-id=<id> --unit-id=<id>`.

Do not run live write checks from automated tests. Do not run write smoke tests against production.

## Manual UI Flow

1. Login: valid login succeeds; invalid login fails without leaking system details.
2. Client Account selection: authorized accounts appear; unavailable state appears when none are active.
3. Dashboard: summary cards, recent orders, pending actions, quick actions, empty states, loading states, and Core-unavailable state render cleanly.
4. Pending Actions: expected outstanding shipments/payments appear; empty state is clear.
5. Universal Search: tracking number, client reference, and batch reference resolve to the correct page; missing result and Core-unavailable states are sanitized.
6. Notification Center: mock-only notice is visible and no polling/push behavior is implied.
7. Settings API Status: connected, missing credential, revoked/expired credential, 401/403, timeout, and unavailable states are safe.
8. Single Order Checking: receiver/product/unit search, validation errors, duplicate client reference, submit success, and unavailable Core behavior.
9. Bulk Order Checking: row validation, duplicate references, preview/confirm, partial failure, retry failed rows, and manual fixture review.
10. Order History: filters, empty state, unauthorized state, and detail link.
11. Order Detail: information, receiver, items, freight summary, shipment information, timeline/status history, and unavailable states.
12. Shipment Tracking: known tracking number success, not found, invalid input, and Core unavailable.
13. Payment Center: list/detail filters, permission denied, empty state, and Core errors.
14. Logout/session: logout clears access; expired session redirects to login; secure cookies work over HTTPS.
15. Mobile/responsive: nav drawer, tables/cards, copy buttons, forms, banner, and toasts fit on mobile and desktop.

## Authorization And Isolation

- Switch Client Account and confirm data, credentials, mappings, dashboard, history, shipments, and payments change scope.
- A user without capability sees the authorization failure path.
- Stale selected-account state does not expose another account's data.

## Security Checks

- No API key, authorization header, decrypted credential, private Core payload, server path, or stack trace appears in UI, command output, or docs.
- Order-creation POST requests are not retried automatically.
- Generated smoke references are clearly test data.
- Notification Center is still marked as mock-only.
