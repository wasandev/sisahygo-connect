<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">✓</div>
        <h1 class="mt-4 text-xl font-semibold text-connect-navy-900">ส่งคำขอใช้งานแล้ว</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">ทีม Sisahygo จะตรวจสอบข้อมูลของ {{ $accessRequest->company_name }} และติดต่อกลับตามช่องทางที่ให้ไว้</p>
        <div class="mt-6 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm text-slate-600">
            <p class="font-semibold text-connect-navy-900">สถานะคำขอ: pending</p>
            <p class="mt-1">Invitation token mock ถูกสร้างไว้แล้วสำหรับการทดสอบภายใน Sprint นี้</p>
        </div>
        <x-connect.button :href="route('welcome')" class="mt-6 w-full">กลับหน้าแรก</x-connect.button>
    </div>
</x-guest-layout>
