# Database

Client Account foundation tables:

- `client_accounts`
- `client_account_users`
- `client_account_customers`
- `client_account_capabilities`
- `client_account_activity_logs`

`client_account_customers.customer_id` is an external Sisahygo customer identifier consumed through the Sisahygo API. It intentionally does not reference a local `customers` table.

Important constraints:

- `client_accounts.code` is unique.
- `client_account_users` is unique by `client_account_id` and `user_id`.
- `client_account_customers` is unique by `client_account_id` and `customer_id`.
- `client_account_capabilities` is unique by `client_account_id` and `capability`.

Indexes support active account membership, customer authorization, payment visibility, and capability checks.
