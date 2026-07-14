# ADR-003: Payment Visibility

Status: Accepted

## Context

Payment visibility is not the same as shipment visibility. Sprint 1.0.1 clarifies the authoritative Sisahygo payment types and payment status values.

## Decision

Use `AuthorizedPaymentQuery` and `PaymentPolicy`. Sender payment visibility requires an authorized sender customer link, `can_view_payment`, payment capability, and `paymenttype IN ('H', 'T', 'F')`.

Receiver payment visibility requires an authorized receiver customer link, `can_view_payment`, payment capability, and `paymenttype IN ('E', 'L')`.

Use the Sisahygo API `payment_status` field only:

- `0` = Outstanding / ค้างชำระ
- `1` = Paid / ชำระแล้ว

## Consequences

Payment access can evolve independently from shipment access while preserving horizontal privilege protection. Future Payment screens must display meaning from `paymenttype` plus `payment_status`, not invented statuses.