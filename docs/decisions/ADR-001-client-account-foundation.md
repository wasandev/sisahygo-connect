# ADR-001: Client Account Foundation

Status: Accepted

## Context

Sisahygo Connect needs a commercial SaaS-ready account model that supports many users and many linked Sisahygo customers per organization.

## Decision

Create Client Account domain tables for accounts, users, customer links, capabilities, and activity logs. Do not model links as `sender`, `receiver`, or `both`; use capability columns on each customer link.

## Consequences

Future customer roles can be added without redesigning the relationship model. Existing authentication remains intact.