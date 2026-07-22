# Feature Matrix

Status values: Completed, Partial, Placeholder, Planned, Deprecated, Unverified.

| Module | Capability | Route/Screen | Backend/Application Layer | Core API Dependency | UI Status | Test Status | Docs Status | Overall Status | Evidence |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Authentication | Login/register/password/email verification/logout | `/login`, `/register`, `/forgot-password`, `/profile`, POST `/logout` | Breeze/Volt auth components | None | Working | Strong | Adequate | Completed | `routes/auth.php`, auth tests, profile tests. |
| Profile | Update profile/password/delete user | `/profile` | Breeze/Volt profile forms | None | Working | Adequate | Adequate | Completed | `resources/views/profile.blade.php`, `tests/Feature/ProfileTest.php`. |
| Client Account | Select/change account | `/client-accounts/select`, POST select/change | `CurrentClientAccountResolver`, controller, middleware | None | Working | Strong | Strong | Completed | `EnsureClientAccountSelected`, `ClientAccountSelectionController`, ClientAccount tests. |
| Client Account | Settings visibility | `/settings`, `/settings/client-account` | Volt components query local Client Account models | None | Read-only working | Adequate | Adequate | Partial | Shows overview/members/access; no mutation workflows. |
| Dashboard | Shipment summary/latest/attention | `/dashboard` | `GetCustomerDashboard`, shipment/history services | `GET /shipments` | Working | Strong | Strong | Completed | Dashboard tests and docs. |
| Dashboard | Payment widgets/cache | `/dashboard` | `DashboardPaymentOverviewService`, `PaymentQueryService` | `GET /payments` | Working | Strong | Strong | Completed | Payment overview/cache tests. |
| Single Order Checking | Single create/submit/reconcile | `/order-checking` | `SubmitSingleOrderChecking` | `/receivers`, `/products`, `/units`, POST `/order-checkings`, GET `/order-checkings/{client_reference_no}` | Working | Strong | Conflicted but source-of-truth updated | Completed | Route, Livewire page, endpoint, mapper, service, tests. |
| Single Order Checking | List/detail/edit/draft/rejection management | None | None | Unverified/not implemented | None | Missing | Mentioned as not in current scope | Planned | No routes/components/services. |
| Bulk Order Checking | Bulk upload/create | None | Capability enum only | Documented likely `POST /order-checkings/bulk` but not implemented | None | Missing | Planned/docs-only | Planned | `ClientCapability::OrderBulk`, docs references only. |
| Shipment Tracking | Tracking lookup | `/tracking` | `TrackingLookup` redirect | `GET /shipments/{trackingIdentifier}` via detail page | Working | Adequate | Adequate | Completed | `ShipmentPagesTest`. |
| Shipments | List/detail | `/shipments`, `/shipments/{trackingIdentifier}` | `ShipmentQueryService`, `ShipmentsEndpoint` | `GET /shipments`, `GET /shipments/{tracking_no}` | Working | Strong | Adequate | Completed | Shipment page/service tests. |
| Shipment History | History list/filter | `/history` | `ListOrderHistory` | `GET /shipments` | Working | Strong | Adequate | Completed | History tests. |
| Payment Center | List/filter/detail F/L/E | `/payments`, `/payments/{paymentIdentifier}` | `PaymentQueryService`, `PaymentsEndpoint`, presenter | `GET /payments`, `GET /payments/{paymentIdentifier}` | Working | Strong | Strong | Completed | Payment tests/localization tests. |
| Reports | Placeholder page | `/reports` | Static placeholder view | None | Placeholder | Basic route coverage only | Planned | Placeholder | `pages.placeholder`, nav entry. |
| Notifications | Header icon / UX prototype | `/ux/notifications` only | None | None | Prototype only | UX prototype test | Planned | Placeholder | No production notification route. |
| UX Prototypes | Static previews | `/ux/*` | Static views plus OrderChecking preview route | Mixed | Internal/preview | Adequate smoke | Historical/prototype | Deprecated | Production routes exist separately. |

## Single Order Checking Deep Audit

| Capability | Status | Evidence | Notes |
| --- | --- | --- | --- |
| Single create page | Completed | Route `/order-checking`, `OrderChecking` Livewire, Blade page | Four-card create workflow. |
| Single submit | Completed | `SubmitSingleOrderChecking::submit`, `OrderCheckingsEndpoint::create` | Posts once to `/order-checkings`; POST not auto-retried. |
| List | Planned | No route/component/service method found | Do not assume from Core docs. |
| Detail | Planned | No production Order Checking detail route | Reconciliation lookup exists, but not a user-facing detail page. |
| Edit | Planned | No route/component/service method found | Not part of current implementation. |
| Draft | Planned | No local draft model/table/route | Current page state is transient Livewire state only. |
| Rejection display | Partial | API validation and recoverable failure states exist | No historical rejected-order page. |
| Status display | Completed for submission result | `OrderCheckingResult`, success modal shows initial status | Not a full status history. |
| Lookup/autocomplete | Completed | `/receivers`, `/products`, `/units`; Livewire search handlers | Debounced receiver/product search; units loaded on mount. |
| Product/unit selection | Completed | `addProduct`, unit select, service revalidation | Product/unit pair is revalidated before POST. |
| Duplicate client-reference handling | Partial | Local submit lock; Core duplicate fixture; unknown-result reconciliation | Exact Core idempotency contract still needs confirmation for Bulk. |
| Dashboard entry link | Completed | Dashboard shortcut to `order-checking` gated by `order.create` | Uses authorization service. |

Conclusion: Sprint 2A delivered Single Order Checking create/submit/reconcile. It should not be rebuilt in Sprint 6. Missing list/detail/edit/draft capabilities are separate planned extensions or maintenance, not blockers for Bulk unless Bulk UX explicitly depends on them.

## Bulk Order Checking Readiness

| Readiness Item | Status | Evidence | Notes |
| --- | --- | --- | --- |
| Production route | Planned | No route found | Needs new route or UI entry. |
| Livewire component/view | Planned | No Bulk component/view found | Should reuse Single row validation patterns where suitable. |
| Endpoint support | Documented only | Docs mention `/order-checkings/bulk`; no endpoint method | Must verify authoritative Core contract. |
| Authentication/selected account | Ready | Existing auth + `client.account` middleware | Reuse current architecture. |
| `order.bulk` capability | Partial | Enum and demo seeder only | Need authorization policy/service usage. |
| `batch_reference_no` | Unverified | Prompt/docs concept only | Confirm with Core. |
| `batch_date` | Unverified | Prompt/docs concept only | Confirm with Core. |
| `orders` array | Unverified | Prompt/docs concept only | Confirm shape and limits. |
| Per-order `client_reference_no` | Likely reusable | Single supports `client_reference_no` | Bulk duplicate behavior still unverified. |
| Receiver selection | Ready/Partial | Single receiver lookup exists | Need row-level/batch UX decision. |
| Product/unit/items | Ready/Partial | Single product/unit lookup and item payload exist | Need bulk limits and CSV/manual UX decision. |
| `remark`, `client_item_no` | Ready/Partial | Single payload supports these fields | Confirm Bulk support. |
| Validation-error paths | Ready/Partial | Single maps standard envelope | Need row/batch error envelope confirmed. |
| `api_batch_no` | Unverified | Prompt concept only | Need Core response evidence. |
| Partial/all-or-nothing | Unverified | No repo evidence | Must be decided before final UX. |
| Retry/idempotency | Partial | ADR-009 and Single lock/reconcile | Bulk idempotency key/reference behavior unverified. |

Decision: Sprint 6 should be Bulk Order Checking implementation only after the contract verification gate is satisfied. If Core cannot confirm the Bulk contract, run a short Bulk prerequisite/API-contract sprint first.

## Sprint 6 Feature Matrix Update

Bulk Order Checking is implemented locally with route /order-checking/bulk, OrderCheckingBulk Livewire component, SubmitBulkOrderChecking application service, Bulk request/response DTOs, mapper, endpoint method, fixtures, focused tests, and documentation. It remains localhost-only until reviewed; no deploy has been performed.

## Sprint 6.2 Feature Matrix Addendum

Bulk Order Checking now includes: active Order navigator, active Order editor, contextual receiver/product lookup, duplicate Order, mandatory review, filtered result view, copy controls, dirty-state warning, and failed-only retry. It still excludes drafts, CSV/Excel import, automatic retry, direct Core database reads, and local reconstruction of Core business results.
