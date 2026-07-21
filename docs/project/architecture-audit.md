# Architecture Audit

## Intended Flow

Core API -> `SisahygoApiClient` -> Endpoint/Gateway -> Mapper -> DTO/Read Model -> Application Service -> Livewire/Page -> Blade Components -> Localization -> Tests.

## Module Findings

| Module | Layers Present | Findings | Classification |
| --- | --- | --- | --- |
| Sisahygo API Client | Config, credential service, context builder, exceptions, logger, client tests | Retry/logging/exception boundary exists. GET retry is bounded; POST does not retry. | Confirmed |
| Client Account | Models, enums, resolver, middleware, policies, settings Volt components, tests | Selected Client Account is tenant boundary. Credentials stay server-side. | Confirmed |
| Single Order Checking | Endpoint, DTO, mapper, application service, Livewire, Blade, lang, tests | Uses Client API only. Local validation and reference-data revalidation exist. No list/detail/edit/draft. | Confirmed |
| Shipments/Tracking | Endpoint, DTOs, mapper, service, Livewire, Blade, lang, tests | Uses Client API only for operational pages. | Confirmed |
| History | Application service over shipment service, Livewire, Blade, lang, tests | Reuses shipment API; derived recent receivers/products only from loaded page. | Confirmed |
| Dashboard | Application service, Livewire, Blade, lang, tests | Shipment metrics from Core meta; Payment Overview uses cache service. | Confirmed |
| Payments | Endpoint, DTOs, mapper, presenter, service, Livewire list/detail, Blade, lang, tests | F/L/E only; summary and amounts from Core; no local reconstruction. | Confirmed |
| Reports | Placeholder view only | No business implementation. | Placeholder |
| Notifications | Header icon and UX prototype only | No route/module/data model. | Placeholder |

## Critical Checks

| Check | Result | Evidence |
| --- | --- | --- |
| No direct Core DB in operational integration paths | Confirmed | Endpoint classes call `SisahygoApiClient`; services build `SisahygoIntegrationContext`. |
| Direct Core-table query objects absent from codebase | Not confirmed | `AuthorizedOrderQuery` and `AuthorizedPaymentQuery` reference `order_headers`; they are not consumed by operational screens. |
| Credentials rendered or serialized publicly | Not found | Tests assert `secret-api-key` absent; Livewire public state stores mapped data only. |
| Client Account tenant boundary | Confirmed | `client.account` middleware and resolver bind selected account. |
| Payment calculations owned by Core | Confirmed | Payment summary/amount displays come from mapped API values; no paid/outstanding total calculation. |
| Payment scope F/L/E | Confirmed | Presenter/validation/tests exclude H/T from Payment Center. |
| Dashboard payment cache account-scoped | Confirmed | Cache key uses local Client Account id/environment/locale/query shape; tests cover isolation. |
| Single Order Checking API boundary | Confirmed | `OrderCheckingsEndpoint` POST/GET via `SisahygoApiClient`. |

## Technical Notes

- `AuthorizedOrderQuery` and `AuthorizedPaymentQuery` are legacy/local authorization query objects from earlier architecture work. They create tension with the current “Core via Client API only” direction and should not be used for new Connect-to-Core feature work unless intentionally repurposed for test-only/local demo contexts.
- Dashboard Payment Overview remains in the existing dashboard Livewire component rather than a lazy child component. This is an acceptable deviation because failure isolation and targeted loading exist, and cache reduces repeat Core payment calls without serializing credentials.
- `/ux/*` routes should not be used as completion evidence for production features.
