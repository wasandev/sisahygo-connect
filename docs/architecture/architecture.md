# Architecture

Sisahygo Connect ใช้ Laravel, Livewire, Tailwind CSS และ Laravel Sail โครงสร้างหลักวาง business rules ไว้ใน `app/Domain` ให้ service classes ทำหน้าที่ประสาน domain logic ใช้ policies สำหรับ authorization และใช้ query objects เพื่อบังคับ data isolation