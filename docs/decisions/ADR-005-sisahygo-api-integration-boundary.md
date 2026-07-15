# ADR-005: Sisahygo API Integration Boundary

## สถานะ

Approved

## บริบท

Sisahygo Connect จะใช้ Sisahygo customer และ transactional data ผ่าน Sisahygo API และต้องไม่พึ่ง direct production database access ไปยัง core Sisahygo database

## การตัดสินใจ

ถือว่า `client_account_customers.customer_id` เป็น external Sisahygo customer identifier ห้ามสร้าง foreign key จาก `client_account_customers.customer_id` ไปยัง local `customers` table ให้คง uniqueness และ query indexes บน external identifier นี้ไว้

Shipment, Payment และ customer integration ในอนาคตต้องเข้าผ่าน API client services และยังต้องใช้ Client Account authorization รวมถึง authorized query boundaries ต่อไป

## ผลกระทบ

Migrations จะทำงานสม่ำเสมอในทุก environment ไม่ว่าจะมี local `customers` table หรือไม่ API integration สามารถพัฒนาต่อได้โดยไม่ผูก schema ของ Sisahygo Connect เข้ากับ schema ของ core Sisahygo production database