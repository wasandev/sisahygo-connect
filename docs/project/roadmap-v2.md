# Roadmap V2

## Completed

- Client Account Foundation
- Sisahygo Client API integration boundary
- Single Order Checking create/submit/reconcile
- Shipment Tracking, Shipments list/detail, and Shipment History
- Customer Dashboard
- Payment Center foundation, UX polish, Dashboard widgets, and Dashboard payment cache

## Current Baseline

Payment Module is the completed baseline. The current code supports Payment Center F/L/E list/detail, Dashboard payment summary/recent records, short-lived Dashboard-only payment caching, selected account isolation, and safe API error handling.

## Next Sprint

Sprint 6 — Bulk Order Checking

## Why

Single Order Checking already has the reusable pieces Bulk needs: selected-account middleware, `order.create` capability handling, sender validation, receiver/product/unit lookups, item payload structure, `client_reference_no`, safe POST through `SisahygoApiClient`, validation-envelope mapping, duplicate/unknown-result caution, and tests. Rebuilding Single Order would duplicate completed work.

Bulk is the next unimplemented order workflow evidenced by roadmap/docs/capability enum. It is not already implemented: no production route, Livewire component, application service, request DTO, endpoint method, fixture, or test currently exists for Bulk.

## Prerequisites

- Verify the authoritative Core endpoint: likely `POST /api/v1/client/order-checkings/bulk`, but current repository evidence is documentation-only.
- Confirm request fields: `batch_reference_no`, `batch_date`, `orders`, per-order `client_reference_no`, receiver identity, items, `product_id`, `unit_id`, `amount`, `remark`, `client_item_no` or equivalent.
- Confirm response fields: `api_batch_no`, accepted/rejected counts, per-order identifiers/status, validation details.
- Confirm duplicate reference and idempotency expectations.
- Confirm partial versus all-or-nothing behavior.
- Decide upload/input UX and row-level validation display before coding.

## Out Of Scope For Sprint 6 Unless Explicitly Added

- Rebuilding Single Order Checking
- Shipment/payment/report redesign
- Online payment, receipt, invoice, or allocation flows
- Direct Core database access
- Scheduled sync or queue prefetching

## Following Milestones

1. Reports or Client Account management, depending on product priority.
2. Notifications only after product behavior is defined.
3. Payment submission/invoice/receipt work only after Core/accounting contracts exist.
4. Export/analytics and configurable Dashboard work after core operational workflows are stable.
