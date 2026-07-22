# Technical Debt Register

| Severity | Title | Affected Module | Evidence | User/Business Impact | Technical Impact | Recommended Treatment | Suggested Milestone | Blocks Sprint 6 |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Major | Bulk API contract not confirmed in executable code | Bulk Order Checking | Only docs mention `/order-checkings/bulk`; no endpoint/DTO/fixture/test | Bulk scope can drift or duplicate Single assumptions | Risk of wrong payload/error semantics | Confirm contract before or as first task of Sprint 6 | Sprint 6 kickoff | Yes, until checklist verified |
| Major | Legacy direct Core-table query objects remain in app namespace | Shipment/Payment architecture | `AuthorizedOrderQuery`, `AuthorizedPaymentQuery` use `DB::table('order_headers')` | Could mislead future work into bypassing Client API | Violates current architectural direction if reused operationally | Mark as legacy/test-only or remove after replacing tests | Sprint 6 hardening or maintenance | No, if not used |
| Moderate | Order Checking docs are historical/stale | Single Order Checking | `docs/features/order-checking.md` still says Draft and contains pre-implementation uncertainty | Product planning may recreate completed work | Conflicts with current source | Add superseded banner or revise against implementation | Sprint 6 prep | No |
| Moderate | Reports is visible in nav while placeholder | Reports | `/reports` route renders `pages.placeholder` | Users may expect working reports | Placeholder route maintained in production nav | Decide whether to hide, keep placeholder, or implement | Phase 2 | No |
| Moderate | Notifications are prototype-only | Notifications | `/ux/notifications`, header icon only | Users see icon without module behavior | No data model/service/route | Hide icon or define module scope | Phase 2/Future | No |
| Moderate | Settings is read-only partial | Client Account Settings | Volt components display overview/members/access only | Admin users cannot manage members/access in app | Future mutation/authz work needed | Define settings-management sprint | Phase 2 | No |
| Minor | Shipment dashboard all-or-nothing for shipment failures | Dashboard | Payment failure isolated; shipment service exceptions still page-level | A shipment API issue can hide dashboard content | Less granular resilience | Consider section-level dashboard services if needed | Future reliability | No |
| Minor | `/ux/*` routes remain in authenticated selected-account group | UX prototypes | Route file contains prototype routes | Possible confusion during audits/demo | Prototype/prod route overlap | Keep documented or move behind local-only guard | Maintenance | No |
| Informational | Dashboard payment cache freshness is TTL-bound | Dashboard payments | 60 second default TTL | Core changes may lag briefly on dashboard | Accepted performance tradeoff | Keep documented; tune config if users report staleness | Operations | No |

## Sprint 6 Bulk Technical Debt

Bulk manual entry is intentionally implemented without CSV/Excel import, templates, persisted drafts, or background jobs. Future import or draft work should preserve the same Client API boundary and failed-row-only retry rule.

## Sprint 6.2 Technical Debt Addendum

Bulk Order Checking still does not include persisted drafts, import/export files, background processing, browser-level visual regression tests, or automated clipboard/browser-beforeunload assertions. These remain future enhancements, not blockers for the Client API workflow.
