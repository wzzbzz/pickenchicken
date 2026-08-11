# Product Requirements Document (PRD)

## Product Name
The Picken Chicken

## Author
The Tan Gents
Jim Williams 
Glenn Hillman

## 1. Purpose & Overview

PickenChicken is a free-to-play, daily ATS (Against The Spread) picks competition built on a signal marketplace.   

"Chicken" bots are **data products**. Each one is a world-blind, pure-math algorithm that publishes a pick signal for every game it covers. Players are **signal consumers** — they discover bots, evaluate their signal quality, subscribe by challenging them, and compete directly against the signal they consume.

It is designed to be fun, engaging, and light hearted. 

The product includes several components:

- **Hen House** — The signal marketplace. A canvas-based social room where chicken bots exist as NPC characters with names, tiers, and positions. Players browse the catalog, inspect bot profiles (signal family, win%, sport coverage, confidence threshold), and challenge the bot they want to compete against.

- **Picks Game** — The competition layer. Players pick game outcomes Against The Spread. Their picks are scored head-to-head against their current bot opponent each day.

- **Progression Ladder** — A bot catalog sorted by signal sophistication across five zones, ending in the Chicken Council boss wing. Beating a bot advances the player to the next signal in the ladder.
- **Leaderboard** — Cross-player standings scoped by day, week, and season. Players ranked by ladder position, personal win%, and bot score.
- **Admin Console** — Tools for managing competitions, locking markets, importing games, and monitoring system health.
- **odds-warehouse** — The data pipeline and signal engine. Ingests odds and scores from external APIs, runs bot algorithms, and publishes bot picks via REST API. The canonical source of truth for all signal data.

The product includes:
- **Frontend**: React 19 SPA
- **Backend API**: Symfony 7.3 (PHP 8.2)
- **Database**: PostgreSQL
- **Real-time**: Mercure (SSE)
- **Presence**: Redis
- **AI**: Anthropic Claude API (chicken personas, trash talk)
- **Signal Engine**: Python / FastAPI (odds-warehouse)

This PRD is structured to be easily consumed by AI development tools such as Claude Code, Cursor, or internal LLM-based agents.

---

## 2. Goals & Success Metrics

### Business Goals
- Players can enter the Hen House, browse the signal catalog, and immediately understand which bot to challenge and why
- The picks competition is compelling enough that players return daily to see how their signal performed and pick the next slate
- Each chicken has a distinct identity — players develop opinions about signals, discover which ones work for which sports, and feel the pull of harder opponents
- The social layer (presence, chat, shared canvas) makes the game feel alive even when picking alone
- The progression ladder rewards sustained engagement — every day of picks builds a meaningful record against a specific signal

### Success Metrics
- Daily active pickers per competition
- Pick submission rate (pickers who submit at least one pick per active day)
- Challenge rate against higher-tier bots over time (ladder progression velocity)
- Retention: players returning the following day after a loss
- Signal catalog engagement: how many distinct bots players inspect before challenging

---

## 3. Core Concepts

### 3.1 ATS Picks

All picks are Against The Spread. A pick wins if the chosen team covers the spread, loses if it doesn't, and pushes if the margin is exactly the spread. Pushes are excluded from win% calculations.

The spread is the median pre-game line across all available sportsbooks, sourced from odds-warehouse.

### 3.2 Bots as Signals

Every bot in the progression ladder is a live algorithm running in odds-warehouse. Bot picks and bot records are read from the odds-warehouse API — PickenChicken stores only user picks and lightweight snapshots for audit. The bot's published win% is its **quality rating**. A player browsing the catalog in the Hen House is evaluating signal quality before subscribing. A player challenging a bot is subscribing to that signal and competing directly against it.

Signal strength is displayed as:

- **Green** `>60%` — strong signal. Trust it.
- **Yellow** `50–60%` — marginal signal. Noisy but watchable.
- **Red** `<50%` — noise. Worth beating on luck alone.

### 3.3 Signal Families

Bots fall into distinct algorithm families. Each family is a different approach to extracting signal from ATS data:

| Family | Description |
|--------|-------------|
| **Deterministic / Random / Pseudorandom** | No learning. Fixed rules (always home, always fav) or Thue-Morse sequence. ~50% win%. Baseline noise. |
| **Cumulative ATS** | Tracks team ATS% since records began, picks the team with the better record. |
| **Rolling Window** | Tracks team ATS% over the last N games (roll5, roll10). Follows recent form. |
| **Season-Scoped** | Like cumulative, but resets at each season boundary. |
| **Lock Threshold** | Only publishes a pick when the ATS% gap between teams meets a minimum threshold (10/20/30%). Fewer picks, higher precision. A bot that skips a game is a quality gate in action. |
| **Double Window** | Requires roll-5 AND roll-10 to agree. More selective. |
| **Triple Window** | Requires cumulative AND roll-5 AND roll-10 to all agree. Very selective. |
| **The Chicken (meta)** | For each game, delegates to whichever source bot has the highest current win%, subject to a confidence floor. |

### 3.4 The Chicken Council

The five Chicken variants are the final bosses. They observe all other bots' current win% and delegate each game to the strongest signal available, filtered by a minimum confidence threshold. The source bot and its confidence are surfaced to the player as part of the pick display — the Council is a transparent meta-signal.

| Bot | Display Name | Confidence Floor | Behavior |
|-----|--------------|-----------------|----------|
| `ats_chicken_tin` | Tin Chicken | None | Picks every game |
| `ats_chicken_bronze` | Bronze Chicken | 55%+ | Only picks when best source bot ≥ 55% |
| `ats_chicken_silver` | Silver Chicken | 60%+ | Only picks at ≥ 60% |
| `ats_chicken_gold` | Gold Chicken | 65%+ | Only picks at ≥ 65% |
| `ats_chicken_platinum` | Platinum Chicken | 70%+ | Only picks at ≥ 70% |

Higher confidence floor = fewer picks published, but each is a stronger signal. Platinum Chicken may publish very few picks on a given day but is nearly impossible to beat.

### 3.5 Multi-Sport

All bot algorithms are sport-agnostic — they apply ATS logic to any sport's game data. Competitions are scoped to a specific sport (e.g., an NBA competition, an MLB competition). A player's current bot opponent publishes picks within that sport's competition scope. Phase 1 sports: **NBA, NHL, MLB, NFL, WNBA**. Phase 2: **NCAAB, NCAAF**.

The `chicken_profiles` table has an optional `sport` field used for display identity — a chicken's persona may be anchored to a sport it is "known for" as character flavor, independent of the algorithm's actual coverage.

### 3.6 Chicken Character Identity

Each bot in the progression catalog has a **character identity** layered on top of its algorithm:

- **Display name** — a character name (not the algorithm key)
- **Persona** — a voice, attitude, and backstory used in trash talk and AI chat responses
- **Visual** — sprite color/tint and tier badge
- **Home sport** — the sport the character is thematically associated with (flavor only)
- **Tier badge** — The Yard, The Coop, The Season Coop, The Barn, The Silo, The Council

The character layer is maintained in `chicken_profiles` and the `henhouse:seed:chickens` command. Bot algorithm logic lives entirely in odds-warehouse and is unchanged by character design. A chicken persona saying "I'm a hoops bird" is marketing; the underlying algorithm running season fav/dog ATS% covers whatever sport the active competition is scoped to.

### 3.7 Empty Slate Handling

When a player's current bot has no picks for a given day (e.g., a lock-threshold bot that found no qualifying matchups), the day is skipped for defeat-condition tracking — it counts neither as a win nor a loss. The player still sees the day's game card but is informed the bot passed on all games. If the player picks on a day the bot skips, those picks count toward the player's personal win% but not toward the defeat condition result for that day.

---

## 4. Game Mechanics

### 4.1 Progression Ladder

Players advance through the bot catalog one signal at a time. The ladder has five zones of increasing sophistication, followed by the Chicken Council boss wing.

**Defeat Condition** is configurable per Competition:

| Mode | Description |
|------|-------------|
| `single_day` | Win more correct picks than the bot on a single day |
| `series_N` | Best-of-N days; first to win ⌈N/2⌉ days advances |
| `record_pct` | Beat the bot's cumulative win% over at least M games |
| `season` | Beat the bot's win% by end of the competition season |

Default: `single_day`.

When a player defeats their current bot they are immediately assigned the next one. They can challenge their current opponent on any day that bot has picks. They always see the bot's live win% sourced from odds-warehouse.

### 4.2 Daily Session

Each day:
1. Player sees today's game docket for their sport(s)
2. Each game shows: matchup, spread, current bot's pick, signal strength indicator
3. Player picks home or away for each game they choose to action
4. Games settle; odds-warehouse results are pulled
5. Player correct picks vs. bot correct picks for the day are compared

Players pick as many or as few games as they want. They are only compared on games they actually pick — no penalty for skipping, but more picks reduces variance.

### 4.3 Leaderboard Ranking

Within a competition, players are ranked by:
1. **Ladder position** — current bot index (primary)
2. **Personal win%** — cumulative ATS win% against all opponents (tiebreaker)
3. **Bot score** — degree by which the player outperformed defeated bots

---

## 5. User Personas

### Persona 1: The Signal Consumer (Player)
A player has committed to the game. They have an account, a current bot opponent, and a daily habit of checking their record.

- Can enter the Hen House, see the live chicken roster with signal quality indicators, and identify their current opponent
- Can click any chicken to view its full signal profile: algorithm family, win%, sport coverage, historical pick volume, daily pick count, and confidence threshold
- Can view Today's Card: the game slate for their active competition(s) with each game's spread, their current bot's pick, and signal strength indicator
- Can submit picks before game lock; picks auto-lock at commence time
- Can see bot picks revealed once their own pick locks, then game results as they come in
- Can view their ladder position and the next bot they will face on defeating the current one
- Can review their full pick history grouped by competition, segment, and bot opponent
- Can view the leaderboard for any active competition, scoped to day / week / season
- Can chat in the Hen House room and with individual chicken personas via AI-backed chat
- Can log in via magic link and manage their display name

### Persona 2: The Spectator (Guest)
A spectator is present in the Hen House but not yet picking. The conversion goal is challenge initiation.

- Can view the Hen House canvas: all chicken NPCs with their tier, display name, and win% signal indicator
- Can browse the full signal catalog: all 98 bots, their families, tiers, and current quality ratings
- Can view the leaderboard and player records without creating an account
- Can inspect any chicken's profile card without an account
- Cannot submit picks, cannot challenge a bot, cannot chat — prompts to create account appear contextually

### Persona 3: The Admin
The admin manages the operational layer: the data ingestion pipeline, competition configuration, and the Hen House world state.

- Can create and configure Competitions: sport, season, defeat condition type and value, active period
- Can create and manage CompetitionSegments within a Competition
- Can import today's game slate from ESPN and the Odds API and associate games with an active segment
- Can lock markets at game time: triggers bot pick snapshot and closes the pick window
- Can re-trigger scoring for a game if a result was delayed or corrected
- Can manage the chicken roster: display names, tiers, persona descriptions, canvas positions, and sport identity
- Can monitor system health: ingestion job status, bot run results, scoring pipeline, Mercure hub status
- Can view audit logs of all admin operations

---

## 6. User Stories

**Signal Catalog & Hen House**
- As a Player, I can enter the Hen House and see all active chicken NPCs on the canvas with their tier badge, display name, and signal strength indicator (green / yellow / red)
- As a Player, I can see which other players are currently in the Hen House as presence sprites
- As a Player, I can click any chicken NPC and view their full signal profile card: algorithm family, signal description, current win%, pick volume this season, sport identity, and confidence threshold
- As a Player, I can challenge a bot from its profile card to begin competing against its published picks
- As a Spectator, I can browse the full signal catalog and inspect any chicken profile without an account

**Picks & Competition**
- As a Player, I can view Today's Card for my active competition: all games in today's slate with the spread, my current bot's pick highlighted in signal color, and my own pick radio buttons
- As a Player, my picks lock automatically at each game's commence time; I cannot change a locked pick
- As a Player, I can see the bot's pick for a game revealed once my own pick is locked, and still pending if I haven't picked
- As a Player, I can see game results update in real time as games finish, and my running W/L record for the day
- As a Player, on a day when my current bot passes on all games (lock-threshold bot with no qualifying matchups), I am informed the bot skipped — the day does not count toward my defeat condition progress
- As a Player, I can see my defeat condition progress at all times: in single_day mode, today's score vs the bot; in series_N mode, the series record; in record_pct mode, my cumulative win% vs the bot's

**Progression**
- As a Player, I can view my current ladder position, my current bot, and the next bot waiting for me when I advance
- As a Player, I can view the full 98-bot signal catalog organized by zone, with my current position marked
- As a Player, when I defeat my current bot, I am immediately assigned the next one and can begin picking against it the following day
- As a Player, I can view my full pick history grouped by competition, segment, and bot opponent

**Leaderboard**
- As a Player, I can view the leaderboard for any active competition scoped to today / this week / full season, ranked by ladder position, then win%, then bot score
- As a Spectator, I can view the leaderboard without an account

**Social & AI**
- As a Player, I can chat with other players in the Hen House room chat
- As a Player, I can send a message directly to my current bot and receive an AI-generated in-character response
- As a Player, I receive AI-generated trash talk from my current bot when the leaderboard is loaded

**Auth & Profile**
- As a Player, I can log in via a magic link sent to my email — no password required
- As a Player, I can set and update my display name
- As a Player, I can view my account pick summary: total games, win%, current streak, competitions entered

**Admin**
- As an Admin, I can create a Competition specifying sport, season, defeat condition type and value, and active dates
- As an Admin, I can create CompetitionSegments within a competition and manage their status
- As an Admin, I can import today's game slate from ESPN or the Odds API and associate games with the active segment
- As an Admin, I can lock markets at game time, triggering a bot pick snapshot and closing the pick window for all locked games
- As an Admin, I can re-trigger scoring for a specific game if its result was delayed or corrected in the source
- As an Admin, I can manage the chicken roster: display names, tier assignments, persona descriptions, sport identity, and canvas positions in the Hen House
- As an Admin, I can view system health: ingestion job status, bot run results, scoring pipeline status, and Mercure hub connectivity

---

## 7. Stack

- **Backend API**: Symfony 7.3, PHP 8.2
- **Backend ORM**: Doctrine ORM with Doctrine Migrations
- **Database**: PostgreSQL 16
- **Real-time**: Mercure (SSE hub via Docker)
- **Presence**: Redis (via Predis)
- **AI**: Anthropic Claude API (`claude-haiku-4-5` for persona chat, trash talk)
- **Frontend**: React 19 (Create React App)
- **Auth**: Magic-link email; `user_id` stored in localStorage; no JWT or sessions
- **Signal Engine**: Python 3, FastAPI, uvicorn (odds-warehouse)
- **Bot algorithms**: Python (`bots/picks.py`, `bots/polly.py`)
- **Ingestion**: Python scripts — The Odds API, ESPN
- **Transforms**: dbt (profile: `sports_warehouse`)
- **Admin dashboards**: Static HTML + vanilla JS (`frontend/` sub-project)

---

## 8. Applications

### PickenChicken React SPA (`PickenChicken/proof_of_concept_NCAA2026/frontend`)

#### Pages / Views

- **Hen House** — The signal marketplace canvas. Chicken NPCs are rendered as positioned sprites (tinted by tier) against an arena background. Online player sprites (green orbs) update in real time via Mercure. Click a chicken to open its Signal Profile Card (algorithm family, win% quality rating, pick volume, sport identity, confidence threshold). A Challenge button on the card initiates a pick competition against that bot. Room chat runs alongside the canvas. Spectators can browse without an account; the challenge button prompts sign-up.

- **Signal Catalog** — Tabular view of all 98 bots organized by zone. Each row: rank, display name, algorithm family, signal description, current win%, tier badge, signal strength indicator. Filterable by zone, family, sport, signal strength. A player's current position is highlighted. Clicking any row opens the Signal Profile Card. Read-only for Spectators.

- **Today's Card** — The daily picks interface for the player's active competition(s). Displays each game in the slate: matchup, commence time, spread, bot's pick (with signal strength color), and the player's pick radio buttons. Locked games show the bot pick revealed and the player's submitted pick side-by-side. Settled games show the result badge (W / L / Push). A deficit-condition progress bar shows today's score vs the bot in real time.

- **My Progress** — Ladder visualization showing all five zones plus the Council. The player's current position is marked. Above the ladder: current bot card (name, signal family, win%, current record vs player). Below: defeated bots history as a scrollable chip list with win records. For series_N defeat condition, shows series progress (e.g., "Best of 7 — You lead 3-2"). Includes a "Next: [bot name]" preview of the next opponent.

- **My Picks** — Full pick history. Grouped first by Competition, then by bot opponent, then by date. Each row: game, spread, player pick, bot pick, result, settlement date. Summary row per bot opponent: W/L/Push totals, win%, days won vs days lost.

- **Leaderboard** — Competition standings. Tabs for today / this week / full season. Each row: rank, player name, ladder position (zone + bot name), personal win%, bot score (margin of outperformance over defeated bots). AI-generated trash talk from each player's current chicken opponent appears below the table on page load.

- **Chat** — Full-width room chat backed by Mercure SSE. Message history since room open. Presence count. Subtab: Chicken Chat — direct AI persona conversation with the player's current bot opponent. Responses are generated via Claude API using the chicken's persona definition.

- **Profile** — Username editor, email display, aggregate stats: total picks, overall win%, current active competitions, longest win streak.

- **Bracket** *(legacy — NCAA 2026 tournament only)* — Tournament bracket view. Retained while the 2026 NCAA tournament is active. Will be archived post-tournament.

- **Admin Console** *(role-gated — admin users only)* — Tabbed administration interface.
  - **Competitions** — Create and manage competitions: sport, season, defeat condition, dates, active/archived status.
  - **Segments** — Add and manage CompetitionSegments within a competition; set status (upcoming / in_progress / complete).
  - **Games** — Import today's game slate from ESPN or the Odds API into the active segment; view imported games and their status.
  - **Lock Markets** — View today's scheduled games and lock them one-by-one or in bulk. Triggers bot pick snapshot and closes the pick window for locked games.
  - **Score** — View scoring status per game; re-trigger scoring for delayed or corrected results.
  - **Chicken Roster** — CRUD interface for chicken profile records: display name, tier, persona description, sport identity, canvas position (x/y), sprite color. Previews the Hen House layout.
  - **System Health** — Ingestion job status, last odds fetch timestamp, last bot run timestamp, scoring pipeline status, Mercure hub connectivity status.

#### Look and Feel
- **Color Palette**: Dark mode primary (`#0f1117` bg). Signal colors: green `#34d399` (win / strong signal), red `#f87171` (loss / noise), yellow `#fbbf24` (push / marginal). Indigo `#6366f1` accent.
- **Fonts**: System sans-serif throughout.
- **Canvas**: Arena background image. Chicken sprites tinted by tier metal (tin / bronze / silver / gold / platinum). Player presence sprites as green orbs.
- **Signal Strength**: Consistent green / yellow / red dot indicator applied uniformly wherever a bot pick appears: Hen House card, Today's Card, Signal Catalog, My Picks history.

#### Preview
- Dev: `http://localhost:3000`
- Prod: `REACT_APP_API_URL` configured per environment

---

### PickenChicken Symfony API (`PickenChicken/proof_of_concept_NCAA2026/api`)

Serves all REST API endpoints. Reads signal data from odds-warehouse; never writes to it. Manages user picks, ladder progression, scoring, Mercure publishing, and Redis presence.

#### Endpoint Groups

**Auth**
- `POST /auth/request-login` — send magic-link email
- `POST /auth/verify-token` — verify token, return `{id, email, username}`
- `GET /auth/me/{userId}` — user profile + aggregate pick stats

**Hen House**
- `GET /henhouse/room` — room state: full chicken roster with live win% (proxied from odds-warehouse) + list of online player IDs (from Redis)
- `POST /henhouse/join {userId}` — write to Redis `presence:henhouse`; publish Mercure join event
- `POST /henhouse/ping {userId}` — refresh Redis TTL (called every 15s from frontend)
- `POST /henhouse/leave {userId}` — remove from Redis; publish Mercure leave event
- `GET /henhouse/chicken/{botKey}/profile` — full chicken profile (display name, tier, persona, algorithm family, sport identity) + live win% + pick volume proxied from odds-warehouse

**Signal Catalog**
- `GET /henhouse/catalog` — all 98 chicken profiles in ladder order with current win% ratings; cacheable, refreshed from odds-warehouse on TTL

**Competition & Picks**
- `GET /competitions` — list active competitions
- `GET /competitions/{id}/docket?date=Y` — today's game slate for the competition's sport, with each game's spread and the current player's bot picks; returns 204 if bot skipped all games
- `POST /competitions/{id}/picks` — submit one or more user picks for a day (before lock)
- `GET /competitions/{id}/picks?date=Y` — user's submitted picks for a specific date with result if settled
- `GET /competitions/{id}/progress` — player's current ladder position, current bot, defeat condition progress, and series state
- `POST /competitions/{id}/settle?date=Y` — score picks for a date: fetches results from odds-warehouse, scores user picks, evaluates defeat conditions, advances ladder if applicable
- `GET /competitions/{id}/leaderboard?scope=day|week|season` — ranked standings within scope

**AI Persona**
- `POST /chicken/chat {message, history, botKey}` — stream AI response in-character as the specified chicken's persona (Claude API)
- `POST /chicken/talk {standings}` — generate one-liner trash talk per player for leaderboard display (Claude API)

**Real-time / Chat**
- `GET /chat/token` — Mercure subscriber JWT for frontend SSE subscription
- `POST /chat/join` / `POST /chat/ping` / `POST /chat/leave` / `POST /chat/message` — room presence and message publishing

**Admin**
- `POST /admin/competitions` — create a competition with sport, season, defeat condition, and dates
- `POST /admin/competitions/{id}/segments` — add a segment to a competition
- `POST /admin/games/import?date=Y&sport=X` — import game slate from ESPN/Odds API into active segment
- `POST /admin/lock?date=Y&sport=X` — lock markets at game time: snapshots bot picks, closes pick window
- `POST /admin/score?date=Y` — re-trigger scoring for a specific date
- `GET /admin/health` — system health: ingestion job status, bot run timestamps, scoring pipeline status

**Dev**
- `GET|POST /dev/clock` — read or set the simulated clock (`SimulatedClockService`)

#### Console Commands

| Command | Purpose |
|---------|---------|
| `app:odds:lock` | Lock markets + snapshot bot picks at game time |
| `app:league:score-picks` | Score picks for settled games |
| `app:odds:fetch` | Fetch live odds from The Odds API |
| `app:clock:set` / `app:clock:advance` | Manipulate the simulated clock |
| `henhouse:seed:chickens` | Create bot Users + ChickenProfile records for the full roster |

#### Preview
- Dev: `http://localhost:8001`

---

### odds-warehouse Signal Engine (`odds-warehouse/`)

Independent Python/FastAPI service. PickenChicken reads from it; never writes to it. Canonical source for all bot signals and game data.

#### Key Endpoints Used by PickenChicken

| Endpoint | Purpose |
|----------|---------|
| `GET /bots/chicken-picks?sport=X&start=Y&end=Z&variant=V` | Chicken Council picks for a date range |
| `GET /bots/daily-picks?sport=X&date=Y&bot=Z` | Any bot's picks for a specific day |
| `GET /bots/records?sport=X` | All bots' current win% (quality ratings) |
| `GET /events?sport=X&start=Y&end=Z` | Game docket with scores |

#### Preview
- Dev: `http://localhost:8000`
- Prod: `https://odds.jimwilliamsconsulting.com`

---

### Admin Dashboards (`frontend/`)
Static HTML signal analytics dashboards for odds-warehouse. No build step. Toggle between local/live API via `?env=local`.

#### Pages
- `index.html` — Games / Chickens / Splits browser
- `daily.html` — Day-by-day signal win rate chart (Chart.js)
- `performance.html` — Signal family performance comparison
- `bettor.html` — Simulated bankroll curve per signal
- `leaderboard.html` — Signal quality leaderboard
- `locks.html` — Today's published picks + Polly parlays
- `matchup.html` — Matchup ATS history
- `ats_standings.html` — Team ATS standings
- `team_ats.html` — Team ATS rolling trend
- `status.html` — Ingestion pipeline status
- `parlay.html` — Parlay builder
- `builder.html` — Bet builder
- `boutique.html` — Boutique/exotic picks
- `card.html` — Daily game card
- `demo.html` — Analytics sandbox

---

## 9. Structure

```text
sports-odds/
├── PickenChicken/
│   ├── docs/                              # Design documents and PRD
│   ├── proof_of_concept_NCAA2026/
│   │   ├── api/                           # Symfony 7.3 backend
│   │   │   ├── src/
│   │   │   │   ├── Command/               # Console commands (lock, sync, seed, clock)
│   │   │   │   ├── Controller/            # HTTP controllers
│   │   │   │   ├── Entity/                # Doctrine ORM entities
│   │   │   │   ├── Repository/            # Doctrine repositories
│   │   │   │   └── Service/               # Business services (Presence, Clock, OddsWarehouse)
│   │   │   ├── migrations/                # Doctrine migrations
│   │   │   ├── config/                    # Symfony config
│   │   │   └── compose.yaml               # Docker: postgres + mercure
│   │   └── frontend/                      # React 19 SPA
│   │       └── src/
│   │           ├── components/            # Shared UI components
│   │           ├── pages/                 # Page-level components
│   │           └── App.js                 # Root: routing, tab state, auth
├── odds-warehouse/                        # Python signal engine
│   ├── api/                               # FastAPI app + routers
│   ├── bots/                              # Bot algorithms (picks.py, polly.py, config.py)
│   ├── ingest/                            # Ingestion scripts (odds.py, espn.py)
│   ├── dbt/                               # dbt staging + mart models
│   └── scripts/                           # Daily maintenance scripts
└── frontend/                              # Static HTML signal dashboards
```

---

## 10. Database Schema

### users table

```sql
id                       SERIAL PRIMARY KEY
username                 TEXT UNIQUE
email                    TEXT UNIQUE NOT NULL
login_token              TEXT
login_token_expires_at   TIMESTAMP
roles                    JSONB NOT NULL DEFAULT '[]'
type                     TEXT NOT NULL DEFAULT 'human'
                           CHECK (type IN ('human', 'bot'))
bot_key                  TEXT
created_at               TIMESTAMP NOT NULL DEFAULT NOW()
```

### chicken_profiles table
Bot character identities and Hen House positions. One row per bot in the signal catalog.

```sql
id              SERIAL PRIMARY KEY
user_id         INTEGER UNIQUE REFERENCES users(id)
bot_key         TEXT UNIQUE NOT NULL
display_name    TEXT NOT NULL
description     TEXT
tier            TEXT NOT NULL
                  CHECK (tier IN ('tin','bronze','silver','gold','platinum'))
sport           TEXT
pos_x           INTEGER NOT NULL DEFAULT 50
pos_y           INTEGER NOT NULL DEFAULT 50
color           TEXT NOT NULL DEFAULT '#f87171'
created_at      TIMESTAMP NOT NULL DEFAULT NOW()
```

### competitions table

```sql
id                       SERIAL PRIMARY KEY
sport_key                TEXT NOT NULL
league                   TEXT NOT NULL
type                     TEXT NOT NULL
                           CHECK (type IN ('regular_season','playoffs','tournament'))
season                   TEXT NOT NULL
name                     TEXT NOT NULL
defeat_condition_type    TEXT NOT NULL DEFAULT 'single_day'
                           CHECK (defeat_condition_type IN
                             ('single_day','series_N','record_pct','season'))
defeat_condition_value   NUMERIC
active                   BOOLEAN NOT NULL DEFAULT TRUE
starts_at                TIMESTAMP
ends_at                  TIMESTAMP
```

### competition_segments table

```sql
id              SERIAL PRIMARY KEY
competition_id  INTEGER NOT NULL REFERENCES competitions(id)
name            TEXT NOT NULL
segment_number  INTEGER NOT NULL
status          TEXT NOT NULL DEFAULT 'upcoming'
                  CHECK (status IN ('upcoming','in_progress','complete'))
starts_at       TIMESTAMP
ends_at         TIMESTAMP
```

### games table

```sql
id                  SERIAL PRIMARY KEY
segment_id          INTEGER NOT NULL REFERENCES competition_segments(id)
home_team           TEXT NOT NULL
away_team           TEXT NOT NULL
commence_time       TIMESTAMP NOT NULL
status              TEXT NOT NULL DEFAULT 'scheduled'
                      CHECK (status IN ('scheduled','in_progress','final'))
home_score          INTEGER
away_score          INTEGER
winner              TEXT
odds_api_event_id   TEXT
home_spread         NUMERIC(5,1)
home_price          INTEGER
away_price          INTEGER
metadata            JSONB
```

### player_progress table
Tracks each player's current position in the ladder within a competition.

```sql
id                      SERIAL PRIMARY KEY
competition_id          INTEGER NOT NULL REFERENCES competitions(id)
user_id                 INTEGER NOT NULL REFERENCES users(id)
current_bot             TEXT NOT NULL
current_bot_days_won    INTEGER NOT NULL DEFAULT 0
current_bot_days_lost   INTEGER NOT NULL DEFAULT 0
current_bot_games_won   INTEGER NOT NULL DEFAULT 0
current_bot_games_lost  INTEGER NOT NULL DEFAULT 0
ladder_position         INTEGER NOT NULL DEFAULT 1
defeated_bots           JSONB NOT NULL DEFAULT '[]'
advanced_at             TIMESTAMP
UNIQUE (competition_id, user_id)
```

### user_picks table
One row per game pick a player makes.

```sql
id                  SERIAL PRIMARY KEY
user_id             INTEGER NOT NULL REFERENCES users(id)
competition_id      INTEGER NOT NULL REFERENCES competitions(id)
odds_api_event_id   TEXT NOT NULL
pick_date           DATE NOT NULL
outcome_name        TEXT NOT NULL
home_spread         NUMERIC(5,1)
result              TEXT CHECK (result IN ('win','loss','push'))
bot_name            TEXT NOT NULL
bot_outcome_name    TEXT
bot_result          TEXT CHECK (bot_result IN ('win','loss','push'))
settled_at          TIMESTAMP
```

### bot_matchups table
One row per day a player and their current bot both have picks for the same game slate.

```sql
id              SERIAL PRIMARY KEY
user_id         INTEGER NOT NULL REFERENCES users(id)
competition_id  INTEGER NOT NULL REFERENCES competitions(id)
bot_name        TEXT NOT NULL
match_date      DATE NOT NULL
player_correct  INTEGER NOT NULL DEFAULT 0
bot_correct     INTEGER NOT NULL DEFAULT 0
player_won_day  BOOLEAN
```

### game_markets table

```sql
id                  SERIAL PRIMARY KEY
game_id             INTEGER NOT NULL REFERENCES games(id)
market_key          TEXT NOT NULL
bookmaker           TEXT NOT NULL
odds_api_event_id   TEXT
fetched_at          TIMESTAMP NOT NULL
locked_at           TIMESTAMP
```

### market_outcomes table

```sql
id          SERIAL PRIMARY KEY
market_id   INTEGER NOT NULL REFERENCES game_markets(id)
name        TEXT NOT NULL
description TEXT
price       INTEGER NOT NULL
point       NUMERIC(5,1)
label       TEXT NOT NULL
```

### app_config table

```sql
key     TEXT PRIMARY KEY
value   TEXT
```

### sessions table

```sql
id          SERIAL PRIMARY KEY
user_id     INTEGER REFERENCES users(id)
ip_address  TEXT
user_agent  TEXT
token       TEXT UNIQUE NOT NULL
started_at  TIMESTAMP NOT NULL DEFAULT NOW()
ended_at    TIMESTAMP
```

---

## 11. Seeded Data for Development and Testing

- **users**: 1 admin user; 15 human player users with realistic display names; 1 bot user per chicken in the initial seed roster (10 chickens across all zones). All humans have `type = 'human'`; all bots have `type = 'bot'` and a `bot_key` matching their `chicken_profiles` record.

- **chicken_profiles**: Seed 10 chickens representing each zone and the Council — at minimum one from The Yard, two from The Coop, one from The Season Coop, two from The Barn, two from The Silo, and Tin Chicken from the Council. Each row includes: `bot_key`, `display_name`, `description` (3–4 sentence persona blurb), `tier`, `sport` (flavor identity), `pos_x`, `pos_y` (spread across canvas with no overlap), `color` (hex, one per tier level).

- **competitions**: 2 active competitions — one NBA (`defeat_condition_type = 'single_day'`) and one MLB (`defeat_condition_type = 'series_N'`, `defeat_condition_value = 7`). Both with `active = TRUE` and a current season value.

- **competition_segments**: One `in_progress` segment per active competition covering the current week.

- **games**: 8–12 static fixture games per active competition's sport, with `commence_time` spread across today and the next 3 days. At least 3 games should be `status = 'final'` with scores and a `winner` set for scoring pipeline testing. At least 2 games should be `status = 'in_progress'`.

- **player_progress**: All 15 player users enrolled in the NBA competition. 10 players also enrolled in the MLB competition. Ladder positions varied: approximately half at position 1 (`home_away_chicken`), a quarter in Zone 2, and a few in Zone 3–4 for leaderboard differentiation.

- **user_picks**: For each player enrolled in a competition, picks for the last 3 days of games. Cover all result states: at least 5 wins, 3 losses, 1 push, and 2 pending (unsettled). Include `bot_name`, `bot_outcome_name`, and `bot_result` matching the bot's expected signal logic for the game.

- **bot_matchups**: One record per player per active competition per day for the last 3 days. `player_won_day` set for settled days; NULL for today.

- **app_config**: `simulated_now` set to current timestamp so SimulatedClockService has a value to read on startup.

- **game_markets** / **market_outcomes**: At least one spread market per seeded game with two outcome rows (home / away), median spread line, and prices near -110.

---

## Appendix A — Signal Catalog (Canonical Bot Ladder)

All 98 bots in progression order. `ladder_position` stored in `player_progress`. Bot names match exactly the `bot_name` field in odds-warehouse `raw_bot_picks`.

### Zone 1 — "The Yard" (Positions 1–6)
*Random and deterministic-sequence pickers. No learning. ~50% win%. Baseline noise.*

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 1 | `home_away_chicken` | Home Field Henny | Always picks home team |
| 2 | `fav_dog_chicken` | Favorite Foghorn | Always picks the spread favorite |
| 3 | `odds_fav_dog_chicken` | Odds Foghorn | Always picks the odds-role favorite |
| 4 | `home_away_thue_morse` | Morse Hen | Thue-Morse sequence: home/away |
| 5 | `fav_dog_thue_morse` | Morse Mutt | Thue-Morse: fav/underdog |
| 6 | `odds_fav_dog_thue_morse` | Odds Morse | Thue-Morse: odds-role |

### Zone 2 — "The Coop" (Positions 7–18)
*Single-signal ATS bots. Picks every game. Learns from historical data with no selectivity gate.*

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 7 | `ats_home_away_chicken` | Home Stats Henny | Cumulative home/away ATS% |
| 8 | `ats_fav_dog_chicken` | Fav Stats Foghorn | Cumulative fav/dog ATS% |
| 9 | `ats_odds_fav_dog_chicken` | Odds Stats Hen | Cumulative odds-role ATS% |
| 10 | `ats_consensus_chicken` | Consensus Cluck | Home/away AND fav/dog signals agree |
| 11 | `ats_roll5_chicken` | Hot Streak (5) | Home/away ATS%, last 5 games |
| 12 | `ats_roll10_chicken` | Hot Streak (10) | Home/away ATS%, last 10 games |
| 13 | `ats_fav_dog_roll5_chicken` | Fav Streak (5) | Fav/dog ATS%, last 5 games |
| 14 | `ats_fav_dog_roll10_chicken` | Fav Streak (10) | Fav/dog ATS%, last 10 games |
| 15 | `ats_consensus_roll5_chicken` | Consensus Streak (5) | Consensus, last 5 games |
| 16 | `ats_consensus_roll10_chicken` | Consensus Streak (10) | Consensus, last 10 games |
| 17 | `ats_odds_fav_dog_roll5_chicken` | Odds Streak (5) | Odds-role ATS%, last 5 games |
| 18 | `ats_odds_fav_dog_roll10_chicken` | Odds Streak (10) | Odds-role ATS%, last 10 games |

### Zone 3 — "The Season Coop" (Positions 19–30)
*Season-scoped variants. ATS records reset at each season boundary.*

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 19 | `ats_season_home_away_chicken` | Season Home Henny | Home/away ATS%, current season only |
| 20 | `ats_season_fav_dog_chicken` | Season Foghorn | Fav/dog ATS%, current season |
| 21 | `ats_season_consensus_chicken` | Season Consensus | Consensus, current season |
| 22 | `ats_season_lock_10_chicken` | Season Bronze Lock | Season home/away, 10% gap required |
| 23 | `ats_season_fav_dog_lock_10_chicken` | Season Fav Bronze | Season fav/dog, 10% gap |
| 24 | `ats_season_consensus_lock_10_chicken` | Season Consensus Bronze | Season consensus, 10% gap |
| 25 | `ats_season_odds_fav_dog_lock_10_chicken` | Season Odds Bronze | Season odds-role, 10% gap |
| 26 | `ats_season_lock_20_chicken` | Season Silver Lock | Season home/away, 20% gap |
| 27 | `ats_season_fav_dog_lock_20_chicken` | Season Fav Silver | Season fav/dog, 20% gap |
| 28 | `ats_season_consensus_lock_20_chicken` | Season Consensus Silver | Season consensus, 20% gap |
| 29 | `ats_season_odds_fav_dog_lock_20_chicken` | Season Odds Silver | Season odds-role, 20% gap |
| 30 | `ats_season_lock_30_chicken` | Season Gold Lock | Season home/away, 30% gap |

### Zone 4 — "The Barn" (Positions 31–66)
*Lock threshold bots. Only publish a pick when one team's ATS% dominates by a minimum margin. Fewer picks, higher precision.*

**Bronze Lock — 10% gap required**

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 31 | `ats_lock_10_chicken` | Bronze Lock Henny | Cumulative home/away, 10%+ gap |
| 32 | `ats_fav_dog_lock_10_chicken` | Fav Bronze Lock | Cumulative fav/dog, 10%+ gap |
| 33 | `ats_consensus_lock_10_chicken` | Consensus Bronze | Consensus, 10%+ gap |
| 34 | `ats_odds_fav_dog_lock_10_chicken` | Odds Bronze Lock | Odds-role, 10%+ gap |
| 35 | `ats_roll5_lock_10_chicken` | Streak-5 Bronze | Last 5 home/away, 10%+ gap |
| 36 | `ats_roll10_lock_10_chicken` | Streak-10 Bronze | Last 10 home/away, 10%+ gap |
| 37 | `ats_fav_dog_roll5_lock_10_chicken` | Fav Streak-5 Bronze | Last 5 fav/dog, 10%+ gap |
| 38 | `ats_fav_dog_roll10_lock_10_chicken` | Fav Streak-10 Bronze | Last 10 fav/dog, 10%+ gap |
| 39 | `ats_consensus_roll5_lock_10_chicken` | Consensus S5 Bronze | Consensus last 5, 10%+ gap |
| 40 | `ats_consensus_roll10_lock_10_chicken` | Consensus S10 Bronze | Consensus last 10, 10%+ gap |
| 41 | `ats_odds_fav_dog_roll5_lock_10_chicken` | Odds S5 Bronze | Odds-role last 5, 10%+ gap |
| 42 | `ats_odds_fav_dog_roll10_lock_10_chicken` | Odds S10 Bronze | Odds-role last 10, 10%+ gap |

**Silver Lock — 20% gap required**

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 43 | `ats_lock_20_chicken` | Silver Lock Henny | Cumulative home/away, 20%+ gap |
| 44 | `ats_fav_dog_lock_20_chicken` | Fav Silver Lock | Cumulative fav/dog, 20%+ gap |
| 45 | `ats_consensus_lock_20_chicken` | Consensus Silver | Consensus, 20%+ gap |
| 46 | `ats_odds_fav_dog_lock_20_chicken` | Odds Silver Lock | Odds-role, 20%+ gap |
| 47 | `ats_roll5_lock_20_chicken` | Streak-5 Silver | Last 5 home/away, 20%+ gap |
| 48 | `ats_roll10_lock_20_chicken` | Streak-10 Silver | Last 10 home/away, 20%+ gap |
| 49 | `ats_fav_dog_roll5_lock_20_chicken` | Fav Streak-5 Silver | Last 5 fav/dog, 20%+ gap |
| 50 | `ats_fav_dog_roll10_lock_20_chicken` | Fav Streak-10 Silver | Last 10 fav/dog, 20%+ gap |
| 51 | `ats_consensus_roll5_lock_20_chicken` | Consensus S5 Silver | Consensus last 5, 20%+ gap |
| 52 | `ats_consensus_roll10_lock_20_chicken` | Consensus S10 Silver | Consensus last 10, 20%+ gap |
| 53 | `ats_odds_fav_dog_roll5_lock_20_chicken` | Odds S5 Silver | Odds-role last 5, 20%+ gap |
| 54 | `ats_odds_fav_dog_roll10_lock_20_chicken` | Odds S10 Silver | Odds-role last 10, 20%+ gap |

**Gold Lock — 30% gap required**

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 55 | `ats_lock_30_chicken` | Gold Lock Henny | Cumulative home/away, 30%+ gap |
| 56 | `ats_fav_dog_lock_30_chicken` | Fav Gold Lock | Cumulative fav/dog, 30%+ gap |
| 57 | `ats_consensus_lock_30_chicken` | Consensus Gold | Consensus, 30%+ gap |
| 58 | `ats_odds_fav_dog_lock_30_chicken` | Odds Gold Lock | Odds-role, 30%+ gap |
| 59 | `ats_roll5_lock_30_chicken` | Streak-5 Gold | Last 5 home/away, 30%+ gap |
| 60 | `ats_roll10_lock_30_chicken` | Streak-10 Gold | Last 10 home/away, 30%+ gap |
| 61 | `ats_fav_dog_roll5_lock_30_chicken` | Fav Streak-5 Gold | Last 5 fav/dog, 30%+ gap |
| 62 | `ats_fav_dog_roll10_lock_30_chicken` | Fav Streak-10 Gold | Last 10 fav/dog, 30%+ gap |
| 63 | `ats_consensus_roll5_lock_30_chicken` | Consensus S5 Gold | Consensus last 5, 30%+ gap |
| 64 | `ats_consensus_roll10_lock_30_chicken` | Consensus S10 Gold | Consensus last 10, 30%+ gap |
| 65 | `ats_odds_fav_dog_roll5_lock_30_chicken` | Odds S5 Gold | Odds-role last 5, 30%+ gap |
| 66 | `ats_odds_fav_dog_roll10_lock_30_chicken` | Odds S10 Gold | Odds-role last 10, 30%+ gap |

### Zone 5 — "The Silo" (Positions 67–93)
*Multi-window agreement bots. Two or three time-window lenses must all agree. Very selective. Publishes fewer picks; each has higher prior quality.*

**Double Window — roll-5 AND roll-10 must agree**

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 67 | `ats_double_chicken` | Double Vision | Roll-5 AND roll-10 home/away agree |
| 68 | `ats_fav_dog_double_chicken` | Double Fav | Roll-5 AND roll-10 fav/dog agree |
| 69 | `ats_consensus_double_chicken` | Double Consensus | 4 signals: h/a + fav/dog over roll-5 and roll-10 |
| 70 | `ats_double_lock_10_chicken` | Double Bronze | Double window + 10%+ gap |
| 71 | `ats_fav_dog_double_lock_10_chicken` | Fav Double Bronze | Fav/dog double + 10%+ gap |
| 72 | `ats_consensus_double_lock_10_chicken` | Consensus Double Bronze | Consensus double + 10%+ gap |
| 73 | `ats_double_lock_20_chicken` | Double Silver | Double window + 20%+ gap |
| 74 | `ats_fav_dog_double_lock_20_chicken` | Fav Double Silver | Fav/dog double + 20%+ gap |
| 75 | `ats_consensus_double_lock_20_chicken` | Consensus Double Silver | Consensus double + 20%+ gap |
| 76 | `ats_double_lock_30_chicken` | Double Gold | Double window + 30%+ gap |
| 77 | `ats_fav_dog_double_lock_30_chicken` | Fav Double Gold | Fav/dog double + 30%+ gap |
| 78 | `ats_consensus_double_lock_30_chicken` | Consensus Double Gold | Consensus double + 30%+ gap |

**Triple Window — cumulative AND roll-5 AND roll-10 must all agree**

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 79 | `ats_triple_chicken` | Triple Threat | Cumulative + roll-5 + roll-10 home/away |
| 80 | `ats_fav_dog_triple_chicken` | Triple Fav | Cumulative + roll-5 + roll-10 fav/dog |
| 81 | `ats_consensus_triple_chicken` | Triple Consensus | 6 signals: h/a + fav/dog each over cum, r5, r10 |
| 82 | `ats_triple_lock_10_chicken` | Triple Bronze | Triple window + 10%+ gap |
| 83 | `ats_fav_dog_triple_lock_10_chicken` | Fav Triple Bronze | Fav/dog triple + 10%+ gap |
| 84 | `ats_consensus_triple_lock_10_chicken` | Consensus Triple Bronze | Consensus triple + 10%+ gap |
| 85 | `ats_triple_lock_20_chicken` | Triple Silver | Triple window + 20%+ gap |
| 86 | `ats_fav_dog_triple_lock_20_chicken` | Fav Triple Silver | Fav/dog triple + 20%+ gap |
| 87 | `ats_consensus_triple_lock_20_chicken` | Consensus Triple Silver | Consensus triple + 20%+ gap |
| 88 | `ats_triple_lock_30_chicken` | Triple Gold | Triple window + 30%+ gap |
| 89 | `ats_fav_dog_triple_lock_30_chicken` | Fav Triple Gold | Fav/dog triple + 30%+ gap |
| 90 | `ats_consensus_triple_lock_30_chicken` | Consensus Triple Gold | Consensus triple + 30%+ gap |

**Season-scoped Gold Lock**

| # | bot_key | Display Name | Signal |
|---|---------|--------------|--------|
| 91 | `ats_season_fav_dog_lock_30_chicken` | Season Fav Gold | Season fav/dog, 30%+ gap |
| 92 | `ats_season_consensus_lock_30_chicken` | Season Consensus Gold | Season consensus, 30%+ gap |
| 93 | `ats_season_odds_fav_dog_lock_30_chicken` | Season Odds Gold | Season odds-role, 30%+ gap |

### Boss Wing — "The Council" (Positions 94–98)
*The Chicken variants. Meta-signals that delegate each game to the highest-quality source bot available, filtered by a confidence floor.*

| # | bot_key | Display Name | Confidence Floor |
|---|---------|--------------|-----------------|
| 94 | `ats_chicken_tin` | Tin Chicken | None — publishes on every game |
| 95 | `ats_chicken_bronze` | Bronze Chicken | 55%+ source bot win% |
| 96 | `ats_chicken_silver` | Silver Chicken | 60%+ source bot win% |
| 97 | `ats_chicken_gold` | Gold Chicken | 65%+ source bot win% |
| 98 | `ats_chicken_platinum` | Platinum Chicken | 70%+ source bot win% |

---

## Appendix B — odds-warehouse Integration Reference

Bot names in Appendix A match exactly the `bot_name` field in `raw_bot_picks`. Key integration endpoints:

| Endpoint | Purpose |
|----------|---------|
| `GET /bots/daily-picks?sport=X&date=Y&bot=Z` | A specific bot's picks for a date |
| `GET /bots/records?sport=X` | All bots' current win% quality ratings |
| `GET /bots/chicken-picks?sport=X&start=Y&end=Z&variant=V` | Chicken Council picks |

The `source_bot` and `source_confidence` fields on Chicken picks identify which underlying bot was strongest that day — used to render the signal strength indicator in the UI.

`odds_api_event_id` is the foreign key linking PickenChicken `games` to odds-warehouse events.
