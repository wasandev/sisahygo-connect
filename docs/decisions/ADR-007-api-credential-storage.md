# ADR-007: API Credential Storage

Status: Accepted

## Context

A global API key is not sufficient for SaaS-ready Client Account isolation, credential rotation, environment separation, and auditability.

## Decision

Store encrypted Sisahygo API credentials per Client Account and environment in `sisahygo_api_credentials`. Keep a key fingerprint for safe identification. Support active and revoked credentials, rotation history, `last_used_at`, and creator tracking.

API credentials authenticate Sisahygo Connect only. Sender and receiver customer identity comes from Client Account customer links.

## Consequences

Credential lifecycle can evolve without exposing keys to Blade, Livewire, logs, exceptions, or browser responses.