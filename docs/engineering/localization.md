# Localization Conventions

Thai is the default user interface language.

Rules:

- User-facing Blade and Livewire strings should use Laravel language files.
- Matching English keys should be maintained for future locale support.
- Do not translate route names, internal class names, database values, capability keys, or API field names.
- Navigation keys live under `navigation.*`.
- Page copy lives under `page.*`.
- Account selection copy lives under `account_selection.*`.
- Client Account foundation copy lives under `client_account.*`.
- Payment type and status labels live under `payment.*`.
