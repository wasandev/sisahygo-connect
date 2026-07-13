# ADR-002: Shipment Authorization

Status: Accepted

## Context

A Client Account must never see shipments that do not belong to itself.

## Decision

Use `AuthorizedOrderQuery` to derive shipment visibility from authorized sender and receiver customer links. Do not expose customer, product, or shipment master data globally.

## Consequences

Livewire, controllers, APIs, reports, and services can reuse the same data isolation logic.