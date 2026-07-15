# Technical Debt

งานที่เลื่อนไปทำก่อนหรือระหว่าง sprints ถัดไป:

- เพิ่มประสบการณ์ account switcher ที่สมบูรณ์กว่าหน้า account selection ขั้นต่ำ
- ตัดสินใจว่า profile UI ควรย้ายจาก Breeze components ไปใช้ Sisahygo Connect components ทั้งหมดหรือไม่
- เพิ่ม model factories สำหรับ Client Account domain models เพื่อลด test duplication
- เพิ่ม HTTP-level shipment/payment endpoint tests เมื่อ endpoints เหล่านั้นมีอยู่จริง
- ขยายความครอบคลุมภาษาไทยของ validation messages สำหรับ auth/profile ทั้งหมด
- เพิ่ม CI automation สำหรับ build, migration และ test verification
- เพิ่ม API client contract tests เมื่อ Sprint 1.5 กำหนด Sisahygo API boundary แล้ว

แก้ไขแล้วใน hardening baseline:

- ยกเลิก silent first-account selection สำหรับผู้ใช้ multi-account
- ยกเลิก conditional customer foreign key behavior จาก Client Account customer migration
- ลบ Laravel starter welcome และ preview views ที่ไม่ได้ใช้งาน
- รวม payment type grouping เข้าใน `PaymentType`

แก้ไขแล้วใน Sprint 1.5 foundation:

- เพิ่ม Sisahygo API configuration, encrypted per-Client-Account credential storage, integration context, HTTP client foundation, fixtures และ contract-style tests
- บันทึก receiver Core API compatibility gap ไว้ในเอกสาร