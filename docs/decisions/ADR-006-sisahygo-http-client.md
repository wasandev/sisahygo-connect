# ADR-006: Sisahygo HTTP Client

## สถานะ

Approved

## บริบท

Sisahygo Connect ต้องมี API foundation ที่ reuse ได้สำหรับ external Sisahygo Client API โดยไม่เข้าฐานข้อมูลโดยตรง

## การตัดสินใจ

ใช้ Laravel HTTP Client ภายใต้ `app/Integrations/Sisahygo/V1/SisahygoApiClient` Requests ใช้ trusted configured base URLs, `X-Api-Key`, JSON headers, correlation IDs, configured timeouts, conservative GET retry และ safe exception mapping

POST requests จะไม่ถูก retry แบบ blind retry ใน Sprint 1.5

## ผลกระทบ

Modules ในอนาคตสามารถใช้ transport boundary เดียวกันได้ และยังป้องกันไม่ให้ secrets หลุดไปใน logs หรือ UI state