# API

Sisahygo Connect เชื่อมต่อกับ Sisahygo ผ่าน Sisahygo API เท่านั้น

กฎของขอบเขตการเชื่อมต่อ:

- ห้าม query ฐานข้อมูล Production ของ Sisahygo core โดยตรง
- ให้ถือว่า Sisahygo customer IDs เป็น external identifiers
- API client services ในอนาคตต้องทำงานภายใต้ Client Account context ปัจจุบันเสมอ
- API ในอนาคตต้องเรียกใช้ domain services, policies และ authorized query objects ชุดเดียวกับ Livewire และ web controllers
- API handlers ห้าม query ตารางธุรกรรมของ Sisahygo โดยตรง

Sprint 1.5 สร้างเฉพาะ foundation สำหรับการเชื่อมต่อ API ที่นำกลับมาใช้ซ้ำได้ ส่วน business API modules และ UI workflows ยังอยู่นอก scope

## Sprint 1.5 Foundation

Sisahygo integration foundation ใช้ `app/Integrations/Sisahygo` สำหรับ API transport, exceptions, DTOs, endpoint classes, logging และ context objects

API credentials ถูกเข้ารหัสแยกตาม Client Account และ environment credentials เหล่านี้ใช้เพื่อ authentication เท่านั้น ไม่ใช่ customer identity ขอบเขตของผู้ส่งสินค้าและผู้รับสินค้ามาจาก active `client_account_customers` links

Receiver-only integration ยังมี Core API compatibility dependency ที่ต้องยืนยันก่อน โดยเฉพาะ authorization จาก `order_headers.customer_rec_id` และ receiver payment rules