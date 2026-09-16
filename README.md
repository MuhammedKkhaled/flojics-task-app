# Flojics Help Desk

Flojics Help Desk is a single-repository Laravel 11 and Vue 3 application for escalating support tickets and tracking independent Email and Slack notification deliveries.

An escalation is committed before notifications are dispatched. A failed webhook can therefore never undo a valid ticket escalation.

## Requirements

- PHP 8.2+
- Composer 2
- Node.js 20+ and npm
- MySQL 8
- Redis (required for Horizon and the normal queue workflow)

## Setup Instructions

### 1. Clone and install dependencies

Clone the repository, install dependencies, and create your local environment file:

```bash
git clone <repository-url> flojics-help-desk
cd flojics-help-desk
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 2. Configure MySQL and Redis

Create a MySQL database named `flojics_help_desk`, then update the `DB_*` values in `.env` if your local MySQL credentials differ. Ensure Redis is running for Horizon and the normal queue workflow.

### 3. Prepare the database and frontend assets

```bash
php artisan migrate --seed
npm run build
```

### 4. Start the application

For local development, use three terminals:

```bash
php artisan serve
php artisan horizon
npm run dev
```

Open `http://localhost:8000/login` and sign in with the seeded agent account below.

## Demo accounts

All seeded users use the password `password`.

| Role | Email |
|---|---|
| Admin | `admin@flojics.test` |
| Agent | `agent@flojics.test` |

The Vue application signs in through the Sanctum token endpoint and stores the token in browser local storage. You can also obtain a token with curl:

```bash
curl -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"agent@flojics.test","password":"password","device_name":"reviewer"}'
```

Pass the returned value as `Authorization: Bearer <token>` to protected endpoints.

## Demoing notification retries

No SMTP or Slack credential is required to demonstrate the retry pipeline. Keep the mailer as `log`, then set this local-only value in `.env`:

```dotenv
NOTIFICATIONS_SIMULATE_FAILURE=slack
```

Restart Horizon after changing environment configuration:

```bash
php artisan horizon:terminate
php artisan horizon
```

Escalate an open or in-progress ticket using both channels. Email is recorded as sent through the log mailer; Slack is retried a total of four times (one initial attempt plus three retries) and then marked failed. The UI refreshes every three seconds while a delivery is pending.

Default retry values are `10`, `60`, and `300` seconds plus up to five seconds of jitter. For a faster local demonstration only, lower the values in `config/notifications.php` or use an uncommitted local config override.

If a worker was unavailable, requeue old pending rows with:

```bash
php artisan notifications:reconcile --minutes=5
```

## Configuration

`config/notifications.php` is the notification registry and retry contract.

| Variable | Purpose | Default |
|---|---|---|
| `NOTIFICATIONS_DEFAULT_CHANNELS` | Channels used when the API payload omits `channels` | `email,slack` |
| `NOTIFICATIONS_EMAIL_RECIPIENTS` | Comma-separated Email recipients; falls back to admins and assigned agent when empty | empty |
| `SLACK_WEBHOOK_URL` | Slack incoming-webhook URL | empty |
| `SLACK_CHANNEL` | Target Slack channel | `#support-escalations` |
| `NOTIFICATIONS_MAX_ATTEMPTS` | Total attempts, including the initial try | `4` |
| `NOTIFICATIONS_RETRY_JITTER` | Maximum added delay in seconds | `5` |
| `NOTIFICATIONS_TIMEOUT` | Provider request timeout in seconds | `15` |
| `NOTIFICATIONS_SIMULATE_FAILURE` | Comma-separated channels to force into transient failure | empty |

Never commit real Slack webhook URLs or credentials. Keep them in `.env` only.

## API and Postman

The API routes are available under `/api`. Public routes list tickets and available channels; escalation requires a Sanctum bearer token.

Import [Flojics Help Desk.postman_collection.json](Flojics%20Help%20Desk.postman_collection.json) into Postman. Run **Login** first; its test script stores the token for the protected requests. Set the collection `ticket_id` variable to an open or in-progress seeded ticket before running **Escalate Ticket**.

## Testing and checks

```bash
./vendor/bin/pint --test
composer analyse
php artisan test
npm run lint
npm run format:check
npm run test
npm run build
php artisan route:list
```

The backend tests require the configured test MySQL database. The frontend test suite uses Vitest and jsdom without a running Laravel server.

## Local fallback without Redis

For a quick smoke test without Redis, use SQLite and the synchronous queue in your uncommitted `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
QUEUE_CONNECTION=sync
CACHE_STORE=file
```

Horizon requires Redis. With this fallback, use `php artisan queue:work` instead. The retry visualization is most representative with Redis and Horizon enabled.
