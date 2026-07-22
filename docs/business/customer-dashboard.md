# Customer Dashboard

Customer Dashboard คือหน้าหลักของ Sisahygo Connect สำหรับสมาชิก Client Account ที่เลือกอยู่ใน session ปัจจุบัน หน้านี้ช่วยให้เห็นภาพรวมรายการขนส่งล่าสุด รายการที่ควรตรวจสอบ และทางลัดไปยังงานหลัก โดยไม่แสดงข้อมูล credential หรือรายละเอียด API client ใด ๆ

## แหล่งข้อมูล

Dashboard ใช้ข้อมูลจาก Sisahygo Core ผ่าน Client API เดิมเท่านั้น โดยเรียก Client API ผ่าน service ของ Connect ที่มีอยู่แล้ว:

- `App\Application\Shipment\ShipmentQueryService`
- `App\Application\History\ListOrderHistory`
- `App\Application\Payment\PaymentQueryService`
- `App\Application\Dashboard\DashboardPaymentOverviewService` สำหรับ cache boundary ของ payment widget

ไม่มีการ query ฐานข้อมูลของ Sisahygo Core โดยตรง และไม่มีการเพิ่ม endpoint ใหม่ใน Sprint นี้

## Client Account และสิทธิ์

ทุกการโหลดข้อมูลต้องอยู่ภายใต้ Client Account ที่เลือกอยู่เท่านั้น Livewire component resolve account ด้วย `CurrentClientAccountResolver` เมื่อไม่มี binding จาก middleware ระหว่าง hydration แล้ว rebind `ClientAccount` กลับเข้า container

Dashboard ใช้ capability `shipment.view` สำหรับข้อมูลที่มาจาก shipment list และใช้ `payment.view` เฉพาะส่วน Payment Overview หาก payment section โหลดไม่ได้จะไม่ทำให้ shipment sections ล้ม ส่วนทางลัดสร้างรายการส่งสินค้าแสดงเป็นปุ่มใช้งานได้เฉพาะเมื่อผู้ใช้มี `order.create`; หากไม่มีสิทธิ์ ปุ่มจะแสดงเป็นสถานะปิดใช้งานและไม่ bypass authorization ของหน้า Order Checking

## นิยาม metric

Metric ทั้งหมดใช้ `meta.total` จาก Core API เท่านั้น ไม่คำนวณ total จากข้อมูลบางหน้า

- รายการวันนี้: `from_date=today`, `to_date=today`, `per_page=1`
- กำลังดำเนินการ: แสดงสถานะยังคำนวณไม่ได้ เพราะ Core API ปัจจุบันยังไม่มี summary endpoint หรือ multi-status filter ที่ใช้คำนวณได้อย่างถูกต้อง
- สำเร็จใน 30 วัน: `order_status=completed`, ช่วง 30 วันล่าสุด, `per_page=1`
- รายการที่ควรติดตาม: `order_status=problem`, ช่วง 30 วันล่าสุด, `per_page=5` เพื่อใช้ทั้ง `meta.total` และรายการแสดงผล

Initial dashboard load ใช้ได้สูงสุด 5 Core API requests เมื่อ payment section พร้อมใช้งานและ payment cache ยังว่าง:

1. รายการล่าสุด 30 วันล่าสุด `per_page=5`
2. รายการวันนี้ `per_page=1`
3. รายการ completed 30 วันล่าสุด `per_page=1`
4. รายการ problem 30 วันล่าสุด `per_page=5`
5. Payment overview `GET /api/v1/client/payments?page=1&per_page=5` เพื่อใช้ทั้ง Core summary และ recent payments

เมื่อ Dashboard payment overview มี cache hit ภายใน TTL จะไม่เรียก payment endpoint ซ้ำในรอบโหลดนั้น และยังคงเรียก shipment endpoints ตามรายการข้างต้น

## Payment Overview

Sprint 5B เพิ่ม payment widgets บน Dashboard:

- มูลค่ารวม
- รายการค้างชำระ
- รายการชำระแล้ว
- จำนวนรายการ

ค่าทั้งหมดมาจาก Core Payment API summary ไม่คำนวณจาก recent records และไม่ reconstruct paid/outstanding totals เอง Recent payments จำกัด 5 รายการจาก Core list endpoint และแสดง order number, payment type, total amount, status, billing date และ detail link

Dashboard มี filtered links ไป Payment Center:

- `/payments`
- `/payments?payment_status=outstanding`
- `/payments?payment_status=paid`

Payment API failure ถูก isolate ใน widget และแสดงข้อความ retry ได้ โดยไม่แสดง zero summary และไม่ทำให้ส่วน shipment ของ Dashboard หาย

### Dashboard Payment Cache

Sprint 5C เพิ่ม cache แบบสั้นเฉพาะ Dashboard Payment Overview เท่านั้น ค่า default คือ 60 วินาที ปรับได้ด้วย `SISAHYGO_DASHBOARD_PAYMENT_CACHE_TTL` และปิดได้ด้วย `SISAHYGO_DASHBOARD_PAYMENT_CACHE_ENABLED=false` ผ่าน `config/sisahygo.php`

Cache key ใช้ข้อมูลไม่ลับ ได้แก่ environment, locale, local Client Account id และ query shape คงที่ `page=1&per_page=5` ไม่ใช้ API key, API key hash, Core customer id, sender id หรือ receiver id payload ที่ cache มีเฉพาะ mapped summary, recent payments ไม่เกิน 5 รายการ และ pagination meta; ไม่ cache exception, unavailable state, raw HTTP response หรือ header

Manual Dashboard refresh bypasses cache ของ Client Account ปัจจุบันแล้ว fetch ใหม่ ถ้า refresh ล้มเหลวแต่ยังมี successful cache ในรอบ TTL เดิม widget จะแสดงข้อมูลนั้นพร้อมข้อความ “ข้อมูลล่าสุดที่บันทึกไว้” แทนการแสดงเป็นข้อมูลสด ถ้า cache store อ่าน/เขียน/ลบไม่ได้ ระบบ fallback ไปเรียก Core ตรงและ payment widget ยัง isolate error เหมือนเดิม

ไม่มี stale cache layer แยกต่างหาก TTL ยังเป็น freshness mechanism หลัก การเปลี่ยนแปลงใน Core อาจใช้เวลาสูงสุดตาม TTL ก่อนแสดงบน Dashboard เว้นแต่ผู้ใช้กดโหลดใหม่

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

Payment widget มี skeleton เฉพาะส่วน summary cards และ recent rows ระหว่าง refresh โดยไม่แสดงค่าจำลองเช่น 0.00 และมี `aria-busy`/loading text สำหรับ assistive technology

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

Shipment dashboard ยังคง all-or-nothing สำหรับข้อมูล shipment แต่ Payment Overview เป็น section-level isolated failure เพื่อไม่ให้ payment API issue ทำให้ Dashboard หลักใช้งานไม่ได้

## ข้อจำกัดที่ทราบ

- Core API ยังไม่มี dashboard summary endpoint
- Core API ยังไม่รองรับ multi-status filter สำหรับนับ “กำลังดำเนินการ” อย่างแม่นยำ
- Shipment summary ยังไม่มี filtered deep links ไป History/Shipment เพราะปลายทางยังไม่ initialize filter จาก query string
- Payment Overview ยังอยู่ใน Dashboard Livewire component เดิม ไม่แยกเป็น lazy child component ใน Sprint 5C เพราะ architecture ปัจจุบัน resolve selected account และ isolate payment error ได้แล้ว cache ลด repeat payment call โดยไม่ serialize credential ลง public state
- ไม่มี realtime polling, WebSocket notification, analytics chart, SLA warning หรือ configurable widgets ใน Sprint นี้

## Deferred enhancements

- Dedicated Core dashboard summary endpoint
- Multi-status count support
- Filtered deep links
- Section-level partial success
- Realtime notification หรือ polling ตาม product requirement
- Delivery SLA warning เมื่อมี business rule ที่ยืนยันแล้ว
- Dashboard personalization และ configurable cards
