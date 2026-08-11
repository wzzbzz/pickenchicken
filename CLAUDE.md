# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

PickenChicken is a free-to-play sports picks game where users compete against **The Chicken** — a meta-bot sourced from the `odds-warehouse` project. Two sub-projects:

- `api/` — Symfony 8.0 (PHP 8.4) REST API
- `web/` — React 19 SPA (Vite)

The prototype lives at `../proof_of_concept_NCAA2026/` — reference only, do not build on it.

A separate, independent project provides The Chicken's picks:
- **odds-warehouse** at `/Users/jamespwilliams/Ampelos/greenhouse/sports-odds/odds-warehouse` — Python/FastAPI, serves `GET /bots/chicken-picks`

## Running locally

### API (Symfony)

```bash
cd api

# Start postgres (port 5433) + mercure (port 3001) + redis (port 6379)
docker compose up -d

# Install deps (first time)
composer install

# Create database + run migrations
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate

# Start dev server
symfony server:start          # or: php -S localhost:8001 -t public/
```

Default `.env` values work out of the box with docker compose. Override in `.env.local` if needed:
```
DATABASE_URL=postgresql://chicken:chicken@127.0.0.1:5433/pickenchicken?serverVersion=16&charset=utf8
MERCURE_URL=http://localhost:3001/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3001/.well-known/mercure
MERCURE_JWT_SECRET="!ChangeThisMercureHubJWTSecretKey!"
REDIS_URL=redis://localhost:6379
FRONTEND_URL=http://localhost:5173
ODDS_WAREHOUSE_API_URL=http://localhost:8000
```

### Frontend (React/Vite)

```bash
cd web
npm install
npm run dev      # dev server on :5173
npm run build    # production build
```

Configure `web/.env.local`:
```
VITE_API_URL=http://localhost:8001/api
```

### odds-warehouse (required for Chicken picks)

```bash
cd /Users/jamespwilliams/Ampelos/greenhouse/sports-odds/odds-warehouse
/Users/jamespwilliams/Projects/python/my_env/bin/python3 -m uvicorn api.main:app --reload --port 8000
```

## Tests

```bash
cd api
php bin/phpunit                        # all tests
php bin/phpunit tests/SomeTest.php     # single test file
```

The test env uses `APP_ENV=test` (see `.env.test`).

## Symfony console commands

| Command | Purpose |
|---|---|
| `app:picks:lock` | Lock game markets + generate Chicken picks at game time |
| `app:picks:score` | Score settled picks |
| `app:picks:import` | Import games from odds-warehouse |
| `app:clock:set` | Set simulated clock to a specific datetime |
| `app:clock:advance` | Advance simulated clock by an interval |
| `app:henhouse:seed` | Seed competition + bot ladder |

## Architecture

### Entity model

```
Competition → CompetitionSegment → Game → UserPick
                                       → BotPickSnapshot
Competition → PlayerProgress (per user)
User ← Session (auth)
```

### Authentication

Magic-link email flow: `POST /auth/request-login` → email with token → `POST /auth/verify-token` → returns `{user, sessionToken}`. The frontend stores `sessionToken` in `localStorage` and sends it as `X-Session-Token` header on all API requests. `SessionAuthenticator` validates tokens via the `session` table.

`SimulatedClockService` is used for all time-sensitive logic — call `$clock->now()` instead of `new \DateTimeImmutable()`.

### Real-time

Mercure hub (port 3001 via docker compose) for presence and score broadcasts. Redis (port 6379) for presence heartbeat via `PresenceService` — ping every 15s, 30s TTL.

### The Chicken

`PicksLockCommand` calls `OddsWarehouseService::getChickenPicks()` at game time to get bot picks from odds-warehouse. The bot with the highest historical win% for the sport/game is selected. All bot picks are snapshotted in `BotPickSnapshot` regardless of whether a user is challenging that bot.

### Port map

| Service | Port |
|---|---|
| Symfony API | 8001 |
| Vite dev server | 5173 |
| PickenChicken Postgres | 5433 |
| Mercure | 3001 |
| Redis | 6379 |
| odds-warehouse API | 8000 |
| odds-warehouse Postgres | 5432 |
