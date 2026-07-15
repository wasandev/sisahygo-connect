# Sisahygo API Configuration

Configuration กำหนดอยู่ใน `config/sisahygo.php`

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

รองรับ environment เฉพาะ `sandbox` และ `production` เท่านั้น Base URLs ต้องเป็น HTTPS URLs จาก trusted configuration Application classes ห้าม hard-code environment URLs

ห้ามใส่ API keys จริงใน tracked files