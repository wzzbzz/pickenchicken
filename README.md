# The Picken' Chicken 🐔

> Can you beat pure randomness?

A free-to-play March Madness picks game. Users pick against the spread each round. A chicken picks randomly. Beat the chicken. Or don't.

## Stack

- **Backend**: Symfony 7 API (`/api`)
- **Frontend**: React 19 (`/frontend`)
- **Database**: PostgreSQL (Docker)
- **Odds**: The Odds API (spreads)
- **Scores**: ESPN API

## Getting Started

### API

```bash
cd api
composer install
cp .env .env.local   # fill in DATABASE_URL, ODDS_API_KEY, ANTHROPIC_API_KEY
docker compose up -d
php bin/console doctrine:migrations:migrate
php bin/console symfony:serve
```

### Frontend

```bash
cd frontend
npm install
cp .env.example .env.local   # set REACT_APP_API_URL
npm start
```

## Key Commands

```bash
# Import bracket from ESPN
php bin/console app:tournament:import --start=20260318 --end=20260407

# Sync results + score picks
php bin/console app:tournament:sync --start=20260318 --end=20260407

# Lock odds + generate chicken picks at 10am Eastern on game day
php bin/console app:odds:lock

# Full reset (clears picks, chicken picks, unlocks markets)
php bin/console app:tournament:reset
```

## Cron Setup

```
* * * * * /path/to/api/cron.sh >> /path/to/api/var/log/cron.log 2>&1
```

`cron.sh` checks for active games and manages a sync daemon that runs every 30 seconds.

## Environment Variables

| Variable | Description |
|---|---|
| `DATABASE_URL` | PostgreSQL connection string |
| `ODDS_API_KEY` | The Odds API key |
| `ANTHROPIC_API_KEY` | Anthropic API key (leaderboard trash talk) |
| `MAILER_DSN` | SMTP for magic link auth |
