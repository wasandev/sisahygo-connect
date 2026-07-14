# Authorization

Authorization is layered:

1. Authentication
2. Current Client Account resolution
3. Active user membership in the selected Client Account
4. User role check where account management is required
5. Client Account capability check
6. Authorized query object for transaction data isolation

Capabilities use namespace strings such as `shipment.view`, `payment.view`, and `users.manage`.

Tenant-dependent routes use the `client.account` middleware. Authentication, logout, email verification, profile, and account selection are user-level routes and remain outside that middleware by design.

Shipment queries must begin with `AuthorizedOrderQuery`. Payment queries must begin with `AuthorizedPaymentQuery`. Blade filtering is not a security boundary.

Payment authorization is independent from shipment authorization. Sender payment details are visible only for `H`, `T`, and `F`. Receiver payment details are visible only for `E` and `L`.
