<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Sisahygo Connect') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <main class="min-h-screen bg-slate-50">
            <div class="mx-auto grid min-h-screen w-full max-w-6xl items-center gap-8 px-5 py-8 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
                <section class="hidden lg:block">
                    <a href="/" wire:navigate class="connect-focus inline-flex rounded-lg" aria-label="Sisahygo Connect">
                        <x-connect.logo class="h-12 w-auto" />
                    </a>
                    <div class="mt-10 max-w-md">
                        <p class="text-sm font-bold uppercase tracking-wide text-connect-blue-600">Sisahygo Connect</p>
                        <h1 class="mt-3 text-4xl font-bold leading-tight text-connect-navy-900">จัดการงานขนส่งได้ง่ายขึ้น</h1>
                        <p class="mt-4 text-base leading-7 text-slate-600">พื้นที่ลูกค้าสำหรับตรวจรายการส่งสินค้า ติดตามสถานะ และดูข้อมูลที่ต้องทำต่ออย่างมั่นใจ</p>
                    </div>
                </section>

                <section class="w-full">
                    <div class="mb-6 flex justify-center lg:hidden">
                        <a href="/" wire:navigate class="connect-focus rounded-lg" aria-label="Sisahygo Connect">
                            <x-connect.logo class="h-11 w-auto" />
                        </a>
                    </div>
                    <div class="mx-auto w-full max-w-md rounded-lg border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
