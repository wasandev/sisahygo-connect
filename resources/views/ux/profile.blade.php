<x-app-layout title="UX Profile">
    <div class="space-y-6">
        <x-connect.page-header title="โปรไฟล์ของฉัน" eyebrow="Profile" description="ข้อมูลส่วนตัวที่จำเป็นต่อการใช้งานระบบ" />
        <x-connect.card title="ข้อมูลผู้ใช้">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-connect.input label="ชื่อ" value="คุณอร ตัวอย่าง" />
                <x-connect.input label="อีเมล" value="owner@abc-demo.test" />
                <x-connect.select label="ภาษา"><option>ไทย</option><option>English</option></x-connect.select>
            </div>
            <div class="mt-5"><x-connect.button>บันทึกข้อมูล</x-connect.button></div>
        </x-connect.card>
    </div>
</x-app-layout>
