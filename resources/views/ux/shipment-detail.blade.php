<x-app-layout title="UX Shipment Detail">
    <div class="space-y-6">
        <x-connect.page-header title="SH-240715-001" eyebrow="รายละเอียดการขนส่ง" description="กรุงเทพฯ → เชียงใหม่">
            <x-slot:actions><x-connect.button :href="route('ux.tracking')" wire:navigate variant="secondary">กลับไปค้นหา</x-connect.button></x-slot:actions>
        </x-connect.page-header>

        <div class="grid gap-4 lg:grid-cols-[1fr_22rem]">
            <x-connect.card title="สถานะการเดินทาง">
                <x-connect.timeline :items="[
                    ['title' => 'รับสินค้า', 'meta' => '09:12', 'state' => 'done'],
                    ['title' => 'คัดแยกที่คลัง', 'meta' => '11:35', 'state' => 'done'],
                    ['title' => 'ออกจากคลังต้นทาง', 'meta' => '14:10', 'state' => 'current'],
                    ['title' => 'ถึงปลายทาง', 'meta' => 'คาดการณ์พรุ่งนี้', 'state' => 'pending'],
                ]" />
            </x-connect.card>

            <x-connect.card title="ข้อมูลสรุป">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">ผู้รับ</dt><dd class="font-semibold text-connect-navy-900">บริษัท สยามตัวอย่าง</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">จำนวน</dt><dd class="font-semibold text-connect-navy-900">3 ชิ้น</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">สถานะ</dt><dd><x-connect.badge variant="blue">กำลังจัดส่ง</x-connect.badge></dd></div>
                </dl>
            </x-connect.card>
        </div>
    </div>
</x-app-layout>
