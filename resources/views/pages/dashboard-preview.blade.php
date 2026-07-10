<x-layouts.connect title="Dashboard | Sisahygo Connect">
    <div class="flex min-h-screen">
        <aside class="hidden w-72 bg-brand-navy p-6 text-white lg:block">
            <x-application.logo mode="dark" height="44" />
            <nav class="mt-10 space-y-2 text-sm">
                <a class="block rounded-xl bg-white/10 px-4 py-3 font-semibold" href="#">Dashboard</a>
                <a class="block rounded-xl px-4 py-3 text-white/70 hover:bg-white/10" href="#">Orders</a>
                <a class="block rounded-xl px-4 py-3 text-white/70 hover:bg-white/10" href="#">Tracking</a>
                <a class="block rounded-xl px-4 py-3 text-white/70 hover:bg-white/10" href="#">Billing</a>
            </nav>
        </aside>
        <main class="flex-1 p-6 lg:p-8">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-brand-navy">ภาพรวมการขนส่ง</h1>
                    <p class="text-sm text-brand-muted">Sisahygo Connect Dashboard</p>
                </div>
                <x-ui.button>สร้างรายการฝากส่ง</x-ui.button>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <x-ui.card title="Orders Today"><div class="text-3xl font-bold text-brand-navy">128</div></x-ui.card>
                <x-ui.card title="In Transit"><div class="text-3xl font-bold text-brand-blue">86</div></x-ui.card>
                <x-ui.card title="Delivered"><div class="text-3xl font-bold text-green-600">342</div></x-ui.card>
                <x-ui.card title="Pending"><div class="text-3xl font-bold text-brand-orange">12</div></x-ui.card>
            </div>

            <x-ui.card class="mt-6" title="Recent Shipments" description="ตัวอย่างตารางสำหรับรายการล่าสุด">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead><tr class="text-left text-brand-muted"><th class="py-3">เลขอ้างอิง</th><th>ผู้รับ</th><th>สถานะ</th><th>วันที่</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr><td class="py-3 font-semibold">ORD-2026-00128</td><td>บริษัท ตัวอย่าง จำกัด</td><td><x-ui.badge type="info">In Transit</x-ui.badge></td><td>10/07/2026</td></tr>
                            <tr><td class="py-3 font-semibold">ORD-2026-00127</td><td>ร้านค้าออนไลน์</td><td><x-ui.badge type="success">Delivered</x-ui.badge></td><td>10/07/2026</td></tr>
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </main>
    </div>
</x-layouts.connect>
