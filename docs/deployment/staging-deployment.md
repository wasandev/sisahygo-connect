# Forge Staging Deployment Runbook

Sprint 10 prepares deployment only. Do not execute these steps automatically from Codex.

## Site Shape

- Domain: `connect.sisahygo.online`
- Repository: `wasandev/sisahygo-connect`
- Initial deployment branch: `feature/staging-deployment`
- Final stable branch after review: `main`
- Web directory: `/public`
- PHP: 8.3
- Staging Core API: `https://sandbox-api.sisahygo.online/api/v1/client`

The permanent domain is used from the beginning. During staging it runs `APP_ENV=staging`; at production go-live the domain remains unchanged.

## Forge Setup Checklist

1. DNS: point `connect.sisahygo.online` to the Forge server and wait for propagation.
2. Site: create a Forge site for `connect.sisahygo.online`, PHP 8.3, web directory `/public`.
3. Repository: connect `wasandev/sisahygo-connect` and select `feature/staging-deployment` for the initial release candidate.
4. SSL: issue and verify a valid certificate for `connect.sisahygo.online`; enable HTTPS redirects if desired.
5. Environment: paste values based on `.env.staging.example` into Forge. Generate `APP_KEY` on the server. Do not include real secrets in docs.
6. Database: provision `sisahygo_connect_staging` with a dedicated least-privilege user. Never point to Sisahygo Core or production Connect DB.
7. Storage: run `php artisan storage:link` once if public storage is used. Confirm `storage/` and `bootstrap/cache/` are writable by the Forge user.
8. Queue: configure a worker for `database` queue if queued jobs are enabled. Restart workers on deploy.
9. Scheduler: configure Forge scheduler for `php artisan schedule:run` every minute if scheduled tasks are enabled.
10. Logs: verify Laravel log and web server error log after first boot.
11. Health: run diagnostics and read-only smoke checks.

## Deployment Script Draft

Verify Node/npm availability on the Forge server before enabling the build steps. If assets are built elsewhere, replace `npm ci` and `npm run build` with artifact upload/checkout steps.

```bash
set -e

cd /home/forge/connect.sisahygo.online

# Optional: enable maintenance mode for disruptive releases.
# $FORGE_PHP artisan down --render="errors::503" || true

git pull origin feature/staging-deployment

$FORGE_COMPOSER install     --no-interaction     --prefer-dist     --optimize-autoloader     --no-dev

npm ci
npm run build

$FORGE_PHP artisan migrate --force

$FORGE_PHP artisan optimize:clear
$FORGE_PHP artisan config:cache
$FORGE_PHP artisan route:cache
$FORGE_PHP artisan view:cache

$FORGE_PHP artisan queue:restart

# Optional: leave maintenance mode only after diagnostics pass.
# $FORGE_PHP artisan up
```

The script intentionally avoids destructive migration rollback. If a step fails, stop, keep the failed deploy visible in Forge logs, inspect Laravel/web logs, and either fix forward or restore the previous known-good release.

## Post-Deployment Diagnostics

```bash
php artisan sisahygo:diagnostics
php artisan sisahygo:integration-status --account=<client-account-id>
php artisan sisahygo:smoke-test --account=<client-account-id> --search=<known-sandbox-reference>
```

`diagnostics` should exit `0` and show application environment, app URL, release identifier, API environment, sanitized API host, database status, cache/session/queue config, PHP, and Laravel version. It must not show credentials.

`integration-status` should exit `0` only when configuration, credential, and connectivity pass. Failures must be sanitized and should not include headers, payloads, API keys, or stack traces.

`smoke-test` is read-only by default. It exits non-zero if required read checks fail and skips write checks unless explicitly requested.

## Controlled Single-Order Write Verification

Do not run automatically. Use only with approved sandbox fixture IDs:

```bash
php artisan sisahygo:smoke-test   --account=<client-account-id>   --search=<known-sandbox-reference>   --include-write   --confirm-write   --receiver-id=<sandbox-receiver-id>   --product-id=<sandbox-product-id>   --unit-id=<sandbox-unit-id>   --amount=1
```

Expected generated references start with `STG-SMOKE-` in staging and `SBX-SMOKE-` elsewhere. Record the generated reference, verify the created order through the Core sandbox API or UI, and confirm only one POST was sent. Production app env or production Core host must refuse this command. Bulk Order Checking remains a manual staging E2E item.

## Rollback Considerations

1. Record current and previous deployed commits.
2. Enable maintenance mode if user traffic or writes make rollback risky.
3. Restore the previous code release or branch/SHA.
4. Restore previous environment values if they changed.
5. Run `composer install --no-dev --optimize-autoloader` if dependencies differ.
6. Review migration compatibility. Do not automatically roll back data-bearing migrations.
7. Clear and rebuild Laravel caches.
8. Restart queue workers.
9. Run diagnostics and read-only smoke checks.
10. Inspect Laravel and web server logs.
11. Disable affected Client Account credentials if compromise is suspected.
