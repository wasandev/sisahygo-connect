<x-app-layout title="UX Tracking">
    <div class="space-y-6">
        <x-connect.page-header title="ติดตามสถานะสินค้า" eyebrow="Tracking" description="ค้นหา → เห็นการ์ด shipment → อ่าน timeline เป็นหลัก">
            <x-slot:actions><x-connect.search placeholder="ค้นหาเลขติดตาม" /></x-slot:actions>
        </x-connect.page-header>

        <div class="grid gap-4 lg:grid-cols-[24rem_1fr]">
            <x-connect.card title="ผลการค้นหา">
                <div class="space-y-3">
                    @foreach ([['SH-240715-001', 'กำลังจัดส่ง', 'blue'], ['SH-240715-002', 'ถึงปลายทาง', 'success'], ['SH-240714-018', 'ต้องติดตาม', 'warning']] as $shipment)
                        <a href="{{ route('ux.shipment-detail') }}" wire:navigate class="connect-focus block rounded-lg border border-slate-200 p-4 hover:border-connect-blue-200 hover:bg-connect-blue-50">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-connect-navy-900">{{ $shipment[0] }}</p>
                                <x-connect.badge :variant="$shipment[2]">{{ $shipment[1] }}</x-connect.badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">กรุงเทพฯ → เชียงใหม่</p>
                        </a>
                    @endforeach
                </div>
            </x-connect.card>

            <x-connect.card title="Timeline ล่าสุด" description="ใช้เส้นเวลาเป็นภาพหลัก เพราะลูกค้าต้องการรู้ว่าสินค้าอยู่ขั้นตอนไหน">
                <x-connect.timeline :items="[
                    ['title' => 'รับรายการแล้ว', 'meta' => 'วันนี้ 09:12', 'description' => 'ระบบรับข้อมูลรายการส่งสินค้าเรียบร้อย', 'state' => 'done'],
                    ['title' => 'เข้าคลังต้นทาง', 'meta' => 'วันนี้ 11:35', 'description' => 'พัสดุอยู่ระหว่างเตรียมออกเดินทาง', 'state' => 'done'],
                    ['title' => 'กำลังจัดส่ง', 'meta' => 'ประมาณ 15:30', 'description' => 'รถขนส่งอยู่ระหว่างเดินทางไปปลายทาง', 'state' => 'current'],
                    ['title' => 'ส่งสำเร็จ', 'meta' => 'รออัปเดต', 'state' => 'pending'],
                ]" />
            </x-connect.card>
        </div>
    </div>
</x-app-layout>
