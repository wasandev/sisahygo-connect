# Navigation

Navigation ควรช่วยให้ลูกค้าเริ่มทำงานได้ง่าย ไม่ใช่เปิดเผย system modules ทั้งหมด

## Final Recommendation

ใช้ primary items 6 รายการ:

- Dashboard
- Order Checking
- Tracking
- Payments
- Reports
- Settings

## Rationale

- Dashboard ตอบคำถาม “ตอนนี้มีอะไรต้องดูแล”
- Order Checking เป็น workflow หลักสำหรับสร้างรายการ
- Tracking ครอบคลุม active shipments และประวัติรายละเอียด
- Payments ครอบคลุมยอดค้างชำระ invoices และ history
- Reports ครอบคลุม summary views
- Settings ครอบคลุม account, team และ profile-related setup

Shipments และ History ไม่ได้ถูกลบจาก backend แต่ถูกเลื่อนออกจาก primary navigation เพื่อลดความซับซ้อนของ product ในช่วงแรก