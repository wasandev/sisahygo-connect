<x-app-layout title="UX Settings">
    <div class="space-y-6">
        <x-connect.page-header title="ตั้งค่า" eyebrow="Settings" description="เก็บเฉพาะสิ่งที่ลูกค้าต้องจัดการเอง ลดตัวเลือกที่ไม่จำเป็น" />
        <div class="grid gap-4 lg:grid-cols-2">
            <x-connect.card title="บัญชีลูกค้า">
                <div class="space-y-4">
                    <x-connect.input label="ชื่อบริษัท" value="บริษัท ตัวอย่าง โลจิสติกส์ จำกัด" />
                    <x-connect.select label="บัญชีที่ใช้งาน"><option>ABC Demo Account</option></x-connect.select>
                </div>
            </x-connect.card>
            <x-connect.card title="ทีมงาน">
                <div class="space-y-3 text-sm">
                    @foreach ([['คุณอร', 'เจ้าของบัญชี'], ['คุณต้น', 'ฝ่ายคลัง'], ['คุณนิด', 'บัญชี']] as $member)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3"><span class="font-semibold text-connect-navy-900">{{ $member[0] }}</span><span class="text-slate-500">{{ $member[1] }}</span></div>
                    @endforeach
                </div>
            </x-connect.card>
        </div>
    </div>
</x-app-layout>
