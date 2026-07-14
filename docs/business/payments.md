# Payments

Payment visibility is independent from shipment visibility. Being allowed to view a shipment does not grant access to payment details.

Sender-linked Client Accounts may view payment details only when:

- `order_headers.customer_id` belongs to an active linked customer with `can_send = true`
- the linked customer has `can_view_payment = true`
- `paymenttype` is `H`, `T`, or `F`

Receiver-linked Client Accounts may view payment details only when:

- `order_headers.customer_rec_id` belongs to an active linked customer with `can_receive = true`
- the linked customer has `can_view_payment = true`
- `paymenttype` is `E` or `L`

Future Payment screens must use both `paymenttype` and `payment_status` from the Sisahygo API.