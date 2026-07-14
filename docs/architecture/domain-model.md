# Domain Model

A Client Account represents one organization. It can have multiple users and can link to multiple external Sisahygo customer identifiers.

A customer link can independently define:

- `can_send`
- `can_receive`
- `can_view_payment`
- `is_default_sender`
- `is_default_receiver`
- `is_active`

Client Accounts are not tied to a single customer and are not modeled as sender/receiver/both enum values.

Tenant-dependent workflows run inside a current Client Account context. Users with multiple active Client Accounts must explicitly select the current account.

Shipment visibility and payment visibility are separate domain rules. Both derive access from authorized transactions rather than customer master data.
