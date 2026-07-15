# Developer Setup

Sisahygo Connect ใช้ Laravel 13, Livewire 3, Tailwind CSS และ Laravel Sail

แนวทางที่แนะนำสำหรับ development บน Windows:

1. เก็บ project files ไว้ใน WSL
2. Start Sail จาก WSL project directory
3. รัน PHP/Laravel commands ผ่าน Sail เมื่อคำสั่งนั้นต้องพึ่ง application container
4. รัน frontend builds ด้วย `npm run build` จาก project root

คำสั่งตรวจสอบที่ใช้บ่อย:

```bash
composer dump-autoload
npm run build
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test
```

งาน sprint ปกติห้าม reinstall Laravel, Breeze, Livewire, Tailwind หรือ Vite

## Sisahygo API Local Configuration

คัดลอกค่า configuration ที่ปลอดภัยของ Sisahygo API จาก `.env.example` และจัดการ credentials ผ่าน encrypted credential model ห้ามใส่ API keys จริงลงใน tracked files

Tests ปกติใช้ `Http::fake()` และ fixtures ส่วน live Sandbox smoke tests ปิดไว้ตามค่าเริ่มต้นด้วย `SISAHYGO_API_LIVE_SMOKE_TESTS=false`