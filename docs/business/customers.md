# Customers

Client Account customer links ใช้เชื่อมบัญชีใน Sisahygo Connect กับ external Sisahygo customer identifiers

`customer_id` เป็น external Sisahygo identifier จาก API boundary ไม่ใช่ foreign key ไปยัง local `customers` table

หนึ่ง link สามารถให้สิทธิ์แยกกันได้ดังนี้:

- ส่งสินค้าได้ด้วย `can_send`
- รับสินค้าได้ด้วย `can_receive`
- ดูข้อมูล Payment ได้ด้วย `can_view_payment`

Customer links ต้องอยู่ในสถานะ active ก่อน จึงจะใช้ authorize shipment visibility หรือ payment visibility ได้