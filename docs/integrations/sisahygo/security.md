# Sisahygo API Security

Security rules:

- API credentials ใช้ authenticate requests เท่านั้น ไม่ใช่ customer identity
- Sender scope มาจาก active Client Account customer links ที่มี `can_send = true`
- Receiver scope มาจาก active Client Account customer links ที่มี `can_receive = true`
- Payment access ต้องมี `can_view_payment = true` และ payment capability ด้วย
- Arbitrary customer IDs จาก browser หรือ callers ต้องถูกปฏิเสธ เว้นแต่ตรงกับ scope ของ Client Account ที่เลือก
- Queued work ต้อง reconstruct context จาก explicit IDs และ revalidate account, credential, capability และ customer scope
- Logs ห้ามมี API keys หรือ full sensitive payloads

Capability checks ต้องเกิดก่อนส่ง external requests