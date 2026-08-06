# Business Capability Model

Sisahygo Connect uses Client Account capabilities to decide which customer-facing business features an active account can use. Capabilities are account-level feature flags. User roles still decide administrative authority, especially for settings and user management.

## Standard Business Baseline

Newly approved Client Accounts receive this business baseline during invitation activation:

| Enum case | Capability key | Feature |
| --- | --- | --- |
| `ClientCapability::OrderCreate` | `order.create` | Single Order Checking |
| `ClientCapability::OrderBulk` | `order.bulk` | Bulk Order Checking |
| `ClientCapability::ShipmentView` | `shipment.view` | Dashboard shipment data, Tracking, Shipments |
| `ClientCapability::ShipmentHistory` | `shipment.history` | Shipment History readiness and policy scope |
| `ClientCapability::PaymentView` | `payment.view` | Payment Center list/detail |

This baseline is intentionally limited to production-ready customer business features. It does not grant every enum capability automatically.

## Administrative Capabilities

Administrative capabilities are separate from the business baseline:

| Enum case | Capability key | Notes |
| --- | --- | --- |
| `ClientCapability::SettingsManage` | `settings.manage` | Granted separately during activation only when the activated role can manage account settings. Required for API credential setup/replacement. |
| `ClientCapability::UsersManage` | `users.manage` | Administrative user-management capability. Not part of the normal customer business baseline. |

## Deferred Or Optional Capabilities

These capabilities remain excluded from the baseline until their customer-facing workflows are production-ready or explicitly enabled for an account:

| Enum case | Capability key | Notes |
| --- | --- | --- |
| `ClientCapability::ShipmentExport` | `shipment.export` | Export workflow is not part of the baseline. |
| `ClientCapability::PaymentDownload` | `payment.download` | Download workflow is not part of the baseline. |

## Customer Mapping Roles

Customer links define data visibility and action eligibility:

| Mapping role | Local flags | Intended visibility/action |
| --- | --- | --- |
| `sender` | `can_send = true`, `can_receive = false` | Can create order checking as an authorized sender and view sender-side scoped data. |
| `receiver` | `can_send = false`, `can_receive = true` | Can view receiver-side scoped data where the Core endpoint supports receiver scope. |
| `both` | `can_send = true`, `can_receive = true` | Can use both sender and receiver scopes where the Core endpoint supports both scopes. |

Connect preserves separate authorized sender and receiver customer IDs in `SisahygoIntegrationContext`. Core payment endpoints already apply sender and receiver mapping roles. Core shipment endpoints currently scope `/shipments` by the API client's sender customer only, so receiver-side shipment/history visibility requires a Core endpoint enhancement before Connect can display receiver shipments through that endpoint.

## Existing Account Repair

Existing activated accounts can be repaired with the idempotent command:

```bash
php artisan client-account:provision-baseline-capabilities {account} --dry-run
php artisan client-account:provision-baseline-capabilities {account}
```

The command displays the account identity, currently present capabilities, missing baseline capabilities, and capabilities to add. It adds only missing baseline capabilities, does not remove or duplicate rows, preserves custom capabilities, does not touch API credentials, and does not call Sisahygo Core.

## Reports Phase 1

- `report.view` controls the Reports center and HTML report pages.
- `report.export` controls Excel download routes and is checked server-side.
- These are separate from `shipment.export`, `payment.download`, `settings.manage`, and `users.manage`.
- The standard business baseline now provisions `report.view` and `report.export`; the baseline repair command adds missing rows idempotently without removing custom capabilities.
