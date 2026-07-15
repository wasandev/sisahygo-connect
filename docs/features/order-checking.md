# Order Checking Feature Contract

| รายการ | รายละเอียด |
|---|---|
| สถานะ | Draft |
| Scope | Single Order Checking only |
| อัปเดตล่าสุด | 2026-07-15 |

เอกสารนี้กำหนด Feature Contract ด้าน Product, Domain, UX, Security, Integration และ Testing สำหรับ workflow แรกของ Order Checking ใน Sisahygo Connect เอกสารนี้เป็นเอกสารสัญญาเท่านั้น ไม่ได้ implement feature, ไม่สร้าง database schema, ไม่เรียก live API และไม่เปลี่ยน business logic เดิม

## 1. Feature Summary

Single Order Checking ช่วยให้ลูกค้าที่ authenticated แล้วส่งรายการตรวจรับสินค้า 1 รายการจาก Sisahygo Connect ไปยัง Sisahygo ได้

เมื่อ external Sisahygo Client API รับคำขอสำเร็จ Sisahygo จะสร้าง Order Checking record โดย record ที่รับเข้าระบบจะเริ่มด้วย `order_status = checking` และอยู่ในสถานะนี้จนกว่าเจ้าหน้าที่ Sisahygo จะตรวจสอบและยืนยัน

UI ของ Connect ต้องนำเสนอ workflow นี้ด้วยภาษาลูกค้า ไม่ใช่ฟอร์ม API label ภาษาไทยสำหรับ `checking` คือ `รอตรวจสอบ`

## 2. Business Goal

Feature นี้มีเป้าหมายเพื่อลดงานรับรายการแบบ manual ลดเวลารอของลูกค้า และเพิ่มความมั่นใจว่ารายการที่ส่งเข้าถึง Sisahygo อย่างปลอดภัย

ผลลัพธ์ที่ลูกค้าควรได้รับคือ สามารถส่งรายการตรวจรับสินค้า 1 รายการพร้อมผู้ส่งสินค้า ผู้รับสินค้า รายการสินค้า และเลขอ้างอิงที่ถูกต้อง แล้วเห็นชัดเจนว่า Sisahygo ได้รับรายการเพื่อรอตรวจสอบแล้ว

## 3. Scope

สิ่งที่รวมอยู่ใน Sprint 2A implementation หลังจาก contract นี้ได้รับอนุมัติ:

- Authenticated Single Order Checking page
- การบังคับใช้ current Client Account และ selected account
- การตรวจ capability `order.create`
- Sender authorization จาก active Client Account customer links ที่มี `can_send = true`
- Receiver search/selection ด้วยข้อมูลผู้รับสินค้าที่ authorized หรือ available ผ่าน Sisahygo API ตามที่ API รองรับ
- รายการสินค้าอย่างน้อยหนึ่งรายการ
- Client reference number และ order remark
- Review step ภายในหน้าเดียวกัน
- Safe POST submission ผ่าน Sisahygo integration boundary
- สถานะ success, validation, recoverable error และ unknown-result ที่ปลอดภัย
- Thai-first localization พร้อม English fallback strings
- Automated tests ที่ใช้ faked HTTP และ fixtures

## 4. Out Of Scope

สิ่งที่ไม่รวมใน Sprint 2A:

- Bulk Order Checking
- Excel upload
- Shipment Tracking UI implementation
- Payments, reports, invoices หรือ payment status changes
- Local order, shipment, payment, product master, receiver master หรือ customer master tables
- Direct database access ไปยัง Sisahygo production tables
- Manual receiver creation เว้นแต่ Core API documentation จะยืนยันชัดเจนก่อน implementation
- Product/unit lookup endpoints เว้นแต่ Core API documentation จะยืนยันชัดเจนก่อน implementation
- Automatic POST retry หลัง timeout หรือ server failure
- Analytics implementation Product metrics ในเอกสารนี้เป็นเพียงเป้าหมาย

## 5. Eligible Users And Accounts

ผู้ใช้เปิดและ submit Single Order Checking ได้เฉพาะเมื่อเงื่อนไขทั้งหมดนี้เป็นจริง:

- ผู้ใช้ authenticated แล้ว
- มี current Client Account ที่ถูกต้องจาก `client.account` Middleware เดิม
- ผู้ใช้มี active membership ใน Client Account ที่เลือก
- Client Account ที่เลือกอยู่ในสถานะ active
- Client Account ที่เลือกมี active encrypted Sisahygo API credential สำหรับ environment ที่ตั้งค่าไว้
- Client Account ที่เลือกมี capability `order.create`
- Client Account ที่เลือกมี active customer link อย่างน้อยหนึ่งรายการที่ `can_send = true`

Receiver-only Client Accounts ห้ามสร้าง Order Checking records ส่วน Client Account ที่เป็นได้ทั้งผู้ส่งสินค้าและผู้รับสินค้า สามารถสร้าง Order Checking ได้เฉพาะผ่าน authorized sender customer link เท่านั้น

API credential ห้ามถูกตีความเป็น sender identity Sender identity ต้องมาจาก authorized sender customer link ที่เลือก

## 6. User Stories

- ในฐานะ account owner ฉันต้องการสร้าง checking request หนึ่งรายการ เพื่อให้ Sisahygo ตรวจสอบและยืนยัน shipment ของฉัน
- ในฐานะ operator ฉันต้องการให้ระบบเลือกผู้ส่งสินค้าให้อัตโนมัติเมื่อมี valid sender เพียงรายเดียว เพื่อทำงานได้เร็วขึ้น
- ในฐานะ operator ที่มี sender accounts หลายรายการ ฉันต้องการเลือกผู้ส่งสินค้าเองอย่างชัดเจน เพื่อให้ shipment ถูกสร้างใต้ customer identity ที่ถูกต้อง
- ในฐานะ viewer ฉันต้องไม่สามารถสร้าง Order Checking request ได้ ถ้า account ของฉันไม่มี create capability
- ในฐานะ accounting user ฉันต้องไม่สามารถสร้าง order ได้เพียงเพราะฉันดู Payment information ได้
- ในฐานะผู้ใช้ของ receiver-only account ฉันต้องเห็น unavailable message ที่ปลอดภัย แทนที่จะเห็น submission form
- ในฐานะ mobile user ฉันต้องการฟอร์มที่แบ่งเป็น cards สั้น ๆ เพื่อทำงานให้จบได้โดยไม่ต้องอ่านคู่มือ
- ในฐานะผู้ใช้ที่เจอ timeout ฉันต้องการ next steps ที่ชัดเจนและไม่ทำให้สร้าง duplicate order โดยไม่ตั้งใจ

## 7. Business Flow

1. ผู้ใช้เปิดหน้า Order Checking
2. Connect resolve current Client Account
3. Connect validate membership, account status, `order.create`, active credential และ sender availability
4. Connect resolve authorized sender customer links
5. ถ้ามี sender เพียงหนึ่งรายการ Connect เลือกให้อัตโนมัติ
6. ถ้ามี senders หลายรายการ ผู้ใช้ต้องเลือกเอง
7. Connect ให้ผู้ใช้ search/select authorized receiver
8. ผู้ใช้เพิ่มรายการสินค้าอย่างน้อยหนึ่งรายการ
9. ผู้ใช้กรอกหรือยอมรับ `client_reference_no` ตาม reference policy ที่ยืนยันแล้ว
10. ผู้ใช้ตรวจทาน sender, receiver, items, reference number, remark และ environment indication
11. ผู้ใช้ submit หนึ่งครั้ง
12. Connect สร้าง API payload ฝั่ง server จาก form state และ authorized identity ที่ validate แล้ว
13. Sisahygo API รับหรือปฏิเสธคำขอ
14. Connect แสดง safe success, validation, recoverable error หรือ unknown-result state

## 8. UX Flow

Production page ต้องใช้ four-card workflow ที่อนุมัติแล้ว:

1. `ผู้รับสินค้า`
2. `รายการสินค้า`
3. `หมายเหตุและเลขอ้างอิง`
4. `ตรวจทานและยืนยัน`

Workflow นี้ไม่ใช่ multi-page wizard Cards เรียงซ้อนแนวตั้งบน mobile และบน desktop อาจเพิ่ม supporting summary panel ได้

Primary submit action label คือ:

`ส่งรายการตรวจรับสินค้า`

ผู้ใช้สามารถย้อนกลับไปแก้ไข sections ก่อน submission ได้ ไม่จำเป็นต้องมี confirmation modal แยก เพราะ card ที่ 4 เป็น confirmation surface อยู่แล้ว

## 9. Sender Rules

Sender identity เป็นข้อมูลที่ server เป็นเจ้าของ

Rules:

- Authorized senders มาจาก active `client_account_customers` records ที่ `can_send = true` และ `is_active = true`
- ถ้ามี authorized sender เพียงหนึ่งรายการ ให้เลือกให้อัตโนมัติและแสดง read-only sender summary
- ถ้ามี authorized senders หลายรายการ ต้องบังคับให้เลือกก่อน submit
- ถ้าไม่มี authorized sender ให้ block form พร้อม Thai copy ที่ปลอดภัย
- Browser ต้องไม่สามารถ submit arbitrary sender customer ID ได้
- Server ต้อง revalidate sender ที่ submit มากับ current `SisahygoIntegrationContext` ก่อนสร้าง API request
- ห้าม infer sender selection จาก API credential

Recommended blocked copy:

`บัญชีลูกค้านี้ยังไม่มีสิทธิ์ส่งสินค้า กรุณาติดต่อผู้ดูแลบัญชี Sisahygo Connect`

## 10. Receiver Rules

Receiver data ต้องไม่เปิดเผย global customer master

Rules:

- Receiver search ต้องใช้เฉพาะ authorized หรือ API-available receiver data
- Read-only Sandbox verification ปัจจุบันยืนยันว่า `GET /receivers` ส่ง root `data` array พร้อม receiver fields ได้แก่ `customer_rec_id`, `to_customer_name`, `to_customer_phone` และ `branch_rec_id`
- `ReceiverSummary` DTO ปัจจุบันรองรับเฉพาะ `customerId`, `name` และ optional `phone`
- `branch_rec_id` พบใน Sandbox observations แต่ยังไม่มีใน DTO ปัจจุบัน
- ก่อนเขียน submission code ต้องตัดสินใจว่า branch identity จำเป็นต่อ Single Order Checking หรือไม่
- Receiver-only authorization สำหรับ `GET /receivers` ยังไม่ได้ verify และห้าม claim ว่ารองรับแล้ว
- Manual receiver creation ไม่อนุญาต เว้นแต่ Core API documentation จะยืนยัน

Receiver search UX requirements:

- ต้องกำหนดและ enforce minimum search length ค่าเริ่มต้นที่แนะนำคือ 2 Thai/English characters ระหว่างรอ API capability confirmation
- ใช้ debounce ค่าเริ่มต้นที่แนะนำคือ 300 ถึง 500 ms
- แสดง loading, no-results, API-unavailable, selected และ change states
- แสดงข้อมูลระบุตัวผู้รับสินค้าที่เพียงพอเพื่อป้องกันการเลือกผิด โดยไม่เปิดเผย full addresses หรือ sensitive payloads
- Server ต้อง revalidate selected receiver identity ก่อน submission

## 11. Product And Unit Rules

ผู้ใช้ต้องเพิ่มรายการสินค้าอย่างน้อยหนึ่งรายการ

สถานะที่ยืนยันได้จาก implementation ปัจจุบัน:

- ยังไม่มี confirmed product lookup endpoint ใน codebase
- ยังไม่มี confirmed unit lookup endpoint ใน codebase
- Shipment Sandbox responses แสดง item field names เช่น `product_id`, `product_name`, `unit_id`, `unit`, `amount`, `remark`, `client_line_id`, `client_item_no` และ `client_product_code` แต่ข้อมูลเหล่านี้มาจาก `GET /shipments` ไม่ใช่ confirmed Order Checking POST request contract

Item rules สำหรับ Sprint 2A implementation:

- Minimum item count: 1
- แต่ละ item ต้องมี product reference หรือ product text ตาม API contract ที่ยืนยันแล้ว
- แต่ละ item ต้องมี unit reference หรือ unit text ตาม API contract ที่ยืนยันแล้ว
- Quantity/amount ต้องเป็นค่าบวก
- Item remark เป็น optional เว้นแต่ API จะบังคับ
- Client item reference เป็น optional เว้นแต่ API จะยืนยัน support และ validation
- Remove item action ต้องรักษา validation state ของ items ที่เหลือ
- Duplicate products อนุญาตเฉพาะเมื่อ API/business contract อนุญาตหลาย lines ที่แยกด้วย units, remarks หรือ client item references
- Item-level API validation errors ต้อง map ไปยัง item row/card ที่ถูกต้อง

Mobile layout ต้องแสดง item แต่ละรายการเป็น compact editable block ส่วน desktop อาจใช้ grid ที่ dense ขึ้นได้ หาก labels, focus states และ item-level errors ยังชัดเจน

## 12. Request Contract

Exact Single Order Checking POST payload ยังไม่ยืนยันครบจาก repository code หรือ read-only Sandbox verification ตารางนี้เป็น Connect-side contract candidate ก่อน implementation ต้อง verify final API field names กับ authoritative Core API documentation ก่อนเขียน endpoint DTO/mapper

| Concept | Candidate API name | Thai label | Type | Required | Source | Validation | Visible to user | Generated | Security |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Client reference | `client_reference_no` | เลขอ้างอิงลูกค้า | string | Required unless API confirms optional | User-entered or generated by Connect | Max length/format pending API confirmation | Yes | Possibly | ใช้ป้องกัน duplicate/reconciliation ไม่ใช่ tenant key |
| Sender customer | unconfirmed, likely sender/customer reference | ผู้ส่งสินค้า | integer/string | Required | Server from authorized sender link | Must be in `authorizedSenderCustomerIds` | Summary only | No | Browser ห้ามควบคุม arbitrary sender ID |
| Receiver customer | unconfirmed, likely receiver/customer reference | ผู้รับสินค้า | integer/string | Required | Selected receiver, server revalidated | Must be authorized or API-available under selected account | Yes, as safe summary | No | ห้ามเปิดเผย global customer data |
| Order remark | `remark` or unconfirmed equivalent | หมายเหตุ | string/null | Optional unless API requires | User input | Max length pending API confirmation | Yes | No | Sanitize/display safely |
| Items | `items` | รายการสินค้า | array | Required | User input | At least one valid line | Yes | No | Validate every line server-side |
| Product reference | unconfirmed; observed shipment aliases include `product_id`, `client_product_code` | สินค้า | integer/string | Required pending API confirmation | User input/lookup | Must match reference-data rule if lookup exists | Yes | No | Do not trust browser-only values |
| Unit reference | unconfirmed; observed shipment aliases include `unit_id`, `unit` | หน่วย | integer/string | Required pending API confirmation | User input/lookup | Must match reference-data rule if lookup exists | Yes | No | Do not invent unit IDs |
| Amount/quantity | `amount` or unconfirmed equivalent | จำนวน | numeric | Required | User input | Positive, precision pending API confirmation | Yes | No | Server validates |
| Item remark | item `remark` or unconfirmed equivalent | หมายเหตุสินค้า | string/null | Optional | User input | Max length pending API confirmation | Yes | No | Safe display only |
| Client item ref | `client_item_no`, `client_line_id`, or unconfirmed equivalent | เลขอ้างอิงรายการ | string/null | Optional pending API confirmation | User input/generated | Uniqueness pending API confirmation | Optional | Possibly | Useful for reconciliation only |

Browser payload ห้ามมี decrypted credential, raw API Key, user role หรือ internal database ID

## 13. Response Contract

Exact Single Order Checking response shape ยังไม่ยืนยัน

เมื่อ API ส่งข้อมูลกลับมา Connect ควร map และแสดงข้อมูลเหล่านี้เมื่อมี:

- Result status
- `client_reference_no`
- API Order Checking identifier
- Initial `order_status = checking`
- Thai status label `รอตรวจสอบ`
- Accepted timestamp
- Safe user message
- Next action URL หรือ instruction

Connect ห้าม invent field names เอง Implementation ควรสร้าง response DTO ที่รับเฉพาะ confirmed API fields และมอง harmless additional fields เป็น ignored metadata

UI success message ต้องบอกว่า Sisahygo ได้รับ checking request แล้ว ห้ามสื่อว่า shipment ได้รับการอนุมัติ รับขึ้นรถ รับเข้าขนส่ง หรือจัดส่งแล้ว

## 14. Validation Rules

Validation ต้องแยกเป็นหลายชั้น

UI validation:

- Required visible fields
- Basic string length/number checks
- At least one item
- Disable review/submit จนกว่าข้อมูล local จะ valid

Connect server-side validation:

- Authenticated user
- Current Client Account
- Active membership
- Active account
- `order.create` capability
- Active credential สำหรับ selected environment
- Authorized sender
- Receiver selected และ revalidated
- Items revalidated
- Reference number policy enforced

API validation:

- Field names และ types ตาม Core API
- Required reference IDs
- Business constraints ที่ Core API เป็นเจ้าของ

Reference-data validation:

- Receiver reference ต้อง valid สำหรับ selected account/API key/sender relationship
- Product และ unit references ต้อง validate หลังจากยืนยัน authoritative source แล้วเท่านั้น

API validation errors ต้อง map ไปยัง card หรือ item line ที่ถูกต้อง ห้ามแสดง raw API field names หรือ raw API messages ให้ลูกค้าเห็นโดยตรง

## 15. Submission Safety

Single Order Checking เป็น POST workflow และต้องถือว่า non-idempotent จนกว่า Core API จะยืนยันเป็นอย่างอื่น

Required behavior:

- Disable primary submit action ระหว่าง processing
- ป้องกัน double-click duplicate submission
- ป้องกัน repeated browser submission จาก Livewire state เดิม
- ห้าม blind retry POST หลัง timeout, connection failure หรือ 5xx response
- รักษา user-entered form data หลัง validation หรือ recoverable failure
- Clear processing state หลัง known validation/recoverable errors
- เข้าสู่ unknown-result state หลัง timeout หรือ ambiguous server failure
- ให้ safe next step สำหรับ unknown results โดยควรใช้ reconciliation ผ่าน `client_reference_no` หาก Core API รองรับ

## 16. Idempotency And Unknown Results

ADR-009 เป็น authoritative source สำหรับสถานะปัจจุบัน final idempotency storage ถูก defer จนกว่า Core API จะยืนยัน support สำหรับ `Idempotency-Key`, `client_reference_no`, `batch_reference_no`, timeout reconciliation และ unknown-result recovery

Sprint 2A ห้าม claim full idempotency เว้นแต่ Core API จะยืนยัน

Recommended policy ก่อน implementation:

- ถือว่า `client_reference_no` เป็น business reference หลักสำหรับ duplicate prevention และ reconciliation หาก API ยืนยัน
- ห้าม auto-generate reference ใหม่หลัง unknown timeout
- ห้าม submit POST ครั้งที่สองโดยอัตโนมัติ
- แสดง unknown-result screen ที่แนะนำให้ผู้ใช้ตรวจ history/tracking หรือ contact support พร้อม reference เมื่อ reconciliation support พร้อม

Open dependency: ต้องยืนยันว่า Core API enforce uniqueness สำหรับ `client_reference_no` หรือไม่ และ uniqueness scope คือ per Client Account, sender, API Key, environment, day หรือ global

## 17. Error Handling

Error classes ต้อง map เป็น safe UI outcomes

| Scenario | UI level | User behavior | Notes |
| --- | --- | --- | --- |
| Receiver search unavailable | Card | คง form ไว้และให้ retry | ห้ามเปิด raw API payload |
| No receiver results | Card | แสดง empty state | ห้ามแนะนำ global search |
| Invalid receiver | Card | ให้ผู้ใช้เลือกใหม่ | Revalidate server-side |
| Product/unit unavailable | Card/item | Block submit | ห้าม invent reference data |
| Item validation failed | Item | Highlight item ที่ผิด | Preserve lines อื่น |
| Duplicate reference | Reference card | ให้ผู้ใช้แก้หรือ regenerate | เฉพาะเมื่อ API ยืนยัน duplicate error semantics |
| Missing capability | Page | แสดง unavailable state | ไม่มี form submission |
| Receiver-only account | Page | แสดง unavailable state | ไม่แสดง create form |
| API authentication failure | Page/system | ให้ติดต่อ administrator | ห้ามกล่าวถึง key value |
| API authorization failure | Page/system | อธิบายว่า account ไม่ได้รับอนุญาต | ห้ามเปิดเผย external IDs |
| Rate limit | System | ให้รอและ retry | ไม่มี automatic POST retry |
| API unavailable | System | Preserve form และให้ retry ภายหลังถ้ารู้ว่าล้มเหลวแน่นอน | Timeout ยังเป็น unknown-result |
| Timeout unknown | Page/system | ห้าม resubmit แบบ blind | ใช้ reference สำหรับ reconciliation หากรองรับ |
| Unexpected response | System | แสดง generic failure ที่ปลอดภัย | Log เฉพาะ safe metadata |

Error UI ห้ามเปิดเผย API keys, decrypted credentials, full addresses, raw JSON, PHP class names, stack traces หรือ unrestricted customer IDs

## 18. Success Handling

เมื่อ accepted response สำเร็จ UI ควรแสดง:

- Success title ที่ชัดเจน เช่น `ส่งรายการตรวจรับสินค้าแล้ว`
- Status label `รอตรวจสอบ` สำหรับ API status `checking`
- `client_reference_no`
- API Order Checking reference ถ้ามี
- Accepted timestamp ถ้ามี
- Safe next action

Primary next action ควรพาผู้ใช้ไปยัง follow-up ที่มีประโยชน์ที่สุด เช่น Tracking/History เมื่อ feature นั้นมีอยู่ Secondary action อาจเป็น `สร้างรายการใหม่`

Success copy ห้ามสื่อว่า shipment ได้รับ final approval แล้ว

## 19. UI States

Allowed states:

- `initial`: eligibility checked แล้ว form พร้อมหรือถูก block
- `loading_reference_data`: receiver/product/unit data กำลังโหลด
- `editing`: ผู้ใช้กำลังกรอกข้อมูล
- `locally_invalid`: มี visible validation errors
- `ready_for_review`: required local fields ถูกต้องครบ
- `submitting`: POST กำลังทำงาน submit disabled
- `api_validation_failed`: API ส่ง validation errors กลับมาและถูก map เข้า cards/items
- `recoverable_failure`: API unavailable, rate limited หรือ known failed response
- `unknown_result`: timeout หรือ ambiguous response หลัง POST
- `success`: API accepted checking request

Transitions:

- `initial` ไป `editing` หลัง eligibility สำเร็จ
- `editing` ไป `locally_invalid` เมื่อ local validation fail
- `editing` หรือ `locally_invalid` ไป `ready_for_review` เมื่อ input valid
- `ready_for_review` ไป `submitting` ได้เฉพาะผ่าน primary submit action
- `submitting` ไป `api_validation_failed`, `recoverable_failure`, `unknown_result` หรือ `success`
- `api_validation_failed` และ `recoverable_failure` กลับไป `editing` ได้
- `unknown_result` ห้าม auto-return ไป `submitting`
- `success` เริ่ม fresh form ได้เฉพาะหลัง reset state และใช้ reference policy ใหม่แล้ว

## 20. Livewire/Application/Integration Boundaries

โครงสร้าง implementation ที่แนะนำ โดยยังไม่สร้างใน contract phase นี้:

- Livewire page component: ถือ UI state, localized presentation decisions และ form interactions
- Form state object หรือ focused value object: ถือ temporary form data และ local validation structure
- Application service: orchestrate account/capability/sender validation, build integration context, create DTO, call endpoint และ map safe result
- Domain layer: ถือ authorization rules, capability checks และ sender/receiver scope rules
- Integration endpoint: ถือ API path และ transport call
- Request DTO/mapper: แปลง validated Connect state เป็น confirmed API payload
- Response DTO/mapper: แปลง API response เป็น safe application result
- Blade components: presentation only

Future application flow:

`Livewire Page -> Order Checking Application Service -> Client Account/capability/sender validation -> SisahygoIntegrationContext -> OrderCheckingsEndpoint -> Request DTO/Mapper -> Sisahygo API -> Response DTO -> Safe UI result`

Rules:

- ห้ามเรียก API ใน Blade
- ห้ามมี decrypted credentials ใน Livewire public properties
- ห้ามใส่ security decisions ใน Blade
- ห้ามส่ง raw API arrays ไปยัง UI
- หลีกเลี่ยง over-componentizing ก่อน workflow พิสูจน์ว่าต้อง reuse จริง

## 21. Localization

Future implementation ควรเพิ่ม:

- `lang/th/order_checking.php`
- `lang/en/order_checking.php`

Recommended groups:

- `page.title`
- `page.description`
- `sections.receiver`
- `sections.items`
- `sections.reference`
- `sections.review`
- `fields.sender`
- `fields.receiver`
- `fields.client_reference_no`
- `fields.remark`
- `fields.product`
- `fields.unit`
- `fields.amount`
- `actions.submit`
- `actions.add_item`
- `actions.remove_item`
- `actions.change_receiver`
- `loading.receiver_search`
- `validation.*`
- `errors.*`
- `success.*`
- `status.checking`

User-facing Thai copy ในอนาคตไม่ควรถูก hard-code ใน Livewire classes หรือ Blade templates

## 22. Responsive And Accessibility Requirements

Breakpoints:

- 390px: single column, cards stacked, primary action เข้าถึงได้โดยไม่เกิด horizontal scrolling
- 768px: fields สั้น ๆ ใช้ two columns ได้
- 1024px: optional sticky review/summary panel อาจแสดงได้
- 1440px: เพิ่ม scan efficiency โดยยังคุม content width

Accessibility requirements:

- Touch target อย่างน้อยประมาณ 44px สำหรับ buttons และ interactive rows
- Keyboard focus ต้องเห็นชัดทุก control
- Labels ต้องสัมพันธ์กับ inputs
- Validation messages ต้องสัมพันธ์กับ fields และ item rows
- Loading state ต้อง announce สำหรับ receiver search และ submit
- Disabled submit ต้องมี visible state และไม่พึ่งสีอย่างเดียว
- Item row errors ต้องระบุว่า item ใดต้องแก้
- ใช้ `x-connect.*` components เดิมก่อน สร้าง UI components ใหม่เฉพาะเมื่อ workflow พิสูจน์ว่าต้อง reuse

## 23. Security And Tenant Isolation

Security ต้องป้องกัน horizontal privilege escalation

Rules:

- ทุก request เริ่มจาก authenticated user และ selected Client Account
- Tenant-dependent route อยู่หลัง `auth` และ `client.account` Middleware
- ใช้ `SisahygoIntegrationContextBuilder` พร้อม required capability `ClientCapability::OrderCreate`
- Sender และ receiver customer IDs เป็น external references และห้ามเชื่อค่าจาก browser
- Sender ID ต้องถูก assert กับ `authorizedSenderCustomerIds`
- Receiver ID ต้องถูก assert หรือ revalidate กับ authorized/API-available receiver set
- Receiver-only Client Accounts ห้าม submit Order Checking
- Payment capability ห้าม imply order creation capability
- Viewer role ห้าม bypass missing `order.create` capability
- Logs อาจมี correlation ID, endpoint, method, safe status, credential fingerprint, account ID และ duration
- Logs ห้ามมี decrypted API keys, full payloads, full addresses หรือ secrets

## 24. Test Scenarios

Authorization tests:

- Guest ถูก redirect ไป login
- Authenticated user ที่ไม่มี selected Client Account เข้า tenant page ไม่ได้
- Inactive Client Account ถูก block
- Inactive membership ถูก block
- Missing `order.create` ถูก block
- Receiver-only account ถูก block จาก submission
- Sender-and-receiver account submit ได้เฉพาะในฐานะ authorized sender
- Accounting-only capability ไม่อนุญาต order creation

Receiver tests:

- เลือก receiver ได้หนึ่งรายการและ revalidated ได้
- API receiver search loading/no-results/failure states render อย่างปลอดภัย
- Arbitrary receiver ID จาก browser ถูก reject
- Raw receiver payload ไม่ถูกเปิดเผย

Item tests:

- ไม่มี item แล้ว block submit
- Invalid amount แล้ว block submit
- Item-level API validation map ไปยัง item ที่ถูกต้อง
- Remove item แล้วยัง preserve remaining item data และ errors

Submission tests:

- POST ใช้ confirmed endpoint และ `X-Api-Key` ผ่าน client เดิม
- POST ไม่ถูก retry แบบ blind retry
- Double submit ถูก block
- 422 map เป็น validation state
- 401/403/429/5xx map เป็น safe UI states
- Timeout หลัง POST map เป็น unknown-result state

Tenant safety tests:

- Sender จาก Client Account อื่นถูก reject
- Receiver จาก Client Account อื่นถูก reject หรือ unavailable
- API credential จาก Client Account อื่นไม่ถูกใช้
- Environment-specific credentials แยกกันอยู่

Success tests:

- Accepted response แสดง `รอตรวจสอบ`
- Success ไม่ claim ว่ามี shipment pickup/transport แล้ว
- Safe references แสดงเฉพาะเมื่อมีข้อมูล

Localization tests:

- Thai strings resolve จาก `lang/th/order_checking.php`
- English fallback strings มีครบ
- Raw API field names ไม่โผล่ใน customer-facing validation messages

## 25. Product Metrics

Metrics เป็น targets เท่านั้น ห้าม implement analytics ใน Sprint 2A เว้นแต่มีอนุมัติแยกต่างหาก

- First-time user สร้าง checking request ได้โดยไม่ต้องอ่านเอกสาร
- Normal completion ต่ำกว่า 2 นาที
- Primary flow ใช้ navigation interactions ไม่เกิน 3 ครั้งหลังเปิดหน้า
- Duplicate submission rate ใกล้ศูนย์
- Validation และ API errors actionable
- Receiver selection สำหรับผู้รับสินค้าที่ใช้บ่อยต้องรู้สึกเร็ว

## 26. Definition Of Done

Sprint 2A implementation ถือว่า done เฉพาะเมื่อ:

- UI ทำตาม approved four-card prototype structure
- ใช้ canonical `x-connect.*` components ตามความเหมาะสม
- Verify mobile และ desktop states แล้ว
- Authentication, dashboard, account selection และ navigation ยังทำงานเหมือนเดิม
- Tenant isolation และ capability checks มี tests ครอบคลุม
- Sender/receiver rules ถูก enforce server-side
- Confirmed API request/response contracts ถูกแทนด้วย DTOs/mappers
- API validation ถูก map อย่างปลอดภัยไปยัง cards/items
- POST ไม่ถูก retry แบบ blind retry
- Implement timeout unknown-result behavior แล้ว
- มี Thai และ English localization files
- Automated tests ผ่านด้วย `Http::fake()` และ fixtures
- Documentation ตรงกับ implementation
- ไม่มี secrets, raw payloads หรือ API keys ใน logs หรือ UI
- ไม่มี unresolved high-severity API ambiguity สำหรับ scope ที่ implement

## 27. Dependencies

Confirmed existing dependencies:

- Laravel, Livewire 3, Tailwind CSS, Laravel Sail
- Existing authentication และ application shell
- Client Account Foundation
- Encrypted per-Client-Account Sisahygo API credentials
- `SisahygoIntegrationContext` และ `SisahygoApiClient`
- Current capabilities รวม `order.create` และ `order.bulk`
- Current receiver endpoint และ mapper

Unconfirmed external dependencies:

- Authoritative Single Order Checking POST endpoint
- Final Single Order Checking request fields
- Final Single Order Checking response fields
- Core API uniqueness/idempotency behavior สำหรับ `client_reference_no`
- Product reference source
- Unit reference source
- Receiver branch requirement
- Manual receiver creation policy
- Receiver-only authorization semantics

## 28. Open Questions

1. Single Order Checking endpoint เป็น `POST /order-checkings` แน่นอนหรือไม่
2. Bulk ใช้ `POST /order-checkings` endpoint เดียวกันด้วย payload ที่ต่างกัน หรือใช้ `POST /order-checkings/bulk`
3. Exact request field names สำหรับ sender, receiver, items, product, unit, amount, remarks และ client item reference คืออะไร
4. API ต้องใช้ `branch_rec_id` สำหรับ receiver selection/submission หรือไม่
5. `client_reference_no` required, optional, generated by clients, generated by Sisahygo หรือรองรับทั้งสองแบบ
6. Uniqueness scope และ duplicate error format ของ `client_reference_no` คืออะไร
7. Core API รองรับ `Idempotency-Key` สำหรับ Order Checking POST หรือไม่
8. Unknown timeout ควร reconcile ด้วย reference number อย่างไร
9. Connect ควรใช้ product และ unit reference lists จากที่ใด
10. API อนุญาต free-text product/unit values หรือใช้เฉพาะ reference IDs
11. Max lengths และ formats ของ remark และ references คืออะไร
12. Exact 422 validation error format สำหรับ nested item errors คืออะไร
13. Response field ใดระบุ created Order Checking record
14. `order_status = checking` ถูกส่งกลับมาใน POST response เสมอ หรือ Connect ต้อง infer หลัง accepted response
15. Receiver-only accounts สามารถ search receivers ได้ หรือดูได้เฉพาะ shipment history

## 29. API Documentation Conflicts

หลักฐานจาก repository ปัจจุบัน:

- `SisahygoApiClient::post()` รองรับ POST และตั้งใจไม่ retry POST
- `tests/Feature/Integrations/Sisahygo/SisahygoHttpClientTest.php` ใช้ `/order-checkings` เฉพาะเพื่อพิสูจน์ POST no-retry behavior
- ยังไม่มี `OrderCheckingsEndpoint`, request DTO, response DTO, mapper หรือ fixture
- Read-only Sandbox verification ครอบคลุมเฉพาะ `GET /receivers` และ `GET /shipments`

External task context ระบุว่า Single และ Bulk อาจใช้ endpoint เดียวกันคือ `POST /order-checkings`

Earlier implementation notes อาจระบุ `POST /order-checkings` สำหรับ Single และ `POST /order-checkings/bulk` สำหรับ Bulk

Contract decision สำหรับ Sprint 2A:

- Single endpoint ถูก document แบบ provisional เป็น `POST /order-checkings` เพราะเป็น endpoint string เดียวที่มีอยู่ใน code/tests และตรงกับ supplied overview
- Endpoint นี้ยังไม่ได้ live-verified และต้องยืนยันกับ authoritative Core API documentation ก่อน implementation
- Bulk endpoint ยัง unresolved และอยู่นอก scope ของ Sprint 2A
- ห้าม implement Bulk route, DTO, UI หรือ endpoint ใน Sprint 2A

Authoritative source ที่ต้องได้ก่อน coding:

- Core API OpenAPI specification, Postman collection, formal endpoint document หรือ controlled Sandbox POST contract verification ที่ใช้ fake/non-production data และได้รับอนุมัติชัดเจน

## 30. Recommended Sprint 2A Implementation Order

1. ยืนยัน Core API contract สำหรับ Single Order Checking POST
2. เพิ่มหรือปรับ integration fixture สำหรับ successful Single Order Checking response
3. เพิ่ม integration DTOs และ mapper tests สำหรับ request/response shape
4. เพิ่ม `OrderCheckingsEndpoint` หลัง `SisahygoApiClient` เดิม
5. เพิ่ม application service สำหรับ Single Order Checking orchestration
6. เพิ่ม Livewire page/form state พร้อม local validation
7. เพิ่ม localization files `lang/th/order_checking.php` และ `lang/en/order_checking.php`
8. แทน placeholder `/order-checking` page ด้วย authenticated Livewire workflow
9. Map API validation/errors ไปยัง cards/items
10. Implement success และ unknown-result UI states
11. เพิ่ม authorization, capability, tenant isolation, receiver, item, submission, localization และ UI tests
12. รัน build และ full test verification

## Referenced Project Paths

- `docs/business/order-checking.md`
- `docs/decisions/ADR-009-idempotent-order-submission.md`
- `docs/integrations/sisahygo/overview.md`
- `docs/integrations/sisahygo/error-handling.md`
- `docs/integrations/sisahygo/sandbox-contract-verification.md`
- `docs/architecture/authorization.md`
- `docs/architecture/client-account-context.md`
- `docs/architecture/api.md`
- `docs/architecture/security.md`
- `docs/architecture/ui.md`
- `docs/product/01-product-principles.md`
- `docs/product/02-information-architecture.md`
- `docs/product/05-wireframes.md`
- `docs/product/07-component-library.md`
- `docs/product/08-mobile-first.md`
- `app/Domain/ClientAccount/Enums/ClientCapability.php`
- `app/Domain/ClientAccount/Models/ClientAccountCustomer.php`
- `app/Integrations/Sisahygo/Support/SisahygoIntegrationContext.php`
- `app/Integrations/Sisahygo/Support/SisahygoIntegrationContextBuilder.php`
- `app/Integrations/Sisahygo/V1/SisahygoApiClient.php`
- `app/Integrations/Sisahygo/V1/Endpoints/ReceiversEndpoint.php`
- `config/sisahygo.php`
- `routes/web.php`
- `resources/views/ux/order-checking.blade.php`
- `resources/views/components/connect/`
- `tests/Fixtures/Sisahygo/V1/`