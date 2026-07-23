# Project Status

Last verified: 2026-07-23
Branch: `feature/staging-readiness`
Commit audited: `2d2947e`
Starting worktree: clean
Route count: 41 routes from `./vendor/bin/sail artisan route:list`
Verification baseline at audit start: `git diff --check` passed

## Executive Status

Sisahygo Connect is a Laravel/Livewire customer portal with authenticated tenant selection through Client Account, Core Client API integrations for Order Checking, Shipments, History, Dashboard, and Payment Center, plus read-only settings/profile surfaces. The Payment Module is the current completed baseline.

## Completed Modules

- Authentication and profile management from Laravel Breeze/Volt auth routes and tests.
- Client Account foundation: selection middleware, membership/capability/customer-link models, settings read-only overview, API connectivity status, credential storage.
- Single Order Checking create/submit/reconcile flow through Core Client API.
- Shipment Tracking, Shipment list/detail, and History using Core shipment endpoints.
- Customer Dashboard workspace with shipment metrics, Dashboard Payment Overview, universal search, pending actions, and notification preview.
- Order Detail page backed by the existing Shipment Detail Core API.
- Payment Center list/detail for payment types `F`, `L`, and `E`, plus Dashboard payment cache.

## Partial Or Placeholder

- Reports route is a placeholder page.
- Settings is operational for Client Account visibility and API connectivity status, but not full account/member/access management mutation UI.
- Notifications has a Phase 1 production route using mock data only; polling, push notifications, and persisted read state are not implemented.
- `/ux/*` routes are prototype/internal preview screens, not production modules.

## Next Milestone

Next milestone: staging smoke testing and deployment planning after Sprint 8 hardening review. No staging deployment has been performed in this sprint.

## Current Blockers And Cautions

- Bulk endpoint is documented only; no Connect route/component/DTO/endpoint exists.
- Existing docs contain historical conflicts, especially `docs/features/order-checking.md` still marking the contract as Draft.
- Legacy local query objects reference Core table names (`order_headers`) but current operational modules use Core Client API. They are not consumed by the current screens.
- Complete Core endpoint catalog is not present in this repository, so unconsumed Core endpoints outside documented/tested evidence are Unknown.

## Verification Commands For This Audit

- `git branch --show-current`
- `git status --short`
- `git log --oneline --decorate -15`
- `git diff --check`
- `./vendor/bin/sail artisan route:list`
- `composer show laravel/framework --locked`
- `composer show livewire/livewire --locked`
- `rg --files routes app resources/views resources/js lang tests config database/migrations database/seeders`
- `rg -n "order-checking|order-checkings|bulk|shipment|payment|placeholder|TODO|FIXME" docs routes app resources tests config database`

## Documentation Consistency Findings

| Finding | Status | Evidence | Treatment |
| --- | --- | --- | --- |
| Order Checking feature contract says Draft | Conflict | `docs/features/order-checking.md` | Treat as historical/superseded by source and `docs/project`. |
| Product roadmap lists Bulk before shipment/payment | Stale sequence | `docs/product/roadmap.md` | Use `docs/project/roadmap-v2.md` for current planning. |
| Payment API proposal says payment endpoints were missing | Superseded | `docs/architecture/core-payment-api-contract-proposal.md` | Preserve as proposal history; current code implements `/payments`. |
| Reports appears in nav but is placeholder | Accurate but easy to misread | `routes/web.php`, `pages.placeholder` | Track as Placeholder in source-of-truth. |
| UX prototype routes exist | Accurate but easy to misread | `/ux/*` routes | Treat as prototype/deprecated, not production status. |

## Sprint 6 Bulk Order Checking Update

Bulk Order Checking is now implemented locally on feature/bulk-order-checking. The production route is /order-checking/bulk and uses the existing auth plus selected Client Account middleware. The workflow uses Sisahygo Client API POST /order-checkings/bulk only, handles HTTP 201, 207, and 422 distinctly, and never retries a full partially successful Batch automatically.

Current limitation: no Excel/CSV import, templates, persisted drafts, background jobs, or editing after Core acceptance.

## Sprint 6.2 Local Status

Bulk Order Checking UX refactor is implemented locally on `feature/bulk-order-checking`. The work is layered on top of uncommitted Sprint 6 and Sprint 6.1 changes. No deploy and no commit have been performed.

## Sprint 7 Customer Workspace Update

Customer Workspace Enhancement is implemented locally on the current worktree. The dashboard remains the main customer workspace and now includes universal search, pending actions, and notification preview. Order History opens a dedicated Order Detail route that reuses the existing Shipment Detail Core API and DTO/mapper path. `/notifications` is Phase 1 UI with mock data only. No deploy and no commit have been performed.

## Sprint 8 Staging Readiness Update

Staging Readiness and Integration Hardening is implemented locally on `feature/staging-readiness`. Configuration is centralized in `config/sisahygo.php` with explicit HTTPS base URL validation and no production fallback to sandbox. Settings includes a protected Sisahygo API connectivity card using existing Core Client API `/units`. User-safe Core API error messaging is centralized, logging remains privacy-safe, GET retries stay limited to safe read-only requests, and POST order creation/bulk creation are not automatically retried. New docs live under `docs/deployment` and `docs/operations`. No deploy and no commit have been performed.
