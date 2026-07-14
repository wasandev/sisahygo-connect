# ADR-005: Sisahygo API Integration Boundary

Status: Accepted

## Context

Sisahygo Connect will consume Sisahygo customer and transactional data through the Sisahygo API. It must not depend on direct production database access to the core Sisahygo database.

## Decision

Treat `client_account_customers.customer_id` as an external Sisahygo customer identifier. Do not create a foreign key from `client_account_customers.customer_id` to a local `customers` table. Keep uniqueness and query indexes on the external identifier.

Future shipment, payment, and customer integration must enter through API client services and continue to use Client Account authorization and authorized query boundaries.

## Consequences

Migrations behave consistently across environments even when a local `customers` table does or does not exist. API integration can evolve without coupling Sisahygo Connect schema to the core Sisahygo production database schema.
