# Staging Environment

Sisahygo Connect is still local-only in Sprint 9. This document describes the target staging shape for a future deployment.

## Forge Site Requirements

- PHP version compatible with the locked Laravel version.
- Composer install with production dependencies.
- Node/npm available for `npm ci` and `npm run build`, or build artifacts produced in CI.
- HTTPS domain with valid SSL certificate.
- Database reachable from the app server.
- Queue worker configured if asynchronous jobs are enabled later.
- Scheduler configured to run Laravel schedule if scheduled tasks are introduced.

## Required Environment Variables

```env
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://connect-staging.example.test
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_BASE_URL=
SISAHYGO_API_SANDBOX_URL=https://sandbox-api.sisahygo.online/api/v1/client
SISAHYGO_API_PRODUCTION_URL=https://api.example.test/api/v1/client
SISAHYGO_API_CONNECT_TIMEOUT=5
SISAHYGO_API_TIMEOUT=15
SISAHYGO_API_RETRY_TIMES=2
SISAHYGO_API_RETRY_SLEEP_MS=250
SISAHYGO_API_USER_AGENT="Sisahygo Connect Staging"
SISAHYGO_API_LIVE_SMOKE_TESTS=false
```

Do not store real API keys in environment variables. Use encrypted per-Client Account credentials.

## Encrypted Credential Setup

1. Create or verify the Client Account.
2. Enable required capabilities for the account.
3. Link authorized sender/receiver customer IDs.
4. Store the API key through the encrypted credential workflow.
5. Run `php artisan sisahygo:integration-status --account=<id>`.

## Database Preparation

- Run migrations.
- Seed only approved staging/demo Client Accounts.
- Do not import production secrets into staging.
- Confirm credential records are encrypted at rest.

## Build Commands

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Queue And Scheduler

- Current smoke-test commands are manually invoked.
- Configure queue workers only when staging workflows require them.
- Configure scheduler only when scheduled jobs are introduced.

## SSL And Domain Checklist

- Staging domain resolves to Forge server.
- SSL certificate is valid and auto-renewing.
- `APP_URL` matches the HTTPS staging domain.
- Cookies/session settings are correct for HTTPS.
