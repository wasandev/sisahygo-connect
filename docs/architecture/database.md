# Database

Client Account foundation tables:

- `client_accounts`
- `client_account_users`
- `client_account_customers`
- `client_account_capabilities`
- `client_account_activity_logs`

`client_account_customers.customer_id` เป็น external Sisahygo customer identifier ที่ใช้ผ่าน Sisahygo API โดยตั้งใจไม่ทำ foreign key ไปยัง local `customers` table

Constraints สำคัญ:

- `client_accounts.code` ต้อง unique
- `client_account_users` unique ตาม `client_account_id` และ `user_id`
- `client_account_customers` unique ตาม `client_account_id` และ `customer_id`
- `client_account_capabilities` unique ตาม `client_account_id` และ `capability`

Indexes รองรับ active account membership, customer authorization, payment visibility และ capability checks