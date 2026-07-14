# Sisahygo API Versioning

Version-specific integration code lives under `app/Integrations/Sisahygo/V1`.

V1 owns endpoint classes, request/response mapping, DTOs, and field-name translation. Domain code and Livewire components must not depend on raw API arrays or external field names.

If a future Core API version changes response shapes, new versioned mappers should isolate that change without rewriting domain authorization rules.