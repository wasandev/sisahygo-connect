# Localization Conventions

ภาษาไทยเป็นภาษาหลักของ user interface

กฎการใช้งาน:

- User-facing strings ใน Blade และ Livewire ควรอยู่ใน Laravel language files
- ควรรักษา matching English keys ไว้เพื่อรองรับ locale อื่นในอนาคต
- ห้ามแปล route names, internal class names, database values, capability keys หรือ API field names
- Navigation keys อยู่ใต้ `navigation.*`
- Page copy อยู่ใต้ `page.*`
- Account selection copy อยู่ใต้ `account_selection.*`
- Client Account foundation copy อยู่ใต้ `client_account.*`
- Payment type และ status labels อยู่ใต้ `payment.*`