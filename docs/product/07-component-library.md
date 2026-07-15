# Component Library

## Blade Usage

```blade
<x-connect.button>สร้างรายการส่ง</x-connect.button>
<x-connect.card title="ผู้รับ">...</x-connect.card>
<x-connect.input label="ชื่อสินค้า" />
<x-connect.select label="สาขา">...</x-connect.select>
<x-connect.stat-card label="กำลังขนส่ง" value="48" />
<x-connect.timeline :items="$items" />
<x-connect.empty-state title="ยังไม่มีข้อมูล" />
```

## Rules

- Components เป็น presentation only
- ห้ามใส่ authorization rules ใน Blade components
- ห้ามใช้ API terminology ใน component copy
- ห้ามสร้าง components ชั่วคราวที่ใช้เฉพาะ prototype