<x-app-layout title="UX Dashboard">
    <div class="space-y-6">
        <x-connect.page-header title="ภาพรวมวันนี้" eyebrow="Prototype" description="ดูงานสำคัญของวันนี้และเริ่มงานหลักได้ในไม่กี่ขั้นตอน">
            <x-slot:actions>
                <x-connect.button :href="route('ux.order-checking')" wire:navigate>สร้างรายการส่ง</x-connect.button>
            </x-slot:actions>
        </x-connect.page-header>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-connect.stat-card label="รายการรอตรวจ" value="12" trend="วันนี้" />
            <x-connect.stat-card label="กำลังขนส่ง" value="48" variant="success" />
            <x-connect.stat-card label="ต้องติดตาม" value="3" variant="warning" />
            <x-connect.stat-card label="ยอดค้างชำระ" value="฿18,420" variant="danger" />
        </div>

        <div class="grid gap-4 lg:grid-cols-[1.3fr_0.7fr]">
            <x-connect.card title="งานที่ควรทำต่อ" description="ออกแบบให้ลูกค้าเริ่มงานหลักได้เร็วโดยไม่ต้องมองหาเมนูเยอะ">
                <div class="grid gap-3 sm:grid-cols-3">
                    <a href="{{ route('ux.order-checking') }}" wire:navigate class="connect-focus rounded-lg border border-slate-200 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                        <p class="font-semibold text-connect-navy-900">ตรวจรายการส่ง</p>
                        <p class="mt-1 text-sm text-slate-500">เลือกผู้รับ เพิ่มสินค้า ตรวจทาน</p>
                    </a>
                    <a href="{{ route('ux.tracking') }}" wire:navigate class="connect-focus rounded-lg border border-slate-200 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                        <p class="font-semibold text-connect-navy-900">ติดตามพัสดุ</p>
                        <p class="mt-1 text-sm text-slate-500">ค้นหาด้วยเลขติดตาม</p>
                    </a>
                    <a href="{{ route('ux.payments') }}" wire:navigate class="connect-focus rounded-lg border border-slate-200 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                        <p class="font-semibold text-connect-navy-900">ดูยอดชำระ</p>
                        <p class="mt-1 text-sm text-slate-500">ยอดค้างและใบแจ้งหนี้ล่าสุด</p>
                    </a>
                </div>
            </x-connect.card>

            <x-connect.card title="การแจ้งเตือนล่าสุด">
                <div class="space-y-3">
                    <x-connect.toast title="มี 3 รายการต้องตรวจ" message="ตรวจข้อมูลผู้รับก่อนส่งเข้าระบบ" />
                    <x-connect.toast variant="warning" title="พัสดุหนึ่งรายการล่าช้า" message="กรุงเทพฯ → เชียงใหม่" />
                </div>
            </x-connect.card>
        </div>
    </div>
</x-app-layout>
