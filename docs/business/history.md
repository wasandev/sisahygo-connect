# ประวัติรายการ

Sprint 2C นิยาม History เป็นมุมมอง read-only ของรายการ order/shipment ที่มองเห็นได้ผ่าน Sisahygo Core Client API ภายใต้ Client Account ปัจจุบัน

Connect ไม่อ่านฐานข้อมูล Core โดยตรง ไม่สร้าง local history table และไม่สร้าง integration endpoint ใหม่แยกจาก Shipment API

## Data Source

History ใช้ endpoint เดียวกับ Shipment list:

- `GET /api/v1/client/shipments`

Application layer ใช้ `App\Application\History\ListOrderHistory` เป็น thin service ที่ delegate ไป `App\Application\Shipment\ShipmentQueryService`

## Default Range

ค่าเริ่มต้นคือ `30 วันล่าสุด`:

- `date_from` = วันนี้ - 29 วัน
- `date_to` = วันนี้

Date presets ที่ UI รองรับ:

- วันนี้
- 7 วันล่าสุด
- 30 วันล่าสุด
- เดือนนี้
- กำหนดเอง

การเปลี่ยน preset หรือ filter reset pagination ไปหน้า 1 ส่วน refresh จะคง filter ปัจจุบันไว้

## Supported Filters

History ส่งเฉพาะ filters ที่ Core `/shipments` รองรับจริง:

- `from_date`
- `to_date`
- `order_status`
- `tracking_no`
- `order_header_no`
- `page`
- `per_page`

Keyword เป็นตัวเลขจะ map เป็น `tracking_no` เพราะ Core ปัจจุบันตีความ `tracking_no` เป็น `order_headers.id`

Keyword ที่ไม่ใช่ตัวเลขจะ map เป็น `order_header_no`

## Unsupported Filters

Core contract ปัจจุบันยังไม่รองรับ:

- `client_reference_no` filtering
- receiver name search
- receiver customer ID filtering
- product filtering
- sorting direction
- completed-only history
- server-side date presets

UI จึงไม่โฆษณา filter เหล่านี้ และ Connect ไม่ส่งค่าเหล่านี้ไป Core

## Visibility And Isolation

ทุก history request ต้องผ่าน:

- authenticated user
- selected active Client Account
- active membership
- `shipment.view` capability
- active Sisahygo credential ที่ environment ตรงกับ config
- Core API-key sender-scoped visibility

Livewire component ใช้ `CurrentClientAccountResolver` fallback และ rebind `ClientAccount` เข้า container ระหว่าง action hydration เพื่อป้องกันการใช้ account ผิดหรือ empty model

## Recent Receivers

ส่วน “ผู้รับที่ใช้ล่าสุด” derive จาก records ที่ Core ส่งกลับมาในหน้าปัจจุบันเท่านั้น

หากมี receiver identifier ที่ stable จะใช้ identifier นั้น แต่ contract ปัจจุบันมักส่งเฉพาะชื่อผู้รับ จึง fallback เป็น normalized receiver name

ส่วนนี้ไม่ใช่ receiver master list และไม่ cache ข้าม Client Account

## Recent Products

ส่วน “สินค้าที่ใช้ล่าสุด” derive จาก `items` ที่ Core ส่งมากับ shipment list response ปัจจุบัน

Connect ไม่เรียก detail endpoint แบบ N+1 เพื่อ aggregate สินค้า

หาก Core ไม่ส่ง `items` ในอนาคต ส่วนนี้จะแสดง empty state แทน และควรพิจารณาเพิ่ม Core summary/history contract ก่อนทำ product analytics จริง

## Future Enhancements

ยังไม่ได้ทำใน Sprint 2C:

- ใช้ข้อมูลผู้รับเก่าสร้างรายการใหม่
- duplicate/reorder จาก order เก่า
- recent-product summary endpoint
- export history
- receiver-side shipment visibility
- advanced analytics
