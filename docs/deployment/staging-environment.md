# Staging Environment

Sprint 10 uses the permanent public domain as the controlled release-candidate environment. Do not create `connect-sandbox.sisahygo.online`.

## Staging Values

```env
APP_NAME="Sisahygo Connect"
APP_ENV=staging
APP_KEY=base64:<generate-on-server>
APP_DEBUG=false
APP_URL=https://connect.sisahygo.online
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisahygo_connect_staging
DB_USERNAME=<connect_staging_db_user>
DB_PASSWORD=<staging-db-password>
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_BASE_URL=https://sandbox-api.sisahygo.online/api/v1/client
SISAHYGO_API_CONNECT_TIMEOUT=5
SISAHYGO_API_TIMEOUT=15
```

Use `.env.staging.example` as the safe template. It contains placeholders only; do not add API keys, database passwords, mail credentials, tokens, or production secrets to tracked files.

## Production Values Later

At go-live the URL stays the same and only the runtime mode and Core endpoint change:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://connect.sisahygo.online
SISAHYGO_API_ENVIRONMENT=production
SISAHYGO_API_BASE_URL=https://api.sisahygo.online/api/v1/client
```

Production must not use the sandbox endpoint. Staging must not use the production endpoint.

## Separate Connect Staging Database

Provision a Connect-only database, for example `sisahygo_connect_staging`. It must not be the Sisahygo Core database and must not be the production Connect database.

Example DBA steps with placeholders:

```sql
CREATE DATABASE sisahygo_connect_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '<connect_staging_db_user>'@'%' IDENTIFIED BY '<staging-db-password>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP ON sisahygo_connect_staging.* TO '<connect_staging_db_user>'@'%';
FLUSH PRIVILEGES;
```

Set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in Forge only. Run `php artisan migrate --force`, then verify with `php artisan sisahygo:diagnostics`. Back up staging before future production rehearsal imports or destructive test cycles.

## Staging Administrator And Client Account Provisioning

Supported by existing UI/workflows:

1. Create or identify a staging administrator using the normal application user-management/admin workflow.
2. Log in to Sisahygo Connect at `https://connect.sisahygo.online`.
3. Create or select a staging Client Account.
4. Associate the administrator or test operator with the Client Account.
5. Add sender/receiver customer mappings using known sandbox customer IDs only.
6. Provision the sandbox API credential with `php artisan sisahygo:credential:set`; the command reads the key with hidden input and prints only a safe fingerprint.
7. Run `php artisan sisahygo:integration-status --account=<client-account-id>`.

Currently requiring Tinker/database administration if no UI is available in the deployed admin surface:

- Creating the first administrator.
- Creating the initial Client Account and membership.
- Creating or correcting customer mapping records.
- Verifying encrypted-at-rest storage by inspecting that `sisahygo_api_credentials.encrypted_api_key` is ciphertext and never the plaintext API key.

Credential replacement: create a new credential for the same Client Account/environment, verify status, then revoke the old credential. Do not print decrypted values.

## Runtime Notes

- `APP_DEBUG=false` for staging and production.
- `SESSION_SECURE_COOKIE=true` once HTTPS is active.
- Notification Center remains mock-only until a later sprint.
- Staging uses sandbox credentials only; never copy production credentials or production data into staging.
