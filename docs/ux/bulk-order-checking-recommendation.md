# Bulk Order Checking UX Recommendation

Status: Proposal
Branch reviewed: feature/bulk-order-checking
Audited commit/baseline: 78ce35b6cb21b28023cb939198834bfe7d1592b8 plus uncommitted Sprint 6 implementation
Date: 2026-07-21
Current implementation basis: Sprint 6 Bulk Order Checking source and tests.
Production code changed in Sprint 6.1: No. This document is proposal-only.

## Recommended Design

Recommend Alternative D: Hybrid Order Navigator Plus Item Grid.

This design combines:
- Sticky Batch toolbar.
- Compact Order navigator/sidebar.
- Active Order editor.
- Compact item grid within the active Order.
- Required Review step before submit.
- Result step with filters and failed-row-only retry.
- Mobile workspace that shows one active Order at a time.

## Why It Fits Sisahygo Users

Sisahygo Bulk Order Checking has a nested data model: one Batch, many Orders, one receiver per Order, many Items per Order. A pure spreadsheet makes item rows fast but can blur the Order boundary and receiver ownership. Full cards preserve Order boundaries but become slow at 15-50 Orders. The hybrid keeps the Order boundary visible while giving fast item entry inside the active Order.

## Why It Is Better Than The Current Form

Current Sprint 6 form:
- Renders all Orders as full cards.
- Uses global lookup results with "use for Order N" buttons.
- Repeats product results inside every item row.
- Requires heavy scrolling for large Batches.
- Has no true navigation map for invalid Orders.

Recommended refactor:
- Shows all Orders in a compact navigator.
- Edits one active Order at a time.
- Makes lookup target explicit.
- Keeps validation and completion badges visible.
- Reduces DOM size and repeated product-result rendering.
- Makes failed-row retry easier to understand because retry mode can load only failed Orders into the navigator.

## Code To Retain

Retain:
- Route /order-checking/bulk.
- OrderCheckingBulk public state shape where practical: batchReferenceNo, batchDate, orders, processedResult, submittedOrders.
- SubmitBulkOrderChecking service.
- Bulk request/response DTOs.
- BulkOrderCheckingMapper.
- OrderCheckingsEndpoint::createBulk.
- Safe API error handling.
- Failed-row retry payload logic.
- Existing tests as regression base.
- Connect Blade components.

## Code To Refactor

Expected changes:
- Split the Blade into toolbar, order navigator, active order editor, review, and result partials or components.
- Add activeOrderKey behavior that actually drives the visible editor.
- Replace global receiver-result "use for each Order" buttons with active Order picker.
- Replace repeated product-result buttons in every item row with active Item picker.
- Add Order completion metadata and validation summary helpers.
- Add Duplicate Order action.
- Add review step state.
- Add result filters.
- Add dirty-state browser refresh warning if acceptable.

## Desktop Layout

Desktop should use a three-zone layout:

1. Sticky Batch toolbar across the top.
2. Left Order navigator, 18-22rem wide.
3. Main active Order editor with compact item grid.

The toolbar should keep Submit/Review, counts, and error navigation visible. The navigator should list all Orders with status badges, client_reference_no, receiver, and item count. The editor should keep receiver, Order remark, and item rows in one visible work area.

## Tablet Layout

Tablet should use:
- Sticky Batch toolbar.
- Order navigator as a top horizontal/scrolling list or collapsible drawer.
- Active Order editor stacked below.
- Item grid becomes two-column or compact cards.

## Mobile Layout

Mobile should not attempt a spreadsheet. It should use:
- Sticky summary bar.
- Active Order selector: "Order 8 of 30".
- Previous/Next buttons.
- Stacked fields for client reference, receiver, remark, and item cards.
- Bottom Review/Save/Add actions.

The same Livewire state can support desktop and mobile with different Blade presentation.

## Validation Behavior

Recommended model:
- Top validation summary with total issue count and links.
- Navigator badges: Complete, Missing, Error.
- Batch-level errors under Batch fields.
- Order-level errors in active Order header.
- Item-level errors beside fields.
- Duplicate references highlighted on every duplicate Order.
- First invalid field receives focus after validation.
- HTTP 422 displays "No Orders were created."
- HTTP 207 displays "Some Orders succeeded; successful Orders are not retried."

## Result Behavior

Use a dedicated result step:
- Summary cards: total, success, failed, API Batch No.
- Tabs: All, Successful, Failed.
- Success rows show client_reference_no, tracking_no when present, order_status, Core message.
- Failed rows show client_reference_no, safe message, error code, details, retry eligibility.
- Hide Core internal id.
- Actions: Retry failed only, create another Batch, go to History/Tracking.

## Failed-Row Retry Behavior

Retry should:
- Keep only failed Orders in the editor.
- Preserve client_reference_no unless duplicate-reference failure requires user change.
- Preserve receiver, remark, and item rows.
- Show a banner that the next submit creates a new Batch.
- Keep successful rows out of the retry payload.
- Never submit automatically.

## Duplicate Order Recommendation

Include Duplicate Order in Sprint 6.2 if the hybrid refactor is selected.

Copy:
- receiver
- Order remark
- item rows
- products
- units
- amounts
- item remarks

Do not copy:
- client_reference_no

Human review required:
- Whether client_line_id, client_item_no, and client_product_code are unique per order line or stable product references. Default recommendation is copy product code only if product treats it as SKU-like, and clear line/item numbers.

## Review Step Recommendation

Make Review required before submit. Bulk can create partial success, so the user should see exactly what is about to be sent. The extra click is justified for Batches with many Orders and no server idempotency key.

Review should not calculate pricing or freight.

## Implementation Risk

Risk: Medium.

Reasons:
- API/service layer can remain stable.
- Most risk is Livewire state and Blade refactor.
- Accessibility and keyboard behavior need careful tests.
- DOM size should improve if only one active Order editor renders product choices.

## Migration Strategy

1. Keep existing route and component.
2. Add computed presentation helpers without changing payload structure.
3. Introduce active Order editor behind the current tests.
4. Preserve existing submit/result behavior.
5. Add Duplicate Order and Review step after navigator is stable.
6. Expand tests around validation navigation and partial-success retry.

## Product Decisions For Human Review

- Should Review be mandatory for every Batch or only for more than one Order?
- Should Duplicate Order copy client_line_id, client_item_no, and client_product_code?
- Should result rows support copying visible references to clipboard?
- Should dirty-state browser refresh warning be added without draft persistence?
- Should mobile allow editing multiple Orders in one scroll, or always active Order only?

## Sprint 6.2 Implementation Note

Status: Accepted and implemented locally.

Alternative D was selected for Sprint 6.2. The route, Livewire component, application service, endpoint, DTOs, mapper, and Core API behavior were retained. The implementation adds active Order navigation, contextual receiver/product selection, mandatory review before POST, result filters, clipboard actions, dirty-state protection, and failed-only retry that returns to edit mode and requires review again.

No new architectural decision record was added because this is a UX/state refactor inside the existing Bulk Order Checking architecture.
