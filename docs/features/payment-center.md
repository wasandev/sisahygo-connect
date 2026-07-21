# Payment Center Foundation

Payment Center แสดงข้อมูลการชำระเงินจาก Sisahygo Core ผ่าน Core Client Payment API เท่านั้น Connect ไม่เชื่อมต่อฐานข้อมูล Core โดยตรง และไม่คำนวณยอดทางบัญชีเอง

## Scope

รองรับการดูรายการและรายละเอียด:

- `F` วางบิลต้นทาง ผู้ชำระคือผู้ส่ง public identifier รูปแบบ `AR-P-{id}`
- `L` วางบิลปลายทาง ผู้ชำระคือผู้รับ public identifier รูปแบบ `AR-P-{id}`
- `E` เก็บเงินปลายทาง ผู้ชำระคือผู้รับ public identifier รูปแบบ `BR-{id}`

ไม่แสดงและไม่รับตัวกรอง `H` เงินสดต้นทาง หรือ `T` เงินโอนต้นทาง ใน Payment Center foundation

## Core API

Connect เรียกผ่าน gateway เดิม:

```text
Connect Payment Livewire
        ↓
Connect Core API Gateway
        ↓
Sisahygo Core Client Payment API
        ↓
F/L: ar_balances doctype=P
E: branch_balances
```

Endpoints:

- `GET /api/v1/client/payments`
- `GET /api/v1/client/payments/{payment_identifier}`

ตัวกรองที่ส่งได้:

- `from_date`
- `to_date`
- `payment_status`
- `payment_type`
- `order_header_no`
- `client_reference_no`
- `page`
- `per_page`

Connect ไม่ส่ง customer id, sender id หรือ receiver id เป็น filter เพราะ Core เป็น source of truth ของ authorization และ visibility

## Summary

Summary มาจาก Core ตาม filtered query:

- `record_count` จำนวนรายการ
- `total_amount` มูลค่ารวม
- `paid_record_count` จำนวนรายการที่ชำระแล้ว
- `outstanding_record_count` จำนวนรายการค้างชำระ

`paid_record_count` และ `outstanding_record_count` เป็นจำนวนรายการ ไม่ใช่ยอดเงิน Connect ไม่ derive paid/outstanding amount totals เอง

## Nullable Amounts

จำนวนเงินจาก Core ถูกเก็บเป็น decimal string และ format เพื่อแสดงผลเท่านั้น ไม่มีการ cast เป็น float

ถ้า Core ไม่ส่ง `paid_amount`, `outstanding_amount`, `discount_amount` หรือ `tax_amount` Connect แสดง `—` และไม่คำนวณแทน เช่น ไม่ใช้ `total - paid` และไม่ตีความ receipt ว่าเป็นการชำระครบหรือ partial allocation

## Error Handling

Payment Center ใช้ transport, authentication, timeout, retry, logging และ exception mapping ของ Sisahygo integration เดิม

- `401` แสดงข้อความตั้งค่า API โดยไม่เปิดเผย credential
- `403` แสดงว่า Client Account ไม่มีสิทธิ์
- `404` detail แสดง not found แบบปลอดภัย
- `422` แสดง validation message
- `429` แสดง temporary rate-limit message
- `5xx` และ network failure แสดง service unavailable/retryable state

API failure ไม่ถูกแปลงเป็น empty state

## Current Limitations

Sprint 5A เป็น foundation สำหรับดูข้อมูลเท่านั้น ยังไม่รองรับ online payment, payment submission, receipt creation, invoice creation หรือ partial-payment allocation และยังไม่สร้าง shipment/order link จาก Payment Detail จนกว่าจะมี safe public route/reference ที่ยืนยันแล้ว
