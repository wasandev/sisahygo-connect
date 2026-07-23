<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-connect.meta title="Sisahygo Connect" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <main class="min-h-screen bg-slate-50 text-slate-900">
        <x-connect.environment-banner :show-host="false" />

        <section class="flex min-h-screen flex-col">
            <header class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-5 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('welcome') }}" class="connect-focus rounded-md" aria-label="Sisahygo Connect">
                    <x-connect.logo class="h-10 w-auto sm:h-12" />
                </a>

                <nav class="flex items-center gap-2" aria-label="Guest navigation">
                    <a href="{{ route('login') }}" class="connect-focus rounded-lg px-3 py-2 text-sm font-semibold text-connect-navy-800 transition hover:bg-white hover:text-connect-blue-700">
                        เข้าสู่ระบบ
                    </a>
                    <a href="{{ route('register') }}" class="connect-focus hidden rounded-lg bg-connect-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-connect-blue-700 sm:inline-flex">
                        สมัครใช้งาน
                    </a>
                </nav>
            </header>

            <div class="mx-auto grid w-full max-w-7xl flex-1 items-center gap-8 px-5 py-8 sm:px-6 sm:py-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-12 lg:px-8 lg:py-12">
                <div class="max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-connect-orange-600">Sisahygo Connect</p>
                    <h1 class="mt-4 max-w-4xl text-3xl font-bold leading-tight text-connect-navy-900 sm:text-5xl lg:text-6xl">
                        พื้นที่ลูกค้าสำหรับจัดการงานขนส่งกับ Sisahygo
                    </h1>
                    <p class="mt-6 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg">
                        เข้าสู่ระบบเพื่อสร้างรายการรับส่งสินค้า ติดตามสถานะการขนส่ง ตรวจสอบประวัติรายการและสถานะการชำระเงิน พร้อมจัดการข้อมูลผ่านบัญชีลูกค้าอย่างปลอดภัย
                    </p>

                    <ul class="mt-6 grid gap-3 text-sm font-medium text-slate-700 sm:grid-cols-2">
                        <li class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">สร้างรายการรับส่งสินค้า</li>
                        <li class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">ติดตามสถานะการขนส่ง</li>
                        <li class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">ตรวจสอบประวัติรายการ</li>
                        <li class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">ตรวจสอบสถานะการชำระเงิน</li>
                    </ul>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="{{ route('login') }}" class="connect-focus inline-flex items-center justify-center rounded-lg bg-connect-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-connect-blue-700">
                            เข้าสู่ระบบ
                        </a>
                        <a href="{{ route('register') }}" class="connect-focus inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                            สมัครใช้งาน
                        </a>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 lg:p-8">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-connect-blue-50">
                            <img src="{{ asset('images/brand/symbol.svg') }}" alt="" class="h-10 w-10">
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-connect-navy-900">Sisahygo Connect</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                ระบบศูนย์กลางสำหรับลูกค้า เพื่อสร้างรายการขนส่ง ติดตามสถานะ และตรวจสอบข้อมูลการให้บริการผ่าน Sisahygo Core API
                            </p>
                        </div>
                    </div>

                    <dl class="mt-6 grid gap-3 text-sm">
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-600">บัญชีลูกค้า</dt>
                            <dd class="font-semibold text-emerald-700">พร้อมใช้งาน</dd>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-600">สร้างและติดตามรายการ</dt>
                            <dd class="font-semibold text-connect-blue-700">พร้อมทดสอบ</dd>
                        </div>
                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                            <dt class="font-medium text-slate-600">Sisahygo Core API</dt>
                            <dd class="font-semibold text-connect-orange-700">เชื่อมต่อ Sandbox</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>
    </main>
</body>
</html>