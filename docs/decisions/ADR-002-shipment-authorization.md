# ADR-002: Shipment Authorization

## สถานะ

Approved

## บริบท

Client Account ต้องไม่เห็น shipments ที่ไม่ได้เป็นของตัวเอง

## การตัดสินใจ

ใช้ `AuthorizedOrderQuery` เพื่อ derive shipment visibility จาก authorized sender และ receiver customer links ห้ามเปิดเผย customer, product หรือ shipment master data แบบ global

## ผลกระทบ

Livewire, controllers, APIs, reports และ services สามารถใช้ data isolation logic ชุดเดียวกันซ้ำได้