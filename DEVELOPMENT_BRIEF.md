# PickenChicken Daily Picks Game — Development Brief

## Overview

**PickenChicken** is a daily picks competition game where users compete against **The Chicken**, a meta-bot from the odds-warehouse project that selects the strongest signal pick for each game across major sports leagues.

Unlike the original NCAA March Madness tournament implementation, this version operates on a **day-by-day basis**, allowing users to pick games from any of the supported sports without bracket or tournament structure.

## Architecture

### Two Systems, One Integration

**odds-warehouse** (independent Python/FastAPI project at `/Users/jamespwilliams/Projects/odds-warehouse`)
- Core sports betting data warehouse
- Ingests odds and scores daily
- Runs multiple betting bots, including **The Chicken**
- Provides REST API endpoints for picks, records, and results
- Covers: NHL, NBA, WNBA, MLB, NFL

**PickenChicken** (Symfony API + React frontend at `~/Ampelos/greenhouse/PickenChicken/proof_of_concept_NCAA2026`)
- Competition/league management application
- Users pick against The Chicken and each other
- Tracks records, standings, leaderboards
- Currently tournament/bracket-focused (needs simplification)

### The Chicken Bot

The Chicken is a **meta-bot** that:
1. Queries all other bots' picks for each game
2. Calculates each bot's historical win% (before the game's commence_time)
3. Selects the pick from the highest win% bot (strongest signal)
4. Generates a pick for every game that has bot picks
5. Grades picks by signal strength:
   - **Green** (>60% win%): High confidence
   - **Yellow** (50-60% win%): Medium confidence
   - **Red** (<50% win%): Low confidence

**Key API Endpoint:**
```
GET /bots/chicken-picks
Query: sport (required), start (required), end (required), book (optional), timezone (optional)

Response: array of picks with structure:
{
  odds_api_event_id,
  game_date,
  home_team,
  away_team,
  commence_time,
  status,
  home_score,
  away_score,
  outcome_name,       // The Chicken's pick
  home_spread,
  picks,              // dict of all bot picks for this game
  consensus_pick,
  result              // 'win', 'loss', null if not settled
}
```

## Daily Picks Game Model

### Core Flow

1. **Load Game Docket** (daily)
   - User selects sport and date
   - UI fetches odds-warehouse's Chicken picks for that day: `GET /bots/chicken-picks?sport=basketball_nba&start=2026-04-23&end=2026-04-23`
   - Displays all games with The Chicken's pick, signal strength, and spreads

2. **User Makes Picks**
   - For each game, user picks: home/away or favorite/underdog
   - Stores picks locally or in PickenChicken database

3. **Games Settle**
   - Results come from odds-warehouse (games transition to 'final' status)
   - odds-warehouse recalculates all bot records including The Chicken
   - PickenChicken queries updated picks/records via API to score user picks

4. **Scoring & Leaderboard**
   - Track user win% vs The Chicken's win% for the day/week/season
   - Leaderboard: users ranked by cumulative win%
   - Optional: tier badges (beat The Chicken, beat consensus, etc.)

### Simplified Schema (vs Current Tournament Model)

**Current** (NCAA bracket-focused):
- Tournament → Round → Game → GameMarket → GamePick (user) / ChickenPick (random)
- Bracket progression, seedings, round results

**New** (daily picks):
- Competition (league/season) → Competition_Segment (day) → DailyPick (user) / TheChickenPick (from API)
- No bracket structure, just daily snapshots

## Integration Points

### odds-warehouse Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `/bots/chicken-picks?sport=X&start=Y&end=Z` | Fetch Chicken's daily picks |
| `/bots/records?sport=X` | Fetch bot records (Chicken's win% for leaderboard context) |
| `/events?sport=X&start=Y&end=Z` | Get game docket with scores |

### Data Mapping

| PickenChicken | odds-warehouse |
|---|---|
| Game | raw_events (sport_key, commence_time, home_team, away_team) |
| GameMarket (spreads) | raw_selections (spreads market) |
| ChickenPick | raw_bot_picks where bot_name='the_chicken' |
| MarketOutcome | Spread lines from raw_selections |

### Key Consideration: odds_api_event_id

odds-warehouse uses `odds_api_event_id` as the primary key for events. PickenChicken needs to:
- Store `odds_api_event_id` in Game or GameMarket entity
- Use it to match games when querying odds-warehouse API
- Use it as foreign key for picks tied to Chicken picks

## Technology Stack

### Backend (Symfony API)
- REST endpoints for:
  - `/api/competitions` — list active competitions
  - `/api/competitions/{id}/daily-picks` — fetch Chicken picks for a day (proxies to odds-warehouse)
  - `/api/competitions/{id}/user-picks` — user's picks for a day
  - `/api/competitions/{id}/leaderboard` — standings
  - `/api/competitions/{id}/score` — score a day's games (query odds-warehouse for results, record wins/losses)

### Frontend (React 19)
- **Pick Page**
  - Sport/date selector (dropdown or calendar)
  - Game grid: home vs away, spreads, The Chicken's pick + signal color, user's pick (radio buttons)
  - Real-time score updates (if live games shown)
  - Odds picker (if multiple books available)
- **Leaderboard**
  - User standings: win%, record, vs Chicken %, vs Consensus %
  - Daily breakdown (optional)
  - Trash talk / comments

### Database (PostgreSQL)
- Simplified entities:
  - `Competition` — league/season (e.g., "NFL 2026")
  - `CompetitionSegment` — day/date slice
  - `Game` — event (with odds_api_event_id foreign key)
  - `GameMarket` — spreads for a game
  - `UserPick` — user's pick for a game in a segment
  - `ChickenPickSnapshot` — cached Chicken pick + signal for audit/history
  - `User` — competitor
  - `Leaderboard` — daily/season aggregates

## Development Phases

### Phase 1: MVP (Daily Picks Scoring)
- [ ] Simplify entities (remove tournament/bracket)
- [ ] Create `/api/daily-picks` endpoint that proxies to odds-warehouse `/bots/chicken-picks`
- [ ] Basic React page: pick games, store picks in PickenChicken DB
- [ ] Scoring: query odds-warehouse for results, calculate win%
- [ ] Simple leaderboard

### Phase 2: UX Polish
- [ ] Signal strength color coding (green/yellow/red based on Chicken win%)
- [ ] Live score updates
- [ ] Multi-sport support in UI
- [ ] Keyboard shortcuts for picking

### Phase 3: Community
- [ ] Leaderboard tiers / badges
- [ ] Trash talk / comments on picks
- [ ] Weekly/seasonal resets

## Important Files / References

### odds-warehouse
- **Main picks generator:** `/Users/jamespwilliams/Projects/odds-warehouse/bots/picks.py`
  - `generate_the_chicken_picks()` — creates Chicken picks (now working with thousands of picks)
  - Runs daily, covers all sports
- **API endpoints:** `/Users/jamespwilliams/Projects/odds-warehouse/api/routers/bots.py`
  - `/bots/chicken-picks` — fetch Chicken picks by date range
  - `/bots/records` — fetch bot win% records
- **Database:** PostgreSQL (odds_warehouse) via `DATABASE_URL` env var

### PickenChicken (Current)
- **OddsLockCommand:** `api/src/Command/OddsLockCommand.php` (lines 157-171)
  - Currently generates random Chicken pick
  - **Action:** Replace with API call to odds-warehouse
- **Entities:** `api/src/Entity/Game.php`, `ChickenPick.php`, `GameMarket.php`
  - **Action:** Add `odds_api_event_id` field, simplify for daily model
- **Frontend:** `frontend/src/pages/` and `components/`
  - **Action:** Build daily pick UI

## Environment Setup

### Prerequisites
- odds-warehouse running (API on port 8000)
  ```bash
  cd /Users/jamespwilliams/Projects/odds-warehouse
  /Users/jamespwilliams/Projects/python/my_env/bin/python3 -m uvicorn api.main:app --reload --port 8000
  ```
- PostgreSQL for PickenChicken (use existing docker-compose in api/)

### .env Variables (PickenChicken)
```
DATABASE_URL=postgresql://postgres:postgres@localhost:5432/picken_chicken
ODDS_WAREHOUSE_API_URL=http://localhost:8000
ODDS_WAREHOUSE_API_KEY=(if needed for auth)
MAILER_DSN=...
```

## Next Conversation Starter

> We're building PickenChicken as a simplified daily picks game using The Chicken picks from odds-warehouse. Start with Phase 1: set up the `/api/daily-picks` endpoint that proxies to odds-warehouse's `/bots/chicken-picks`, refactor entities to remove tournament structure, and build a basic UI for users to pick games and track records. The Chicken covers all sports (NHL, NBA, WNBA, MLB, NFL) daily.

---

**Created:** 2026-04-23  
**Status:** Ready for development  
**Depends On:** odds-warehouse running with The Chicken picks generated
