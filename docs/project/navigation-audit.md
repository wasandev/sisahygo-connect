# Navigation Audit

Production navigation is defined in `resources/views/livewire/layout/navigation.blade.php` and runs inside authenticated selected-account layout.

| Label | Target Route | Route Exists | Functional Page | Account Selection | Localization | Active State | Status | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Dashboard | `dashboard` | Yes | Yes | Yes | Yes | Yes | Working | Customer dashboard with shipment and payment widgets. |
| สร้างรายการส่งสินค้า | `order-checking` | Yes | Yes | Yes | Yes | Yes | Working | Single Order Checking only. |
| รายการขนส่ง | `shipments` | Yes | Yes | Yes | Yes | Yes | Working | List page. Detail route not in nav but linked from records. |
| ติดตามพัสดุ | `tracking` | Yes | Yes | Yes | Yes | Yes | Working | Lookup form redirects to shipment detail. |
| ประวัติรายการ | `history` | Yes | Yes | Yes | Yes | Yes | Working | History list/filter. |
| การชำระเงิน | `payments` | Yes | Yes | Yes | Yes | `payments*` | Working | Active state covers list and detail. |
| Reports | `reports` | Yes | Placeholder | Yes | Yes | Yes | Placeholder | Static placeholder view. |
| Settings | `settings` | Yes | Partial | Yes | Yes | Yes | Partial | Read-only Client Account overview/members/access. |

## Non-Production UX Routes

`/ux/dashboard`, `/ux/tracking`, `/ux/shipment-detail`, `/ux/payments`, `/ux/reports`, `/ux/settings`, `/ux/profile`, `/ux/notifications` exist under selected-account middleware. They are prototype/static preview routes. `/ux/order-checking` points to the real `OrderChecking` component for preview parity.

## Findings

- No dead production nav links found.
- Reports is intentionally linked while still placeholder; this is product-visible unfinished scope.
- Notifications has a header icon and UX prototype route, but no production notification module route.
- `/settings` and `/settings/client-account` both render the same settings page; this is acceptable aliasing but should be documented.
## Route Audit

| Method | URI | Name | Middleware | Module | Action | Purpose | Status | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GET/HEAD | `/` | `welcome` | web | Welcome | Closure/view redirect | Public entry | Working | Redirects authenticated users to Dashboard. |
| GET/HEAD | `/dashboard` | `dashboard` | auth, client.account | Dashboard | `CustomerDashboard` | Authenticated account dashboard | Working | Includes shipment and payment widgets. |
| GET/HEAD | `/client-accounts/select` | `client-accounts.select` | auth | Client Account | `ClientAccountSelectionController@index` | Account selection | Working | Outside tenant middleware by design. |
| POST | `/client-accounts/select` | `client-accounts.select.store` | auth | Client Account | `ClientAccountSelectionController@store` | Persist selected account | Working | Validates authorized account id. |
| POST | `/client-accounts/change` | `client-accounts.change` | auth | Client Account | `ClientAccountSelectionController@change` | Clear selected account | Working | Redirects to selector. |
| GET/HEAD | `/order-checking` | `order-checking` | auth, client.account | Single Order Checking | `OrderChecking` | Create one checking request | Working | No list/detail/edit/draft route. |
| GET/HEAD | `/shipments` | `shipments` | auth, client.account | Shipments | `ShipmentIndex` | Shipment list | Working | Core API list. |
| GET/HEAD | `/shipments/{trackingIdentifier}` | `shipments.show` | auth, client.account | Shipments | `ShipmentShow` | Shipment detail/timeline | Working | Core API detail. |
| GET/HEAD | `/tracking` | `tracking` | auth, client.account | Tracking | `TrackingLookup` | Tracking lookup form | Working | Redirects to shipment detail. |
| GET/HEAD | `/history` | `history` | auth, client.account | History | `OrderHistory` | Shipment/order history | Working | Core shipment list with presets. |
| GET/HEAD | `/payments` | `payments` | auth, client.account | Payments | `PaymentIndex` | Payment Center list/filters | Working | F/L/E only. |
| GET/HEAD | `/payments/{paymentIdentifier}` | `payments.show` | auth, client.account | Payments | `PaymentShow` | Payment detail | Working | `AR-P-*` and `BR-*`. |
| GET/HEAD | `/reports` | `reports` | auth, client.account | Reports | `pages.placeholder` | Future reports page | Placeholder | In primary navigation. |
| GET/HEAD | `/settings` | `settings` | auth, client.account | Settings | `settings.client-account` | Account settings view | Partial | Read-only Client Account sections. |
| GET/HEAD | `/settings/client-account` | `settings.client-account` | auth, client.account | Settings | `settings.client-account` | Account settings alias | Partial | Duplicate target intentionally. |
| Auth routes | `/login`, `/register`, password, verification | varied | guest/auth | Auth | Volt/Breeze | Authentication | Working | Starter-kit routes. |
| Livewire internals | `/livewire/*` | internal | web | Livewire | Framework | Runtime assets/upload/update | Hidden/Internal | Not product screens. |
| Storage/up | `/storage/{path}`, `/up` | framework | web | Framework | Framework | local storage/health | Hidden/Internal | Not product screens. |
| UX previews | `/ux/*` | `ux.*` | auth, client.account | UX Prototype | Static views or `OrderChecking` | Design previews | Placeholder/Deprecated | Not completion evidence for production features. |

## Sprint 6 Navigation Update

Bulk Order Checking is available at /order-checking/bulk under the existing Order Checking area. The global navigation still has one Order Checking item, whose active state covers order-checking subroutes, avoiding a duplicate sidebar entry.

## Sprint 6.2 Navigation Addendum

The `/order-checking/bulk` route remains under the Order Checking area. The page now includes a back link to single Order Checking, active Order previous/next controls, validation-summary jump buttons, and result links to Tracking and History.
