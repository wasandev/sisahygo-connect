# Payments

Payment visibility แยกจาก shipment visibility การมีสิทธิ์ดู shipment ไม่ได้แปลว่ามีสิทธิ์ดูรายละเอียด Payment

## Payment Center Foundation

Sprint 5A ใช้ Sisahygo Core Client Payment API เป็น source of truth เท่านั้น Connect ไม่ query ฐานข้อมูล Core โดยตรง และไม่ reconstruct financial calculations เอง

Payment Center foundation แสดงเฉพาะประเภทที่ Core Payment API ยืนยันแล้ว:

- `F` = วางบิลต้นทาง ผู้ชำระคือผู้ส่ง public identifier `AR-P-{id}`
- `L` = วางบิลปลายทาง ผู้ชำระคือผู้รับ public identifier `AR-P-{id}`
- `E` = เก็บเงินปลายทาง ผู้ชำระคือผู้รับ public identifier `BR-{id}`

ไม่แสดง `H` เงินสดต้นทาง และ `T` เงินโอนต้นทาง ใน Payment Center foundation และไม่รับเป็นตัวกรองหน้าจอ

Summary ที่แสดงมาจาก Core ตาม filter ปัจจุบัน:

- `record_count` จำนวนรายการ
- `total_amount` มูลค่ารวม
- `paid_record_count` จำนวนรายการชำระแล้ว
- `outstanding_record_count` จำนวนรายการค้างชำระ

`paid_record_count` และ `outstanding_record_count` เป็นจำนวนรายการ ไม่ใช่ยอดเงิน

## Visibility

Client Accounts ที่เชื่อมในฐานะผู้ส่งสินค้าจะดูรายละเอียด Payment ได้เฉพาะเมื่อ Core API อนุญาตตาม role ผู้ส่งและรายการประเภท `F`

Client Accounts ที่เชื่อมในฐานะผู้รับสินค้าจะดูรายละเอียด Payment ได้เฉพาะเมื่อ Core API อนุญาตตาม role ผู้รับและรายการประเภท `E` หรือ `L`

Connect ไม่ส่ง customer id, sender id หรือ receiver id เป็น filter เพื่อเปลี่ยน visibility scope

Payment screens ต้องใช้ `payment_type`, `payer_role`, `payment_status` และ amount fields จาก Sisahygo API เท่านั้น ถ้า amount บางค่าเป็น `null` ให้แสดง `—` และไม่คำนวณแทน
