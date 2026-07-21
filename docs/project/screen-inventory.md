# Screen Inventory

| Screen | Route | Module | Auth | Selected Account | Data Source | Implementation Status | Responsive Evidence | Tests | Docs | Notes |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Welcome | `/` | Landing/auth gateway | Optional | No | Local view | Working | Basic | Starter health | Minimal | Redirects authenticated users to Dashboard. |
| Login/Register/Password/Verify | Auth routes | Authentication | Guest/user | No | Local auth | Working | Breeze defaults | Strong | Basic | Provided by Volt/Breeze. |
| Profile | `/profile` | Profile | Yes | No | Local user DB | Working | Breeze/connect layout | Adequate | Basic | User-level route outside tenant middleware. |
| Client Account Select | `/client-accounts/select` | Client Account | Yes | No | Local Client Account DB | Working | Basic | Strong | Strong | Auto-selects when one active account. |
| Dashboard | `/dashboard` | Dashboard | Yes | Yes | Core Client API + local account | Working | Source uses responsive grids/cards | Strong | Strong | Payment failure isolated. |
| Single Order Checking | `/order-checking` | Order Checking | Yes | Yes | Core Client API + local account links | Working | Source uses stacked cards/grid | Strong | Conflicted historical docs | Create/submit/reconcile only. |
| Shipment List | `/shipments` | Shipments | Yes | Yes | Core Client API | Working | Desktop table/mobile cards | Strong | Adequate | Filter/search/pagination. |
| Shipment Detail | `/shipments/{trackingIdentifier}` | Shipments | Yes | Yes | Core Client API | Working | Responsive sections | Strong | Adequate | Timeline/items. |
| Tracking Lookup | `/tracking` | Tracking | Yes | Yes | Redirect to shipment detail | Working | Simple form | Adequate | Adequate | No direct API call until detail route. |
| History | `/history` | History | Yes | Yes | Core shipment list | Working | Responsive cards/table | Strong | Adequate | Recent receivers/products derived from loaded page. |
| Payment Center | `/payments` | Payments | Yes | Yes | Core Client API | Working | Desktop table/mobile cards | Strong | Strong | F/L/E only. |
| Payment Detail | `/payments/{paymentIdentifier}` | Payments | Yes | Yes | Core Client API | Working | Responsive two-column/stacked | Strong | Strong | No local financial reconstruction. |
| Reports | `/reports` | Reports | Yes | Yes | None | Placeholder | Placeholder | Basic | Planned | Static placeholder. |
| Settings | `/settings`, `/settings/client-account` | Client Account Settings | Yes | Yes | Local Client Account DB | Partial | Responsive grid | Adequate | Adequate | Read-only overview/members/access. |
| UX Preview Pages | `/ux/*` | UX prototypes | Yes | Yes | Static views or real component | Placeholder/Deprecated | Prototype | Adequate smoke | Historical | Not production feature completion evidence. |
