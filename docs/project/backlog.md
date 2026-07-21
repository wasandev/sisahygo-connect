# Evidence-Based Backlog

## MVP / Next

- Sprint 6 — Bulk Order Checking implementation after contract verification.
- Add/confirm Bulk endpoint contract: payload, success response, validation envelope, duplicate semantics, all-or-nothing vs partial acceptance, and idempotency/retry expectations.
- Update or supersede stale Order Checking feature contract so Single does not get rebuilt.

## Phase 2

- Reports implementation or hide Reports nav until scoped.
- Client Account settings management: member invitation/removal, customer-link/capability admin UX if product-approved.
- Notification module definition or remove inactive header affordance.
- Shipment dashboard section-level failure isolation if operationally needed.
- Shipment/history filtered deep links from Dashboard once target pages hydrate query string.

## Future

- Online payment or payment submission only after Core/payment product scope is approved.
- Receipt/invoice creation only after Core contract and accounting ownership are defined.
- Export/download capabilities for shipments/payments.
- Realtime notifications, polling, or webhook-driven updates if Core supports them.
- Analytics and configurable Dashboard widgets.

## Not Backlog / Do Not Rebuild

- Single Order Checking create/submit/reconcile is already implemented and tested.
- Payment Center F/L/E list/detail is already implemented and tested.
- Dashboard Payment Overview/cache is already implemented and tested.
