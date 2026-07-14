# Sisahygo API Authentication

The Sisahygo Client API uses `X-Api-Key` authentication.

Sisahygo Connect stores credentials per Client Account and environment. Credentials are encrypted at rest and hidden from serialization. Decrypted keys must never be exposed to Blade, Livewire public properties, logs, exceptions, or browser responses.

Supported credential lifecycle:

- create active credential
- replace active credential for an account/environment
- keep historical credentials
- revoke credentials
- identify credentials by fingerprint
- update `last_used_at`

The credential is not customer identity. Sender and receiver scope must always be derived from `client_account_customers`.