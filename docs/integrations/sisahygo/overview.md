# Sisahygo API Integration Overview

Sisahygo Connect เชื่อมต่อกับ external Sisahygo Client API ผ่าน API-only boundary เท่านั้น Application ห้าม query Sisahygo production database โดยตรง

Integration code อยู่ใต้ `app/Integrations/Sisahygo` ส่วน domain authorization ยังคงอยู่ใน `app/Domain`

External request ทุกครั้งต้องสร้างจาก integration context ที่ validate แล้ว และมีข้อมูลต่อไปนี้:

- authenticated user identity
- selected active Client Account identity
- required Client Account capability
- active encrypted API credential สำหรับ environment ที่เลือก
- authorized sender customer IDs
- authorized receiver customer IDs
- correlation ID

API credentials ใช้ authenticate Sisahygo Connect กับ Core API เท่านั้น ไม่ใช่ sender หรือ receiver identities Sender และ receiver identities ต้องมาจาก Client Account customer links เท่านั้น