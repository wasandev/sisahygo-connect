<x-layouts.connect title="เข้าสู่ระบบ | Sisahygo Connect">
    <main class="grid min-h-screen lg:grid-cols-2">
        <section class="hidden bg-brand-navy p-12 text-white lg:flex lg:flex-col lg:justify-between">
            <x-application.logo mode="dark" height="56" />
            <div>
                <h1 class="text-4xl font-bold leading-tight">เชื่อมต่อทุกการขนส่ง<br>ให้ธุรกิจของคุณง่ายขึ้น</h1>
                <p class="mt-4 max-w-md text-white/75">สร้างรายการฝากส่ง ติดตามสถานะ ดูประวัติ และจัดการข้อมูลขนส่งได้ในที่เดียว</p>
            </div>
            <p class="text-sm text-white/60">Digital Logistics Customer Platform</p>
        </section>

        <section class="flex items-center justify-center p-6">
            <div class="w-full max-w-md connect-card p-8">
                <div class="mb-8 text-center">
                    <x-application.logo variant="vertical" height="110" class="mx-auto" />
                    <h2 class="mt-6 text-2xl font-bold text-brand-navy">เข้าสู่ระบบ</h2>
                    <p class="mt-2 text-sm text-brand-muted">สำหรับลูกค้า Sisahygo Connect</p>
                </div>

                <form class="space-y-4">
                    <x-ui.input label="อีเมล หรือรหัสลูกค้า" name="email" type="text" placeholder="กรอกอีเมลหรือรหัสลูกค้า" />
                    <x-ui.input label="รหัสผ่าน" name="password" type="password" placeholder="กรอกรหัสผ่าน" />
                    <x-ui.button type="submit" class="w-full">เข้าสู่ระบบ</x-ui.button>
                </form>
            </div>
        </section>
    </main>
</x-layouts.connect>
