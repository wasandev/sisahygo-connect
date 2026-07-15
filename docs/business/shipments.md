# Shipments

Shipment visibility ต้อง derive จาก authorized orders เท่านั้น

Sender visibility เริ่มจาก `AuthorizedOrderQuery` และอนุญาตเฉพาะ orders ที่ `order_headers.customer_id` เป็นของ active customer link ที่มี `can_send = true`

Receiver visibility เริ่มจาก `AuthorizedOrderQuery` และอนุญาตเฉพาะ orders ที่ `order_headers.customer_rec_id` เป็นของ active customer link ที่มี `can_receive = true`

ภายใน orders ที่ authorize แล้ว modules ในอนาคตอาจแสดง sender, receiver, shipment items, shipment status และ shipment history ที่เกี่ยวข้องได้ แต่ต้องไม่เปิดเผย customer master data ที่ไม่เกี่ยวข้อง หรือ products ที่ไม่เกี่ยวข้องกับ authorized shipments