# Customer Onboarding

Sprint 12B Phase 1 replaces the previous local/mock access-request submission with a real Sisahygo Core Client API integration. Connect remains the customer-facing app; Core is the source of truth for submitted access requests, approval, invitation state, and later activation.

## Scope

- Public request-access form for prospective customers.
- Livewire submission flow with Thai success and error states.
- Server-side POST from Connect to Sisahygo Core Client API.
- Stable `connect_reference` generation in Connect so retrying the same component/session does not unexpectedly create a second Core request.
- Core response display with request number, submitted email, and pending approval explanation.
- Existing mock invitation activation remains unchanged for now. Invitation activation is not part of this phase.

## Architecture Boundary

Connect must not read or write the Sisahygo Core database. Access-request submission follows the same integration boundary used elsewhere in Connect:

```text
Client Account / UI
-> API Endpoint class
-> DTO / Mapper
-> Application Service
-> Livewire
-> Blade
```

For this pre-account public onboarding endpoint, the existing Sisahygo HTTP client transport conventions are reused without a client-account credential. Requests use the trusted configured base URL, JSON headers, `X-Correlation-ID`, configured timeouts, safe exception mapping, and sanitized request logging. They do not include `X-Api-Key`.

## Core API Contract

Endpoint:

```http
POST /api/v1/client/access-requests
```

Connect sends the endpoint relative to `SISAHYGO_API_BASE_URL` / selected environment URL as `/access-requests`.

Request payload sent by Connect:

```json
{
  "company_name": "Acme Logistics",
  "contact_name": "Anong Contact",
  "email": "contact@example.com",
  "phone": "0812345678",
  "province": "กรุงเทพมหานคร",
  "website": "https://example.com",
  "branch_count": 3,
  "notes": "Need Connect onboarding.",
  "connect_reference": "CONNECT-REQ-20260724-ABCDEF1234567890",
  "submitted_at": "2026-07-24T10:00:00+07:00"
}
```

Current Connect form fields map as follows:

| Connect form field | Core field |
| --- | --- |
| `company_name` | `company_name` |
| `contact_name` | `contact_name` |
| `email` | `email` |
| `phone` | `phone` |
| `province` | `province` |
| `website` | `website` |
| `number_of_branches` | `branch_count` |
| `additional_notes` | `notes` |

Core success response:

```json
{
  "data": {
    "request_no": "CAR-20260724-ABCDEFGH",
    "public_id": "CAR-20260724-ABCDEFGH",
    "connect_reference": "CONNECT-REQ-20260724-ABCDEF1234567890",
    "status": "pending",
    "status_label": "รออนุมัติ",
    "submitted_at": "2026-07-24T10:00:00+07:00"
  },
  "meta": {
    "duplicate": false
  }
}
```

Core treats `(api_client_id, connect_reference)` as the idempotency key. If the same reference is submitted again, Core returns the existing request with `meta.duplicate=true`.

## Environment Variables

```env
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_BASE_URL=
SISAHYGO_API_SANDBOX_URL=https://sandbox-api.sisahygo.online/api/v1/client
SISAHYGO_API_LIVE_URL=https://api.sisahygo.online/api/v1/client
SISAHYGO_API_CONNECT_TIMEOUT=5
SISAHYGO_API_TIMEOUT=15
SISAHYGO_API_USER_AGENT="Sisahygo Connect"
```

Public pre-account onboarding requests do not use a Sisahygo Client API key. Authenticated per-client operations such as products, units, order checking, shipments, payments, and history continue to resolve encrypted API credentials per Client Account and send `X-Api-Key` server-side only.

## Public vs Authenticated API Requests

Public pre-account onboarding:

- Endpoint: `/access-requests` under the configured Sisahygo Client API base URL.
- Header: no `X-Api-Key`; only JSON headers, user agent, and `X-Correlation-ID`.
- Purpose: collect a request so Core administrators can approve and later issue invitations.

Authenticated per-client operations:

- Endpoints: existing customer/account operations such as `/products`, `/units`, `/order-checkings`, `/shipments`, and `/payments`.
- Header: `X-Api-Key` resolved from the active encrypted credential for the selected Client Account and environment.
- Purpose: tenant-scoped operations after a Client Account exists.

## Error Handling

Connect maps Core errors to safe Thai UI messages and preserves sanitized context in logs:

- `422` validation errors are mapped back onto matching form fields, including `branch_count` -> `number_of_branches` and `notes` -> `additional_notes`.
- Duplicate/idempotency responses use Core `meta.duplicate`; duplicate reference validation errors are shown as a page-level validation message.
- `401` and `403` show support-oriented authentication/authorization copy.
- `429` asks the user to wait before retrying.
- Connection failures, timeouts, and `5xx` responses do not show raw Core response bodies or stack traces.

## Local Connect To Sandbox Core Procedure

1. Configure `.env` with sandbox values:

```env
SISAHYGO_API_ENVIRONMENT=sandbox
SISAHYGO_API_BASE_URL=https://sandbox-api.sisahygo.online/api/v1/client
```

2. Clear cached config:

```bash
php artisan config:clear
```

3. Open `/request-access` as a guest.
4. Submit company, contact, email, phone, province, optional website, branch count, and notes.
5. Confirm the success screen shows the Core `request_no`, submitted email, and pending approval message.
6. In Core/Nova, confirm a `pending` Connect access request exists with the same `connect_reference`.
7. Reloading or retrying the same Livewire component before success should reuse the same `connect_reference`; Core should return the existing request rather than creating a duplicate.


## Invitation Activation Contract Needed From Core

Sprint 12B Phase 2 implements the Connect-side integration contract, UI, local activation transaction, and tests with Http::fake. Core is the source of truth for invitation state, access-request activation, API client creation, and customer mappings; Connect provisions only its local user and client-account projection after a successful Core activation response.

Required endpoints:

- GET /api/v1/connect-onboarding/invitations/{token}
- POST /api/v1/connect-onboarding/invitations/{token}/activate

The token is opaque and appears only in the server-side request path. Connect logs these calls with a redacted endpoint, /connect-onboarding/invitations/{token}, and never persists the raw token.

Preview response expected by Connect includes `data.status`, `data.email`, `data.company_name`, `data.contact_name`, `data.role`, `data.email_verified_by_invitation`, `data.expires_at`, and `data.client_account.code/name`.

Activation request sent by Connect contains only the invited email. Connect does not send the password to Core. Password storage is local to Connect and uses Laravel hashing after Core confirms activation for a new user. If the invitation email already belongs to an existing Connect user, activation never resets or re-hashes that user's password; the existing credentials remain valid and Connect only provisions the additional client-account membership, customer mappings, and capabilities.

Current UX still shows the password form before Connect knows whether the local user exists. For existing users, the submitted password is intentionally ignored. A follow-up UX improvement should let existing users authenticate with their existing password instead of presenting the flow as a password reset.

Activation response expected by Connect follows the Core provisioning contract: `data.invitation_reference`, `data.activation_status`, `data.user.email`, `data.user.role`, `data.user.email_verified_by_invitation`, `data.client_account.code/name`, `data.customer_mappings`, `data.capabilities`, and `data.credential`. `data.capabilities` may be an empty array and `data.credential` may be null; both are valid present values, not incomplete responses. `api_client_id` is internal Core diagnostic metadata and is not part of the public activation response contract.

Core should return explicit statuses or standard error envelopes for invalid, expired, revoked, already-used, rate-limited, and server-error states. Connect creates or reuses the local user, client account projection, membership, customer mappings, and capabilities only after the activation response is successful. A repeated Core activation with `activation_status: already_activated` is a replayable recovery path after a local provisioning failure and must not create duplicate local records.

## Verification

Focused coverage lives in `tests/Feature/Onboarding/AccessRequestCoreSubmissionTest.php` and the updated `tests/Feature/CustomerOnboardingTest.php`. Tests use `Http::fake()` and assert that Connect does not create a duplicate local `access_requests` row during Core submission.

## Post-Activation Credential Setup

Invitation activation provisions only Connect-local account access. Core remains the source of truth for approval, invitation state, customer mappings, and the Core API Client, but activation does not issue a Client API credential and `data.credential` remains `null`.

After activation, owners or administrators with `settings.manage` should open Client Account Settings -> Sisahygo API and paste the one-time plaintext API key generated by a Sisahygo Core administrator from the approved API Client Nova action `สร้าง/เปลี่ยน API Key`. Connect verifies the key with Core `GET /api/v1/client/ping` before saving it encrypted. If verification fails, the key is not stored and any existing active local credential remains active.

Members without settings-management permission see neutral guidance that an account administrator must complete API setup. No saved credential, decrypted key, raw Core response, token, password, or API secret is shown in the browser after submission.


## First-time Setup Experience

After successful invitation activation and login, Connect routes the user to `/welcome` without requiring a Sisahygo API credential. The welcome page derives setup state from persisted local data for the selected Client Account rather than from session-only flags:

1. Account created
2. Client Account created
3. Active membership provisioned
4. Active customer mapping provisioned
5. Sisahygo API Credential configured
6. Sisahygo API connection verified by credential setup

A Client Account is considered ready for use when the local account, membership, customer mapping, active credential, and verification-backed connection state are all complete. The setup UI shows completed, current, and future steps. Owners or administrators with `settings.manage` get a direct path to Client Account Settings -> Sisahygo API. Other members see guidance that an account owner or administrator must complete the Sisahygo API connection.

The authenticated dashboard includes a selected-account-scoped, non-blocking setup banner while the Client Account is incomplete. The banner disappears for ready accounts and is recalculated when the user switches Client Accounts. It does not block login or general navigation; individual Core-powered features may still show their normal unavailable states until an active credential exists.
