# Sisahygo Connect

Sisahygo Connect is a Laravel and Livewire application for authenticated client-account workflows.

Current baseline:

- Laravel with Livewire 3
- Tailwind CSS
- Laravel Sail development workflow
- Existing authentication preserved
- Tenant-safe Client Account foundation
- Thai default localization with English keys prepared
- Sisahygo API integration foundation implemented; business API modules are not implemented yet

The root URL shows a guest welcome page. Authenticated users are sent to the dashboard, and tenant-dependent pages require a valid current Client Account context.
## Local Demo Data

Seed deterministic Client Account demo data with:

```bash
./vendor/bin/sail artisan db:seed '--class=Database\Seeders\Development\ClientAccountDemoSeeder'
```

Demo users and reset instructions are documented in `docs/engineering/demo-data.md`.

## Sisahygo API Integration Foundation

Sprint 1.5 adds the secure integration foundation for the external Sisahygo Client API. API credentials are stored per Client Account and encrypted at rest. Sender and receiver scope is always derived from validated Client Account customer links, not from the API credential itself.

Configuration lives in `config/sisahygo.php`. Do not commit real API keys. Receiver-only API support remains dependent on Core API authorization by `order_headers.customer_rec_id`; see `docs/integrations/sisahygo/receiver-compatibility-gap.md.
