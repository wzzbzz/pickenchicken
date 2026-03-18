# Picken Chicken — Product Vision

> *Can you beat pure randomness?*

---

## What We're Building

Picken Chicken is a free-to-play sports prediction platform where users go head-to-head
against a chicken that picks randomly. The joke is the game. The meta is that beating
a chicken is harder than it sounds.

This is not a DFS site. This is not a sportsbook. It is a social game — lightweight,
irreverent, and built for people who want skin in the game without skin in the game.

---

## The Core Loop

1. A **Competition** goes live (NCAA Tournament, NBA Playoffs, MLB Regular Season, etc.)
2. Users make **picks** against the spread before lock time
3. The **Chicken** picks randomly at lock
4. Results come in. The scoreboard updates. The trash talk flows.
5. At the end of the Competition, a winner is crowned.

---

## Competitions

Picken Chicken is organized around **Competitions** — discrete, time-bounded slices of
sports activity. A Competition maps to how Odds API and Sportradar already segment the world:

| Sport | League | Type | Example |
|---|---|---|---|
| Basketball | NCAA | Tournament | March Madness 2026 |
| Basketball | NBA | Playoffs | NBA Playoffs 2026 |
| Basketball | NBA | Regular Season | NBA 2025–26 Regular Season |
| Baseball | MLB | Regular Season | MLB 2026 Regular Season |
| Football | NFL | Regular Season | NFL 2026 Regular Season |
| Football | NFL | Playoffs | NFL Playoffs 2027 |

Each Competition has:
- A **sport** and **league** (matching Odds API `sport_key` conventions)
- A **type** (`tournament` | `playoffs` | `regular_season`)
- A **season** identifier
- One or more **Segments** (rounds, weeks, series)
- A collection of **Games**

---

## The Pluggable Game Architecture

Picken Chicken is one game on a platform. The platform supports multiple games
(see also: Doink at playdoink.com). Each game:

- Shares core infrastructure: auth, sessions, presence, chat, challenges, gangs, events
- Has its own domain logic, entities, and UI
- Can be developed, deployed, and scaled independently
- Will eventually be unified under a single Greenhouse/Ampelos umbrella

The Competition model introduced here is the foundation of Picken Chicken's expansion.
It is designed to be sport-agnostic and competition-type-agnostic from day one.

---

## Design Principles

**Simplicity first.** The interface should feel like a game, not a dashboard.

**The Chicken is the character.** She is the antagonist, the mascot, and the mechanic.
Her randomness is the whole point. Never let the engineering obscure the bit.

**Social over solo.** Gangs, challenges, leaderboards, and chat are first-class features.
Playing alone is fine. Playing with friends is the product.

**Pluggable, not monolithic.** Every Competition type added should require zero changes
to core infrastructure. New sport? New entity data. Not new architecture.

**Live feels live.** Scores update. Odds lock. The chicken picks at tip-off.
This is a real-time game, not a weekly poll.

---

## Out of Scope (for now)

- Real money / wagering of any kind
- User-generated competitions
- Native mobile apps (web-first)
- Merging with Doink or other Greenhouse games
