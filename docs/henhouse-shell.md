# The Hen House — Design Outline

## Vision

The Hen House is the social shell of PickenChicken. Instead of a bracket dashboard, players enter a persistent canvas world where the chicken bots exist as characters — NPCs with names, personalities, and positions in a shared space. The picks competition is the game; the Hen House is the world the game lives in.

The MUD is the reference model: a gamified social network where identity, presence, and competition are inseparable from place.

---

## What We Know

### The spatial engine (`../location`)
A Symfony project that models a world in Greek vocabulary:
- **Topos** — a room/place
- **Thura / Katofli / Porta** — passages and doors connecting rooms
- **Prosopon** — a person or entity that occupies a Topos
- **Pragma** — items (keys, containers, food) with size and location
- **Kleisto** — lockable things (doors, lids)

This engine will be the foundation for any room the game eventually has. The Hen House is the first Topos.

### The canvas room pattern (`../Doink`)
- Canvas + requestAnimationFrame draw loop
- Players and NPCs as positioned sprites (orbs) on a background image
- DOM hitbox overlays for click detection
- Context menu: Look / Challenge / High Five / Spectate
- Mercure/SSE for real-time presence (join/leave/events)
- Redis heartbeat presence (ping every 15s, 30s timeout)
- Challenge entity: code, player1, player2, status (waiting → active → finished)

### The existing PickenChicken stack
- Symfony 7 API (port 8001), React 19 SPA (port 3000)
- Magic-link auth, user stored in localStorage
- Mercure hub for real-time (chat, score broadcasts)
- Redis presence (already wired for chat tab)
- Bot Users already modeled (`User.type = bot`, `User.botKey`)
- `SimulatedClockService` for time-sensitive logic
- odds-warehouse API provides live bot picks and historical win%

---

## What Still Needs to Be Designed

### Chicken identities
The bots currently have algorithmic keys (`ats_lock_30_chicken`, etc.). Before they can inhabit the Hen House they need:
- **Display names** — who are they as characters?
- **Personas / voices** — how do they talk, taunt, react?
- **Visual identity** — sprite, color, tier marker
- **Specialization** — sport(s) they are known for, or are they generalists?
- **Tier / progression** — how does the ladder work? Do players unlock access to higher-tier chickens?

### The competitive structure
The PRD outlines a bot progression ladder (tin → bronze → silver → gold → platinum Chicken Council), but the game flow around it is not fully defined:
- Does a player have a single "current opponent" they are climbing toward?
- How is a win/loss against a chicken recorded and what does it unlock?
- Is there a season structure, or is it rolling?
- Can players challenge any chicken freely, or must they earn the right?
- What is the unit of competition — a single game pick, a day's slate, a week?

### The Hen House as a place
- What does the room look like? (background art / aesthetic)
- Are there sub-areas (perches per tier, a leaderboard wall, a challenge ring)?
- Does the room have doors to other Topoi in the future? If so, what's adjacent?
- How do chickens "behave" when not being challenged — idle animations, ambient dialogue?

### Social interactions
- Beyond Challenge: can players talk to chickens (triggering AI persona responses)?
- Can players interact with each other in the room (high-five, trash talk, spectate)?
- Is there a room chat alongside the canvas?

---

## Plan Outline

### Phase 0 — Design (current)
- Define chicken roster: names, tiers, personas, sport specializations
- Define competitive structure: ladder rules, challenge unit, unlock flow
- Sketch the Hen House room: layout, sub-areas, navigation to future rooms
- Decide on visual language for chicken sprites (pixel? orb? illustrated?)

### Phase 1 — World foundation
- Port `location` entities (Topos, Thura, Prosopon, etc.) into PickenChicken API
- Seed the Hen House Topos and place chicken Prosopon entities in it
- Create `ChickenProfile` entity: display name, persona, tier, position, botKey link
- Seed command to create initial chicken roster

### Phase 2 — Canvas room (Hen House shell)
- New `HenhouseTab` React component
- Canvas draw loop: background + chicken NPC sprites + online player sprites
- DOM hitbox + context menu: Look, Challenge
- Mercure room topic for real-time presence (join/leave)
- Heartbeat ping reusing existing Redis/PresenceService

### Phase 3 — Challenge flow
- Challenge drawer: shows today's games for chicken's sport(s)
- User picks via existing pick UI
- Chicken's pick fetched from odds-warehouse at lock time
- Result scored after game finishes, stored against player's ladder record

### Phase 4 — Persona and social layer
- Chicken ambient dialogue (idle, on challenge, on win, on loss)
- AI-backed chicken chat (extending existing `/chicken/chat` pattern)
- Player-to-player interactions (high-five, spectate)
- Room chat alongside canvas

### Phase 5 — World growth
- Additional Topoi connected via Thura/Porta
- Player-owned spaces (apartments, etc.)
- Pragma items (keys, containers) as game artifacts

---

## Open Questions

1. What are the chicken names and personalities?
2. What is the exact unit of competition (one game? one day's slate?)?
3. Does the ladder have explicit unlock gates, or is every chicken always challengeable?
4. What does beating a chicken mean — a win percentage threshold over N games?
5. What sport(s) does each chicken specialize in, if any?
6. What does the Hen House look like visually?
