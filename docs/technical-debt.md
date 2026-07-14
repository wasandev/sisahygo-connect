# Technical Debt

Deferred work before or during later sprints:

- Add a full account switcher experience beyond the minimal account selection page.
- Decide whether profile UI should migrate fully from Breeze components to Sisahygo Connect components.
- Add model factories for Client Account domain models to reduce test duplication.
- Add HTTP-level shipment/payment endpoint tests when those endpoints exist.
- Expand Thai validation language coverage for all auth/profile validation messages.
- Add CI automation for build, migration, and test verification.
- Add API client contract tests once Sprint 1.5 defines the Sisahygo API boundary.

Resolved in hardening baseline:

- Removed silent first-account selection for multi-account users.
- Removed conditional customer foreign key behavior from the Client Account customer migration.
- Removed unused Laravel starter welcome and preview views.
- Consolidated payment type grouping into `PaymentType`.

Resolved in Sprint 1.5 foundation:

- Added Sisahygo API configuration, encrypted per-Client-Account credential storage, integration context, HTTP client foundation, fixtures and contract-style tests.
- Documented receiver Core API compatibility gap.