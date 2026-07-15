# Receiver Compatibility Gap

Sisahygo Core API ปัจจุบันถูกออกแบบมาโดยเน้น sender clients เป็นหลัก Sisahygo Connect รองรับ receiver-linked Client Accounts ใน domain model แล้ว แต่ความสมบูรณ์ระดับ Production ยังขึ้นกับการรองรับจาก Core API

Receiver shipment access ต้องใช้ Core API authorization จาก:

`order_headers.customer_rec_id`

Receiver payment access ต้องใช้:

- authorized receiver relationship
- `paymenttype IN ('E', 'L')`

สิ่งที่ต้องให้ Core API ยืนยันหรือปรับปรุง:

- shipment list filtering และ authorization ด้วย receiver customer ID
- shipment detail authorization สำหรับ tracking numbers ที่ receiver มีสิทธิ์เห็น
- payment visibility จาก receiver customer ID และ receiver payment types
- deduplication behavior ในกรณีที่เป็นทั้ง sender/receiver
- server-side customer authorization ที่ปลอดภัยและไม่เชื่อ arbitrary client-supplied IDs

จนกว่าสัญญาเหล่านี้จะถูกยืนยัน receiver API integration ต้องถูกบันทึกว่าเป็น provisional และห้ามอ้างว่าสมบูรณ์แล้ว

## Sandbox Verification Note

Sandbox smoke verification วันที่ 2026-07-14 ใช้ credential ที่มีทั้ง sender และ receiver customer relationships `GET /receivers` ตอบกลับ HTTP 200 สำหรับ credential นั้น Receiver-only authorization ยังไม่ได้ verify และห้ามระบุว่ารองรับครบถ้วนจนกว่า Core API contract จะยืนยัน หรือมีการทดสอบด้วย receiver-only Sandbox credential