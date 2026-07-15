# Payment Types

Sisahygo payment types เป็นแหล่งอ้างอิงหลัก และ Sisahygo Connect ห้ามตีความใหม่เอง

## Sender Payment Types

| Code | Thai | English Meaning | Visibility |
| --- | --- | --- | --- |
| H | เงินสดต้นทาง | The sender pays cash at the origin. | Sender-linked Client Account only |
| T | เงินโอนต้นทาง | The sender transfers money to pay freight charges. | Sender-linked Client Account only |
| F | วางบิลต้นทาง | Origin Billing; accounts receivable against the sender. | Sender-linked Client Account only |

## Receiver Payment Types

| Code | Thai | English Meaning | Visibility |
| --- | --- | --- | --- |
| E | เก็บเงินปลายทาง | The receiver pays the freight charge after delivery. | Receiver-linked Client Account only |
| L | วางบิลปลายทาง | Destination Billing; accounts receivable against the receiver. | Receiver-linked Client Account only |

## Payment Status

ใช้ field `payment_status` ที่ Sisahygo API ส่งกลับมา

| Code | Thai | English |
| --- | --- | --- |
| 0 | ค้างชำระ | Outstanding |
| 1 | ชำระแล้ว | Paid |

ห้ามเพิ่ม payment status field หรือชุดคำเรียกสถานะอีกชุดหนึ่ง