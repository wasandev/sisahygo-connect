# Core Client API Coverage

This matrix covers endpoints evidenced in Connect code, fixtures, tests, and docs. The repository does not contain a complete authoritative Core endpoint catalog, so endpoints not present here are Unknown/Unverified.

## Consumed And Operational

| Method | Core Endpoint | Purpose | Gateway Class | Consuming Service/Component | Implementation | Tests | Docs | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GET | `/receivers` | Receiver lookup for Order Checking | `ReceiversEndpoint` | `SubmitSingleOrderChecking`, `OrderChecking` | Completed | Strong | Partial | Search query; `findScoped` reuses list search by id. |
| GET | `/products` | Product lookup and product/unit pair revalidation | `ProductsEndpoint` | `SubmitSingleOrderChecking`, `OrderChecking` | Completed | Strong | Partial | Supports `search` and `product_id`. |
| GET | `/units` | Unit lookup | `UnitsEndpoint` | `SubmitSingleOrderChecking` | Completed | Strong | Partial | Loaded on Order Checking mount and revalidated on submit. |
| POST | `/order-checkings` | Single Order Checking submit | `OrderCheckingsEndpoint` | `SubmitSingleOrderChecking` | Completed | Strong | Conflicted historical docs | No automatic POST retry; duplicate prevention via local lock and reconciliation flow. |
| GET | `/order-checkings/{client_reference_no}` | Reconcile unknown order result | `OrderCheckingsEndpoint` | `SubmitSingleOrderChecking::reconcile` | Completed | Strong | Partial | Path uses client reference. |
| GET | `/shipments` | Shipment list/history/dashboard metrics | `ShipmentsEndpoint` | `ShipmentQueryService`, `ListOrderHistory`, Dashboard | Completed | Strong | Adequate | Filters: date/status/tracking/id/order/page/per_page. |
| GET | `/shipments/{tracking_no}` | Shipment detail/timeline | `ShipmentsEndpoint` | `ShipmentQueryService`, `ShipmentShow` | Completed | Strong | Adequate | Tracking route redirects here. |
| GET | `/payments` | Payment Center list/summary and Dashboard overview | `PaymentsEndpoint` | `PaymentQueryService`, `DashboardPaymentOverviewService`, `PaymentIndex` | Completed | Strong | Strong | F/L/E only in UI; summary comes from Core. |
| GET | `/payments/{payment_identifier}` | Payment detail | `PaymentsEndpoint` | `PaymentQueryService`, `PaymentShow` | Completed | Strong | Strong | Accepts `AR-P-*` and `BR-*`. |

## Present In Connect But Unused Or Non-Operational

| Endpoint/Concept | Status | Evidence | Notes |
| --- | --- | --- | --- |
| Local `AuthorizedOrderQuery` against `order_headers` | Present but not consumed by screens | `app/Domain/Shipment/Queries/AuthorizedOrderQuery.php` | Legacy/data-isolation query object; operational shipment screens use Client API. |
| Local `AuthorizedPaymentQuery` against `order_headers` | Present but not consumed by screens | `app/Domain/Payment/Queries/AuthorizedPaymentQuery.php` | Conflicts with current Payment Center Client API boundary if reused for live feature work. |

## Documented Only / Planned

| Method | Endpoint | Status | Evidence | Notes |
| --- | --- | --- | --- | --- |
| POST | `/order-checkings/bulk` | Planned/Unverified | `docs/architecture/core-payment-api-contract-proposal.md`, `docs/features/order-checking.md` | No Connect endpoint class, DTO, route, component, test, or fixture currently implements Bulk. |
| GET | `/order-checkings` | Documented only | Historical proposal docs | No Connect gateway method or screen consumes a list endpoint. |
| Payment summary standalone | Documented as absent/not needed | Payment proposal docs | Dashboard uses `/payments` response summary; no separate summary endpoint. |

## Retry And Logging Ownership

`SisahygoApiClient` owns transport retry and structured request logging. GET requests may retry 429 and selected 5xx/network failures using bounded attempts from config. POST requests do not retry automatically. Application services should not add nested retry loops.
