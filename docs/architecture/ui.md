# UI Architecture

Authenticated application pages use the reusable application shell from `resources/views/layouts/app.blade.php` and `resources/views/livewire/layout/navigation.blade.php`.

Canonical Sisahygo Connect Blade components live under `resources/views/components/connect/` and should be referenced with dot notation such as:

```blade
<x-connect.logo />
<x-connect.card />
<x-connect.button />
```

Breeze authentication components remain active for authentication and profile workflows. Preview-only starter artifacts have been removed from active source.

Tenant-dependent navigation assumes the `client.account` middleware has already resolved the current Client Account.
