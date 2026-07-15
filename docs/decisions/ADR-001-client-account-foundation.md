# ADR-001: Client Account Foundation

## สถานะ

Approved

## บริบท

Sisahygo Connect ต้องมี account model ที่พร้อมสำหรับ SaaS เชิงพาณิชย์ รองรับผู้ใช้หลายคนและ Sisahygo customers หลายรายการภายใต้องค์กรเดียว

## การตัดสินใจ

สร้าง Client Account domain tables สำหรับ accounts, users, customer links, capabilities และ activity logs ห้าม model links เป็น `sender`, `receiver` หรือ `both` แต่ให้ใช้ capability columns ในแต่ละ customer link แทน

## ผลกระทบ

ในอนาคตสามารถเพิ่ม customer roles ได้โดยไม่ต้องออกแบบ relationship model ใหม่ และยังรักษา authentication เดิมไว้ครบถ้วน