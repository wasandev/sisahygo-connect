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

## Owner-Facing Credential Setup

Production credential setup is completed from Client Account Settings -> Sisahygo API by an owner or administrator with the `settings.manage` capability. The key is still generated in Sisahygo Core Nova: a Core administrator opens the approved API Client, runs `สร้าง/เปลี่ยน API Key`, and copies the plaintext key shown once.

Connect verifies the submitted key with Core `GET /api/v1/client/ping` before saving it. Invalid, inactive, unavailable, rate-limited, malformed, and server-error responses are shown as safe UI messages and the submitted key is not stored as active.

After verification succeeds, Connect stores the key through `SisahygoApiCredentialService`, which uses Laravel encrypted casting for `sisahygo_api_credentials.encrypted_api_key`, stores a SHA-256 fingerprint for display/audit, and keeps one active credential slot per Client Account/environment. Replacing a credential verifies the new key first; only then does the existing service revoke the previous active local credential and link it through `rotated_from_id`.

Saved credentials are never displayed again. UI and logs may show only safe metadata such as environment, status, shortened fingerprint, and last-used time. Do not place raw API keys in invitation preview, activation responses, `.env` files, logs, screenshots, tickets, docs, or command arguments.

Invitation activation and credential issuance remain separate. Core activation returns `credential: null`, and a newly activated account can exist before an owner completes credential setup.


## First-time Setup and Health Status

The owner-facing settings screen is also the first-time setup wizard for the Sisahygo API step. It explains that Core stores only the API key hash, Connect stores the submitted key only after successful `/ping` verification using Laravel encryption, and the saved key is never rendered again.

The API health card displays only supported safe values: environment, active/missing credential status, connection status, shortened fingerprint, response latency, last-used time, and the timestamp of the current check. It classifies failures as connected, no credential, invalid credential, inactive/forbidden API Client, Core unavailable, timeout, rate limited, malformed response, or unknown error. Raw Core payloads and request headers are not displayed.

Credential replacement verifies the new key first. A failed replacement leaves the previous active local credential usable; a successful replacement revokes the prior local credential. Re-submitting the same active key is idempotent and does not create duplicate active credentials.
