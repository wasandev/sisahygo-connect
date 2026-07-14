# Sisahygo API Integration Overview

Sisahygo Connect integrates with the external Sisahygo Client API through an API-only boundary. The application must not query the Sisahygo production database directly.

Integration code lives under `app/Integrations/Sisahygo`. Domain authorization remains in `app/Domain`.

Every external request must be built from a validated integration context containing:

- authenticated user identity
- selected active Client Account identity
- required Client Account capability
- active encrypted API credential for the selected environment
- authorized sender customer IDs
- authorized receiver customer IDs
- correlation ID

API credentials authenticate Sisahygo Connect to the Core API. They are not sender or receiver identities. Sender and receiver identities come only from Client Account customer links.