<?php

return [
    'label' => 'ค้นหาทั่วระบบ',
    'placeholder' => 'เลขติดตาม / เลขอ้างอิงลูกค้า / Batch Reference',
    'submit' => 'ค้นหา',
    'submitting' => 'กำลังค้นหา...',
    'found' => 'พบจาก :type',
    'not_found' => 'ไม่พบผลลัพธ์สำหรับ :query',
    'types' => [
        'tracking' => 'เลขติดตาม',
        'client_reference' => 'เลขอ้างอิงลูกค้า',
        'batch_reference' => 'Batch Reference',
    ],
    'validation' => [
        'required' => 'กรุณากรอกคำค้นหา',
    ],
    'errors' => [
        'no_credential' => 'ยังไม่สามารถเตรียมข้อมูลเชื่อมต่อ Sisahygo ได้ในขณะนี้',
        'unavailable' => 'ยังไม่สามารถค้นหาได้ในขณะนี้ กรุณาลองใหม่อีกครั้ง',
    ],
];
