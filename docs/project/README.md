# Sisahygo Connect Project Source Of Truth

Last verified: 2026-07-21
Audited branch: `chore/project-roadmap-consolidation`
Audited commit: `2d2947e` (`feat(connect): complete payment center module`)
Framework baseline: Laravel `v13.19.0`, Livewire `v3.8.2`

## Purpose

This directory is the current project source of truth for implemented status, architecture, API coverage, route/screen inventory, test coverage, technical debt, backlog, and the next roadmap sequence. It was created by auditing executable source code, routes, tests, fixtures, config, migrations, localization, and existing docs.

## Precedence

1. Current executable source code and routes
2. Automated tests
3. Current API contract evidence and fixtures in this repository
4. `docs/project/*` source-of-truth documents
5. Module documentation in `docs/features`, `docs/business`, `docs/architecture`, and `docs/integrations`
6. Historical sprint notes, proposals, and backlog entries

Older docs remain useful as historical context, but this directory wins when they conflict with current source.

## Index

- [Project Status](project-status.md)
- [Sprint Timeline](sprint-timeline.md)
- [Feature Matrix](feature-matrix.md)
- [API Coverage](api-coverage.md)
- [Navigation Audit](navigation-audit.md)
- [Screen Inventory](screen-inventory.md)
- [Architecture Audit](architecture-audit.md)
- [Test Coverage](test-coverage.md)
- [Technical Debt](technical-debt.md)
- [Backlog](backlog.md)
- [Roadmap V2](roadmap-v2.md)
- [Pre-Sprint Checklist](pre-sprint-checklist.md)

## Update Policy

Update this directory after each completed business module, before starting any new major module, after route/API-contract changes, before Sandbox deployment, and before Production deployment. A new sprint must not start if material checklist items in `pre-sprint-checklist.md` are Unverified.
