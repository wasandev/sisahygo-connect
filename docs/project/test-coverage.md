# Test Coverage Audit

Qualitative values: Strong, Adequate, Partial, Missing, Unverified.

| Test File/Group | Module | Important Scenarios | Status | Gaps/Risks |
| --- | --- | --- | --- | --- |
| `tests/Feature/Auth/*` | Authentication | Login, registration, password, email verification | Strong | Standard starter-kit coverage. |
| `tests/Feature/ProfileTest.php` | Profile | Profile/password/delete behavior | Adequate | Visual/responsive not browser-tested. |
| `tests/Feature/ClientAccount/*` | Client Account | relationships, capabilities, settings, current context | Strong | Mutation workflows for members/access are not implemented. |
| `tests/Feature/Authorization/*` | Authorization | capability checks | Adequate | Module-specific authorization mostly covered in feature tests. |
| `tests/Feature/DataIsolation/*` | Legacy query isolation | `AuthorizedOrderQuery`, `AuthorizedPaymentQuery` | Partial | Uses synthetic local `order_headers`; not operational Client API path. |
| `tests/Feature/Integrations/Sisahygo/*` | API integration foundation | config, credentials, context, HTTP client, Order Checking endpoints | Strong | Complete Core catalog not available. |
| `tests/Feature/OrderChecking*` | Single Order Checking | page, receiver/product/unit lookup, submit, validation, unknown result reconciliation | Strong | No list/detail/edit/draft because not implemented. |
| `tests/Feature/Shipment*` | Shipments/Tracking | list/detail/lookup, selected account, validation | Strong | No export coverage despite capability enum. |
| `tests/Feature/History*` | History | presets, filters, dashboard-derived recent lists | Strong | No analytics/export coverage. |
| `tests/Feature/CustomerDashboard*` | Dashboard | request counts, Core meta totals, selected account, safe errors | Strong | Shipment dashboard remains all-or-nothing on shipment API failures. |
| `tests/Feature/DashboardPayment*` | Dashboard payments/cache | widgets, links, cache hit/miss, isolation, force refresh, stale fallback, safe logs | Strong | No browser timing test for actual skeleton animation. |
| `tests/Feature/Payment*` | Payment Center | list/detail, filters, F/L/E labels, H/T exclusion, errors, large decimals | Strong | No online payment flows because out of scope. |
| `tests/Feature/UxPrototypeTest.php` | UX prototypes | Static preview smoke | Adequate | Prototype routes are not production feature coverage. |
| `tests/Feature/Localization/PaymentLocalizationTest.php` | Localization | Payment localization completeness | Adequate | Only payment-specific localization audited. |

## Skipped Tests

No skipped test files were found during repository inventory.

## Focused Suites For Future Work

- Single Order: `OrderCheckingPageTest`, `OrderCheckingServiceTest`, `Integrations/Sisahygo/OrderCheckingIntegrationTest`
- Payments: `PaymentQueryServiceTest`, `PaymentPagesTest`, `DashboardPaymentOverviewTest`, `DashboardPaymentCacheTest`
- Shipments/History/Dashboard: `ShipmentQueryServiceTest`, `ShipmentPagesTest`, `History*`, `CustomerDashboard*`

Do not report coverage percentages unless a coverage tool is run.
