# Sisahygo Sandbox Smoke Test Credential Provisioning

Workflow นี้ใช้เฉพาะ local เท่านั้น และเตรียม encrypted Sisahygo API credential สำหรับ controlled read-only Sandbox smoke test

ห้ามเก็บ API keys ใน `.env`, tracked files, chat logs, screenshots, tickets หรือ command arguments คำสั่ง credential จะอ่าน key ผ่าน hidden terminal input และแสดงเฉพาะ safe fingerprint

## Enable Local Smoke Tests

ตั้งค่าต่อไปนี้เฉพาะใน local environment:

```env
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_LIVE_SMOKE_TESTS=true
```

จากนั้น clear cached configuration:

```bash
./vendor/bin/sail artisan optimize:clear
```

## Provision A Credential

รัน local-only command ภายใน Sail:

```bash
./vendor/bin/sail artisan sisahygo:credential:set
```

สามารถเลือก Client Account ที่รู้จักอย่างชัดเจนได้ด้วย:

```bash
./vendor/bin/sail artisan sisahygo:credential:set --account=ACCT-10001 --environment=sandbox
```

API Key prompt จะถูกซ่อนไว้ ให้วาง Sandbox key เฉพาะตอนที่ระบบ prompt เท่านั้น ห้ามส่ง key ผ่าน option หรือ argument

คำสั่งจะ validate ว่า:

- app environment เป็น local
- Client Account มีอยู่จริงและ active
- environment ที่เลือกเป็น `sandbox` หรือ `production`
- account มี active customer links
- `shipment.view` เปิดใช้งาน
- มีการรายงาน readiness ของ `shipment.history`
- มีการรายงาน authorized sender relationship สำหรับ `GET /receivers`
- Sandbox base URL resolve ได้ตรงกับ `https://sandbox-api.sisahygo.online/api/v1/client`

ถ้ามี active credential อยู่แล้วสำหรับ Client Account และ environment เดิม คำสั่งจะขอ confirmation ก่อนแทนที่ การแทนที่ใช้ credential lifecycle เดิมและ revoke previous active credential

## Verify The Stored Credential Safely

ตรวจสอบเฉพาะ credential status และ fingerprint ห้าม decrypt หรือ print API Key

```bash
./vendor/bin/sail artisan tinker
```

ตัวอย่าง safe checks ภายใน Tinker:

```php
App\Domain\Sisahygo\Models\SisahygoApiCredential::query()
    ->select('client_account_id', 'environment', 'status', 'key_fingerprint', 'created_at')
    ->latest()
    ->first();
```

## Disable Smoke Testing After Verification

หลังจาก read-only Sandbox verification เสร็จแล้ว ให้ตั้งค่า:

```env
SISAHYGO_API_LIVE_SMOKE_TESTS=false
```

แล้ว clear cached configuration อีกครั้ง:

```bash
./vendor/bin/sail artisan optimize:clear
```

## Local-Only Boundary

Workflow นี้ห้ามใช้กับ production credentials เว้นแต่ได้รับอนุมัติอย่างชัดเจนสำหรับ production-readiness process แยกต่างหาก Workflow smoke test ตามค่าเริ่มต้นเป็น Sandbox เท่านั้น และห้าม persist API response payloads ลง local database