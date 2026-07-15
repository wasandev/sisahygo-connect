# Sandbox Contract Verification

Last verified: 2026-07-14

เอกสารนี้บันทึก controlled read-only Sandbox verification สำหรับ Sisahygo V1 Client API ไม่มีการเรียก POST requests และไม่มีการเก็บ API keys, customer names, phone numbers, addresses หรือ raw response payloads ในเอกสารนี้

## Safety Gate

Verification จะรันได้เฉพาะเมื่อเงื่อนไขทั้งหมดนี้เป็นจริง:

- `SISAHYGO_API_LIVE_SMOKE_TESTS=true`
- configured environment เป็น `sandbox`
- base URL resolve ได้ตรงกับ `https://sandbox-api.sisahygo.online/api/v1/client`
- production host ถูกปฏิเสธ
- มี active encrypted Sandbox credential สำหรับ Client Account
- Client Account อยู่ในสถานะ active
- มี active customer links
- `shipment.view` เปิดใช้งาน

## Endpoints Verified

เรียกเฉพาะ read-only endpoints ต่อไปนี้:

- `GET /receivers`
- `GET /shipments`

ทั้งสอง endpoint ตอบกลับ HTTP `200` ใน Sandbox ระหว่าง verification นี้

## GET /receivers Contract

Observed root structure:

- root keys: `data`
- pagination keys: ไม่พบ
- `per_page` ไม่สะท้อนใน response shape ที่สังเกตได้

Observed receiver item field names:

- `customer_rec_id`
- `to_customer_name`
- `to_customer_phone`
- `branch_rec_id`

Contract adjustment:

- `ReceiverMapper` รับ `customer_rec_id` เป็น receiver customer ID alias แล้ว
- `ReceiverMapper` รับ `to_customer_name` เป็น receiver name alias แล้ว
- `ReceiverMapper` รับ `to_customer_phone` เป็น receiver phone alias แล้ว
- provisional aliases เดิมยังคงรองรับเพื่อ backward compatibility

## GET /shipments Contract

Observed root structure:

- root keys: `data`, `meta`
- meta keys: `current_page`, `per_page`, `total`, `last_page`
- ไม่พบ links object

Observed shipment summary field names include:

- `id`
- `tracking_no`
- `client_reference_no`
- `order_header_no`
- `order_header_date`
- `order_status`
- `order_type`
- `order_amount`
- `branch`
- `branch_rec`
- `customer`
- `customer_rec`
- `items`
- `history`

Observed nested item field names include:

- `id`
- `product_id`
- `product_name`
- `unit_id`
- `unit`
- `price`
- `amount`
- `line_amount`
- `remark`
- `client_line_id`
- `client_item_no`
- `client_product_code`

Observed status history field names:

- `history[].status`
- `history[].changed_at`

Contract adjustment:

- `ShipmentMapper` รับ `product_name` เป็น item name alias แล้ว
- `ShipmentMapper` รับ `history` เป็น status history alias แล้ว
- `ShipmentMapper` รับ `changed_at` เป็น status timestamp alias แล้ว
- aliases เดิมอย่าง `items`, `status_history`, `occurred_at` และ `created_at` ยังรองรับอยู่

## Nullable And Missing Fields

Observed Sandbox shipment list samples ไม่มี `paymenttype` หรือ `payment_status` DTO ปัจจุบันจึงคงให้สอง field นี้เป็น nullable และยังรับ provisional payment fields เดิมเมื่อมีส่งมา

Observed sender และ receiver object structures ไม่ได้มาเป็น nested objects ใน shipment list sample ที่ verify แล้ว mapper ยังรองรับ provisional nested sender/receiver IDs เมื่อพบ แต่ไม่ได้บังคับว่าต้องมี

## Receiver Authorization Finding

Credential ที่ใช้ verify มีทั้ง sender และ receiver customer relationships ดังนั้น Sandbox check ยืนยันได้ว่า credential ปัจจุบันเรียก `GET /receivers` ได้ แต่ receiver-only authorization ยังไม่ได้ verify และห้าม claim ว่าสมบูรณ์จนกว่าจะทดสอบด้วย receiver-only Sandbox credential หรือได้รับการยืนยันจาก Core API team

## Sensitive Data Handling

Verification output จำกัดไว้ที่ HTTP statuses, root keys, field names, counts, safe fingerprints และ redacted shape summaries เท่านั้น API Key, customer names, phone numbers, addresses และ full raw payloads ไม่ถูก print หรือ commit