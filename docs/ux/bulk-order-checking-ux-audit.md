# Bulk Order Checking UX Audit

Status: Proposal
Branch reviewed: feature/bulk-order-checking
Audited commit/baseline: 78ce35b6cb21b28023cb939198834bfe7d1592b8 plus uncommitted Sprint 6 implementation
Date: 2026-07-21
Current implementation basis: OrderCheckingBulk Livewire component, order-checking-bulk Blade view, SubmitBulkOrderChecking service, Bulk DTOs/mapper, OrderCheckingsEndpoint::createBulk, Bulk tests, and Sprint 6 docs.
Production code changed in Sprint 6.1: No. This document is proposal-only.

## Current Workflow

1. User opens /order-checking/bulk from the Order Checking area.
2. Page mounts one Batch with today's batch_date and one generated Order reference.
3. Component loads sender options and units. If no sender or credential is available, it shows an unavailable card.
4. User enters optional Batch Reference and Batch Date.
5. User uses a global receiver search input with 400 ms debounce and minimum two characters.
6. Each receiver result shows one button per current Order: "use for order N".
7. User uses a global product search input with 400 ms debounce and minimum two characters.
8. Every item row repeats all current product-result buttons.
9. User fills Order client_reference_no, selected receiver, optional Order remark.
10. User fills each Item product, unit, amount, optional item remark, client_line_id, client_item_no, and client_product_code.
11. User adds/removes Items inside an Order card.
12. User adds/removes Orders from the main order list.
13. User reads the side review card: Order count, item row count, incomplete Order count, and no-local-totals warning.
14. User submits directly from the side review panel.
15. HTTP 201/207 processed results appear above the form. Successful and failed rows are separated.
16. HTTP 422 maps nested Core field paths back to stable Order/Item keys and preserves form state.
17. Connection failure shows an unknown-result warning and does not resubmit.
18. For HTTP 207, user can prepare failed rows only; the form resets to a new editable Batch containing only failed Orders.

## Current Interaction Count

For one new Order with one Item and no defaults beyond generated reference:

- Batch: 0-2 fields.
- Receiver: type search, wait, click "use for order N".
- Product: type search, wait, click product button inside the item row.
- Unit: select unit, often already set by product selection but still visible.
- Amount: edit numeric value if not 1.
- Optional line refs: up to 4 optional fields.
- Add another Order: one click, then repeat receiver and product assignment.

For 15 Orders with one Item each, receiver assignment creates a repeated receiver-result action matrix: every receiver result displays 15 "use for order N" buttons. For 50 Orders, that becomes 50 buttons per receiver result.

## Friction Points

| Issue | Current behavior | Affected scenario | Impact | Severity | Possible improvement |
| --- | --- | --- | --- | --- | --- |
| Global receiver result actions scale with Order count | Each receiver result renders a button for every Order | 15-50 Orders with different receivers | Hard to choose the intended Order; visual noise grows linearly | High | Make receiver lookup contextual to active Order or row; provide recent receivers and keyboard selection |
| Product results repeat inside every item row | Every item row renders the same product-result button set | Many Items or many Orders after product search | Large DOM and long page; product choice is visually duplicated | High | Use contextual product picker for active item; keep product results in a floating/panel picker |
| Long vertical card list | All Orders are full cards by default; no collapsed summary behavior is implemented despite state field | 15-50 Orders | Heavy scrolling, weak comparison, hard to find Order 37 | High | Add compact Order navigator with collapsed cards and invalid badges |
| Review is passive | Side card shows only counts; no click-to-invalid or itemized validation | Multiple incomplete Orders or Core 422 | Users know something is wrong but not where | High | Add validation summary with links to Order/Item anchors |
| No duplicate Order action | Only add/remove exists | Similar product structures across 30 Orders | Repetitive item entry | Medium | Add Duplicate Order that copies receiver, remark, and item structure but regenerates/clears client_reference_no |
| Remove actions have no confirmation | Remove Order and Item buttons immediately mutate state | Large Batch, mobile tapping | Accidental deletion loses data in transient state | Medium | Confirm remove Order with data; allow undo toast; keep item remove confirmation lightweight |
| Global product search has unclear target | Search results appear in every item row; no active item indicator | Many item rows visible | User may select product into wrong row | Medium | Active item focus state and "select for this row" picker |
| No sticky Batch/actions toolbar | Submit and counts live in side card; on mobile it follows after all Orders | Large Batch/mobile | User loses progress context while editing | Medium | Sticky desktop toolbar and mobile bottom action bar |
| Repeated optional item fields always visible | remark, line id, item no, product code are always shown | One-item Orders or 50 Orders | More vertical height than needed | Medium | Progressive disclosure for optional item details |
| Result filters are absent | 207 result shows successful and failed sections but no All/Successful/Failed filter | 10 success, 5 failed or larger | Good separation, but scanning many rows can still be slow | Low | Add result tabs and copy visible references |
| Browser refresh loses result/form state | Same component state, no persistence | After submit or long editing | Expected by Sprint 6, but risky for large entry | Medium | Warn before navigation/refresh when dirty; no draft persistence required |
| Keyboard workflow is basic browser tab order | No active row shortcuts or combobox semantics | Desktop data-entry users | Slower than spreadsheet-like entry | Medium | Add discoverable shortcuts for Add Item/Order and active picker selection |

## Scenario Review

### Scenario A: 3 Orders, One Item Each, Different Receivers

Works well:
- Current card structure is understandable.
- One global receiver search can assign receivers to each card.
- Direct submit keeps the flow short.

Difficult:
- Receiver result buttons already show one button per Order, which is acceptable at 3 but hints at scaling trouble.
- Product search results appear in all three item rows.

### Scenario B: 15 Orders, One To Three Items Each

Works well:
- Local state supports the data model.
- Stable row keys support nested validation mapping.

Difficult:
- Full card list becomes long.
- Review count does not help jump to incomplete cards.
- Product result duplication creates DOM and visual load.

### Scenario C: 30 Orders, Similar Product Structures

Works well:
- Core API payload supports the case.
- Failed-row retry can preserve failed row payloads.

Difficult:
- No Duplicate Order action means repeated item structure must be rebuilt.
- Comparing references, receivers, and item counts across 30 full cards is awkward.

### Scenario D: One Order, 20 Items

Works well:
- Existing card model keeps all items under one Order.
- Product/unit/amount fields are explicit.

Difficult:
- Product results repeated for 20 item rows after search.
- Optional item fields make each row tall.
- A grid-like item editor would be faster.

### Scenario E: 50 Orders, One Item Each

Works well:
- Limits are enforced.
- The service and DTOs can submit the payload.

Difficult:
- Worst case for scrolling and receiver result buttons.
- Review card gives no Order index map.
- Accidental remove risk is higher because the list is long and transient.

### Scenario F: HTTP 207 With 10 Successful And 5 Failed Orders

Works well:
- 207 is correctly treated as processed.
- Successful and failed sections are separated.
- Retry excludes successful rows.

Difficult:
- No filter tabs.
- Error details are raw field names in the failed result section.
- The relationship between original Order number and result row can be clearer.

### Scenario G: Nested Validation Errors In Multiple Orders And Items

Works well:
- Core field paths map to stable Livewire row keys.
- Form data is preserved.

Difficult:
- There is no top validation index with links.
- Errors are only visible after scrolling to the affected card/field.
- Distinction between local validation and Core 422 exists in copy but can be stronger visually.

## Audit Conclusion

The current Sprint 6 UI is correct and safe, but optimized for implementation clarity and small batches rather than high-volume entry. The strongest refactor direction is a hybrid: keep card-based Order boundaries and Livewire state, add a compact Order navigator, contextual lookup pickers, collapsible summaries, optional detail drawers, and a real Review step. A pure spreadsheet grid would be fastest for expert desktop users but carries higher accessibility, mobile, and data-model risks because each Order can contain multiple Items.
