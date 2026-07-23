<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-connect.meta title="Request Access | Sisahygo Connect" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <main class="min-h-screen bg-slate-50 text-slate-900">
        <x-connect.environment-banner :show-host="false" />
        <header class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('welcome') }}" class="connect-focus rounded-md" aria-label="Sisahygo Connect">
                <x-connect.logo class="h-10 w-auto sm:h-12" />
            </a>
            <nav class="flex items-center gap-2" aria-label="Public navigation">
                <a href="{{ route('welcome') }}" class="connect-focus rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Home</a>
                <a href="{{ route('login') }}" class="connect-focus rounded-lg px-3 py-2 text-sm font-semibold text-connect-navy-800 hover:bg-white">เข้าสู่ระบบ</a>
            </nav>
        </header>

        <section class="mx-auto grid w-full max-w-6xl gap-8 px-5 py-8 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-12">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-connect-orange-600">Request Access</p>
                <h1 class="mt-3 text-3xl font-bold leading-tight text-connect-navy-900 sm:text-4xl">ขอเปิดใช้งาน Sisahygo Connect</h1>
                <p class="mt-4 text-base leading-7 text-slate-600">ส่งข้อมูลบริษัทและผู้ติดต่อเพื่อให้ทีม Sisahygo ตรวจสอบและเตรียมบัญชีลูกค้าสำหรับใช้งาน Connect</p>
                <div class="mt-6 rounded-lg border border-connect-blue-100 bg-connect-blue-50 p-4 text-sm leading-6 text-connect-blue-900">
                    คำขอนี้ถูกเก็บในฐานข้อมูลของ Connect สำหรับ Sprint นี้เท่านั้น ยังไม่มีการส่งอีเมลหรือเชื่อมต่อ Nova อัตโนมัติ
                </div>
            </div>

            <x-connect.card title="ข้อมูลสำหรับขอใช้งาน" description="กรอกข้อมูลที่ทีมงานต้องใช้เพื่อติดต่อกลับและเตรียมบัญชีลูกค้า">
                <form method="POST" action="{{ route('request-access.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div class="sm:col-span-2">
                        <label for="company_name" class="text-sm font-semibold text-slate-700">Company Name *</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name') }}" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('company_name') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="contact_name" class="text-sm font-semibold text-slate-700">Contact Name *</label>
                        <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('contact_name') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="text-sm font-semibold text-slate-700">Email *</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('email') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="text-sm font-semibold text-slate-700">Phone *</label>
                        <input id="phone" name="phone" value="{{ old('phone') }}" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('phone') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="province" class="text-sm font-semibold text-slate-700">Province *</label>
                        <input id="province" name="province" value="{{ old('province') }}" required class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('province') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="website" class="text-sm font-semibold text-slate-700">Website</label>
                        <input id="website" type="url" name="website" value="{{ old('website') }}" placeholder="https://example.com" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('website') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="number_of_branches" class="text-sm font-semibold text-slate-700">Number of Branches</label>
                        <input id="number_of_branches" type="number" min="1" name="number_of_branches" value="{{ old('number_of_branches') }}" class="connect-focus mt-1 block min-h-11 w-full rounded-lg border-slate-300 text-sm shadow-sm">
                        @error('number_of_branches') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="additional_notes" class="text-sm font-semibold text-slate-700">Additional Notes</label>
                        <textarea id="additional_notes" name="additional_notes" rows="4" class="connect-focus mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">{{ old('additional_notes') }}</textarea>
                        @error('additional_notes') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <x-connect.button type="submit" size="lg" class="w-full sm:w-auto">ส่งคำขอใช้งาน</x-connect.button>
                    </div>
                </form>
            </x-connect.card>
        </section>
    </main>
</body>
</html>
