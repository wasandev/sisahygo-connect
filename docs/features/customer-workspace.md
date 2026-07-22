# Customer Workspace Enhancement

Sprint 7 improves the Connect customer workspace while preserving the existing boundary:

Livewire -> Application Service -> DTO/Mapper -> Sisahygo Core API

Connect must not read the Sisahygo Core database directly. Order, shipment, search, and dashboard data continue to flow through the Sisahygo Core Client API.

## Dashboard Workspace

The dashboard is the main customer workspace. It now combines:

- Summary cards from bounded Core API list/count requests.
- Recent order/shipment records from `ListOrderHistory` and `ShipmentQueryService`.
- Quick actions for order checking, shipments, tracking, and history.
- Pending actions derived from visible dashboard data, such as problem shipments and outstanding payments.
- A universal search box for tracking numbers, client references, and batch references.
- A mock notification preview that links to the Notification Center.

The dashboard intentionally reuses existing services and does not introduce new Core calls for the mock notification preview.

## Order Detail

Order History links to `/orders/{trackingIdentifier}`. The Order Detail page is a customer-facing view over the existing Shipment Detail API response and shows:

- Order information.
- Receiver information.
- Items.
- Freight summary.
- Shipment information.
- Timeline/status history.

The page reuses `ShipmentQueryService`, `ShipmentDetail`, `ShipmentSummary`, `ShipmentItem`, `ShipmentStatus`, and `ShipmentMapper` instead of introducing a separate transport path.

## Universal Search

`ResolveUniversalSearch` accepts one query and attempts resolution through `ShipmentQueryService` using Core-supported filters in this order:

- Numeric values: tracking number, order/client reference, client reference, batch reference.
- Non-numeric values: order/client reference, client reference, batch reference, tracking number.

A match redirects to the Order Detail page for the resolved tracking number. A miss stays in place with a safe empty state message.

## Notification Center Phase 1

The Notification Center is UI-only and uses mock data. It includes all/unread filters, empty-state handling, and clear copy explaining that polling, push notifications, and persisted read state are not enabled yet.

## UX Consistency

Sprint 7 uses existing Connect UI components for cards, buttons, badges, empty states, loading state, and toast styling. New screens and states should keep using these components before adding new UI primitives.
