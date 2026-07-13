# Database

Sprint 1 adds only additive tables:

- client_accounts
- client_account_users
- client_account_customers
- client_account_capabilities
- client_account_activity_logs

Existing Sisahygo tables are not redesigned. `client_account_customers.customer_id` maps to `customers.id`; local development can run without the Sisahygo master tables, while production can enforce the FK when `customers` exists.