# Flojics Help Desk

A single-repository Laravel and Vue help desk application. Phase 0 establishes the simulated platform that the ticket-escalation workflow will build on: Sanctum token authentication, MySQL-ready domain tables, Redis/Horizon configuration, deterministic demo data, and a Vue 3 ticket list.

## Requirements

- PHP 8.2+
- Composer 2
- Node.js 20+ and npm
- MySQL 8
- Redis

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create a MySQL database named `flojics_help_desk`, update the `DB_*` values in `.env` when needed, then prepare the application:

```bash
php artisan migrate --seed
npm run build
```

For development, run the application, Horizon, and Vite in separate terminals:

```bash
php artisan serve
php artisan horizon
npm run dev
```

Open `http://localhost:8000`. Laravel serves the single Vue mount point and Vite supplies the frontend assets. Vue loads the seeded tickets from `GET /api/tickets`.

## Demo accounts

All seeded users use the password `password`.

| Role | Email |
|---|---|
| Admin | `admin@flojics.test` |
| Agent | `agent@flojics.test` |

Create a Sanctum personal access token with:

```bash
curl -X POST http://localhost:8000/api/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"agent@flojics.test","password":"password","device_name":"reviewer"}'
```

Pass the returned token as `Authorization: Bearer <token>` to protected API routes.

## Local fallback without services

The committed `.env.example` targets MySQL and Redis. For a quick local smoke test without those services, use SQLite and the synchronous queue in your uncommitted `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
QUEUE_CONNECTION=sync
CACHE_STORE=file
```

Horizon specifically requires Redis, so use `php artisan queue:work` instead when using this fallback.

## Notification configuration contract

`config/notifications.php` owns the channel registry, API-facing labels, default channels, timeout, and retry policy. `NOTIFICATIONS_MAX_ATTEMPTS=4` means one initial delivery attempt plus three retries. Channel implementations and delivery behavior are intentionally introduced in later phases.

## Useful checks

```bash
php artisan test
npm run build
php artisan route:list
```
