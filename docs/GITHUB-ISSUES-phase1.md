# GitHub Issues — Competition Model Phase 1

Milestone: **Competition Model — Phase 1**  
All issues are additive. No existing entities are touched.

---

## Epic: Data Model

---

### ISSUE-001: Create `Competition` entity and migration
**Label:** `entity`  
**Milestone:** Competition Model — Phase 1

Create the `Competition` Doctrine entity with fields:
`id`, `sportKey`, `league`, `type` (enum), `season`, `name`, `status` (enum),
`startsAt`, `endsAt`.

Generate and apply the Doctrine migration.

Acceptance: `Competition` table exists in DB. Entity passes `doctrine:schema:validate`.

---

### ISSUE-002: Create `CompetitionSegment` entity and migration
**Label:** `entity`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-001

Create `CompetitionSegment` entity: `id`, `competition` (FK), `name`,
`segmentNumber`, `status` (enum), `startsAt`, `endsAt`.

Acceptance: Schema validates. Segment is correctly related to Competition.

---

### ISSUE-003: Create `Game` entity and migration
**Label:** `entity`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-002

Create `Game` entity: `id`, `segment` (FK), `homeTeam`, `awayTeam`,
`commenceTime`, `status` (enum), `homeScore`, `awayScore`, `winner`,
`externalId`, `metadata` (JSON column).

Acceptance: Schema validates. JSON metadata column is nullable and queryable.

---

### ISSUE-004: Create `GamePick` entity and migration
**Label:** `entity`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-003

Create `GamePick` entity: `id`, `user` (FK), `game` (FK), `userOutcome` (FK),
`marketKey`, `updatedAt`, `result` (enum, nullable).

Unique constraint on `(user_id, game_id)`.

Acceptance: Schema validates. Unique constraint present.

---

## Epic: Commands

---

### ISSUE-005: `competition:import` command
**Label:** `command`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-003

Create `CompetitionImportCommand` that accepts `--sport-key`, `--season`, `--type`
and fetches game schedule from Sportradar (or ESPN as fallback), creating
`Competition`, `CompetitionSegment`, and `Game` records.

Start with NBA as the target sport.

Acceptance: Running the command against NBA 2025-26 populates Games correctly.

---

### ISSUE-006: `competition:sync` command
**Label:** `command`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-005

Create `CompetitionSyncCommand` that fetches live/final scores for active
`Competition` records and updates `Game` status, scores, and winner.

Acceptance: A final game gets `status=final`, `homeScore`, `awayScore`, and `winner` set.

---

### ISSUE-007: `competition:odds:lock` command
**Label:** `command`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-003

Extend or mirror `OddsLockCommand` to work against `Game` (not `TournamentGame`).
Fetches spreads from Odds API using `Competition.sportKey`, creates
`GameMarket` + `MarketOutcome` records linked to `Game`.

Acceptance: Games for an active Competition get locked odds before game time.

---

## Epic: API

---

### ISSUE-008: `GET /competitions` endpoint
**Label:** `controller`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-001

Return list of all Competitions with `status`, `sport`, `league`, `type`, `season`.
Filter by `?status=active` optional query param.

---

### ISSUE-009: `GET /competitions/{id}/games` endpoint
**Label:** `controller`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-003

Return all Games for a Competition, grouped by CompetitionSegment.
Include current odds (GameMarket/MarketOutcome) where available.

---

### ISSUE-010: `POST /competitions/{competitionId}/games/{gameId}/pick` endpoint
**Label:** `controller`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-004

Allow authenticated user to submit or update a `GamePick`.
Enforce lock time — reject picks after `Game.commenceTime`.

---

## Epic: Frontend

---

### ISSUE-011: Competition selector UI
**Label:** `frontend`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-008

Add a competition switcher to the main nav/lobby. Users can browse and enter
available Competitions. Design should feel like choosing a game mode.

---

### ISSUE-012: Competition game board
**Label:** `frontend`  
**Milestone:** Competition Model — Phase 1  
**Depends on:** ISSUE-009, ISSUE-010

Build the picks UI for a generic Competition — segmented by CompetitionSegment,
with lock states, odds display, and chicken pick reveal. This replaces/extends
the current NCAA-only bracket view.

---

## Epic: Research

---

### ISSUE-013: Sportradar API spike — NBA schedule + scores
**Label:** `spike`  
**Milestone:** Competition Model — Phase 1

Evaluate Sportradar NBA endpoints for schedule import and live score sync.
Document: auth model, rate limits, game ID format, team identifiers.
Determine if `externalId` on `Game` needs a `source` field (e.g. `espn` vs `sportradar`).

Deliverable: 1-page findings doc in `/docs`.

---

### ISSUE-014: Odds API sport coverage audit
**Label:** `spike`  
**Milestone:** Competition Model — Phase 1

List all available `sport_key` values from Odds API.
Map to our target competition types (NBA regular season, NBA playoffs, MLB, NFL).
Confirm spread market availability for each.

Deliverable: table in `/docs/ODDS-API-COVERAGE.md`.
