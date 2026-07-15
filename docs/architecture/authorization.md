# Authorization

Authorization ถูกจัดเป็นหลายชั้น:

1. Authentication
2. Current Client Account resolution
3. Active user membership ใน Client Account ที่เลือก
4. User role check ในกรณีที่เป็นงานจัดการบัญชี
5. Client Account capability check
6. Authorized query object สำหรับแยกข้อมูลธุรกรรมตามสิทธิ์

Capabilities ใช้ namespace strings เช่น `shipment.view`, `payment.view` และ `users.manage`

Routes ที่ขึ้นกับ tenant ใช้ `client.account` middleware ส่วน authentication, logout, email verification, profile และ account selection เป็น user-level routes และตั้งใจให้อยู่นอก middleware นี้

Shipment queries ต้องเริ่มจาก `AuthorizedOrderQuery` เสมอ Payment queries ต้องเริ่มจาก `AuthorizedPaymentQuery` การกรองใน Blade ไม่ใช่ security boundary

Payment authorization แยกจาก shipment authorization รายละเอียด Payment ฝั่งผู้ส่งสินค้าดูได้เฉพาะ `H`, `T` และ `F` รายละเอียด Payment ฝั่งผู้รับสินค้าดูได้เฉพาะ `E` และ `L`