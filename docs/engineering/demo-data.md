# Local Demo Data

Sisahygo Connect includes deterministic local-only demo data for the Client Account Foundation.

Run the demo seeder explicitly:

```bash
./vendor/bin/sail artisan db:seed '--class=Database\Seeders\Development\ClientAccountDemoSeeder'
```

The main `DatabaseSeeder` only calls this demo seeder when the application environment is `local`.

## Demo Password

All demo users use the clearly fake local password:

`password`

Never reuse this password for production or shared environments.

## Demo Login Accounts

| Scenario | Email | Password |
| --- | --- | --- |
| Owner / single account | `owner@abc-demo.test` | `password` |
| Multiple Accounts | `multi@demo.test` | `password` |
| Sender | `sender@sender-demo.test` | `password` |
| Receiver | `receiver@receiver-demo.test` | `password` |
| Accounting | `accounting@abc-demo.test` | `password` |
| No Account | `noaccount@demo.test` | `password` |

Additional authorization test user:

| Scenario | Email | Password |
| --- | --- | --- |
| Viewer / read-only access | `viewer@abc-demo.test` | `password` |

## Demo Accounts

| Code | Scenario |
| --- | --- |
| `SC-DEMO-SINGLE` | Single account auto-selection and sender-and-receiver behavior |
| `SC-DEMO-SENDER` | Sender-only account |
| `SC-DEMO-RECEIVER` | Receiver-only account |
| `SC-DEMO-BOTH` | Sender-and-receiver account for multi-account and viewer authorization checks |
| `SC-DEMO-ACCOUNTING` | Payment/accounting access without order creation |

## Mock External Sisahygo Customer IDs

The demo links use mock external Sisahygo customer identifiers:

- `10001`
- `10002`
- `20001`
- `20002`

These IDs are external references only. The project does not create a local `customers` table and does not require a foreign key to one.

## Local Reset

For a full local reset:

```bash
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan db:seed '--class=Database\Seeders\Development\ClientAccountDemoSeeder'
```

This is for local development only.

## Why Transactional Sisahygo Data Is Not Seeded

Orders, shipments, payments, and customer master records belong behind the Sisahygo API integration boundary. Local demo data seeds only Client Account Foundation records and mock external customer identifiers so future integration work does not depend on direct production database access.