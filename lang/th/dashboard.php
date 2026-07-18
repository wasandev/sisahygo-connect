<?php

return [
    'eyebrow' => 'Customer Dashboard',
    'title' => 'หน้าหลัก',
    'greeting' => 'สวัสดี, :name',
    'description' => 'ภาพรวมรายการขนส่งและงานที่ควรติดตามสำหรับ Client Account ปัจจุบัน',
    'loading' => 'กำลังโหลดข้อมูลหน้าหลัก...',
    'account' => [
        'label' => 'Client Account ปัจจุบัน',
        'current' => 'Client Account ปัจจุบัน',
        'refreshed_at' => 'อัปเดตล่าสุด: :time',
    ],
    'metrics' => [
        'label' => 'สรุปสถานะรายการ',
    ],
    'cards' => [
        'unavailable_value' => 'ยังคำนวณไม่ได้',
        'today' => [
            'label' => 'รายการวันนี้',
            'helper' => 'นับจากจำนวนทั้งหมดที่ Core ส่งผ่าน meta.total สำหรับวันนี้',
        ],
        'in_progress' => [
            'label' => 'กำลังดำเนินการ',
            'helper' => 'รอ Core summary endpoint หรือ multi-status filter เพื่อคำนวณอย่างถูกต้อง',
        ],
        'completed' => [
            'label' => 'สำเร็จใน 30 วัน',
            'helper' => 'นับรายการสถานะ completed ในช่วง 30 วันล่าสุด',
        ],
        'attention' => [
            'label' => 'รายการที่ควรติดตาม',
            'helper' => 'นับรายการสถานะ problem ในช่วง 30 วันล่าสุด',
        ],
    ],
    'shortcuts' => [
        'order_checking' => 'สร้างรายการส่งสินค้า',
        'order_checking_disabled' => 'ยังไม่มีสิทธิ์สร้างรายการ',
        'shipments' => 'รายการขนส่ง',
        'tracking' => 'ติดตามพัสดุ',
        'history' => 'ประวัติรายการ',
    ],
    'latest' => [
        'title' => 'รายการล่าสุด',
        'description' => 'โหลดรายการล่าสุดแบบจำกัดจำนวนจาก Core API ไม่ดึงประวัติทั้งหมด',
        'empty_title' => 'ยังไม่มีรายการล่าสุด',
        'empty_description' => 'เมื่อมีรายการที่ Core มองเห็นได้ รายการล่าสุดจะแสดงที่นี่',
    ],
    'attention' => [
        'title' => 'รายการที่ควรติดตาม',
        'description' => 'รายการสถานะ problem ล่าสุดในช่วง 30 วัน',
        'empty' => 'ยังไม่มีรายการที่ต้องติดตามในช่วงนี้',
    ],
    'fields' => [
        'order' => 'รายการ',
        'date' => 'วันที่',
        'receiver' => 'ผู้รับ',
        'destination' => 'ปลายทาง',
        'status' => 'สถานะ',
        'action' => 'การทำงาน',
        'tracking_no' => 'เลขติดตาม',
    ],
    'actions' => [
        'open_history' => 'เปิดประวัติ',
        'refresh' => 'โหลดใหม่',
        'refreshing' => 'กำลังโหลด...',
        'retry' => 'ลองใหม่',
        'view_detail' => 'เปิดรายละเอียด',
    ],
    'recent_receivers' => [
        'title' => 'ผู้รับที่ใช้ล่าสุด',
        'description' => 'สรุปจากรายการล่าสุดที่โหลดในหน้านี้',
        'latest' => 'ล่าสุด: :date',
        'count' => '{1}พบในรายการล่าสุด :count ครั้ง|[2,*]พบในรายการล่าสุด :count ครั้ง',
        'empty' => 'ยังไม่มีข้อมูลผู้รับในรายการล่าสุด',
    ],
    'recent_products' => [
        'title' => 'สินค้าที่ใช้ล่าสุด',
        'description' => 'สรุปจากสินค้าในรายการล่าสุดที่ Core ส่งกลับมา',
        'count' => '{1}พบในรายการล่าสุด :count ครั้ง|[2,*]พบในรายการล่าสุด :count ครั้ง',
        'empty' => 'ยังไม่มีข้อมูลสินค้าในรายการล่าสุด',
    ],
    'unavailable' => [
        'title' => 'ยังไม่พร้อมแสดงหน้าหลัก',
    ],
    'errors' => [
        'no_credential' => 'ยังไม่สามารถเตรียมข้อมูลเชื่อมต่อ Sisahygo ได้ในขณะนี้',
        'authentication' => 'ไม่สามารถยืนยันตัวตนกับ Sisahygo ได้',
        'authorization' => 'Client Account นี้ยังไม่มีสิทธิ์ดูข้อมูลหน้าหลัก',
        'connection' => 'ไม่สามารถเชื่อมต่อ Sisahygo ได้ กรุณาลองใหม่อีกครั้ง',
        'validation' => 'ข้อมูลค้นหาไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง',
        'rate_limited' => 'มีการเรียกใช้งานถี่เกินไป กรุณารอสักครู่แล้วลองใหม่',
        'server' => 'Sisahygo ยังไม่พร้อมให้บริการ กรุณาลองใหม่ภายหลัง',
        'malformed' => 'รูปแบบข้อมูลจาก Sisahygo ไม่ตรงตามที่คาดไว้',
        'unexpected' => 'เกิดข้อผิดพลาดที่ไม่คาดคิด กรุณาลองใหม่อีกครั้ง',
    ],
];
