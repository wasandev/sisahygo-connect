# Customers

Client Account customer links connect a Sisahygo Connect account to external Sisahygo customer identifiers.

`customer_id` is an external Sisahygo identifier from the API boundary. It is not a foreign key to a local `customers` table.

A link can independently allow:

- sending shipments with `can_send`
- receiving shipments with `can_receive`
- viewing payment data with `can_view_payment`

Customer links must be active before they can authorize shipment or payment visibility.
