<x-app-layout title="UX Payments">
    <div class="space-y-6">
        <x-connect.page-header title="การชำระเงิน" eyebrow="Payments" description="เน้นความเข้าใจของลูกค้า ไม่แสดงศัพท์บัญชีที่ไม่จำเป็น" />
        <div class="grid gap-4 lg:grid-cols-3">
            <x-connect.stat-card label="ยอดค้างชำระ" value="฿18,420" variant="warning" />
            <x-connect.stat-card label="ใบแจ้งหนี้ล่าสุด" value="4" />
            <x-connect.stat-card label="ชำระแล้วเดือนนี้" value="฿52,100" variant="success" />
        </div>
        <x-connect.card title="ใบแจ้งหนี้ล่าสุด">
            <div class="space-y-3">
                @foreach ([['INV-2407-001','ครบกำหนด 20 ก.ค.','฿8,400','warning'],['INV-2407-002','ชำระแล้ว','฿12,000','success'],['INV-2406-018','เกินกำหนด','฿10,020','danger']] as $invoice)
                    <div class="grid gap-2 rounded-lg border border-slate-200 p-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-center">
                        <p class="font-semibold text-connect-navy-900">{{ $invoice[0] }}</p>
                        <p class="text-sm text-slate-500">{{ $invoice[1] }}</p>
                        <p class="font-semibold">{{ $invoice[2] }}</p>
                        <x-connect.badge :variant="$invoice[3]">{{ $invoice[1] }}</x-connect.badge>
                    </div>
                @endforeach
            </div>
        </x-connect.card>
    </div>
</x-app-layout>
