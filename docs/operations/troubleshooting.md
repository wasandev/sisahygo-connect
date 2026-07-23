# Troubleshooting

## API Status Shows Configuration Missing

1. Check `SISAHYGO_API_ENVIRONMENT` is `sandbox` or `production`.
2. Check the selected environment has an HTTPS base URL.
3. For production, confirm the production URL does not point to a sandbox host.
4. Clear and rebuild Laravel config cache after environment changes.

## API Status Shows Credential Missing

1. Confirm the selected Client Account is correct.
2. Confirm an active credential exists for the configured API environment.
3. Rotate or create a credential through the existing encrypted credential workflow.
4. Do not paste credentials into tickets, docs, logs, screenshots, or committed files.

## API Status Shows Unavailable

1. Confirm network access from the runtime to the configured Core API host.
2. Confirm Core sandbox is available.
3. Review application logs for safe fields: client account ID, user ID, endpoint, method, status code, duration, retry count, and correlation ID.
4. Check for 401/403 credential or authorization issues.
5. Check for 429 rate limiting and retry later.
6. Check for Core 5xx responses and coordinate with Core operators.

## Users See Safe Error Messages

This is expected. Connect must not expose raw Core exceptions, stack traces, API keys, private payloads, or authorization headers. Use safe logs and correlation IDs for diagnostics.

## Universal Search Or Order Detail Looks Missing

1. Confirm the selected Client Account is correct.
2. Confirm the record exists in Core for the selected account credential.
3. Switch Client Account and retry only if the user is authorized for that account.
4. Do not query the Core database directly from Connect.

## Notification Center

The Notification Center is still mock-only. Missing real-time updates are expected until a future notification integration sprint adds polling, push delivery, or persisted read state.

## Rollback Notes

- Revert application deployment to the previous known-good commit.
- Restore the previous environment variable set if configuration changed.
- Clear config and route caches after rollback.
- Rotate staging credentials if secret exposure is suspected.
