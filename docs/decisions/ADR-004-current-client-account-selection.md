# ADR-004: Current Client Account Selection

Status: Accepted

## Context

Sisahygo Connect supports users that can belong to more than one Client Account. Selecting the first account by name, ID, or creation date can expose the wrong tenant context and is not acceptable for SaaS use.

## Decision

Use an explicit current Client Account context stored in the session key `selected_client_account_id`.

A user with one active Client Account may be selected automatically, but the account and membership must still be validated. A user with multiple active Client Accounts must choose an account on the account selection screen. A user with no active Client Account receives a safe unavailable response.

Every tenant-dependent request validates that the selected account exists, is active, and has an active membership for the authenticated user.

## Consequences

Tenant pages have a reliable account context before future Sisahygo API integration begins. Profile, logout, authentication, email verification, and account selection remain user-level routes and do not require a selected tenant.
