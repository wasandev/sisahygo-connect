# ข้อเสนอ Core Payment Client API สำหรับ Payment Center

เอกสารนี้สรุป gap ที่พบระหว่าง Sprint 4 Payment Center ของ Sisahygo Connect กับ Sisahygo Core Client API ปัจจุบัน และเสนอ contract ขั้นต่ำที่ควรมีใน Core ก่อน Connect จะสร้าง production Payment Center ได้อย่างปลอดภัย

## สถานะปัจจุบัน

Sisahygo Connect ต้องสื่อสารกับ Sisahygo Core ผ่าน Client API เท่านั้น และต้องไม่ query ฐานข้อมูล Core โดยตรง

จากการตรวจ Core source ที่ `/home/wasandev/sisahygo` พบว่า Client API ภายใต้ `Route::middleware(['client.apikey', 'throttle:60,1'])->prefix('v1/client')` มี endpoint ต่อไปนี้:

- `GET /api/v1/client/shipments`
- `GET /api/v1/client/shipments/{tracking_no}`
- `GET /api/v1/client/receivers`
- `GET /api/v1/client/products`
- `GET /api/v1/client/units`
- `GET /api/v1/client/order-checkings`
- `GET /api/v1/client/order-checkings/{client_reference_no}`
- `POST /api/v1/client/order-checkings`
- `POST /api/v1/client/order-checkings/bulk`
- `GET /api/v1/client/order-rejections`
- `GET /api/v1/client/order-rejections/{client_reference_no}`
- `GET /api/v1/client/profile`
- `GET /api/v1/client/ping`

ไม่พบ payment-specific Client API:

- ไม่พบ `GET /api/v1/client/payments`
- ไม่พบ `GET /api/v1/client/payments/{paymentIdentifier}`
- ไม่พบ `GET /api/v1/client/payment-summary`
- ไม่พบ Client API สำหรับ invoice, receipt, branch balance หรือ billing

`GET /api/v1/client/shipments` ไม่พอสำหรับ Payment Center เพราะ Core source ปัจจุบันคืน `order_amount` แต่ไม่คืน `paymenttype` หรือ `payment_status` และ scope ด้วย `api_clients.customer_id` ไปยัง `order_headers.customer_id` เท่านั้น จึงไม่รองรับ receiver-side visibility สำหรับ `E` และ `L`

## Business Definitions

ประเภทการชำระเงิน:

- `H` = เงินสดต้นทาง: sender pays cash at origin
- `T` = เงินโอนต้นทาง: sender pays by bank transfer at origin
- `E` = เก็บเงินปลายทาง: receiver pays when delivered
- `F` = วางบิลต้นทาง: credit billing to sender
- `L` = วางบิลปลายทาง: credit billing to receiver

สถานะการชำระเงิน:

- `0` = ค้างชำระ
- `1` = ชำระแล้ว

Visibility rules ที่ต้อง enforce ใน Core:

- Sender visibility: `order_headers.customer_id`, เห็นเฉพาะ `H`, `T`, `F`
- Receiver visibility: `order_headers.customer_rec_id`, เห็นเฉพาะ `E`, `L`
- ไม่รับ customer id จาก request เพื่อเปลี่ยน scope
- Core เป็น source of truth ของ payer visibility

## Authentication และ Error Envelope

Core ใช้ middleware `client.apikey` ผ่าน `App\Http\Middleware\ApiClientAuth`

พฤติกรรมที่พบ:

- อ่าน `X-Api-Key`
- hash ด้วย SHA-256 แล้วหา `api_clients.api_key`
- ต้องเป็น `is_active = true`
- ตรวจ `allowed_ips` ถ้ากำหนด
- set request attributes:
  - `api_client`
  - `customer_id`

Error envelope ที่มีอยู่:

```json
{
  "error": {
    "code": "API_KEY_INVALID",
    "message": "...",
    "status": 401
  }
}
```

Core มี helper `App\Support\ClientApiResponse` สำหรับ:

- `VALIDATION_ERROR` 422
- `RESOURCE_NOT_FOUND` 404
- `RATE_LIMITED` 429
- `SERVER_ERROR` 500

Payment endpoints ควรใช้ error envelope เดียวกัน และควรส่ง correlation id สำหรับ server errors หรือ operation ที่ต้อง trace

## Proposed Endpoint: Payment List

`GET /api/v1/client/payments`

### Middleware

- `client.apikey`
- throttle ตามมาตรฐาน Client API

### Supported Filters

- `from_date`
- `to_date`
- `payment_status`
- `payment_type`
- `order_header_no`
- `client_reference_no`
- `page`
- `per_page`

Validation:

- `from_date`, `to_date`: date
- `to_date`: after_or_equal `from_date`
- `payment_status`: `0` หรือ `1`
- `payment_type`: `H`, `T`, `E`, `F`, `L`
- `order_header_no`: string, max length ตาม schema/business rule
- `client_reference_no`: string, max 100
- `page`: integer, min 1
- `per_page`: integer, min 1, max 100

### Authorization Scope

Core ต้อง derive visibility จาก `api_client.customer_id` เท่านั้น:

```text
visible when:
  order_headers.customer_id = api_client.customer_id
  and paymenttype in H,T,F

or:
  order_headers.customer_rec_id = api_client.customer_id
  and paymenttype in E,L
```

ถ้า `payment_type` filter ถูกส่งมา ต้องถูก intersect กับ visibility group ของ payer role ไม่ใช่ override scope

### Response Envelope

```json
{
  "data": [
    {
      "id": 123,
      "payment_identifier": "order:123",
      "order_header_id": 123,
      "order_header_no": "OH202607180001",
      "order_header_date": "2026-07-18",
      "client_reference_no": "SC-20260718-ABC123",
      "payer_role": "sender",
      "payment_type": "H",
      "payment_status": 0,
      "amount": "1000.00",
      "paid_amount": "0.00",
      "balance_amount": "1000.00",
      "billing_date": null,
      "payment_date": null,
      "sender_name": "บริษัท ต้นทาง จำกัด",
      "receiver_name": "บริษัท ปลายทาง จำกัด",
      "waybill_no": "123"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  },
  "summary": {
    "total_amount": "0.00",
    "total_paid": "0.00",
    "total_outstanding": "0.00"
  }
}
```

### Amount Semantics

Core ต้องระบุ source และความหมายของ amount อย่างชัดเจน:

- `amount`: จำนวนเงินตั้งต้นของ record ที่ลูกค้ามองเห็น
- `paid_amount`: จำนวนเงินที่ชำระแล้ว
- `balance_amount`: จำนวนเงินคงค้าง

ห้ามให้ Connect คำนวณ summary จาก partial page ถ้า Core ไม่ส่ง summary ที่คำนวณจาก filtered query ทั้งหมด

ถ้า payment ของ `H`, `T`, `F`, `E`, `L` ต้องอิงต่าง source เช่น `order_headers`, `branch_balances`, `receipts`, `invoices`, Core ต้อง normalize เป็น payment record เดียวก่อนส่งให้ Connect

## Proposed Endpoint: Payment Detail

`GET /api/v1/client/payments/{paymentIdentifier}`

ควรใช้ identifier ที่ไม่เปิดเผย internal accounting table เกินจำเป็น เช่น:

- `order:{order_header_id}`
- หรือ opaque `payment_identifier` ที่ Core สร้าง

Detail response:

```json
{
  "data": {
    "id": 123,
    "payment_identifier": "order:123",
    "order_header_id": 123,
    "order_header_no": "OH202607180001",
    "order_header_date": "2026-07-18",
    "client_reference_no": "SC-20260718-ABC123",
    "payer_role": "sender",
    "payment_type": "H",
    "payment_status": 0,
    "amount": "1000.00",
    "paid_amount": "0.00",
    "balance_amount": "1000.00",
    "billing_date": null,
    "payment_date": null,
    "invoice_no": null,
    "receipt_no": null,
    "sender_name": "บริษัท ต้นทาง จำกัด",
    "receiver_name": "บริษัท ปลายทาง จำกัด",
    "waybill_no": "123",
    "shipment": {
      "tracking_no": "123",
      "order_status": "created"
    }
  }
}
```

ถ้า invoice/receipt ไม่พร้อมหรือไม่ปลอดภัยต่อ customer visibility ให้ส่ง `null` และ document ว่า unavailable

## Possible Core Source Fields

จาก source inspection พบ fields ที่เกี่ยวข้อง แต่ Core ต้องยืนยัน semantics ก่อน expose:

`order_headers`:

- `id`
- `order_header_no`
- `order_header_date`
- `customer_id`
- `customer_rec_id`
- `paymenttype`
- `payment_status`
- `order_amount`
- `base_order_amount`
- `client_reference_no`

`branch_balances`:

- `order_header_id`
- `branch_id`
- `bal_amount`
- `discount_amount`
- `tax_amount`
- `pay_amount`
- `branchbal_date`
- `branchpay_date`
- `payment_status`
- `receipt_id`
- `type`

`receipts`:

- `receipt_no`
- `receipt_date`
- `customer_id`
- `total_amount`
- `discount_amount`
- `tax_amount`
- `pay_amount`
- `receipttype`

`invoices`:

- `invoice_no`
- `invoice_date`
- `due_date`
- `status`
- `receipt_id`

## Required Core Tests

Core should add Client API tests for:

- API key required, invalid, inactive, IP denied
- list route and detail route under `client.apikey`
- supported filters
- unsupported customer scope cannot be injected
- date validation
- sender can see `H`, `T`, `F`
- sender cannot see `E`, `L`
- receiver can see `E`, `L`
- receiver cannot see `H`, `T`, `F`
- mixed sender/receiver customer behavior
- no cross-customer leakage
- summary totals are computed from the full filtered query
- pagination meta is accurate
- amount fields are stable decimal strings
- 404 for unauthorized or missing payment detail
- standard 422/429/500 error envelopes

## Connect Work Deferred

Connect production Payment Center should remain deferred until the Core contract exists

Deferred Connect work:

- `PaymentsEndpoint`
- Payment DTOs/mappers
- `ListPayments`
- optional `ViewPayment`
- Livewire `PaymentIndex`
- optional Livewire `PaymentShow`
- `/payments` route replacement
- payment filters, summary cards, list/table/mobile cards
- payment detail page
- dashboard payment shortcut gated by `payment.view`

## Known Contract Gaps

- No Client payment list endpoint
- No Client payment detail endpoint
- No Client payment summary endpoint
- Existing shipment endpoint does not expose complete payment fields
- Existing shipment endpoint is sender-scoped only
- No confirmed Core normalization for order header payment status versus branch balance payment status
- No confirmed amount semantics for amount, paid amount, and balance amount across payment types
- No confirmed invoice/receipt customer-safe exposure contract
