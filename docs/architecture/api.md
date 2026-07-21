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

## Dashboard Payment Cache

Dashboard Payment Overview มี cache boundary เฉพาะที่ `App\Application\Dashboard\DashboardPaymentOverviewService` เพื่อหลีกเลี่ยงการเรียก Core Payment API ซ้ำสำหรับ query คงที่ `page=1&per_page=5` ภายใน TTL สั้น ๆ ค่า default 60 วินาที และปิดได้ผ่าน config

Cache key ใช้ environment, locale, local Client Account id และ query shape version เท่านั้น ไม่ใช้ API key, credential payload, Core customer id หรือข้อมูลส่วนบุคคล Cached value เป็น mapped application array ที่ได้จาก `PaymentQueryService` แล้วเท่านั้น ไม่เก็บ raw HTTP response/header/body และไม่ cache exception หรือ unavailable state

Observability ใช้ structured debug logs สำหรับ cache hit/miss/bypass/fetch success/failure พร้อม duration, cache status, local Client Account id, environment และ result count โดยไม่ log API credentials หรือ payment identifiers Retry ยังเป็น responsibility ของ `SisahygoApiClient`; service layer ไม่มี retry loop ซ้อน
