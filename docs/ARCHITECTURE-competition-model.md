# Picken Chicken — Architecture Spec: Competition Model

**Status:** Draft  
**Author:** James Williams  
**Last Updated:** 2026-03-18

---

## Context

The current codebase was built around a single competition type: the NCAA Tournament.
`TournamentRound` and `TournamentGame` are tightly coupled to that structure.

The 2026 NCAA Tournament is live. These entities must not be modified or migrated
until the tournament concludes. All new work is additive.

---

## Goal

Introduce a sport-agnostic Competition model that:

- Covers NCAA Tournament, NBA/MLB/NFL Regular Seasons and Playoffs
- Maps cleanly to Odds API (`sport_key`) and Sportradar conventions
- Allows `Pick` to reference any game type, not just `TournamentGame`
- Is the permanent foundation — `TournamentRound`/`TournamentGame` will be
  migrated into it post-tournament and then retired

---

## Entity Hierarchy

```
Competition
  └── CompetitionSegment          (replaces TournamentRound)
        └── Game                  (replaces TournamentGame)
              └── GamePick        (replaces Pick → TournamentGame FK)
```

### Competition

| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| sportKey | string(64) | Odds API key, e.g. `basketball_nba`, `basketball_ncaab` |
| league | string(64) | Human label: `NBA`, `NCAA`, `MLB`, `NFL` |
| type | enum | `regular_season`, `playoffs`, `tournament` |
| season | string(16) | e.g. `2025-26`, `2026` |
| name | string(128) | e.g. `NCAA Men's Basketball Tournament 2026` |
| status | enum | `upcoming`, `active`, `complete` |
| startsAt | datetime | |
| endsAt | datetime | |

### CompetitionSegment

| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| competition | Competition | FK |
| name | string(64) | `Round of 64`, `Week 1`, `First Round`, etc. |
| segmentNumber | int | Ordering within competition |
| status | enum | `upcoming`, `in_progress`, `complete` |
| startsAt | datetime | |
| endsAt | datetime | |

### Game

| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| segment | CompetitionSegment | FK |
| homeTeam | string(128) | |
| awayTeam | string(128) | |
| commenceTime | datetime | |
| status | enum | `scheduled`, `in_progress`, `final` |
| homeScore | int? | |
| awayScore | int? | |
| winner | string(128)? | Set at final |
| externalId | string(64)? | ESPN or Sportradar game ID |
| metadata | json? | Sport-specific extras: seeds, region, series record, etc. |

### GamePick

| Field | Type | Notes |
|---|---|---|
| id | int | PK |
| user | User | FK |
| game | Game | FK |
| userOutcome | MarketOutcome | FK |
| marketKey | string(64) | Denormalised |
| updatedAt | datetime | |
| result | enum? | `user_wins`, `chicken_wins`, `tie_win`, `tie_loss` |

Unique constraint: `(user_id, game_id)`

---

## Migration Plan

### Phase 1 — Additive (now, tournament in flight)
- Create `Competition`, `CompetitionSegment`, `Game`, `GamePick` entities
- New commands and controllers reference new entities only
- No changes to `TournamentRound`, `TournamentGame`, `Pick`

### Phase 2 — Backfill (post-tournament)
- Create a `Competition` record for NCAA Tournament 2026
- Migrate `TournamentRound` → `CompetitionSegment`
- Migrate `TournamentGame` → `Game` (seeds/region go into `metadata`)
- Migrate `Pick` → `GamePick`
- Update all controllers, commands, services to use new entities

### Phase 3 — Cleanup
- Drop `TournamentRound`, `TournamentGame`, `Pick` tables
- Remove legacy commands (`TournamentImportCommand`, `TournamentSyncCommand`, etc.)
  or rename/rewrite them as competition-agnostic

---

## External Data Sources

### Odds API
- `sport_key` maps directly to `Competition.sportKey`
- Used for: spreads, odds locking, market outcomes
- Competitions to add: `basketball_nba`, `baseball_mlb`, `americanfootball_nfl`

### Sportradar
- Used for: live scores, game results, team data
- `sr_game_id` stored in `Game.externalId`
- Will eventually replace ESPN API for score syncing

### ESPN API (current)
- Used for: NCAA Tournament bracket import and score sync
- `espnGameId` moves to `Game.externalId` during migration

---

## GitHub Project Structure

Issues will be organized under the milestone **"Competition Model — Phase 1"**

Labels:
- `entity` — Doctrine entity + migration
- `command` — Symfony console command
- `controller` — API endpoint
- `service` — Service layer
- `frontend` — React UI work
- `migration` — Data migration (Phase 2)
- `spike` — Research / exploration
