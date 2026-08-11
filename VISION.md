# PickenChicken — Vision & Charter

**Tagline:** Beat The Chicken.

## Vision

PickenChicken is a free-to-play contest where a human picks games against The Chicken, a pure-math betting algorithm sourced from odds-warehouse. The game is won or lost entirely on picking skill against a live, honest opponent — not on house edge, not on bookmaking, not on who saw a better line.

## Charter — Non-Negotiables

1. **The Chicken is a real competitor, not a strawman.** Its picks are generated the same way a serious bettor would generate them: from a real odds snapshot, at a defined, fair moment in time. No retroactive picking, no information the user doesn't also effectively have access to.

2. **Fairness is measured by outcome against your own line, not a shared line.** The Chicken and the user are free to be bound to different odds snapshots — different books, different timestamps. That's fine. What's scored is each competitor's success against the slate of odds *they* actually picked against, not a single canonical line both are forced to share.

3. **A pick is bound to history at the moment it's made, permanently.** Once a pick is locked, it is graded against the historical odds snapshot that was current at lock time — never against whatever the live/raw line happens to be later (e.g. not against `raw_selections` at T-30 after the fact). This is the same discipline already applied to bot grading in odds-warehouse: no lookahead bias, anywhere, for anyone.

## The Core Loop

1. **Login.** User authenticates (magic link).
2. **Select league.** User picks a sport/league to view.
3. **See the slate.** User sees today's games: matchups, current odds, and the timestamp of when those odds were last updated.
4. **The Chicken locks its picks at T-30.** For each game, The Chicken's official, certified pick is generated from the odds snapshot taken at *game start minus 30 minutes*. This is the Chicken's one and only entry point — it does not get to react to anything after that.
5. **Certified picks are emailed at game start.** As each game begins, the user receives an email with The Chicken's certified pick for that game.
6. **The user can lock in anytime.** A user can lock a pick the moment they see the slate, or wait — and can edit a locked pick freely up until that game's start time. After game time, the pick is final.
7. **Settle and score.** Each side — Chicken and user — is graded against the specific historical odds snapshot bound to their own pick, not a shared or revised line.

## Why this matters

Most of the engineering effort in this monorepo so far has gone into rooting out lookahead bias in the bot-grading pipeline (see odds-warehouse refinement history). This charter exists to make sure the same discipline carries into the user-facing product: the moment we let a pick be re-evaluated against a *later* odds snapshot instead of the one it was actually made against, the contest stops being a fair fight and starts being a coin flip dressed up as skill. Binding every pick — Chicken's and human's — to its own historical snapshot is what keeps "Beat The Chicken" honest.
