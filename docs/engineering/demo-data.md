# Local Demo Data

Sisahygo Connect มี demo data สำหรับ local development ที่สร้างซ้ำได้อย่าง deterministic สำหรับ Client Account Foundation

เรียก demo seeder แบบ explicit ด้วยคำสั่ง:

```bash
./vendor/bin/sail artisan db:seed '--class=Database\Seeders\Development\ClientAccountDemoSeeder'
```

`DatabaseSeeder` หลักจะเรียก demo seeder นี้เฉพาะเมื่อ application environment เป็น `local` เท่านั้น

## Demo Password

Demo users ทุกคนใช้รหัสผ่าน local ที่ตั้งใจให้เป็นของปลอมชัดเจน:

`password`

ห้ามนำรหัสผ่านนี้ไปใช้กับ Production หรือ shared environments

## Demo Login Accounts

| Scenario | Email | Password |
| --- | --- | --- |
| Owner / single account | `owner@abc-demo.test` | `password` |
| Multiple Accounts | `multi@demo.test` | `password` |
| Sender | `sender@sender-demo.test` | `password` |
| Receiver | `receiver@receiver-demo.test` | `password` |
| Accounting | `accounting@abc-demo.test` | `password` |
| No Account | `noaccount@demo.test` | `password` |

ผู้ใช้เพิ่มเติมสำหรับทดสอบ authorization:

| Scenario | Email | Password |
| --- | --- | --- |
| Viewer / read-only access | `viewer@abc-demo.test` | `password` |

## Demo Accounts

| Code | Scenario |
| --- | --- |
| `SC-DEMO-SINGLE` | บัญชีเดียวสำหรับ auto-selection และพฤติกรรม sender-and-receiver |
| `SC-DEMO-SENDER` | Sender-only account |
| `SC-DEMO-RECEIVER` | Receiver-only account |
| `SC-DEMO-BOTH` | Sender-and-receiver account สำหรับ multi-account และ viewer authorization checks |
| `SC-DEMO-ACCOUNTING` | สิทธิ์ Payment/accounting โดยไม่มี order creation |

## Mock External Sisahygo Customer IDs

Demo links ใช้ mock external Sisahygo customer identifiers ดังนี้:

- `10001`
- `10002`
- `20001`
- `20002`

IDs เหล่านี้เป็น external references เท่านั้น โปรเจกต์ไม่ได้สร้าง local `customers` table และไม่ต้องมี foreign key ไปยัง table ดังกล่าว

## Local Reset

สำหรับ reset local database ทั้งหมด:

```bash
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan db:seed '--class=Database\Seeders\Development\ClientAccountDemoSeeder'
```

คำสั่งนี้ใช้สำหรับ local development เท่านั้น

## Why Transactional Sisahygo Data Is Not Seeded

Orders, shipments, payments และ customer master records ต้องอยู่หลัง Sisahygo API integration boundary Local demo data จึง seed เฉพาะ Client Account Foundation records และ mock external customer identifiers เพื่อให้งาน integration ในอนาคตไม่พึ่ง direct production database access