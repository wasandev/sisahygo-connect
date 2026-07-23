# Customer Onboarding Foundation

Sprint 11A introduces a customer-facing onboarding foundation for Sisahygo Connect. The implementation is intentionally local to Connect and does not call Sisahygo Core API during onboarding.

## Scope

- Public request-access form for prospective customers.
- Local pending access request record with generated invitation token.
- Mock invitation activation screen using the local token, plus support for fake tokens for demo review.
- User activation with password setup, email verification timestamp, client account creation, and owner membership.
- First-login welcome workspace before client account selection.
- Reuse of the existing multiple Client Account selector after onboarding.
- Reusable onboarding progress component.
- Customer empty-state refinements across dashboard, history, payments, shipments, and tracking.
- Public navigation update to promote Request Access.

## Architecture Boundary

Onboarding state is stored in Connect-local tables because this sprint is a foundation and mock-flow phase. It must not read or write the Sisahygo Core database directly.

The sprint does not introduce Core API calls, Core schema changes, polling, push notifications, or live invitation delivery. Future Core integration should keep the established boundary:

Livewire / Controller -> Application Service -> DTO / Mapper -> Sisahygo Core API

## User Flow

1. A guest opens `/request-access`.
2. The guest submits company and contact details.
3. Connect stores a pending `access_requests` row with an invitation token.
4. A reviewer can use `/invitation/{token}` for the mock activation flow.
5. The invited customer sets a password.
6. Connect creates or updates the user, creates a local Client Account, assigns owner membership, logs the user in, and sends them to `/welcome`.
7. The first-login welcome screen marks `onboarding_welcomed_at` and continues to the existing client account selector.

## Current Limitations

- No email delivery.
- No admin approval workspace.
- No persisted onboarding checklist beyond the first-login welcome timestamp.
- No Core customer/account provisioning.
- No production invitation security hardening beyond token lookup suitable for this mock phase.

## Verification

Feature coverage lives in `tests/Feature/CustomerOnboardingTest.php`. Existing authentication, welcome page, dashboard, history, payment, and shipment tests were updated to reflect the new onboarding defaults and empty-state copy.
