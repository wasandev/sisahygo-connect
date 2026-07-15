# Payments

Payment visibility แยกจาก shipment visibility การมีสิทธิ์ดู shipment ไม่ได้แปลว่ามีสิทธิ์ดูรายละเอียด Payment

Client Accounts ที่เชื่อมในฐานะผู้ส่งสินค้าจะดูรายละเอียด Payment ได้เฉพาะเมื่อ:

- `order_headers.customer_id` เป็นของ active linked customer ที่มี `can_send = true`
- linked customer นั้นมี `can_view_payment = true`
- `paymenttype` เป็น `H`, `T` หรือ `F`

Client Accounts ที่เชื่อมในฐานะผู้รับสินค้าจะดูรายละเอียด Payment ได้เฉพาะเมื่อ:

- `order_headers.customer_rec_id` เป็นของ active linked customer ที่มี `can_receive = true`
- linked customer นั้นมี `can_view_payment = true`
- `paymenttype` เป็น `E` หรือ `L`

Payment screens ในอนาคตต้องใช้ทั้ง `paymenttype` และ `payment_status` จาก Sisahygo API