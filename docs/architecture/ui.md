# UI Architecture

Authenticated application pages ใช้ reusable application shell จาก `resources/views/layouts/app.blade.php` และ `resources/views/livewire/layout/navigation.blade.php`

Canonical Sisahygo Connect Blade components อยู่ใต้ `resources/views/components/connect/` และควรเรียกด้วย dot notation เช่น:

```blade
<x-connect.logo />
<x-connect.card />
<x-connect.button />
```

Breeze authentication components ยังใช้งานอยู่ใน authentication และ profile workflows ส่วน preview-only starter artifacts ถูกนำออกจาก active source แล้ว

Tenant-dependent navigation ถือว่า `client.account` middleware ได้ resolve current Client Account เรียบร้อยแล้ว