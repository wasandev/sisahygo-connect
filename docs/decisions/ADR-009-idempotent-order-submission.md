# ADR-009: Idempotent Order Submission

## สถานะ

Approved

## บริบท

Order Checking และ Bulk Order Checking ในอนาคตอาจส่ง POST requests ที่ต้องป้องกัน duplicate submissions และ unknown-result retries

## การตัดสินใจ

ยังไม่สร้าง final idempotency schema ใน Sprint 1.5 ให้บันทึก contract ไว้ก่อน และเลื่อนการออกแบบ storage ขั้นสุดท้ายจนกว่า Core API จะยืนยันการรองรับ `Idempotency-Key`, `client_reference_no`, `batch_reference_no`, timeout reconciliation และ unknown-result recovery

POST requests จะไม่ถูก retry อัตโนมัติใน Sprint 1.5

## ผลกระทบ

Foundation จะไม่เดาพฤติกรรมของ Core API เอง แต่ยังคงมี extension points สำหรับ safe future submissions