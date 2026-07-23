<x-app-layout title="Welcome">
    <div class="space-y-6">
        <x-connect.page-header title="Welcome to Sisahygo Connect" description="เริ่มต้นใช้งานพื้นที่ลูกค้าสำหรับจัดการงานขนส่ง ติดตามสถานะ และตรวจสอบข้อมูลสำคัญ" eyebrow="Customer Workspace" />

        <x-connect.card>
            <x-connect.onboarding-progress />
        </x-connect.card>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Sisahygo Connect features">
            @foreach ([
                ['title' => 'Create Shipment', 'description' => 'สร้างรายการรับส่งสินค้าเดี่ยวหรือแบบหลายรายการ'],
                ['title' => 'Track Shipment', 'description' => 'ติดตามสถานะการขนส่งด้วยเลข Tracking หรือ Reference'],
                ['title' => 'Payment Center', 'description' => 'ตรวจสอบสถานะการชำระเงินและรายการที่เกี่ยวข้อง'],
                ['title' => 'History', 'description' => 'ดูประวัติรายการและเปิดดูรายละเอียดคำสั่งซื้อ'],
            ] as $feature)
                <x-connect.card>
                    <div class="flex min-h-40 flex-col gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">✓</div>
                        <h2 class="text-base font-semibold text-connect-navy-900">{{ $feature['title'] }}</h2>
                        <p class="text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
                    </div>
                </x-connect.card>
            @endforeach
        </section>

        <form method="POST" action="{{ route('onboarding.start') }}">
            @csrf
            <x-connect.button type="submit" size="lg">เริ่มใช้งาน</x-connect.button>
        </form>
    </div>
</x-app-layout>
