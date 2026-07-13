# ADR-003: Payment Visibility

Status: Accepted

## Context

Payment visibility is not the same as shipment visibility.

## Decision

Use `AuthorizedPaymentQuery` and `PaymentPolicy`. Receiver payment visibility requires an authorized receiver customer link, `can_view_payment`, payment capability, and `paymenttype IN ('E', 'L')`.

## Consequences

Payment access can evolve independently from shipment access while preserving horizontal privilege protection.