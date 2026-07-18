# Customer Dashboard

Customer Dashboard คือหน้าหลักของ Sisahygo Connect สำหรับสมาชิก Client Account ที่เลือกอยู่ใน session ปัจจุบัน หน้านี้ช่วยให้เห็นภาพรวมรายการขนส่งล่าสุด รายการที่ควรตรวจสอบ และทางลัดไปยังงานหลัก โดยไม่แสดงข้อมูล credential หรือรายละเอียด API client ใด ๆ

## แหล่งข้อมูล

Dashboard ใช้ข้อมูลจาก Sisahygo Core ผ่าน Client API เดิมเท่านั้น โดยเรียก `GET /api/v1/client/shipments` ผ่าน service ของ Connect ที่มีอยู่แล้ว:

- `App\Application\Shipment\ShipmentQueryService`
- `App\Application\History\ListOrderHistory`

ไม่มีการ query ฐานข้อมูลของ Sisahygo Core โดยตรง และไม่มีการเพิ่ม endpoint ใหม่ใน Sprint นี้

## Client Account และสิทธิ์

ทุกการโหลดข้อมูลต้องอยู่ภายใต้ Client Account ที่เลือกอยู่เท่านั้น Livewire component resolve account ด้วย `CurrentClientAccountResolver` เมื่อไม่มี binding จาก middleware ระหว่าง hydration แล้ว rebind `ClientAccount` กลับเข้า container

Dashboard ใช้ capability `shipment.view` สำหรับข้อมูลที่มาจาก shipment list ส่วนทางลัดสร้างรายการส่งสินค้าแสดงเป็นปุ่มใช้งานได้เฉพาะเมื่อผู้ใช้มี `order.create`; หากไม่มีสิทธิ์ ปุ่มจะแสดงเป็นสถานะปิดใช้งานและไม่ bypass authorization ของหน้า Order Checking

## นิยาม metric

Metric ทั้งหมดใช้ `meta.total` จาก Core API เท่านั้น ไม่คำนวณ total จากข้อมูลบางหน้า

- รายการวันนี้: `from_date=today`, `to_date=today`, `per_page=1`
- กำลังดำเนินการ: แสดงสถานะยังคำนวณไม่ได้ เพราะ Core API ปัจจุบันยังไม่มี summary endpoint หรือ multi-status filter ที่ใช้คำนวณได้อย่างถูกต้อง
- สำเร็จใน 30 วัน: `order_status=completed`, ช่วง 30 วันล่าสุด, `per_page=1`
- รายการที่ควรติดตาม: `order_status=problem`, ช่วง 30 วันล่าสุด, `per_page=5` เพื่อใช้ทั้ง `meta.total` และรายการแสดงผล

Initial dashboard load ใช้ 4 Core API requests:

1. รายการล่าสุด 30 วันล่าสุด `per_page=5`
2. รายการวันนี้ `per_page=1`
3. รายการ completed 30 วันล่าสุด `per_page=1`
4. รายการ problem 30 วันล่าสุด `per_page=5`

## รายการล่าสุด

รายการล่าสุดโหลดแบบจำกัดจำนวน 5 รายการจาก shipment list ไม่ดึงประวัติทั้งหมด แต่ละรายการลิงก์ไปที่ `/shipments/{trackingIdentifier}` โดยใช้ `tracking_no` ที่ Core ส่งกลับมา

## รายการที่ควรตรวจสอบ

ส่วนนี้แสดงรายการสถานะ `problem` ล่าสุดในช่วง 30 วัน หากไม่มีข้อมูลจะแสดง empty state ที่เป็นกลาง ไม่อนุมานความล่าช้าหรือ SLA เพราะ Sprint นี้ยังไม่มี business rule และข้อมูลวันที่สำหรับการตัดสินดังกล่าว

## ผู้รับและสินค้าล่าสุด

ผู้รับล่าสุดและสินค้าล่าสุด reuse logic จาก History service โดย derive จากรายการล่าสุดที่โหลดอยู่เท่านั้น:

- ไม่ใช่ receiver master data ทั้งหมด
- ไม่ยิง detail API เพิ่ม
- ไม่เกิด N+1 request
- จำกัดจำนวนรายการแสดงผล

หาก Core ไม่ส่ง item data มากับ shipment list ส่วนสินค้าล่าสุดจะแสดง empty state แทนการอ้างว่าข้อมูลครบถ้วน

## Refresh และ loading

Dashboard ไม่มี polling อัตโนมัติใน Sprint นี้ ผู้ใช้กดโหลดใหม่เองได้ และปุ่ม refresh ถูก disable ระหว่าง request ด้วย Livewire loading state การโหลดข้อมูลเกิดใน `mount()` และ action `refresh()` เท่านั้น ไม่เรียก Core API จาก `render()` หรือ Blade loop

## Error behavior

ข้อความ error เป็นภาษาไทยและปลอดภัยสำหรับผู้ใช้ ไม่แสดง exception class, stack trace, API key, credential ID, encrypted credential, URL ที่มี secret หรือ raw API response

สถานะที่รองรับ:

- ไม่มี Client Account หรือ credential พร้อมใช้งาน
- ไม่มี capability
- authentication/authorization จาก Sisahygo API
- connection failure
- rate limit
- server failure
- malformed response
- unexpected failure

Sprint นี้ใช้ all-or-nothing dashboard load เพื่อหลีกเลี่ยงข้อมูลผสมที่อาจสับสน หากต้องการ section-level partial success ควรทำหลังมี product decision ชัดเจน

## ข้อจำกัดที่ทราบ

- Core API ยังไม่มี dashboard summary endpoint
- Core API ยังไม่รองรับ multi-status filter สำหรับนับ “กำลังดำเนินการ” อย่างแม่นยำ
- ไม่มี filtered deep links จาก summary card ไป History/Shipment เพราะปลายทางยังไม่ initialize filter จาก query string
- ไม่มี realtime polling, WebSocket notification, analytics chart, SLA warning หรือ configurable widgets ใน Sprint นี้

## Deferred enhancements

- Dedicated Core dashboard summary endpoint
- Multi-status count support
- Filtered deep links
- Section-level partial success
- Realtime notification หรือ polling ตาม product requirement
- Delivery SLA warning เมื่อมี business rule ที่ยืนยันแล้ว
- Dashboard personalization และ configurable cards
