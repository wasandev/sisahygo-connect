# Authorization

Authorization is layered:

1. Active user membership in a Client Account
2. User role check where account management is required
3. Client Account capability check
4. Authorized query object for transaction data isolation

Capabilities use namespace strings such as `shipment.view`, `payment.view`, and `users.manage`.