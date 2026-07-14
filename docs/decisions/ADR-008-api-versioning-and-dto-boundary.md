# ADR-008: API Versioning and DTO Boundary

Status: Accepted

## Context

Raw external API arrays would couple Livewire and domain code to Core API field names.

## Decision

Place versioned endpoint classes, DTOs, and mappers under `app/Integrations/Sisahygo/V1`. Create DTOs only for known and tested API contracts used by the integration foundation.

Use domain enums such as `PaymentType` and `PaymentStatus` during mapping.

## Consequences

Future Core API version changes can be isolated in versioned mappers instead of leaking into UI or domain authorization code.