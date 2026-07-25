# Sisahygo API Configuration

Configuration กำหนดอยู่ใน `config/sisahygo.php`

Environment variables:

```env
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_BASE_URL=
SISAHYGO_API_SANDBOX_URL=https://sandbox-api.sisahygo.online/api/v1/client
SISAHYGO_API_LIVE_URL=https://api.sisahygo.online/api/v1/client
SISAHYGO_API_CONNECT_TIMEOUT=5
SISAHYGO_API_TIMEOUT=15
SISAHYGO_API_RETRY_TIMES=2
SISAHYGO_API_RETRY_SLEEP_MS=250
SISAHYGO_API_USER_AGENT="Sisahygo Connect"
SISAHYGO_API_LIVE_SMOKE_TESTS=false
SISAHYGO_RELEASE_VERSION=
SISAHYGO_RELEASE_BUILD=
SISAHYGO_RELEASE_COMMIT=
```

รองรับ integration environment เฉพาะ `sandbox` และ `production` เท่านั้น Base URLs ต้องเป็น HTTPS URLs จาก trusted configuration Application classes ห้าม hard-code environment URLs

Sprint 10 policy:

- Staging app (`APP_ENV=staging`) ใช้ permanent domain `https://connect.sisahygo.online` และต้องชี้ไปที่ sandbox API `https://sandbox-api.sisahygo.online/api/v1/client` เท่านั้น
- Production app (`APP_ENV=production`) ยังใช้ domain เดิม `https://connect.sisahygo.online` แต่ต้องใช้ production API `https://api.sisahygo.online/api/v1/client`
- Production จะ reject sandbox host และ staging จะ reject production host แบบ fail closed
- API keys จริงต้องถูกเก็บใน encrypted per-Client Account credentials เท่านั้น ไม่เก็บใน environment files

ห้ามใส่ API keys จริงใน tracked files
