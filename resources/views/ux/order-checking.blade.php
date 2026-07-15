<x-app-layout title="UX Order Checking">
    <div class="space-y-6">
        <x-connect.page-header title="ตรวจสอบรายการส่งสินค้า" eyebrow="งานหลัก" description="โครงสร้าง 4 การ์ด ลดความรู้สึกว่าเป็นฟอร์มยาว และช่วยให้ตรวจทานก่อนส่งเสมอ" />

        <div class="grid gap-4 xl:grid-cols-[1fr_22rem]">
            <div class="space-y-4">
                <x-connect.card title="1. ผู้รับ" description="เลือกจากรายชื่อที่ใช้งานบ่อยหรือค้นหาด้วยชื่อบริษัท">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-connect.search placeholder="ค้นหาผู้รับ เช่น บริษัท สยามตัวอย่าง" />
                        <x-connect.select label="สาขาปลายทาง">
                            <option>สำนักงานใหญ่ - บางนา</option>
                            <option>คลังสินค้า - เชียงใหม่</option>
                        </x-connect.select>
                    </div>
                    <div class="mt-4 rounded-lg bg-connect-blue-50 p-4">
                        <p class="font-semibold text-connect-navy-900">บริษัท สยามตัวอย่าง จำกัด</p>
                        <p class="mt-1 text-sm text-slate-600">บางนา กรุงเทพฯ • ผู้รับประจำ</p>
                    </div>
                </x-connect.card>

                <x-connect.card title="2. สินค้า" description="เพิ่มเฉพาะข้อมูลที่จำเป็นต่อการรับงาน">
                    <div class="space-y-3">
                        @foreach ([['กล่องเอกสาร', '2', '120'], ['อะไหล่เครื่องจักร', '1', '450']] as $item)
                            <div class="grid grid-cols-[minmax(0,1fr)_4rem_5rem] gap-2 rounded-lg border border-slate-200 p-3 text-sm">
                                <span class="min-w-0 truncate font-medium text-connect-navy-900">{{ $item[0] }}</span>
                                <span class="text-slate-500">{{ $item[1] }} ชิ้น</span>
                                <span class="text-right font-semibold">฿{{ $item[2] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <x-connect.input label="ชื่อสินค้า" placeholder="เช่น กล่องสินค้า" />
                        <x-connect.input label="จำนวน" type="number" value="1" />
                        <x-connect.input label="มูลค่า" placeholder="0.00" />
                    </div>
                </x-connect.card>

                <x-connect.card title="3. หมายเหตุ">
                    <x-connect.textarea label="คำแนะนำเพิ่มเติม" rows="3" placeholder="เช่น ส่งก่อน 15:00 น. ติดต่อคุณมะลิ"></x-connect.textarea>
                </x-connect.card>

                <x-connect.card title="4. ตรวจทานและส่ง">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><span class="text-slate-500">ผู้รับ</span><span class="font-semibold text-connect-navy-900">บริษัท สยามตัวอย่าง จำกัด</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">จำนวนสินค้า</span><span class="font-semibold text-connect-navy-900">3 ชิ้น</span></div>
                        <div class="flex justify-between gap-3"><span class="text-slate-500">ยอดประเมิน</span><span class="font-semibold text-connect-navy-900">฿570</span></div>
                    </div>
                    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
                        <x-connect.button variant="secondary">บันทึกร่าง</x-connect.button>
                        <x-connect.button>ส่งรายการตรวจสอบ</x-connect.button>
                    </div>
                </x-connect.card>
            </div>

            <aside class="space-y-4">
                <x-connect.card title="ขั้นตอน">
                    <x-connect.timeline :items="[
                        ['title' => 'เลือกผู้รับ', 'meta' => 'เสร็จแล้ว', 'state' => 'done'],
                        ['title' => 'เพิ่มสินค้า', 'meta' => 'กำลังทำ', 'state' => 'current'],
                        ['title' => 'ตรวจทาน', 'meta' => 'รอข้อมูล', 'state' => 'pending'],
                    ]" />
                </x-connect.card>
                <x-connect.empty-state title="ไม่ต้องกรอกเยอะ" description="ข้อมูลเชิงเทคนิคจะถูกซ่อนไว้ ลูกค้าเห็นเฉพาะสิ่งที่ต้องตัดสินใจ" />
            </aside>
        </div>
    </div>
</x-app-layout>
