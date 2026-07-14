# ADR-009: Idempotent Order Submission

Status: Accepted

## Context

Future Order Checking and Bulk Order Checking operations may submit POST requests that must be protected from duplicate submissions and unknown-result retries.

## Decision

Do not create a final idempotency schema in Sprint 1.5. Document the contract and defer the final storage design until the Core API confirms support for `Idempotency-Key`, `client_reference_no`, `batch_reference_no`, timeout reconciliation, and unknown-result recovery.

POST requests are not automatically retried in Sprint 1.5.

## Consequences

The foundation avoids inventing Core API behavior while leaving extension points for safe future submissions.