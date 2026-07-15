<x-app-layout title="UX Reports">
    <div class="space-y-6">
        <x-connect.page-header title="รายงาน" eyebrow="Reports" description="รายงานที่ช่วยตอบคำถามธุรกิจเร็ว ไม่ใช่หน้าตารางซับซ้อน" />
        <div class="grid gap-4 md:grid-cols-3">
            @foreach ([['สรุปการส่งสินค้า', 'จำนวนรายการและสถานะรายสัปดาห์'], ['ปัญหาที่พบบ่อย', 'รายการล่าช้าและข้อมูลไม่ครบ'], ['ค่าใช้จ่ายขนส่ง', 'ยอดรวมตามช่วงเวลา']] as $report)
                <x-connect.card>
                    <p class="font-semibold text-connect-navy-900">{{ $report[0] }}</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $report[1] }}</p>
                    <div class="mt-4"><x-connect.button variant="secondary" size="sm">ดูรายงาน</x-connect.button></div>
                </x-connect.card>
            @endforeach
        </div>
        <x-connect.empty-state title="ยังไม่มีรายงานขั้นสูง" description="เริ่มจากรายงานที่ลูกค้าใช้ตัดสินใจจริงก่อน แล้วค่อยเพิ่มเมื่อมีข้อมูลจากการใช้งาน" />
    </div>
</x-app-layout>
