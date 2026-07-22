# Bulk Order Checking

Status: Implemented locally in Sprint 6.

## Scope

Bulk Order Checking lets an authenticated user with a selected Client Account submit up to 50 checking orders in one Core Client API request. Each order can contain up to 200 item rows.

Connect communicates only through `POST /api/v1/client/order-checkings/bulk`. It does not read Core database tables, create sender/receiver/branch data, calculate freight or totals, or reconstruct Core business rules.

## Contract Behavior

- `201` means every row succeeded.
- `207` means Core processed the request and at least one row failed.
- `422` means Core rejected the request before creating any orders.
- Processing is not batch-wide atomic.
- Core has no `Idempotency-Key`.
- `batch_reference_no` is a customer reference and is not unique.
- Duplicate protection is per `client_reference_no` according to Core business scope.

## Connect Workflow

The Bulk page keeps form and result state in one Livewire component. Results are not persisted to the local database. Successful rows are displayed read-only. Failed rows can be prepared for correction and resubmitted as a new Bulk request containing only failed rows.

If a connection failure happens after submission starts, Connect shows an unknown-result warning and does not automatically resend the POST. Users must check client references before trying again.

## Limits

- Maximum 50 orders per Batch.
- Maximum 200 items per order.
- `client_reference_no` is required and must be distinct within the local form.
- Core remains authoritative for receiver history, product/unit eligibility, duplicate protection, and order creation.

## Limitations

No Excel/CSV import, templates, persisted drafts, background jobs, attachments, online payment, or editing after Core acceptance are included in this sprint.

## Sprint 6.2 UX Refactor

Status: Implemented locally in Sprint 6.2.

The Bulk page now uses Alternative D, Hybrid Order Navigator Plus Active Order Item Grid. The workflow is `edit -> review -> result`:

- `edit`: users work in a sticky Batch toolbar, compact Order navigator, and one active Order editor. Desktop shows the navigator beside the editor. Mobile keeps the active Order, previous/next controls, and stacked fields usable without horizontal scrolling.
- `review`: mandatory before any Core POST. It shows Batch reference/date, Order and item counts, completion count, and a warning that Core may return partial success. Confirm Submit is the only action that sends `POST /api/v1/client/order-checkings/bulk`.
- `result`: shows temporary results only. Results can be filtered by All, Successful, and Failed. Visible rows can be copied as TSV, and individual client references/tracking numbers can be copied.

UX behavior:

- Adding an Order activates it immediately.
- Removing an Order selects a safe neighboring Order and never removes the final blank Order.
- Duplicating an Order copies receiver, remark, products, units, amounts, item remarks, and product code, but clears `client_reference_no`, `client_line_id`, and `client_item_no` so the user must review unique references.
- Receiver search applies only to the active Order.
- Product search applies only to the active item in the active Order.
- `Alt+I` adds an item while the component is in edit mode.
- Local validation creates a summary with jump buttons and activates the first invalid Order.
- Failed-only retry returns to edit mode with only failed Orders loaded and requires review again before submit.
- A dirty-state browser refresh warning protects unsent form changes.

The refactor does not change the Client API contract, DTO serialization, mapper behavior, selected Client Account architecture, or Core authority over validation/business rules. Connect still does not persist drafts or results locally and does not render Core internal IDs.
