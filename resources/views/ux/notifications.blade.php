<x-app-layout title="UX Notifications">
    <div class="space-y-6">
        <x-connect.page-header title="การแจ้งเตือน" eyebrow="Notifications" description="แสดงเฉพาะเรื่องที่ลูกค้าควรรู้หรือควรทำต่อ" />
        <x-connect.card>
            <div class="space-y-3">
                @foreach ([['รายการส่งต้องตรวจ', 'มีข้อมูลผู้รับไม่ครบ 2 รายการ', 'warning'], ['พัสดุถึงปลายทางแล้ว', 'SH-240715-002 ส่งสำเร็จ', 'success'], ['ใบแจ้งหนี้ใหม่', 'INV-2407-003 พร้อมให้ตรวจสอบ', 'blue']] as $notice)
                    <div class="flex gap-3 rounded-lg border border-slate-200 p-4">
                        <x-connect.badge :variant="$notice[2]">{{ $notice[0] }}</x-connect.badge>
                        <p class="text-sm text-slate-600">{{ $notice[1] }}</p>
                    </div>
                @endforeach
            </div>
        </x-connect.card>
    </div>
</x-app-layout>
