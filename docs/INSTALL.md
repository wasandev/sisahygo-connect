# Sisahygo Connect Setup

Sisahygo Connect เป็น application ที่ใช้ Laravel, Livewire 3, Tailwind CSS และ Laravel Sail

## Local Development

ใช้ WSL สำหรับ project files และรัน Sail จาก project root

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run build
./vendor/bin/sail artisan test
```

## Verification

ก่อนส่งมอบงาน sprint ให้รันคำสั่งต่อไปนี้:

```bash
composer dump-autoload
npm run build
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test
```

## Notes

- Authentication มาจาก Laravel/Livewire stack เดิม
- Application pages ที่ขึ้นกับ tenant ต้องมี selected Client Account
- Root URL แสดง guest welcome page ส่วนผู้ใช้ที่ authenticated แล้วจะถูกส่งไป dashboard
- Sisahygo API integration ยังไม่ได้ implement เป็น business features