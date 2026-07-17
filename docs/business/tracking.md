# Tracking

Sprint 2B เปิดใช้ production Tracking lookup สำหรับ Sisahygo Connect แล้ว

## Route

`/tracking` เป็น Livewire lookup form ที่รับ tracking identifier แล้ว redirect ไปยัง `/shipments/{trackingIdentifier}`

## Data Source

Tracking detail ใช้ `GET /api/v1/client/shipments/{tracking_no}` ผ่าน `ShipmentQueryService` และ `ShipmentsEndpoint` เท่านั้น ไม่เรียกฐานข้อมูล Core โดยตรง

## Scope และ Security

- ต้องมี Client Account ที่ active และ membership active
- ต้องมี credential active ที่ environment ตรงกับ `SISAHYGO_API_ENVIRONMENT`
- ต้องมี capability `shipment.view`
- response และ error ที่แสดงใน UI ต้องไม่เปิดเผย API key, encrypted credential, raw response body หรือ sensitive payload

## Identifier

Core endpoint ปัจจุบันตีความ `{tracking_no}` เป็น `order_headers.id` ไม่ใช่ `order_header_no`

## Deferred

- public tracking link
- multi-reference lookup
- shipment event subscription
- receiver-side lookup rules นอกเหนือจาก Core API key scope ปัจจุบัน
