# ADR-008: API Versioning and DTO Boundary

## สถานะ

Approved

## บริบท

ถ้าใช้ raw external API arrays โดยตรง Livewire และ domain code จะผูกกับ field names ของ Core API มากเกินไป

## การตัดสินใจ

วาง versioned endpoint classes, DTOs และ mappers ไว้ใต้ `app/Integrations/Sisahygo/V1` สร้าง DTOs เฉพาะ API contracts ที่รู้และทดสอบแล้วใน integration foundation

ใช้ domain enums เช่น `PaymentType` และ `PaymentStatus` ระหว่าง mapping

## ผลกระทบ

ถ้า Core API version ในอนาคตเปลี่ยน response shapes สามารถ isolate การเปลี่ยนแปลงไว้ใน versioned mappers ได้ โดยไม่ให้หลุดไปถึง UI หรือ domain authorization code