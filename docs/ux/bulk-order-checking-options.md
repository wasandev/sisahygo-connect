# Bulk Order Checking UX Options

Status: Proposal
Branch reviewed: feature/bulk-order-checking
Audited commit/baseline: 78ce35b6cb21b28023cb939198834bfe7d1592b8 plus uncommitted Sprint 6 implementation
Date: 2026-07-21
Current implementation basis: Sprint 6 Bulk Order Checking source and tests.
Production code changed in Sprint 6.1: No. This document is proposal-only.

## Alternative A: Improved Order Cards

Keep the current card model, but make each Order card compact, collapsible, and navigable.

Key changes:
- Sticky Batch toolbar with Batch Reference, Batch Date, Orders, Items, incomplete count, and Submit/Review.
- Compact Order header: number, client_reference_no, receiver, item count, validation badge.
- Collapsed cards show summary only.
- Expanded card shows receiver picker and item editor.
- Contextual receiver/product lookup scoped to active Order or active Item.
- Duplicate Order action.
- Validation summary links to invalid Order cards.
- Optional item fields hidden behind "more details".

Trade-offs:
- Lowest implementation risk.
- Reuses most current state and component code.
- Still card-based, so 50 Orders remain a list, but much shorter.
- Desktop speed improves but does not reach spreadsheet speed.

Complexity: Medium
Technical risk: Low-Medium

## Alternative B: Spreadsheet/Grid Entry

Use a dense desktop-first grid where each item line is a row. Orders are represented by repeated Order fields or grouped row blocks.

Possible columns:
- Order reference
- Receiver
- Product
- Unit
- Amount
- Item remark
- Line ID
- Item No.
- Product Code
- Row actions

Multiple Items per Order:
- Rows share the same Order reference and receiver.
- A grouped visual band marks Order boundaries.
- First row can show Order-level remark and receiver; subsequent rows can inherit.

Trade-offs:
- Fastest for experienced desktop users.
- Good for 50 one-item Orders and one Order with many Items.
- Harder for new users to understand one receiver per Order.
- Mobile requires a different card editor.
- Accessible grid editing requires careful ARIA, keyboard, and focus management.
- More likely to introduce regression in Livewire DOM size and nested validation mapping.

Complexity: High
Technical risk: High

## Alternative C: Wizard With Order Workspace

Use staged navigation:

1. Batch information
2. Orders workspace
3. Review and validation
4. Result

Orders workspace:
- Left sidebar: compact list of Orders with completion badges.
- Right panel: active Order editor.
- User edits one Order at a time.
- Next/previous controls and "Add Order" keep flow contained.

Trade-offs:
- Best for reducing cognitive load.
- Strong mobile fit.
- Great for nested validation because errors attach to sidebar badges.
- Slower for expert desktop users entering many similar Orders.
- Requires more navigation steps.

Complexity: Medium-High
Technical risk: Medium

## Alternative D: Hybrid Order Navigator Plus Item Grid

Use a compact desktop layout:
- Sticky Batch toolbar on top.
- Left Order navigator for all Orders.
- Main active Order editor.
- Active Order item lines use a compact grid.
- Mobile uses stacked Order cards/workspace.

Why this is distinct:
- It keeps one receiver per Order visible and safe.
- It avoids rendering all product results across all item rows.
- It gives desktop users fast row entry within the active Order.
- It avoids the accessibility burden of one giant 50-Order spreadsheet.

Trade-offs:
- More work than Alternative A.
- Less raw spreadsheet speed than Alternative B for 50 one-item Orders.
- Best balance for Sisahygo's nested Order/Item data model.

Complexity: Medium-High
Technical risk: Medium

## Interaction Model By Alternative

| Interaction | A: Improved Cards | B: Grid | C: Wizard Workspace | D: Hybrid |
| --- | --- | --- | --- | --- |
| Add Order | Toolbar button adds collapsed card | Insert grouped row/order block | Sidebar Add Order | Toolbar/sidebar Add Order |
| Remove Order | Header action with confirm/undo | Delete group with confirm | Sidebar action with confirm | Sidebar action with confirm |
| Duplicate Order | Header action, clears reference | Duplicate group, clears reference | Sidebar action, clears reference | Sidebar action, clears reference |
| Add Item | Quick button in expanded card | New grid row under Order | Active editor button | Active Order grid row |
| Remove Item | Row action, optional undo | Row delete | Active editor row delete | Active grid row delete |
| Receiver | Contextual picker per Order | Cell autocomplete | Active Order picker | Active Order picker |
| Product | Contextual picker per item | Cell autocomplete | Active item picker | Active item grid picker |
| Unit | Select filtered by selected product where possible | Cell select | Active item select | Active item select |
| Amount | Numeric field | Numeric cell | Numeric field | Numeric cell |
| Error navigation | Summary links open cards | Cell error list focuses grid cell | Sidebar invalid badges | Sidebar invalid badges plus cell focus |
| Partial success | Existing sections plus filters | Result grid with filters | Result step | Result step with filters |
| Refresh behavior | Still transient; warn if dirty | Still transient; warn if dirty | Still transient; warn if dirty | Still transient; warn if dirty |

## Keyboard Recommendations

Recommended across all alternatives:
- Tab moves through visible fields.
- Shift+Tab reverses.
- Enter inside search selects highlighted lookup option, not submit.
- Escape closes lookup panels or detail drawers.
- Arrow keys move through lookup options.
- Ctrl+Enter submits only from Review, with visible button hint.
- Alt+N adds Order, visible in button tooltip/help text.
- Alt+I adds Item to active Order, visible near Add Item button.

Do not use shortcuts that conflict with browser basics, such as Ctrl+S or raw Enter-to-submit inside arbitrary fields.

## Duplicate Order Recommendation

Add Duplicate Order in the next UX implementation sprint.

Copy:
- receiver
- order remark
- item rows
- product IDs/names
- unit IDs/names
- amounts
- item remarks
- client_line_id only if the team confirms it is structural, not unique

Do not copy unchanged:
- client_reference_no

Default for optional line identifiers:
- Do not copy client_item_no or client_product_code if customers commonly treat them as unique per order line.
- If product code is a SKU-like stable code, it may be copied. This needs human review.

Confirmation:
- No confirmation needed for Duplicate Order; it is non-destructive.
- Show a clear inline message that the new Order needs a unique client reference.

## Lookup UX Recommendations

- Keep 400 ms debounce initially, but make lookup contextual.
- Keep two-character minimum.
- Add loading and no-results states inside the picker, not as page-level errors only.
- Show receiver name, phone, and a non-internal customer-facing reference if available.
- Show product name, product ID, and unit name because similar names are likely.
- Keep selected receiver clearable per Order.
- Keep selected product clearable per Item.
- Add recent receivers/products from current form state without extra Core endpoints.
- Avoid rendering one API result set repeatedly in every Order/Item.

If future unit filtering needs richer API behavior, mark it as Core/API-dependent. Current proposal can still select from loaded units and rely on Core for eligibility.

## Validation UX Recommendations

- Add top validation summary with counts and links.
- Show Batch-level errors under Batch fields.
- Show Order header validation badge: Complete, Missing, Error.
- Show Item-level errors beside fields.
- For HTTP 422, state "No Orders were created."
- For HTTP 207, state "Some Orders were created; successful Orders are not included in retry."
- Highlight duplicate references on every duplicated Order.
- Focus first invalid visible control after validation.
- Do not use color alone; use labels and icons/text.

## Review Step Recommendation

Use a required Review step for Batches with more than one Order, and allow single Order quick submit only if the UI later supports that safely. The Review step should show:

- Batch Reference
- Batch Date
- Order count
- item-line count
- complete/incomplete count
- each Order's client_reference_no
- receiver
- item count
- validation status

No pricing, freight, or business status calculation.

## Result UX Recommendations

Use a result step with:
- Summary cards for total/success/failed.
- Tabs: All, Successful, Failed.
- Success row fields: client_reference_no, tracking_no, order_status, Core message.
- Failed row fields: client_reference_no, safe message, error code, field details, retry eligibility.
- Actions: Retry failed only, create another Batch, go to History/Tracking.
- Do not display Core internal id.

## Comparison Matrix

Scores: 1 is weak, 5 is strong. Complexity and regression risk are scored as implementation favorability, where 5 means easier/lower risk.

| Criterion | A Cards | B Grid | C Wizard | D Hybrid |
| --- | ---: | ---: | ---: | ---: |
| Ease for new users | 4 | 2 | 5 | 4 |
| Speed for experienced users | 3 | 5 | 3 | 4 |
| Large Batch efficiency | 3 | 5 | 4 | 4 |
| Small Batch simplicity | 5 | 3 | 4 | 4 |
| Mobile usability | 4 | 2 | 5 | 4 |
| Keyboard usability | 3 | 5 | 4 | 4 |
| Validation clarity | 4 | 3 | 5 | 5 |
| Partial-success clarity | 4 | 4 | 5 | 5 |
| Accessibility | 4 | 2 | 5 | 4 |
| Implementation favorability | 4 | 2 | 3 | 3 |
| Regression-risk favorability | 4 | 2 | 3 | 3 |
| Reuse of Sprint 6 code | 5 | 3 | 4 | 4 |
| Future extensibility | 3 | 4 | 4 | 5 |

Interpretation:
- A is safest and fastest to ship.
- B is powerful but expensive and risky.
- C is clearest for users but may slow bulk entry.
- D is the best long-term product fit because it respects the nested data model while improving speed.

## Accessibility Findings

Alternative A is closest to the current fieldset/label model and therefore has the least accessibility risk. It still needs better screen-reader announcements when Orders or Items are added, clearer destructive-action labeling, and validation summary links.

Alternative B has the highest accessibility risk. A dense editable grid needs cell coordinates, row group semantics, keyboard mode clarity, focus retention after Livewire updates, and a mobile alternative. A plain HTML table would not be enough because users must search, select, edit, and validate cells.

Alternative C has the strongest reading order because the user edits one active Order at a time. It should announce step changes, Order changes, and validation badge changes through aria-live regions.

Alternative D is accessible if the navigator is implemented as a list of buttons or tabs with clear active state, and the active Order editor remains a conventional labelled form. The item grid should keep real labels or accessible names for repeated fields.

## Livewire And Performance Findings

Current Sprint 6 public state can grow to 50 Orders with 200 Items each, though practical batches will be smaller. The bigger immediate performance issue is DOM repetition: product search results render inside every item row, and receiver search results render one action button per Order.

Alternative A reduces visual height but can still render many cards unless collapsed content is not mounted or is made very light.

Alternative B can create the densest DOM and the most focus-management complexity. It may need row virtualization or smaller child components if real users approach the maximum limits.

Alternative C and D are safer for Livewire because only one active Order editor needs rich lookup controls. The Order navigator can render compact summaries for all Orders while the item editor renders only the active Order rows.

Recommended performance guardrails:
- Keep receiver/product search debounced at 300-400 ms.
- Do not issue lookup requests per Order or per Item.
- Keep lookup results scoped to the active picker.
- Keep wire:key based on row_key.
- Avoid rendering optional item detail fields until opened.
- Keep validation summary derived from current state, not separate duplicated state.
