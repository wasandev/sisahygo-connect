# Sisahygo API Testing

Automated tests ปกติต้องใช้ `Http::fake()` และ deterministic fixtures จาก `tests/Fixtures/Sisahygo/V1/`

Test suite ครอบคลุม:

- configuration resolution และ invalid environments
- encrypted credential storage และ serialization safety
- credential resolution, revocation, rotation และ last-used timestamp
- tenant-safe context building
- sender และ receiver customer scopes
- HTTP headers, correlation IDs และ user agent
- retry behavior สำหรับ GET requests
- no blind retry สำหรับ POST requests
- API error mapping
- malformed JSON handling
- DTO mapping และ harmless extra fields

Live Sandbox smoke tests ปิดไว้ตามค่าเริ่มต้น ต้อง opt-in อย่างชัดเจนและต้องมี locally supplied credential ห้ามรันใน CI ตามค่าเริ่มต้น

## Local Sandbox Credential Workflow

ก่อนรัน read-only live Sandbox smoke test ให้ provision credential ด้วย local-only command ตามที่บันทึกไว้ใน [local-smoke-test.md](local-smoke-test.md) ห้ามรัน live smoke test จนกว่าคำสั่งจะรายงาน fingerprint/status ที่คาดหวัง และ configuration cache ถูก clear แล้ว

## Sandbox Contract Verification

บันทึก controlled read-only Sandbox contract ล่าสุดอยู่ที่ [sandbox-contract-verification.md](sandbox-contract-verification.md) Automated tests ปกติต้องใช้ faked HTTP responses และ local fixtures ต่อไป