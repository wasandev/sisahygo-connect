<?php

return [
    'type' => [
        'H' => 'เงินสดต้นทาง',
        'T' => 'เงินโอนต้นทาง',
        'E' => 'เก็บเงินปลายทาง',
        'F' => 'วางบิลต้นทาง',
        'L' => 'วางบิลปลายทาง',
    ],
    'status' => [
        'outstanding' => 'ค้างชำระ',
        'paid' => 'ชำระแล้ว',
    ],
    'center' => ['eyebrow' => 'Payment Center', 'title' => 'ศูนย์การชำระเงิน', 'description' => 'ตรวจสอบรายการวางบิลต้นทาง วางบิลปลายทาง และเก็บเงินปลายทางจาก Sisahygo Core'],
    'detail' => ['title' => 'รายละเอียดการชำระเงิน', 'description' => 'ข้อมูลนี้อ้างอิงจาก Core Client Payment API', 'order_title' => 'ข้อมูลใบรับส่งสินค้า', 'parties_title' => 'ผู้ส่งและผู้รับ', 'payment_title' => 'ข้อมูลการชำระเงิน', 'references_title' => 'เอกสารอ้างอิง', 'not_found_title' => 'ไม่พบรายการชำระเงิน', 'not_found_description' => 'รายการนี้อาจไม่มีอยู่หรือไม่ได้อยู่ในสิทธิ์ของ Client Account ปัจจุบัน'],
    'summary' => ['record_count' => 'จำนวนรายการ', 'total_amount' => 'มูลค่ารวม', 'paid_record_count' => 'รายการชำระแล้ว', 'outstanding_record_count' => 'รายการค้างชำระ'],
    'filters' => ['title' => 'ตัวกรองรายการชำระเงิน', 'description' => 'ค้นหาจากข้อมูลที่ Core รองรับเท่านั้น', 'payment_type' => 'ประเภทการชำระ', 'payment_status' => 'สถานะ', 'date_from' => 'วันที่ตั้งหนี้ตั้งแต่', 'date_to' => 'ถึงวันที่', 'order_header_no' => 'เลขที่ใบรับส่งสินค้า', 'client_reference_no' => 'เลขอ้างอิงของลูกค้า', 'all_types' => 'ทุกประเภท', 'all_statuses' => 'ทุกสถานะ'],
    'list' => ['results_title' => 'รายการชำระเงิน'],
    'fields' => ['payment_identifier' => 'เลขที่รายการ', 'order_header_no' => 'เลขที่ใบรับส่งสินค้า', 'order_header_date' => 'วันที่ใบรับส่งสินค้า', 'client_reference_no' => 'เลขอ้างอิงของลูกค้า', 'tracking_reference' => 'เลขติดตาม/Waybill', 'billing_date' => 'วันที่ตั้งหนี้', 'payment_date' => 'วันที่ชำระ', 'type' => 'ประเภท', 'payer' => 'ผู้ชำระ', 'parties' => 'ผู้ส่ง / ผู้รับ', 'sender' => 'ผู้ส่ง', 'receiver' => 'ผู้รับ', 'status' => 'สถานะ', 'total_amount' => 'มูลค่ารวม', 'paid_amount' => 'ยอดชำระแล้ว', 'outstanding_amount' => 'ยอดคงค้าง', 'discount_amount' => 'ส่วนลด', 'tax_amount' => 'ภาษี', 'invoice_number' => 'เลขที่ Invoice', 'invoice_date' => 'วันที่ Invoice', 'receipt_number' => 'เลขที่ Receipt', 'receipt_date' => 'วันที่ Receipt'],
    'payer_role' => ['sender' => 'ผู้ส่ง', 'receiver' => 'ผู้รับ'],
    'actions' => ['search' => 'ค้นหา', 'clear' => 'ล้างตัวกรอง', 'refresh' => 'โหลดใหม่', 'refreshing' => 'กำลังโหลด', 'view_detail' => 'ดูรายละเอียด', 'back_to_list' => 'กลับไป Payment Center'],
    'empty' => ['title' => 'ยังไม่มีรายการชำระเงิน', 'description' => 'ไม่พบรายการสำหรับ Client Account หรือเงื่อนไขที่เลือก'],
    'unavailable' => ['title' => 'ไม่สามารถโหลดข้อมูลการชำระเงิน'],
    'errors' => ['authentication' => 'ไม่สามารถยืนยันตัวตนกับ Sisahygo Core ได้ กรุณาตรวจสอบการตั้งค่า API', 'authorization' => 'Client Account นี้ไม่มีสิทธิ์ดูข้อมูลการชำระเงิน', 'connection' => 'ไม่สามารถเชื่อมต่อ Sisahygo Core ได้ชั่วคราว กรุณาลองใหม่อีกครั้ง', 'not_found' => 'ไม่พบรายการชำระเงินที่ร้องขอ', 'validation' => 'ข้อมูลตัวกรองไม่ถูกต้อง', 'rate_limited' => 'มีการเรียกใช้งานถี่เกินไป กรุณารอสักครู่แล้วลองใหม่', 'server' => 'Sisahygo Core ไม่พร้อมให้บริการชั่วคราว กรุณาลองใหม่อีกครั้ง', 'unexpected' => 'ไม่สามารถโหลดข้อมูลการชำระเงินได้ กรุณาลองใหม่อีกครั้ง', 'no_credential' => 'ยังไม่ได้ตั้งค่า API credential สำหรับ Client Account นี้'],
    'validation' => ['date_to_after_or_equal' => 'วันที่สิ้นสุดต้องไม่น้อยกว่าวันที่เริ่มต้น', 'payment_status' => 'สถานะการชำระเงินไม่ถูกต้อง', 'payment_type' => 'ประเภทการชำระเงินไม่รองรับ', 'payment_identifier' => 'รูปแบบเลขที่รายการชำระเงินไม่ถูกต้อง'],
    'pagination' => ['page' => 'หน้า :page', 'total' => 'ทั้งหมด :total รายการ', 'previous' => 'ก่อนหน้า', 'next' => 'ถัดไป'],
    'loading' => 'กำลังโหลดข้อมูลการชำระเงิน',
    'fallback' => ['empty' => '—', 'unknown' => 'ไม่ระบุ'],
];
