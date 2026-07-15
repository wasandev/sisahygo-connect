# Sisahygo API Authentication

Sisahygo Client API ใช้ `X-Api-Key` สำหรับ authentication

Sisahygo Connect เก็บ credentials แยกตาม Client Account และ environment credentials ถูกเข้ารหัสขณะพักอยู่ในระบบ และถูกซ่อนจาก serialization Decrypted keys ห้ามถูกเปิดเผยไปยัง Blade, Livewire public properties, logs, exceptions หรือ browser responses

Credential lifecycle ที่รองรับ:

- create active credential
- replace active credential สำหรับ account/environment เดิม
- เก็บ historical credentials
- revoke credentials
- ระบุ credentials ด้วย fingerprint
- update `last_used_at`

Credential ไม่ใช่ customer identity ขอบเขตของผู้ส่งสินค้าและผู้รับสินค้าต้อง derive จาก `client_account_customers` เสมอ

## Local Credential Provisioning

Local Sandbox credentials provision ด้วย `./vendor/bin/sail artisan sisahygo:credential:set` คำสั่งนี้รับ API Key ผ่าน hidden input เก็บด้วย encrypted credential service เดิม rotate active credential เดิมหลังจากผู้ใช้ยืนยัน และแสดงเฉพาะ safe fingerprint ดู workflow เต็มได้ที่ [local-smoke-test.md](local-smoke-test.md)