# Install Sisahygo Connect Starter Kit into Laravel

1. Copy the contents of this ZIP into your Laravel project root.
2. Merge `tailwind.config.js` if your project already has one.
3. Merge `vite.config.js` if your project already has one.
4. Copy Blade components into `resources/views/components`.
5. Add this to your layout `<head>`:

```blade
<x-application.meta />
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

6. Use logo component:

```blade
<x-application.logo />
<x-application.logo mode="dark" />
<x-application.logo variant="vertical" />
<x-application.logo variant="symbol" />
```

7. Preview sample pages:

```php
Route::view('/connect-login-preview', 'pages.login-preview');
Route::view('/connect-dashboard-preview', 'pages.dashboard-preview');
```
