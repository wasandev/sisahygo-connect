# Release Candidate Signoff

Use this template before approving a future staging deployment.

## Required Evidence

- Full Laravel suite passed.
- Frontend build passed.
- `git diff --check` passed.
- Pint ran on touched PHP files.
- Tracked changes scanned for secrets.
- Read-only smoke test passed with approved sandbox account.
- Controlled write smoke test either passed with approval or was explicitly skipped.

## Signoff Checklist

- Architecture boundary preserved: middleware/resolver -> encrypted credential -> integration context -> API client/endpoint -> DTO/mapper -> application service -> Livewire -> Blade.
- No Core DB access.
- No Core code changes.
- No automatic POST retries.
- No secrets in tracked files or output.
- Account switching verified.
- Notification Center still documented as mock-only.
- Rollback owner and credential-rotation owner identified.

## Known Limitations To Accept

- Notification Center is mock-only.
- Smoke commands validate representative safe endpoints, not every Core feature path.
- Destructive cleanup is not implemented without a Core-supported contract.
