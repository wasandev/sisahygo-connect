# Release Candidate Signoff

## Permanent Domain Staging

- `APP_ENV=staging`
- `APP_DEBUG=false`
- `APP_URL=https://connect.sisahygo.online`
- `SISAHYGO_API_ENVIRONMENT=sandbox`
- `SISAHYGO_API_BASE_URL=https://sandbox-api.sisahygo.online/api/v1/client`

A separate `connect-sandbox.sisahygo.online` site is intentionally not used.

## Signoff Gates

- Full Laravel test suite passes.
- Frontend build passes.
- `git diff --check` passes.
- Secret scan of tracked changes finds no real credentials.
- Non-production banner is visible in staging and hidden in production.
- Staging rejects production Core API endpoint.
- Production rejects sandbox Core API endpoint.
- Diagnostics and read-only smoke commands produce sanitized output.
- Optional write smoke uses sandbox only and records an `STG-SMOKE-` reference.
- Manual UI E2E checklist is complete.
- Operations checklist is complete.

## Operations Checklist

- DNS resolves to the Forge server.
- SSL certificate is valid and HTTPS loads `https://connect.sisahygo.online`.
- Forge deployment log has no failed steps.
- Laravel log and web server error log contain no secrets.
- Database connection is healthy and points to Connect staging DB only.
- Queue worker is running if enabled; failed jobs are reviewed.
- Scheduler is configured if scheduled jobs are enabled.
- Disk usage and log rotation are healthy.
- `storage/` and `bootstrap/cache/` permissions are correct.
- Cache, session, and queue drivers match staging env.
- API latency, timeout behavior, and Core unavailable behavior are acceptable.
- HTTP 401, 403, 404, 422, 429, and 5xx responses render safe messages.
- No unusual repeated requests or accidental POST loops appear.
- Secret leakage review is complete.

## Go-Live Transition Without Domain Change

Before go-live:

```env
APP_ENV=staging
APP_URL=https://connect.sisahygo.online
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_BASE_URL=https://sandbox-api.sisahygo.online/api/v1/client
```

At go-live:

```env
APP_ENV=production
APP_URL=https://connect.sisahygo.online
SISAHYGO_API_ENVIRONMENT=production
SISAHYGO_API_BASE_URL=https://api.sisahygo.online/api/v1/client
```

Go-live steps:

1. Replace sandbox Client Account credentials with production credentials.
2. Verify every credential belongs to the production environment.
3. Remove or disable staging-only test accounts where appropriate.
4. Remove staging-only smoke fixtures.
5. Confirm the non-production banner is hidden.
6. Clear and rebuild config, route, and view caches.
7. Restart queue workers.
8. Run diagnostics and read-only integration status.
9. Avoid write smoke testing against production.
10. Verify key UI journeys.
11. Confirm logs contain no secrets.

Do not perform the production transition during Sprint 10.

## Incident And Rollback Checklist

- Record deployed commit and previous known-good commit.
- Enable maintenance mode if required.
- Restore previous code release.
- Restore environment configuration if it changed.
- Clear and rebuild Laravel caches.
- Restart queue workers.
- Review migration compatibility; do not automatically roll back user-data migrations.
- Run `php artisan sisahygo:diagnostics`.
- Run read-only `php artisan sisahygo:smoke-test --account=<client-account-id>`.
- Inspect Laravel, queue, and web server logs.
- Disable affected Client Account credentials if compromise is suspected.
