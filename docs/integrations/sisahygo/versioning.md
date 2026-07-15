# Sisahygo API Versioning

Version-specific integration code อยู่ใต้ `app/Integrations/Sisahygo/V1`

V1 รับผิดชอบ endpoint classes, request/response mapping, DTOs และ field-name translation Domain code และ Livewire components ต้องไม่พึ่ง raw API arrays หรือ external field names โดยตรง

ถ้า Core API version ในอนาคตเปลี่ยน response shapes ให้สร้าง versioned mappers ใหม่เพื่อ isolate การเปลี่ยนแปลง โดยไม่ต้อง rewrite domain authorization rules