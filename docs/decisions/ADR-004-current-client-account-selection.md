# ADR-004: Current Client Account Selection

## สถานะ

Approved

## บริบท

Sisahygo Connect รองรับผู้ใช้ที่อยู่ได้มากกว่าหนึ่ง Client Account การเลือกบัญชีแรกจากชื่อ, ID หรือวันที่สร้าง อาจทำให้เข้าสู่ tenant context ผิดบัญชี และไม่เหมาะสำหรับ SaaS

## การตัดสินใจ

ใช้ current Client Account context แบบ explicit เก็บไว้ใน session key `selected_client_account_id`

ผู้ใช้ที่มี active Client Account เพียงบัญชีเดียวสามารถถูกเลือกให้อัตโนมัติได้ แต่ต้อง validate account และ membership เสมอ ผู้ใช้ที่มี active Client Accounts หลายบัญชีต้องเลือกบัญชีบนหน้า account selection ส่วนผู้ใช้ที่ไม่มี active Client Account จะได้รับ unavailable response ที่ปลอดภัย

ทุก tenant-dependent request ต้อง validate ว่าบัญชีที่เลือกมีอยู่จริง active และมี active membership ของ authenticated user

## ผลกระทบ

Tenant pages มี account context ที่เชื่อถือได้ก่อนเริ่ม Sisahygo API integration ในอนาคต ส่วน profile, logout, authentication, email verification และ account selection ยังคงเป็น user-level routes และไม่ต้องมี selected tenant