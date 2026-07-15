# Sisahygo Connect

Sisahygo Connect เป็น Laravel และ Livewire application สำหรับ workflows ของ authenticated Client Account

Current baseline:

- Laravel with Livewire 3
- Tailwind CSS
- Laravel Sail development workflow
- Existing authentication preserved
- Tenant-safe Client Account foundation
- Thai default localization with English keys prepared
- Sisahygo API integration foundation implemented; business API modules ยังไม่ได้ implement

Root URL แสดง guest welcome page ผู้ใช้ที่ authenticated แล้วจะถูกส่งไป dashboard และ tenant-dependent pages ต้องมี current Client Account context ที่ถูกต้อง

## Local Demo Data

Seed deterministic Client Account demo data ด้วยคำสั่ง:

```bash
./vendor/bin/sail artisan db:seed '--class=Database\Seeders\Development\ClientAccountDemoSeeder'
```

รายละเอียด demo users และ reset instructions อยู่ใน `docs/engineering/demo-data.md`

## Sisahygo API Integration Foundation

Sprint 1.5 เพิ่ม secure integration foundation สำหรับ external Sisahygo Client API API credentials ถูกเก็บแยกตาม Client Account และเข้ารหัสขณะพักอยู่ในระบบ Sender และ receiver scope ต้อง derive จาก validated Client Account customer links เสมอ ไม่ใช่จาก API credential

Configuration อยู่ใน `config/sisahygo.php` ห้าม commit API keys จริง Receiver-only API support ยังขึ้นกับ Core API authorization ด้วย `order_headers.customer_rec_id`; ดูรายละเอียดที่ `docs/integrations/sisahygo/receiver-compatibility-gap.md`