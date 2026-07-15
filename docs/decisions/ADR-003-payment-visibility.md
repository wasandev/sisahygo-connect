# ADR-003: Payment Visibility

## สถานะ

Approved

## บริบท

Payment visibility ไม่ใช่เรื่องเดียวกับ shipment visibility Sprint 1.0.1 ระบุ Sisahygo payment types และ payment status values ที่เป็น authoritative source แล้ว

## การตัดสินใจ

ใช้ `AuthorizedPaymentQuery` และ `PaymentPolicy` ฝั่งผู้ส่งสินค้าจะเห็น Payment ได้เมื่อมี authorized sender customer link, `can_view_payment`, payment capability และ `paymenttype IN ('H', 'T', 'F')`

ฝั่งผู้รับสินค้าจะเห็น Payment ได้เมื่อมี authorized receiver customer link, `can_view_payment`, payment capability และ `paymenttype IN ('E', 'L')`

ใช้ Sisahygo API field `payment_status` เท่านั้น:

- `0` = Outstanding / ค้างชำระ
- `1` = Paid / ชำระแล้ว

## ผลกระทบ

Payment access สามารถพัฒนาแยกจาก shipment access ได้ โดยยังป้องกัน horizontal privilege escalation หน้าจอ Payment ในอนาคตต้องแสดงความหมายจาก `paymenttype` และ `payment_status` ไม่ใช่สถานะที่สร้างขึ้นเอง