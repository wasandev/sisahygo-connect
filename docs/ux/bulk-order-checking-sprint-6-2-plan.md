# Bulk Order Checking Sprint 6.2 Plan

Status: Proposal
Branch reviewed: feature/bulk-order-checking
Audited commit/baseline: 78ce35b6cb21b28023cb939198834bfe7d1592b8 plus uncommitted Sprint 6 implementation
Date: 2026-07-21
Current implementation basis: Sprint 6 Bulk Order Checking source and tests.
Production code changed in Sprint 6.1: No. This document is proposal-only.

## Goal

Refactor the Sprint 6 Bulk Order Checking UI into the recommended Hybrid Order Navigator Plus Item Grid without changing the Core API contract.

## Phase 1: Structural Layout Refactor

Likely files:
- resources/views/livewire/order-checking-bulk.blade.php
- app/Livewire/OrderCheckingBulk.php
- lang/th/bulk_order_checking.php
- lang/en/bulk_order_checking.php

Reuse:
- Existing route, component, service, DTOs, endpoint, error mapping.

Risks:
- Breaking current Livewire bindings.
- Over-rendering if the old card list remains.

Focused tests:
- Form renders.
- Starts with one active Order and one Item.
- Existing submit builds same request.

Manual review:
- Toolbar visible on desktop.
- Mobile reading order remains sensible.

## Phase 2: Compact Order Navigator And Summary

Likely files:
- OrderCheckingBulk component.
- Bulk Blade view.
- Bulk page tests.

Reuse:
- row_key.
- reviewSummary.
- local validation errors.

Risks:
- activeOrderKey can become stale after remove/retry.

Focused tests:
- Add Order selects new active Order.
- Remove active Order selects a safe neighbor.
- Invalid Order shows badge.
- Clicking invalid Order opens correct editor.

Manual review:
- 50 Orders remain scannable.
- Current Order identity is obvious.

## Phase 3: Improved Item Entry

Likely files:
- Bulk Blade view.
- OrderCheckingBulk component.

Reuse:
- Current item structure.
- productResults and units.

Risks:
- Product picker target ambiguity.
- Unit selection after product choice.

Focused tests:
- Select product applies to active item only.
- Add Item appends to active Order.
- Optional details preserve client_line_id, client_item_no, client_product_code.

Manual review:
- One Order with 20 Items can be edited without excessive vertical height.

## Phase 4: Duplicate Order

Likely files:
- OrderCheckingBulk component.
- Bulk Blade view.
- Localization.
- Bulk page/service tests if helper moves to service.

Reuse:
- blankOrder and blankItem.

Risks:
- Accidentally copying client_reference_no.
- Ambiguity around optional client line fields.

Focused tests:
- Duplicate copies receiver and item rows.
- Duplicate clears or regenerates client_reference_no.
- Duplicate does not submit successful rows after retry.

Manual review:
- Duplicate action is discoverable but not too prominent.

## Phase 5: Review And Validation Summary

Likely files:
- OrderCheckingBulk component.
- Bulk Blade view.
- Localization.

Reuse:
- validatePayload behavior in service.
- mapErrorField.

Risks:
- Duplicating validation logic in the component.
- Review step blocking users without explaining why.

Focused tests:
- Review shows Batch reference/date, Order count, item count, incomplete count.
- Duplicate references link to both Orders.
- HTTP 422 states no Orders were created.

Manual review:
- Users can get from summary to the first invalid field quickly.

## Phase 6: Result Layout Improvements

Likely files:
- Bulk Blade view.
- OrderCheckingBulk component.
- Bulk page tests.

Reuse:
- processedResult.
- failedRetryOrders.

Risks:
- Result filters hiding critical failure context.
- Copy/export scope creep.

Focused tests:
- 201 all-success result.
- 207 partial result tabs.
- Failed tab includes retry button.
- Core internal id is not rendered.

Manual review:
- 10 success and 5 failed rows are easy to scan.

## Phase 7: Responsive And Accessibility Polish

Likely files:
- Bulk Blade view.
- Connect component usage if needed.
- Localization.

Reuse:
- x-connect.card, x-connect.button, x-connect.page-header.

Risks:
- Dense layouts can become poor for screen readers.
- Keyboard shortcuts can conflict with browser behavior.

Focused tests:
- Meaningful labels exist for repeated fields.
- Buttons have clear accessible text.
- Validation summary is rendered.

Manual review:
- Desktop, tablet, and mobile screenshots.
- Keyboard-only navigation.
- Screen-reader reading order smoke test if available.

## Phase 8: Regression Tests

Likely files:
- tests/Feature/BulkOrderCheckingPageTest.php
- tests/Feature/BulkOrderCheckingServiceTest.php
- tests/Feature/Integrations/Sisahygo/BulkOrderCheckingIntegrationTest.php
- existing Single Order tests.

Reuse:
- Existing fixtures.

Risks:
- Test fragility around DOM text after layout changes.

Focused tests:
- Current Sprint 6 focused Bulk suite.
- Single Order Checking regression.
- No API shape changes.
- No automatic POST retry.
- Failed-row-only retry.

Manual review:
- Compare request payload before/after refactor.
- Confirm no Core internal id in rendered HTML.

## Verification For Sprint 6.2

Run:
- ./vendor/bin/sail artisan test tests/Feature/Integrations/Sisahygo/BulkOrderCheckingIntegrationTest.php tests/Feature/BulkOrderCheckingServiceTest.php tests/Feature/BulkOrderCheckingPageTest.php
- ./vendor/bin/sail artisan test tests/Feature/OrderCheckingPageTest.php tests/Feature/OrderCheckingServiceTest.php tests/Feature/Integrations/Sisahygo/OrderCheckingIntegrationTest.php
- ./vendor/bin/sail artisan test
- npm run build
- git diff --check

Do not change the Core API contract.

## Implementation Result

Status: Implemented locally.

Sprint 6.2 followed this plan using the existing `OrderCheckingBulk` Livewire component rather than adding child components. This fit the current page architecture because all form state is serializable Livewire public state and the existing application service remains the only submission boundary to the Sisahygo Core Client API.

Verification target added during implementation: `tests/Feature/BulkOrderCheckingPageTest.php` now covers active Order navigation, duplicate Order behavior, mandatory review, result filters, failed-only retry requiring review again, local validation navigation, contextual receiver/product lookup, transport uncertainty, and Core 422 mapping.
