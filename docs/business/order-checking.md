# Order Checking

Order Checking อยู่นอก scope ของ Sprint 1 การ implement ในอนาคตต้องใช้ Client Account membership, capability checks และ authorized transaction queries เสมอ
## Sprint 6 Bulk Order Checking

Bulk Order Checking is implemented through the Sisahygo Core Client API only. Connect sends POST /order-checkings/bulk with batch_reference_no, batch_date, and up to 50 orders. Each order carries client_reference_no, customer_rec_id, optional remark, and up to 200 item rows.

Core owns sender, receiver, branch, payment type, product/unit eligibility, duplicate protection, and order creation. Connect performs structural UX validation only and never calculates freight, totals, or business status.

HTTP 201 is all-success, HTTP 207 is a processed partial/all-row failure result, and HTTP 422 is request-level rejection before any order is created. Failed-row retry creates a new Bulk request containing only failed rows. batch_reference_no is a customer reference, not an idempotency key.

## Sprint 6.2 Bulk UX Notes

Bulk Order Checking now has a mandatory human review step before submission. This is intentionally a UX control, not a business calculation layer. Connect does not calculate freight, validate receiver/product eligibility beyond local required-field checks, or infer whether Core accepted a timed-out request.

Failed-row retry resubmits only rows reported as failed by Core and always creates a new Bulk request after the user reviews the reduced Batch.
