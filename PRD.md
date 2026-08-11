# PickenChicken — Product Requirements Document

**Created:** 2026-05-07  
**Status:** Draft  
**Stack:** Symfony 7.3 (PHP 8.2) API + React 19 SPA + PostgreSQL + odds-warehouse (Python/FastAPI)

---

## 1. Overview

**PickenChicken** is a free-to-play, daily ATS (Against The Spread) picks contest. Players pick the outcome of real sports games (NBA, NHL, MLB, NFL, WNBA, NCAAB, NCAAF) against the spread, and compete against **The Chicken** — a family of pure-math, world-blind algorithms powered by the odds-warehouse data pipeline.

The defining mechanic is a **bot progression ladder**: players start by beating the weakest bots (simple random pickers), work their way through increasingly sophisticated algorithmic opponents, and ultimately challenge the Chicken Council — five meta-algorithm final bosses that cherry-pick the strongest signal available each day.

In this world, a "chicken" is any world-blind, pure-math picking algorithm. It sees only data; it knows nothing about injuries, narratives, or news. The irony is that some of these chickens are very hard to beat.

---

## 2. Core Concepts

### 2.1 ATS Picks

All picks are Against The Spread. A pick wins if the chosen team covers the spread, loses if it doesn't, and pushes if the margin is exactly the spread (pushes don't count toward records).

The spread is the median pre-game line across all available sportsbooks, sourced from odds-warehouse.

### 2.2 The Bot Roster

Every bot in the progression ladder is a live algorithm running in odds-warehouse. Bot picks and bot records are read from the odds-warehouse API in real time — PickenChicken stores only user picks and lightweight snapshots for audit.

The complete roster of 98 bots is catalogued in Appendix A. They fall into distinct algorithm families:

| Family | Description |
|--------|-------------|
| **Random/Pseudorandom** | Picks by fixed rule (always home, always fav) or Thue-Morse sequence |
| **Cumulative ATS signal** | Tracks team ATS% since records began, picks the team with the better record |
| **Rolling window** | Tracks team ATS% over the last N games (roll5, roll10) — follows hot/cold streaks |
| **Season-scoped** | Like cumulative, but resets to 0 at each season boundary |
| **Lock threshold** | Only picks when the ATS% gap between teams exceeds a threshold (10/20/30%) |
| **Double window** | Requires rolling-5 AND rolling-10 to agree before picking |
| **Triple window** | Requires cumulative AND rolling-5 AND rolling-10 to all agree |
| **The Chicken (meta)** | Each game, picks from whichever source bot has the highest historical win%; confidence filter controls how selective |

### 2.3 The Chicken Council (Final Bosses)

The five Chicken variants are the final bosses. They don't have a fixed strategy — they observe all other bots' current win% and delegate each game's pick to the strongest signal available, subject to a minimum confidence threshold:

| Bot | Confidence Filter | Behavior |
|-----|------------------|----------|
| `ats_chicken_tin` | None | Picks every game |
| `ats_chicken_bronze` | 55%+ | Only picks when best bot is at 55%+ win% |
| `ats_chicken_silver` | 60%+ | Only picks at 60%+ win% |
| `ats_chicken_gold` | 65%+ | Only picks at 65%+ win% |
| `ats_chicken_platinum` | 70%+ | Only picks at 70%+ win% |

Higher confidence = fewer games picked, but each selection is a stronger signal. Platinum Chicken may pick very few games but is nearly impossible to beat.

### 2.4 Multi-Sport, All Bots

All bots operate across all sports simultaneously. A player's current opponent is the same bot whether they're picking NBA, NHL, or MLB games that day. A player who is fighting `ats_roll10_chicken` sees that bot's picks across all sports it has data for.

Phase 1 sports: **NBA, NHL, MLB, NFL, WNBA**  
Phase 2 sports: **NCAAB, NCAAF**

---

## 3. Game Mechanics

### 3.1 Progression Ladder

Players advance through the bot roster by defeating bots one at a time. The ladder has five zones of increasing difficulty, followed by the Chicken Council boss wing.

**Defeat Condition** is configurable per Competition (see §4.1). The platform supports:

| Mode | Description |
|------|-------------|
| `single_day` | Win more correct picks than the bot on a single day |
| `series_N` | Best-of-N days (first to win ceil(N/2) days advances) |
| `record_pct` | Beat the bot's cumulative win% over at least M games |
| `season` | Beat the bot's win% by end of the competition's season |

The default for a new competition is `single_day`.

When a player defeats the current bot, they are immediately assigned the next bot in the ladder. A player can challenge their current opponent on any day that bot has picks. They always see the live bot record sourced from odds-warehouse.

### 3.2 Daily Session

Each day:
1. Player sees today's game docket for their chosen sport(s)
2. Each game shows: matchup, spread, The Chicken's pick (their current bot), signal strength
3. Player picks home or away for each game they want to action
4. Games settle via odds-warehouse results
5. Player's correct picks vs. bot's correct picks for the day are compared

A player can pick as many or as few games as they want. They are only compared on games they actually pick (no penalty for skipping a game, but picking more games reduces variance).

### 3.3 Signal Strength Display

For The Chicken meta-bots, display the source bot's win% as a color signal:
- **Green**: source bot win% > 60%
- **Yellow**: source bot win% 50–60%
- **Red**: source bot win% < 50%

For non-Chicken bots, display the bot's own win% the same way.

### 3.4 Leaderboard

Within a competition, players are ranked by:
1. **Ladder rank** — current bot level in the progression (primary)
2. **Personal win%** — cumulative ATS win% against all opponents (tiebreaker)
3. **Bot score** — how much the player outperformed bots they've defeated

The leaderboard also shows each player's current opponent and how close they are to defeating it.

---

## 4. Data Model

### 4.1 Competition

A Competition is a named game instance with configurable rules. Multiple competitions can run simultaneously (e.g., "NBA Season 2026", "All-Sports Spring 2026").

```
Competition
  name                   string
  sports                 string[]        — e.g. ["basketball_nba", "icehockey_nhl"]
  defeat_condition_type  enum            — single_day | series_N | record_pct | season
  defeat_condition_value int|float|null  — N for series, M min games for record_pct
  active                 bool
  starts_at              datetime
  ends_at                datetime|null
```

### 4.2 PlayerProgress

Tracks which bot a player is currently fighting and their record against it.

```
PlayerProgress
  competition_id         FK → Competition
  user_id                FK → User
  current_bot            string          — bot_name from odds-warehouse
  current_bot_days_won   int             — days player won against current bot
  current_bot_days_lost  int
  current_bot_games_won  int             — individual game picks
  current_bot_games_lost int
  ladder_position        int             — 1-98 index into the canonical bot list
  defeated_bots          string[]        — history
  advanced_at            datetime|null
```

### 4.3 UserPick

One row per game pick a player makes.

```
UserPick
  id
  user_id                FK → User
  competition_id         FK → Competition
  odds_api_event_id      string          — FK to odds-warehouse
  pick_date              date
  outcome_name           string          — team player picked
  home_spread            decimal
  result                 enum|null       — win | loss | push | null (pending)
  bot_name               string          — opponent bot at pick time
  bot_outcome_name       string          — bot's pick for this game
  bot_result             enum|null
  settled_at             datetime|null
```

### 4.4 BotMatchup

One row per day a player and bot both have picks for the same game.

```
BotMatchup
  id
  user_id
  competition_id
  bot_name
  match_date             date
  player_correct         int
  bot_correct            int
  player_won_day         bool|null       — null until settled
```

### 4.5 User / Leaderboard

Standard user entity (magic-link auth). Leaderboard is a materialized view or aggregated on-demand from PlayerProgress + BotMatchup.

---

## 5. API Design

### 5.1 PickenChicken Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/competitions` | List active competitions |
| GET | `/api/competitions/{id}/docket?sport=X&date=Y` | Today's games + bot picks for user's current opponent |
| POST | `/api/competitions/{id}/picks` | Submit user picks for a day |
| GET | `/api/competitions/{id}/picks?date=Y` | User's picks for a day |
| GET | `/api/competitions/{id}/progress` | User's current ladder position + record |
| POST | `/api/competitions/{id}/settle?date=Y` | Score a day's picks (triggers odds-warehouse result fetch) |
| GET | `/api/competitions/{id}/leaderboard` | Ranked standings |
| GET | `/api/competitions/{id}/bot-record?bot=X` | Live win% for a specific bot (proxied from odds-warehouse) |

### 5.2 odds-warehouse Endpoints Used

| Endpoint | Purpose |
|----------|---------|
| `GET /bots/chicken-picks?sport=X&start=Y&end=Z` | Fetch The Chicken meta-bot picks |
| `GET /bots/daily-picks?sport=X&date=Y&bot=Z` | Fetch any bot's picks for a specific day |
| `GET /bots/records?sport=X` | Bot win% records |
| `GET /events?sport=X&start=Y&end=Z` | Game results for scoring |

---

## 6. Frontend

### 6.1 Pages

**Pick Page** (`/competitions/{id}/pick`)
- Sport selector + date picker
- Game grid: home vs away, spread, bot's pick (color-coded by signal strength), user pick (radio button)
- Locked indicator for games that have started (HTTP 423 from API)
- Day summary: user correct / bot correct

**Progress Page** (`/competitions/{id}/progress`)
- Current bot level, name, description (what strategy it uses)
- Record against this bot (W-L)
- Bot's overall win% sourced live from odds-warehouse
- Recent matchup history
- Ladder visualization (zones, how far to go)

**Leaderboard** (`/competitions/{id}/leaderboard`)
- Ranked table: ladder position, username, personal win%, current bot
- Sortable by ladder position or win%

### 6.2 UX Notes

- Green/yellow/red signal strength is the primary visual affordance on the pick page
- Bots should be presented with human-readable names (see Appendix A — display names)
- The "ladder" visualization is a scroll or map showing the five zones and boss wing
- Players should see what bot they'll face next (teaser)

---

## 7. Development Phases

### Phase 1 — MVP (Daily Picks + Progression)
- [ ] Symfony entities: Competition, PlayerProgress, UserPick, BotMatchup
- [ ] API: `/docket`, `/picks` (submit + read), `/settle`, `/progress`
- [ ] odds-warehouse proxy service for bot picks and records
- [ ] React: pick page, simple progress display
- [ ] Seeded canonical bot ladder (all 98 bots in order)
- [ ] Single-day defeat condition
- [ ] Magic-link auth

### Phase 2 — Leaderboard + Polish
- [ ] Leaderboard endpoint + UI
- [ ] Signal strength color coding
- [ ] Ladder visualization
- [ ] Series and record_pct defeat conditions
- [ ] Multi-sport simultaneous picks

### Phase 3 — Community + Scale
- [ ] Competition creation UI (admin)
- [ ] Trash talk / comments on picks
- [ ] Push notifications (game results, advancement)
- [ ] Season leaderboard resets
- [ ] NCAAB / NCAAF (Phase 2 sports)

---

## Appendix A — Canonical Bot Ladder

All 98 bots in progression order. `ladder_position` is stored in `PlayerProgress`. Bot names are exactly as used in odds-warehouse `raw_bot_picks.bot_name`.

### Zone 1 — "The Yard" (Positions 1–6)
*Random and deterministic-sequence pickers. No learning. ~50% win%.*

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
| 1 | `home_away_chicken` | Home Field Henny | Always picks home team |
| 2 | `fav_dog_chicken` | Favorite Foghorn | Always picks the favorite |
| 3 | `odds_fav_dog_chicken` | Odds Foghorn | Always picks the odds-role favorite |
| 4 | `home_away_thue_morse` | Morse Hen | Thue-Morse sequence: home/away alternation |
| 5 | `fav_dog_thue_morse` | Morse Mutt | Thue-Morse: fav/underdog alternation |
| 6 | `odds_fav_dog_thue_morse` | Odds Morse | Thue-Morse: odds-role alternation |

### Zone 2 — "The Coop" (Positions 7–18)
*Single-signal ATS bots. Picks every game. Learns from historical data but with no selectivity filter.*

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
| 7 | `ats_home_away_chicken` | Home Stats Henny | Cumulative home/away ATS% |
| 8 | `ats_fav_dog_chicken` | Fav Stats Foghorn | Cumulative favorite/underdog ATS% |
| 9 | `ats_odds_fav_dog_chicken` | Odds Stats Hen | Cumulative odds-role ATS% |
| 10 | `ats_consensus_chicken` | Consensus Cluck | Home/away AND fav/dog signals must agree |
| 11 | `ats_roll5_chicken` | Hot Streak (5) | Home/away ATS%, last 5 games only |
| 12 | `ats_roll10_chicken` | Hot Streak (10) | Home/away ATS%, last 10 games only |
| 13 | `ats_fav_dog_roll5_chicken` | Fav Streak (5) | Fav/dog ATS%, last 5 games |
| 14 | `ats_fav_dog_roll10_chicken` | Fav Streak (10) | Fav/dog ATS%, last 10 games |
| 15 | `ats_consensus_roll5_chicken` | Consensus Streak (5) | Consensus, last 5 games |
| 16 | `ats_consensus_roll10_chicken` | Consensus Streak (10) | Consensus, last 10 games |
| 17 | `ats_odds_fav_dog_roll5_chicken` | Odds Streak (5) | Odds-role ATS%, last 5 games |
| 18 | `ats_odds_fav_dog_roll10_chicken` | Odds Streak (10) | Odds-role ATS%, last 10 games |

### Zone 3 — "The Season Coop" (Positions 19–30)
*Season-scoped variants. ATS records reset at each season boundary — starts fresh in October, accumulates all season.*

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
| 19 | `ats_season_home_away_chicken` | Season Home Henny | Home/away ATS%, current season only |
| 20 | `ats_season_fav_dog_chicken` | Season Foghorn | Fav/dog ATS%, current season only |
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
*Lock threshold bots. Only pick when one team's ATS% dominates by a threshold margin. Fewer picks, higher precision.*

**Bronze Lock (10% gap required)**

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
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

**Silver Lock (20% gap required)**

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
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

**Gold Lock (30% gap required)**

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
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
*Multi-window agreement bots. Two or three time-window lenses must all point the same direction. Very selective.*

**Double Window — roll5 AND roll10 must agree**

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
| 67 | `ats_double_chicken` | Double Vision | Roll-5 AND roll-10 home/away agree |
| 68 | `ats_fav_dog_double_chicken` | Double Fav | Roll-5 AND roll-10 fav/dog agree |
| 69 | `ats_consensus_double_chicken` | Double Consensus | 4 signals: h/a + fav/dog each over roll-5 and roll-10 |
| 70 | `ats_double_lock_10_chicken` | Double Bronze | Double window + 10%+ gap |
| 71 | `ats_fav_dog_double_lock_10_chicken` | Fav Double Bronze | Fav/dog double + 10%+ gap |
| 72 | `ats_consensus_double_lock_10_chicken` | Consensus Double Bronze | Consensus double + 10%+ gap |
| 73 | `ats_double_lock_20_chicken` | Double Silver | Double window + 20%+ gap |
| 74 | `ats_fav_dog_double_lock_20_chicken` | Fav Double Silver | Fav/dog double + 20%+ gap |
| 75 | `ats_consensus_double_lock_20_chicken` | Consensus Double Silver | Consensus double + 20%+ gap |
| 76 | `ats_double_lock_30_chicken` | Double Gold | Double window + 30%+ gap |
| 77 | `ats_fav_dog_double_lock_30_chicken` | Fav Double Gold | Fav/dog double + 30%+ gap |
| 78 | `ats_consensus_double_lock_30_chicken` | Consensus Double Gold | Consensus double + 30%+ gap |

**Triple Window — cumulative AND roll5 AND roll10 must all agree**

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
| 79 | `ats_triple_chicken` | Triple Threat | Cumulative + roll-5 + roll-10 home/away agree |
| 80 | `ats_fav_dog_triple_chicken` | Triple Fav | Cumulative + roll-5 + roll-10 fav/dog agree |
| 81 | `ats_consensus_triple_chicken` | Triple Consensus | 6 signals: h/a + fav/dog each over cum, roll-5, roll-10 |
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

| # | Bot Name | Display Name | Strategy |
|---|----------|--------------|----------|
| 91 | `ats_season_fav_dog_lock_30_chicken` | Season Fav Gold | Season fav/dog, 30%+ gap |
| 92 | `ats_season_consensus_lock_30_chicken` | Season Consensus Gold | Season consensus, 30%+ gap |
| 93 | `ats_season_odds_fav_dog_lock_30_chicken` | Season Odds Gold | Season odds-role, 30%+ gap |

### Boss Wing — "The Council" (Positions 94–98)
*The Chicken variants. Meta-bots that cherry-pick the strongest signal available for each game.*

| # | Bot Name | Display Name | Min Confidence |
|---|----------|--------------|----------------|
| 94 | `ats_chicken_tin` | Tin Chicken | None — picks every game |
| 95 | `ats_chicken_bronze` | Bronze Chicken | 55%+ source bot win% |
| 96 | `ats_chicken_silver` | Silver Chicken | 60%+ source bot win% |
| 97 | `ats_chicken_gold` | Gold Chicken | 65%+ source bot win% |
| 98 | `ats_chicken_platinum` | Platinum Chicken | 70%+ source bot win% |

---

## Appendix B — odds-warehouse Bot Name Quick Reference

For integration: the `bot_name` field in `raw_bot_picks` matches exactly the names in Appendix A. The odds-warehouse API routes that expose these:

- `GET /bots/daily-picks?sport=X&date=Y&bot=Z` — picks for a specific bot on a date
- `GET /bots/records?sport=X` — all bots' win% records (use to show live bot difficulty)
- `GET /bots/chicken-picks?sport=X&start=Y&end=Z&variant=ats_chicken_gold` — Chicken council picks

The `source_bot` and `source_confidence` fields on Chicken picks tell the UI which underlying bot was strongest that day.
