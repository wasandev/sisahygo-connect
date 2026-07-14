# Sisahygo API Security

Security rules:

- API credentials authenticate requests only; they are not customer identity.
- Sender scope comes from active Client Account customer links with `can_send = true`.
- Receiver scope comes from active Client Account customer links with `can_receive = true`.
- Payment access also requires `can_view_payment = true` and payment capability.
- Arbitrary customer IDs from browsers or callers must be rejected unless they match the selected Client Account scope.
- Queued work must reconstruct context from explicit IDs and revalidate account, credential, capability, and customer scope.
- Logs must never include API keys or full sensitive payloads.

Capability checks happen before external requests are sent.