# Receiver Compatibility Gap

The current Sisahygo Core API was originally designed mainly for sender clients. Sisahygo Connect supports receiver-linked Client Accounts in its domain model, but production completeness depends on Core API support.

Receiver shipment access requires Core API authorization based on:

`order_headers.customer_rec_id`

Receiver payment access requires:

- authorized receiver relationship
- `paymenttype IN ('E', 'L')`

Required Core API confirmations or changes:

- shipment list filtering and authorization by receiver customer ID
- shipment detail authorization for receiver-visible tracking numbers
- payment visibility based on receiver customer ID and receiver payment types
- both sender/receiver deduplication behavior
- safe server-side customer authorization that does not trust arbitrary client-supplied IDs

Until these contracts are confirmed, receiver API integration must be documented as provisional and must not be claimed as complete.