# Current Client Account Context

หน้าที่ขึ้นกับ tenant ต้องมี current Client Account context อย่างชัดเจน

Session key: `selected_client_account_id`

กฎการ resolve:

- ผู้ใช้ที่ไม่มี active Client Accounts จะได้รับ unavailable response ที่ปลอดภัย
- ผู้ใช้ที่มี active Client Account เพียงบัญชีเดียวจะถูกเลือกให้อัตโนมัติ หลังจาก validate active membership แล้ว
- ผู้ใช้ที่มี active Client Accounts หลายบัญชีต้องเลือกบัญชีเองอย่างชัดเจน
- ห้ามเชื่อ session value โดยไม่ validate account status และ active membership อีกครั้ง
- Session selection ที่ไม่ถูกต้องหรือถูก tamper ต้องถูกล้างและ redirect ไปหน้า account selection

`client.account` middleware ใช้เฉพาะ routes ที่ขึ้นกับ tenant เท่านั้น ส่วน user-level routes เช่น profile, logout, account selection และ authentication อยู่นอก middleware นี้