# Pre-Sprint Checklist

A new sprint must not start when a material item is Unverified.

## Project Evidence

- Feature already exists? Completed / Partial / Placeholder / Planned / Unverified
- Route already exists?
- Component/service already exists?
- Endpoint/DTO/mapper already exists?
- Tests already exist?
- Docs already claim it exists?
- API contract verified from source, fixtures, Core docs, or agreed contract?
- Current branch and worktree status recorded?

## Architecture

- Correct Client API boundary?
- Selected Client Account isolation preserved?
- No direct Core DB dependency introduced?
- Existing DTO/mapper/service patterns reused?
- API credentials protected from Livewire public state, Blade, logs, cache, and URLs?
- Error handling uses safe localized messages?
- Caching, if any, is explicitly scoped and invalidated safely?

## Scope

- Duplicate of previous sprint?
- Prerequisite complete?
- Explicit out-of-scope list written?
- Migration/data impact identified?
- Deployment impact identified?
- Backward compatibility and route naming reviewed?

## Verification

- Focused tests identified before coding?
- Full suite baseline known?
- Frontend build baseline known?
- `git diff --check` baseline known?
- Human review criteria written?
- Documentation update targets listed?

## Sprint 6 Specific Gate

- Bulk endpoint confirmed, not inferred only from roadmap prompt.
- Bulk payload and response shape confirmed.
- Partial/all-or-nothing behavior confirmed.
- Duplicate and idempotency behavior confirmed.
- Single Order reuse points listed so it is not rebuilt.
