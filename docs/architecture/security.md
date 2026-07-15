# Security

Shipment query หรือ Payment query ในอนาคตทุกจุดต้องเริ่มจาก authorized Client Account scope เท่านั้น Security ห้ามพึ่งการกรองใน Blade Policies และ query objects ต้องป้องกัน horizontal privilege escalation และ direct URL access ไปยังข้อมูลที่ไม่มีสิทธิ์

## Sisahygo API Integration Security

External API calls ต้องสร้างจาก `SisahygoIntegrationContext` ที่ validate แล้ว context นี้แยก authorized sender customer IDs ออกจาก authorized receiver customer IDs ค่า customer IDs ที่มาจาก caller ต้องถูกปฏิเสธ เว้นแต่ตรงกับ scope ของ Client Account ที่เลือก

Operational logs อาจเก็บ metadata เช่น Client Account ID, credential ID, fingerprint, endpoint, method, status, duration, retry count และ correlation ID แต่ห้ามเก็บ decrypted API keys หรือ full sensitive payloads