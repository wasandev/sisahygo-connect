# ADR-007: API Credential Storage

## สถานะ

Approved

## บริบท

Global API Key เพียงตัวเดียวไม่เพียงพอสำหรับ Client Account isolation ที่พร้อมใช้แบบ SaaS, credential rotation, environment separation และ auditability

## การตัดสินใจ

เก็บ Sisahygo API credentials แบบเข้ารหัสแยกตาม Client Account และ environment ใน `sisahygo_api_credentials` เก็บ key fingerprint ไว้เพื่อระบุตัวตนอย่างปลอดภัย รองรับ active credentials, revoked credentials, rotation history, `last_used_at` และ creator tracking

API credentials ใช้ authenticate Sisahygo Connect เท่านั้น Sender และ receiver customer identity ต้องมาจาก Client Account customer links

## ผลกระทบ

Credential lifecycle สามารถพัฒนาต่อได้โดยไม่เปิดเผย keys ไปยัง Blade, Livewire, logs, exceptions หรือ browser responses