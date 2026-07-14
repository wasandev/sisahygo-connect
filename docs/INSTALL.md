# Sisahygo Connect Setup

Sisahygo Connect is a Laravel, Livewire 3, Tailwind CSS, and Laravel Sail application.

## Local Development

Use WSL for project files and run Sail from the project root.

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run build
./vendor/bin/sail artisan test
```

## Verification

Before handing off sprint work, run:

```bash
composer dump-autoload
npm run build
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test
```

## Notes

- Authentication is provided by the existing Laravel/Livewire stack.
- Tenant-dependent application pages require a selected Client Account.
- The root URL intentionally redirects to login.
- Sisahygo API integration is not implemented yet.
