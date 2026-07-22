# Bulk Order Checking Wireframes

Status: Proposal
Branch reviewed: feature/bulk-order-checking
Audited commit/baseline: 78ce35b6cb21b28023cb939198834bfe7d1592b8 plus uncommitted Sprint 6 implementation
Date: 2026-07-21
Current implementation basis: Sprint 6 Bulk Order Checking source and tests.
Production code changed in Sprint 6.1: No. This document is proposal-only.

## Alternative A: Improved Order Cards

### Initial State

```text
┌────────────────────────────────────────────────────────────┐
│ สร้างรายการตรวจสอบแบบหลายรายการ                         │
│ ส่งได้สูงสุด 50 Orders · 200 Items ต่อ Order              │
│ [กลับไปสร้างรายการเดียว]                                  │
├────────────────────────────────────────────────────────────┤
│ Sticky Batch: Ref [____________] Date [2026-07-21]         │
│ 1 Order · 1 Item · 1 ยังไม่ครบ        [Review] [Submit]   │
├────────────────────────────────────────────────────────────┤
│ Order 1  BC-20260721-AB12  Missing receiver  1 item   [v] │
└────────────────────────────────────────────────────────────┘
```

### Batch With Multiple Orders

```text
┌ Sticky: BATCH-001 · 12 Orders · 18 Items · 2 Errors ──────┐
│ [Add Order] [Go to Errors] [Review]                       │
├────────────────────────────────────────────────────────────┤
│ ✓ Order 1  BC-001  บริษัท A       2 items        [Edit]   │
│ ! Order 2  BC-002  Missing item   0 items        [Open]   │
│ ✓ Order 3  BC-003  บริษัท C       1 item         [Edit]   │
│ ...                                                       │
└────────────────────────────────────────────────────────────┘
```

### Expanded Order With Multiple Items

```text
┌ Order 4  BC-004                         Complete [Collapse]┐
│ Client reference [BC-004____________] Duplicate [ ]         │
│ Receiver [บริษัท รับสินค้าไทย จำกัด] [Change] [Clear]      │
│ Remark [______________________________________________]     │
├─────────────────────────────────────────────────────────────┤
│ Items                                      [Add Item Alt+I] │
│ Product              Unit        Amount    More             │
│ น้ำดื่ม 600 ml       ขวด         [2.5]     [Details] [x]   │
│ กล่องกระดาษ          ลัง         [1]       [Details] [x]   │
└─────────────────────────────────────────────────────────────┘
```

### Validation Error State

```text
┌ Validation summary: 3 issues                              ┐
│ [Order 2: receiver missing] [Order 5 item 1: amount]       │
│ [Duplicate reference BC-008 in Order 8 and Order 9]        │
├────────────────────────────────────────────────────────────┤
│ ! Order 5  BC-005  บริษัท A       1 item        [Open]    │
│ Product: น้ำดื่ม 600 ml  Unit: ขวด  Amount [0] ERROR      │
│ จำนวนต้องไม่น้อยกว่า 0.0001                               │
└────────────────────────────────────────────────────────────┘
```

### Mobile State

```text
┌ Bulk Order Checking ─────────────────────┐
│ BATCH-001 · 12 Orders · 2 Errors          │
│ [Review]                                  │
├───────────────────────────────────────────┤
│ Order 1 ✓                                 │
│ BC-001 · บริษัท A · 2 items               │
│ [Edit]                                    │
├───────────────────────────────────────────┤
│ Order 2 ! Missing receiver                │
│ [Open]                                    │
└───────────────────────────────────────────┘
```

### Review State

```text
┌ Review Batch ก่อนส่ง                                      ┐
│ Batch Ref: BATCH-001        Date: 2026-07-21              │
│ Orders: 12                  Item lines: 18                │
│ Complete: 12                Incomplete: 0                 │
├────────────────────────────────────────────────────────────┤
│ Order | Client Ref | Receiver | Items | Status             │
│ 1     | BC-001     | บริษัท A | 2     | Complete           │
│ 2     | BC-002     | บริษัท B | 1     | Complete           │
└────────────────────────────────────────────────────────────┘
```

### HTTP 201 Result

```text
┌ ส่งสำเร็จทุกประการ                                       ┐
│ API Batch No: APIB202607210001                            │
│ Success: 12 / 12                                          │
├────────────────────────────────────────────────────────────┤
│ Client Ref | Tracking No | Order Status | Message          │
│ BC-001     | 12345       | checking     | สร้างสำเร็จ     │
└────────────────────────────────────────────────────────────┘
```

### HTTP 207 Result

```text
┌ บางรายการสำเร็จและบางรายการล้มเหลว                     ┐
│ Success: 10 · Failed: 5                                  │
│ [All] [Successful] [Failed] [Retry failed only]           │
├───────────────────────────────────────────────────────────┤
│ ✓ BC-001  Tracking 12345  checking                       │
│ ! BC-011  VALIDATION_ERROR  เลขอ้างอิงซ้ำ                │
└───────────────────────────────────────────────────────────┘
```

### Failed-Row Retry State

```text
┌ แก้ไขเฉพาะรายการที่ล้มเหลว                              ┐
│ การส่งครั้งถัดไปจะเป็น Batch ใหม่                        │
│ Orders retained: BC-011, BC-013, BC-014                   │
├───────────────────────────────────────────────────────────┤
│ ! Order 1  BC-011  Duplicate reference  [Edit]            │
└───────────────────────────────────────────────────────────┘
```

## Alternative B: Spreadsheet/Grid Entry

### Initial State

```text
┌ Bulk Grid Entry                                           ┐
│ Batch Ref [___________] Date [2026-07-21] [Review]        │
├────┬────────────┬──────────┬─────────┬──────┬────────────┤
│ #  │ Order Ref  │ Receiver │ Product │ Unit │ Amount     │
├────┼────────────┼──────────┼─────────┼──────┼────────────┤
│ 1  │ BC-001     │ [search] │ [search]│ [ ]  │ [1]        │
└────┴────────────┴──────────┴─────────┴──────┴────────────┘
```

### Batch With Multiple Orders

```text
┌ 30 Orders · 34 Item rows · 2 Errors        [Add Row]      ┐
├────┬────────────┬──────────────┬────────────┬─────┬──────┤
│ 1  │ BC-001     │ บริษัท A     │ น้ำดื่ม    │ ขวด │ 2    │
│ 2  │ BC-002     │ บริษัท B     │ น้ำดื่ม    │ ขวด │ 1    │
│ 3a │ BC-003     │ บริษัท C     │ กล่อง      │ ลัง │ 2    │
│ 3b │ "          │ "            │ สติ๊กเกอร์ │ ม้วน│ 3    │
└────┴────────────┴──────────────┴────────────┴─────┴──────┘
```

### Expanded Order With Multiple Items

```text
┌ Order BC-003 group                                        ┐
│ Receiver: บริษัท C  Remark: [________________]            │
├────┬────────────┬──────┬────────┬─────────┬──────────────┤
│ a  │ กล่อง      │ ลัง  │ [2]    │ [note]  │ [More refs]  │
│ b  │ สติ๊กเกอร์ │ ม้วน │ [3]    │ [note]  │ [More refs]  │
└────┴────────────┴──────┴────────┴─────────┴──────────────┘
```

### Validation Error State

```text
┌ 3 errors [Next error]                                     ┐
├────┬────────────┬──────────┬─────────┬──────┬────────────┤
│ 8  │ BC-008     │ บริษัท A │ น้ำดื่ม │ ขวด  │ [0] ERROR  │
│ 9  │ BC-008 ERR │ บริษัท B │ กล่อง   │ ลัง  │ [1]        │
└────┴────────────┴──────────┴─────────┴──────┴────────────┘
```

### Mobile State

```text
┌ Bulk Grid is replaced by cards on mobile ┐
│ Order 8 ! BC-008                         │
│ Receiver: บริษัท A                       │
│ Item 1: น้ำดื่ม / ขวด / Amount 0 ERROR   │
│ [Edit]                                   │
└──────────────────────────────────────────┘
```

### Review State

```text
┌ Review Grid Summary                                      ┐
│ 30 Orders · 34 Item rows · Complete 30 · Incomplete 0     │
│ [Submit Batch]                                           │
└───────────────────────────────────────────────────────────┘
```

### HTTP 201 Result

```text
┌ Result Grid: All succeeded                               ┐
│ [All] [Successful] [Failed]                              │
│ BC-001 | 12345 | checking | สร้างสำเร็จ                 │
└───────────────────────────────────────────────────────────┘
```

### HTTP 207 Result

```text
┌ Result Grid: 10 success, 5 failed                        ┐
│ Filter [Failed]                                          │
│ BC-011 | VALIDATION_ERROR | เลขอ้างอิงซ้ำ | Retry yes    │
└───────────────────────────────────────────────────────────┘
```

### Failed-Row Retry State

```text
┌ Retry Grid: failed Orders only                           ┐
│ Success rows removed. New Batch will be created.          │
│ BC-011 | Receiver | Product | Unit | Amount               │
└───────────────────────────────────────────────────────────┘
```

## Alternative C: Wizard With Order Workspace

### Initial State

```text
┌ Step 1 of 4: Batch information                            ┐
│ Batch Ref [____________] Date [2026-07-21]                │
│ Note: Batch Reference is not an idempotency key            │
│ [Next: Orders]                                            │
└────────────────────────────────────────────────────────────┘
```

### Batch With Multiple Orders

```text
┌ Step 2: Orders workspace                                  ┐
│ Sidebar                    │ Active Order                 │
│ ✓ 1 BC-001 บริษัท A        │ Client Ref [BC-003____]      │
│ ! 2 Missing receiver       │ Receiver [Search/change]     │
│ > 3 BC-003 Draft           │ Items [Add Item]             │
│ [Add Order]                │                              │
└────────────────────────────────────────────────────────────┘
```

### Expanded Order With Multiple Items

```text
┌ Active Order 3                                            ┐
│ Receiver: บริษัท C [Change]                               │
│ Remark [____________________]                             │
├────────────────────────────────────────────────────────────┤
│ Item 1 Product [search] Unit [ขวด] Amount [2] [Details]   │
│ Item 2 Product [search] Unit [ลัง] Amount [1] [Details]   │
└────────────────────────────────────────────────────────────┘
```

### Validation Error State

```text
┌ Step 3: Review blocked by validation                      ┐
│ 4 issues                                                  │
│ [Order 2 missing receiver] [Order 7 duplicate reference]  │
├────────────────────────────────────────────────────────────┤
│ Sidebar shows red "Error" badges beside invalid Orders     │
└────────────────────────────────────────────────────────────┘
```

### Mobile State

```text
┌ Orders workspace                                          ┐
│ [Order 3 of 12 v]                                         │
│ Client Ref [BC-003]                                      │
│ Receiver [บริษัท C]                                      │
│ Items                                                     │
│ [Previous] [Next] [Review]                               │
└───────────────────────────────────────────────────────────┘
```

### Review State

```text
┌ Step 3: Review                                            ┐
│ Batch: BATCH-001 · Date 2026-07-21                       │
│ 12 Orders · 18 Items · 0 incomplete                       │
│ Order list with receiver/ref/item count/status            │
│ [Back to edit] [Submit Batch]                             │
└────────────────────────────────────────────────────────────┘
```

### HTTP 201 Result

```text
┌ Step 4: Result                                            ┐
│ All rows succeeded · APIB202607210001                     │
│ [Create another Batch] [History]                          │
└────────────────────────────────────────────────────────────┘
```

### HTTP 207 Result

```text
┌ Step 4: Result                                            ┐
│ Partial success · 10 success · 5 failed                   │
│ [Successful] [Failed]                                     │
│ [Prepare failed Orders for new submission]                │
└────────────────────────────────────────────────────────────┘
```

### Failed-Row Retry State

```text
┌ Step 2: Orders workspace                                  ┐
│ Retry mode: failed Orders only                            │
│ Sidebar contains 5 failed Orders                          │
│ Banner: Next submit creates a new Batch                    │
└────────────────────────────────────────────────────────────┘
```

## Alternative D: Hybrid Order Navigator Plus Item Grid

### Initial State

```text
┌ Bulk Order Checking                                       ┐
│ Batch Ref [________] Date [2026-07-21]  [Review]          │
├───────────────┬────────────────────────────────────────────┤
│ Orders        │ Active Order 1                             │
│ > 1 Draft !   │ Client Ref [BC-001____]                    │
│ [Add Order]   │ Receiver [Search receiver]                 │
│               │ Items grid below                           │
└───────────────┴────────────────────────────────────────────┘
```

### Batch With Multiple Orders

```text
┌ Sticky Batch: 30 Orders · 34 Items · 2 Errors              ┐
├───────────────┬────────────────────────────────────────────┤
│ ✓ 1 BC-001    │ Active Order 8                             │
│ ✓ 2 BC-002    │ Receiver: บริษัท H                         │
│ ! 8 BC-008    │ Items                                      │
│ ✓ 9 BC-009    │ Product | Unit | Amount | Details | Action │
└───────────────┴────────────────────────────────────────────┘
```

### Expanded Order With Multiple Items

```text
┌ Active Order 8: BC-008                                    ┐
│ Receiver [บริษัท H] [Change]   Duplicate Order            │
├────────────────────────────────────────────────────────────┤
│ Product             Unit          Amount     Optional      │
│ น้ำดื่ม 600 ml      ขวด           [2.5]      [Details]    │
│ กล่องกระดาษ         ลัง           [1]        [Details]    │
│ [Add Item Alt+I]                                           │
└────────────────────────────────────────────────────────────┘
```

### Validation Error State

```text
┌ 3 issues [Next]                                           ┐
├───────────────┬────────────────────────────────────────────┤
│ ! 8 BC-008    │ Amount [0] ERROR                          │
│ ! 9 BC-008    │ Duplicate reference                        │
└───────────────┴────────────────────────────────────────────┘
```

### Mobile State

```text
┌ Bulk Order Checking                                       ┐
│ Batch summary sticky                                     │
│ [Order 8 of 30 !] [Prev] [Next]                           │
│ Client Ref / Receiver / Items stacked                     │
└────────────────────────────────────────────────────────────┘
```

### Review State

```text
┌ Review                                                    ┐
│ Batch Ref BATCH-001 · Date 2026-07-21                    │
│ 30 Orders · 34 Items · Complete 30                        │
│ List: Order, Reference, Receiver, Items, Status           │
│ [Back] [Submit Batch]                                    │
└───────────────────────────────────────────────────────────┘
```

### HTTP 201 Result

```text
┌ Result: All succeeded                                     ┐
│ API Batch No APIB202607210001                             │
│ [All] [Successful] [Failed]                               │
│ BC-001 | 12345 | checking | สร้างสำเร็จ                  │
└────────────────────────────────────────────────────────────┘
```

### HTTP 207 Result

```text
┌ Result: Partial success                                   ┐
│ 10 successful · 5 failed                                  │
│ Failed tab selected                                       │
│ BC-011 | VALIDATION_ERROR | Retry eligible                │
│ [Retry failed only]                                      │
└────────────────────────────────────────────────────────────┘
```

### Failed-Row Retry State

```text
┌ Retry failed Orders                                       ┐
│ New Batch · 5 Orders retained                             │
│ Order navigator contains failed Orders only               │
│ Successful Orders are read-only in previous result         │
└────────────────────────────────────────────────────────────┘
```
