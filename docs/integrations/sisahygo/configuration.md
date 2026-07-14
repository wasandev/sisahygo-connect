# Sisahygo API Configuration

Configuration is defined in `config/sisahygo.php`.

Environment variables:

```env
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_SANDBOX_URL=https://sandbox-api.sisahygo.online/api/v1/client
SISAHYGO_API_PRODUCTION_URL=https://api.sisahygo.online/api/v1/client
SISAHYGO_API_CONNECT_TIMEOUT=5
SISAHYGO_API_TIMEOUT=15
SISAHYGO_API_RETRY_TIMES=2
SISAHYGO_API_RETRY_SLEEP_MS=250
SISAHYGO_API_USER_AGENT="Sisahygo Connect"
SISAHYGO_API_LIVE_SMOKE_TESTS=false
```

Only `sandbox` and `production` are supported environments. Base URLs must be HTTPS URLs from trusted configuration. Application classes must not hard-code environment URLs.

No real API keys belong in tracked files.