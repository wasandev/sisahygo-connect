# Current Client Account Context

Tenant-dependent pages require an explicit current Client Account context.

Session key: `selected_client_account_id`

Resolution rules:

- Users with no active Client Accounts receive a safe unavailable response.
- Users with exactly one active Client Account are selected automatically after active membership validation.
- Users with multiple active Client Accounts must select one explicitly.
- Session values are never trusted without validating active account status and active membership.
- Invalid or tampered session selections are cleared and redirected to account selection.

The `client.account` middleware is applied only to tenant-dependent routes. User-level routes such as profile, logout, account selection, and authentication remain outside this middleware.
