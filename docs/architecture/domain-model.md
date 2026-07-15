# Domain Model

Client Account แทนองค์กรหนึ่งองค์กร โดยหนึ่ง Client Account มีผู้ใช้ได้หลายคน และเชื่อมกับ external Sisahygo customer identifiers ได้หลายรายการ

Customer link หนึ่งรายการกำหนดสิทธิ์แยกกันได้ดังนี้:

- `can_send`
- `can_receive`
- `can_view_payment`
- `is_default_sender`
- `is_default_receiver`
- `is_active`

Client Accounts ไม่ได้ผูกกับลูกค้าเพียงรายเดียว และไม่ถูก model เป็น enum แบบ sender/receiver/both

Workflows ที่ขึ้นกับ tenant ต้องทำงานภายใต้ current Client Account context ผู้ใช้ที่มี active Client Accounts หลายบัญชีต้องเลือกบัญชีปัจจุบันเองอย่างชัดเจน

Shipment visibility และ payment visibility เป็น domain rules คนละชุด ทั้งสองอย่างได้สิทธิ์จาก authorized transactions ไม่ใช่จาก customer master data โดยตรง