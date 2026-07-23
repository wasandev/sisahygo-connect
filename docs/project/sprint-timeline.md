# Sprint Timeline

Last verified: 2026-07-21
Evidence sources: git history, routes, app code, tests, fixtures, docs.

| Evidence | Milestone | Module | Current Status | Notes |
| --- | --- | --- | --- | --- |
| `fe1f734` tag `sprint-foundation` | Foundation | Product architecture and UX baseline | Completed | Base Laravel/Livewire app, UI and documentation baseline. |
| `3c406b8`, `2ebc08c`, `d79fe68`, `801183d` | Sisahygo API foundation | API client, config, security, docs | Completed | Client API boundary, credentials, exception/logging and tests exist. |
| `3bb33ba`, `f6af34a`, `d4bda7b` | Single Order Checking / Sprint 2A | Order Checking | Completed for single create/submit/reconcile | Route, Livewire page, endpoint, DTO, mapper, validation, error handling, tests exist. No list/detail/edit/draft. |
| `7d80d00` | Shipment Tracking | Shipments | Completed | Tracking lookup redirects to shipment detail; shipment list/detail integrate with Core API. |
| `9c8a55e` | Shipment History | History | Completed | History view uses shipment list with date/status/keyword filters plus derived recent receivers/products. |
| `b06d51c` | Customer Dashboard | Dashboard | Completed | Dashboard uses shipment/history services and selected account context. |
| `6bcc672` | Payment API proposal | Payment | Superseded historical proposal | Proposed Payment API contract before implementation. Later commits implement `/payments`. |
| `94149a8` | Sprint 5A Payment Center foundation | Payment | Completed | Payment list/detail foundations committed. |
| `2d2947e` | Sprint 5B/5C Payment Module baseline | Payment/Dashboard | Completed | Payment Center UX, Dashboard widgets, Dashboard payment cache, tests/docs included in current baseline. |
| Docs/product roadmap | Bulk Order Checking | Orders | Planned | Capability exists in enum/demo seed only; no route/component/service/endpoint implementation. |
| Local branch `feature/customer-onboarding-foundation` | Sprint 11A Customer Onboarding Foundation | Onboarding | Implemented locally | Public request access, mock invitation activation, first-login welcome, Client Account selector reuse, onboarding progress UI, and customer empty-state polish. No Core DB/API onboarding calls. |

No missing sprint numbers were fabricated. Sprint names above are reconstructed only where git/doc evidence exists.
