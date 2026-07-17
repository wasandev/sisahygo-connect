# Shipments

Sprint 2B ใช้ Sisahygo Core Client API เป็น authoritative source สำหรับ shipment list และ shipment detail ห้ามอ่าน shipment/order tables โดยตรงจาก Connect

## Production Routes

- `/shipments` แสดงรายการขนส่งจาก `GET /api/v1/client/shipments`
- `/shipments/{trackingIdentifier}` แสดงรายละเอียดและ timeline จาก `GET /api/v1/client/shipments/{tracking_no}`
- `/tracking` เป็น lookup form ที่ส่งผู้ใช้ไปยัง detail route

## Contract ที่ใช้ใน Sprint 2B

Connect ส่งเฉพาะ filters ที่ Core endpoint รองรับ:

- `from_date`
- `to_date`
- `order_status`
- `tracking_no`
- `id`
- `order_header_no`
- `page`
- `per_page`

Connect ไม่ส่ง `sender_customer_ids` หรือ `receiver_customer_ids` ไปที่ shipment endpoints เพราะ Core scope รายการด้วย API key อยู่แล้ว

## Identifier Semantics

`tracking_no` ของ Core endpoint ปัจจุบันเทียบกับ `order_headers.id` และถูกส่งกลับเป็น string ใน response field `tracking_no`

ถ้า user ค้นหาด้วย keyword เป็นตัวเลข Connect map เป็น `tracking_no` ถ้าไม่ใช่ตัวเลข Connect map เป็น `order_header_no`

## Visibility

Sprint 2B visibility อยู่หลัง Core API boundary:

- Connect ใช้ active encrypted Sandbox/Production credential ของ Client Account ปัจจุบันตาม environment
- Connect ตรวจ `shipment.view` ก่อนสร้าง integration context
- Core API ใช้ API key เพื่อ resolve sender `customer_id`
- Core shipment query filter รายการด้วย sender `order_headers.customer_id`

Receiver-side shipment visibility ยังไม่ถูก claim ใน Sprint 2B จนกว่า Core contract จะรองรับ receiver-scoped shipment access อย่างชัดเจน

## UI Behavior

หน้า shipment ต้องแสดงข้อมูลอย่างปลอดภัยและอ่านง่ายบน mobile:

- loading state ระหว่างค้นหาและ refresh
- empty state เมื่อไม่พบรายการ
- error state ที่ไม่แสดง API key หรือ raw payload
- timeline จาก `history` หรือ `status_history`
- status label ใช้ localization ผ่าน `lang/*/shipments.php`

## Deferred

- export shipment
- receiver-scoped shipment visibility
- direct shipment mutation
- local shipment cache table
- notification/polling workflow
