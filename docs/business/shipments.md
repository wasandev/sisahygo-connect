# Shipments

Shipment visibility is derived only from authorized orders.

Sender visibility starts from `AuthorizedOrderQuery` and allows orders where `order_headers.customer_id` belongs to an active customer link with `can_send = true`.

Receiver visibility starts from `AuthorizedOrderQuery` and allows orders where `order_headers.customer_rec_id` belongs to an active customer link with `can_receive = true`.

Within authorized orders, future modules may show related sender, receiver, shipment items, shipment status, and shipment history. They must never expose unrelated customer master data or products unrelated to authorized shipments.