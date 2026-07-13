<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-connect-blue-600">Sisahygo Connect</p>
                <h2 class="text-xl font-semibold leading-tight text-connect-navy-900">
                    {{ __('Dashboard') }}
                </h2>
            </div>
            <x-connect.logo class="h-9 w-auto" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'รายการวันนี้', 'value' => '0'],
                    ['label' => 'กำลังขนส่ง', 'value' => '0'],
                    ['label' => 'ส่งสำเร็จ', 'value' => '0'],
                    ['label' => 'รายการมีปัญหา', 'value' => '0'],
                ] as $stat)
                    <x-connect.card>
                        <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-connect-navy-900">{{ $stat['value'] }}</p>
                    </x-connect.card>
                @endforeach
            </div>

            <x-connect.card title="เริ่มต้นใช้งาน">
                <p class="text-sm leading-6 text-slate-600">
                    ระบบ Authentication, Livewire, Tailwind และ Sisahygo Connect Brand Theme พร้อมใช้งานแล้ว
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <x-connect.button>สร้างรายการ Order Checking</x-connect.button>
                    <x-connect.button variant="secondary">ติดตามสินค้า</x-connect.button>
                </div>
            </x-connect.card>
        </div>
    </div>
</x-app-layout>
