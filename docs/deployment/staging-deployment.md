# Staging Deployment Draft

Sprint 9 does not deploy. This is a draft for a future staging deployment.

## Deployment Script Draft

```bash
set -e
cd /home/forge/connect-staging
php artisan down --render="errors::503" || true
git fetch --all --prune
git checkout <release-candidate-sha>
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
php artisan sisahygo:diagnostics
php artisan sisahygo:integration-status --account=<client-account-id>
php artisan sisahygo:smoke-test --account=<client-account-id>
```

## Smoke-Test Procedure

1. Run `php artisan sisahygo:diagnostics`.
2. Run `php artisan sisahygo:integration-status --account=<id>`.
3. Run read-only `php artisan sisahygo:smoke-test --account=<id>`.
4. Run controlled write smoke only when approved:

```bash
php artisan sisahygo:smoke-test \
  --account=<id> \
  --include-write \
  --confirm-write \
  --receiver-id=<sandbox-receiver-id> \
  --product-id=<sandbox-product-id> \
  --unit-id=<sandbox-unit-id>
```

Controlled write tests are refused outside sandbox and never auto-retry POST creation requests.

## Rollback Procedure

1. Put app in maintenance mode.
2. Checkout previous known-good SHA.
3. Restore previous env/config values if changed.
4. Run `composer install --no-dev --optimize-autoloader`.
5. Rebuild caches.
6. Bring app up.
7. Run diagnostics and read-only smoke test.
8. Rotate credentials if any secret exposure is suspected.

## Secret Handling

- Never print API keys, Authorization headers, passwords, database credentials, or private payloads.
- Share command output only after reviewing for secrets.
- Keep staging credentials revocable independently from production credentials.

## Known Limitations

- Notification Center remains mock-only.
- Live smoke tests require real sandbox credentials and must be explicitly invoked.
- Connectivity status checks `/units`; it does not prove every Core endpoint is healthy.
- No destructive cleanup command exists because no supported Core cleanup contract is available.
