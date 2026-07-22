<?php

return [
    'eyebrow' => 'Notification Center',
    'title' => 'การแจ้งเตือน',
    'description' => 'ศูนย์รวมการแจ้งเตือนของ Workspace ระยะที่ 1 ใช้ข้อมูลตัวอย่างเท่านั้น',
    'center_title' => 'รายการแจ้งเตือน',
    'unread' => 'ยังไม่อ่าน',
    'phase_one' => [
        'title' => 'Phase 1: Mock Data',
        'message' => 'หน้านี้ยังไม่มี polling, push notification หรือการบันทึกสถานะอ่านจริง',
    ],
    'filters' => [
        'label' => 'ตัวกรองการแจ้งเตือน',
        'all' => 'ทั้งหมด',
        'unread' => 'ยังไม่อ่าน',
    ],
    'empty' => [
        'title' => 'ไม่มีการแจ้งเตือน',
        'description' => 'เมื่อมีข้อมูลจริงจากระบบ การแจ้งเตือนจะแสดงที่นี่',
    ],
    'mock' => [
        'shipment' => ['title' => 'รายการจัดส่งต้องติดตาม', 'message' => 'OH90001 อยู่ในสถานะมีปัญหา กรุณาตรวจสอบกับสาขาปลายทาง'],
        'payment' => ['title' => 'มีรายการค้างชำระ', 'message' => 'พบยอดค้างชำระใหม่ใน Payment Center สำหรับ Client Account นี้'],
        'system' => ['title' => 'ข้อมูลจาก Sisahygo พร้อมใช้งาน', 'message' => 'การเชื่อมต่อ Core API ล่าสุดสำเร็จในสภาพแวดล้อม Sandbox'],
    ],
];
