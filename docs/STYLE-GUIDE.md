# Documentation Style Guide

เอกสารนี้เป็นมาตรฐานหลักสำหรับการเขียนและดูแล documentation ของ Sisahygo Connect ทุกไฟล์ควรอ่านเหมือนเขียนโดยทีม technical writing ภาษาไทยทีมเดียวกัน: กระชับ ชัดเจน ถูกต้องทางเทคนิค และใช้งานได้จริง

## 1. Documentation Goals

- อธิบายระบบให้ทีม Product, Engineering, Integration และ Business เข้าใจตรงกัน
- บันทึก business rules, architecture decisions และ API contracts โดยไม่กำกวม
- ทำให้ onboarding และ sprint handoff เร็วขึ้น
- ลดการใช้คำหลายแบบสำหรับความหมายเดียวกัน
- เก็บรายละเอียดทางเทคนิคที่จำเป็น โดยไม่เขียนยาวเกินจำเป็น

## 2. Intended Audiences

กลุ่มผู้อ่านหลักคือ Thai developers, system analysts, product owners, project managers และ business users ที่เกี่ยวข้องกับ Sisahygo Connect

ให้เขียนโดยสมมติว่าผู้อ่านรู้บริบทโลจิสติกส์และ software delivery ระดับหนึ่ง แต่ไม่ควรบังคับให้ผู้อ่านต้องอ่าน source code ก่อนจึงเข้าใจเอกสาร

## 3. Language And Tone

- ใช้ภาษาไทยแบบมืออาชีพ เป็นธรรมชาติ และอ่านง่าย
- ใช้ active voice เมื่อทำได้
- หลีกเลี่ยงภาษาราชการหรือประโยคยาวเกินจำเป็น
- หลีกเลี่ยง English ที่ไม่จำเป็นเมื่อมีคำไทยที่ชัดเจน
- คง English terms ที่เป็น official domain หรือ technical names
- ถ้า English term อาจไม่ชัดเจน ให้อธิบายครั้งแรกในเอกสารหรือในเอกสารที่เป็น authority แล้วไม่ต้องอธิบายซ้ำทุกไฟล์

ตัวอย่าง:

Client Account คือบัญชีลูกค้าที่ใช้กำหนดขอบเขตผู้ใช้งาน สิทธิ์ และข้อมูลของลูกค้าในระบบ

## 4. Thai Technical Writing Rules

- ใช้คำไทยที่ตรงความหมายก่อน ถ้าไม่ทำให้ความหมายทางเทคนิคเสีย
- อย่าแปล identifiers เช่น class names, API fields, route names หรือ environment variables
- แยกประโยคยาวเป็นหลายประโยคสั้น
- ใช้คำเดียวกันกับ concept เดิมเสมอ
- เขียนข้อจำกัดและเงื่อนไขแบบตรงไปตรงมา
- อย่าใช้คำคลุมเครือ เช่น “น่าจะ”, “ประมาณว่า”, “อาจจะได้” ถ้าเป็น rule หรือ contract

## 5. Approved Domain Terminology

ใช้คำต่อไปนี้ให้สม่ำเสมอ:

| Concept | Approved Term |
|---|---|
| Client Account | Client Account / บัญชีลูกค้า |
| Sender | ผู้ส่งสินค้า |
| Receiver | ผู้รับสินค้า |
| Order Checking | Order Checking / รายการตรวจรับสินค้า |
| Shipment | รายการส่งสินค้า |
| Shipment History | ประวัติการขนส่ง |
| Shipment Tracking | Shipment Tracking / ติดตามสถานะสินค้า |
| Item line | รายการสินค้า |
| Unit | หน่วยนับ |
| Client reference | เลขอ้างอิงลูกค้า |
| Capability | สิทธิ์การใช้งาน |
| Data scope | ขอบเขตข้อมูล |
| Tenant isolation | การแยกข้อมูลระหว่างลูกค้า |
| Payment status | สถานะการชำระเงิน |
| Outstanding | ค้างชำระ |
| Paid | ชำระแล้ว |
| Origin cash | เงินสดต้นทาง |
| Origin transfer | เงินโอนต้นทาง |
| Destination collect | เก็บเงินปลายทาง |
| Origin billing | วางบิลต้นทาง |
| Destination billing | วางบิลปลายทาง |
| API integration | การเชื่อมต่อ API |
| Reference data | ข้อมูลอ้างอิง |
| Sisahygo core system | ระบบหลัก Sisahygo |
| Sisahygo Core API | Sisahygo Core API |

Terms ที่คง English ได้: Sisahygo Connect, Client Account, Order Checking, Bulk Order, Shipment Tracking, Payment, API, API Key, Endpoint, Sandbox, Production, Laravel, Livewire, DTO, Policy, Middleware, Feature Contract, ADR, HTTP Client, Repository, Domain, Service, Query และ Scope

## 6. Heading Hierarchy

ใช้ heading hierarchy นี้:

```markdown
# Document Title

## Major Section

### Subsection

#### Detailed Subsection
```

ห้ามมี H1 มากกว่าหนึ่งหัวข้อในเอกสารเดียว ห้ามข้ามระดับ heading และหลีกเลี่ยง heading ลึกกว่า `####` เว้นแต่จำเป็นจริง ๆ

## 7. Standard Document Structure

ไม่ต้องบังคับให้ทุกไฟล์มีโครงสร้างเดียวกัน ให้เลือกโครงสร้างตามชนิดเอกสารและรักษา local context ที่จำเป็น

Metadata block ใช้เมื่อเอกสารมี status, owner, version หรือ date ที่ควรรู้:

| รายการ | รายละเอียด |
|---|---|
| สถานะ | Draft / Review / Approved / Implemented / Deprecated |
| ผู้เกี่ยวข้อง | Product / Engineering / Integration |
| เวอร์ชัน | 1.0 |
| อัปเดตล่าสุด | YYYY-MM-DD |

เอกสารสั้นมากไม่จำเป็นต้องมี metadata

## 8. Markdown Formatting Rules

- เว้นหนึ่งบรรทัดก่อนและหลัง headings
- เว้นหนึ่งบรรทัดก่อนและหลัง lists
- เว้นหนึ่งบรรทัดก่อนและหลัง code blocks
- หลีกเลี่ยง horizontal rules ที่ไม่จำเป็น
- ใช้ bold เฉพาะข้อความที่สำคัญจริง
- ห้ามใช้ emoji ใน architecture, ADR, security หรือ engineering documents
- Product หรือ onboarding documents ใช้ emoji ได้จำกัด เฉพาะเมื่อช่วยให้อ่านง่ายขึ้นจริง
- หลีกเลี่ยง HTML ถ้า Markdown แสดงผลได้ชัดเจนอยู่แล้ว

## 9. Table Formatting

ใช้ tables เมื่อเปรียบเทียบข้อมูลที่มีโครงสร้าง เช่น fields, API contracts, status mappings, roles/capabilities หรือ implementation options

อย่าใช้ table สำหรับย่อหน้ายาว ๆ ใช้ headers ที่สั้นและอ่านง่าย

ตัวอย่าง:

| Field | ความหมาย | Required |
|---|---|---:|
| `client_reference_no` | เลขอ้างอิงลูกค้า | Yes |

## 10. Lists And Numbered Procedures

- ใช้ bullet lists สำหรับ concepts ที่ไม่มีลำดับ
- ใช้ numbered lists เฉพาะขั้นตอนที่ต้องทำตามลำดับ
- ใช้ checklists เฉพาะรายการตรวจสอบที่ actionable
- อย่าใช้ nested lists ลึกเกินจำเป็น

## 11. Notes, Warnings, And Important Callouts

ใช้ callout labels เหล่านี้เท่านั้น:

> **หมายเหตุ:** ข้อมูลเพิ่มเติมที่ช่วยให้เข้าใจบริบท

> **สำคัญ:** กฎหรือข้อกำหนดที่ต้องปฏิบัติตาม

> **คำเตือน:** ความเสี่ยงที่อาจทำให้ข้อมูลผิดหรือเกิดปัญหาด้านความปลอดภัย

> **ข้อจำกัด:** สิ่งที่ระบบยังไม่รองรับ

อย่าสร้าง callout style ใหม่เองในแต่ละไฟล์

## 12. Code Blocks

- ระบุ language เมื่อเหมาะสม เช่น `bash`, `php`, `env`, `blade`, `json`
- ห้ามแปล code examples
- ห้ามเปลี่ยน command, JSON, PHP, SQL หรือ URLs ระหว่าง rewrite เอกสาร
- ใช้ inline code สำหรับ identifiers สั้น ๆ

## 13. API Endpoints And Field Names

ห้ามแปลหรือเปลี่ยน API endpoints, field names, JSON keys, enum values, HTTP status codes และ capability keys

ใช้ inline code formatting เช่น:

- `POST /order-checkings`
- `GET /receivers`
- `client_reference_no`
- `order.create`
- `SISAHYGO_API_ENVIRONMENT`

## 14. Cross-References And Links

ใช้ relative links ภายใน Repository ห้ามใช้ absolute local file paths ของเครื่องผู้พัฒนา

ใช้ link text ที่บอกความหมายชัดเจน ไม่ใช้ข้อความ link ที่ไม่สื่อความหมาย

ตัวอย่าง:

[ดูรายละเอียด Authorization](architecture/authorization.md)

ตรวจสอบให้ relative links resolve ได้เมื่อทำได้

## 15. Diagrams And Text Diagrams

- คง diagrams เดิมไว้ถ้ายังถูกต้อง
- ถ้าใช้ text diagram ให้สั้นและอ่านได้ใน Markdown
- ถ้า diagram ซับซ้อน ให้ใช้ Mermaid เฉพาะเมื่อช่วยให้เข้าใจมากขึ้น
- ห้ามใช้ diagram เพื่อแทนรายละเอียดที่ต้องเป็น business rule หรือ API contract

## 16. ADR Format

ADR ใช้โครงสร้างนี้:

```markdown
# ADR-XXX: Decision Title

## สถานะ

## บริบท

## การตัดสินใจ

## เหตุผล

## ทางเลือกที่พิจารณา

## ผลกระทบเชิงบวก

## ผลกระทบเชิงลบ

## ความเสี่ยง

## งานต่อเนื่อง

## เอกสารที่เกี่ยวข้อง
```

ถ้า ADR เดิมสั้นมาก อาจใช้เฉพาะ `สถานะ`, `บริบท`, `การตัดสินใจ` และ `ผลกระทบ` ได้ แต่ห้ามเปลี่ยน decision เดิม

## 17. Feature Contract Format

Feature Contract ควรมีหัวข้อหลักเหล่านี้:

1. Feature Summary
2. Business Goal
3. Scope
4. Out of Scope
5. Eligible Users and Accounts
6. User Stories
7. Business Flow
8. UX Flow
9. Business Rules
10. Request Contract
11. Response Contract
12. Validation
13. Security and Tenant Isolation
14. Error Handling
15. Success Handling
16. UI States
17. Localization
18. Responsive and Accessibility
19. Test Scenarios
20. Product Metrics
21. Definition of Done
22. Dependencies
23. Open Questions
24. Implementation Order

Feature Contract ที่มีรายละเอียดมากกว่าโครงสร้างนี้ได้ แต่ต้องไม่ลดทอน API contracts, business rules หรือ security rules

## 18. Architecture Document Format

Architecture documents ควรตอบคำถามเหล่านี้ให้ชัด:

1. วัตถุประสงค์
2. บริบท
3. Architecture Overview
4. Components
5. Data Flow
6. Security
7. Error Handling
8. Performance
9. Extensibility
10. Trade-offs
11. Dependencies
12. เอกสารที่เกี่ยวข้อง

## 19. Business Rule Document Format

Business rule documents ควรมี:

1. ภาพรวม
2. คำนิยาม
3. กฎธุรกิจ
4. เงื่อนไข
5. ตัวอย่าง
6. กรณีพิเศษ
7. ข้อจำกัด
8. ผลกระทบต่อระบบ
9. Test Scenarios
10. เอกสารที่เกี่ยวข้อง

## 20. Product Document Format

Product documents ควรมี:

1. วัตถุประสงค์
2. กลุ่มผู้ใช้งาน
3. ปัญหาที่ต้องการแก้
4. คุณค่าทางธุรกิจ
5. ขอบเขต
6. สิ่งที่อยู่นอกขอบเขต
7. หลักการสำคัญ
8. ตัวชี้วัดความสำเร็จ
9. ความเสี่ยงหรือข้อจำกัด
10. งานที่เกี่ยวข้อง

## 21. Integration Document Format

Integration documents ควรมี:

1. ภาพรวม
2. Authentication
3. Environment
4. Endpoint
5. Request
6. Response
7. Error Mapping
8. Retry and Timeout
9. Security
10. Logging
11. Testing
12. Known Limitations
13. เอกสารที่เกี่ยวข้อง

## 22. File Naming Rules

- ใช้ lowercase kebab-case สำหรับไฟล์ documentation ใหม่
- คง ADR numbering เดิมไว้
- อย่า rename ไฟล์เดิมถ้าอาจทำให้ references แตก
- ถ้าควร rename แต่มีความเสี่ยง ให้บันทึกเป็น documentation debt แทน

ตัวอย่าง:

- `order-checking.md`
- `payment-visibility.md`
- `client-account-selection.md`

## 23. Date And Version Formatting

- ใช้วันที่แบบ `YYYY-MM-DD`
- ใช้ version แบบ `1.0`, `1.1` หรือ semantic version เมื่อจำเป็น
- หลีกเลี่ยงคำวันที่สัมพันธ์ เช่น “วันนี้”, “เมื่อวาน”, “เร็ว ๆ นี้” ในเอกสารระยะยาว

## 24. Status Labels

ใช้ status labels ต่อไปนี้:

- Draft
- Review
- Approved
- Implemented
- Deprecated

ถ้าสถานะเดิมไม่ชัดเจน ให้คงค่าเดิมไว้และรายงาน ไม่ควรเดา

## 25. Document Maintenance Rules

- อัปเดตเอกสารเมื่อ business rules, API contracts, architecture decisions หรือ workflows เปลี่ยน
- เมื่อพบ duplicate content ที่ยาวเกินจำเป็น ให้เก็บ authority ไว้ในไฟล์ที่เหมาะสม และใช้ summary พร้อม relative link ในไฟล์อื่น
- ตรวจ broken relative links ก่อนส่งมอบ
- ตรวจว่าไม่มี absolute local file paths
- ตรวจว่า Laravel version, canonical components และ API boundary ยังตรงกับ project standards
- ห้ามเปลี่ยน business rules, API contracts หรือ ADR decisions ระหว่าง standardization