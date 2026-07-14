# Developer Setup

Sisahygo Connect uses Laravel, Livewire 3, Tailwind CSS, and Laravel Sail.

Recommended Windows development path:

1. Use WSL for project files.
2. Start Sail from the WSL project directory.
3. Run PHP/Laravel commands through Sail when they depend on the application container.
4. Run frontend builds with `npm run build` from the project root.

Common verification commands:

```bash
composer dump-autoload
npm run build
./vendor/bin/sail artisan optimize:clear
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan route:list
./vendor/bin/sail artisan test
```

Do not reinstall Laravel, Breeze, Livewire, Tailwind, or Vite for routine sprint work.
