import React, { useState, useEffect, useCallback, useRef } from 'react';
import { BrowserRouter, Routes, Route, Navigate, Link } from 'react-router-dom';
import Login from './pages/Login';
import VerifyAuth from './pages/VerifyAuth';
import TermsOfUse from './pages/TermsOfUse';
import PrivacyPolicy from './pages/PrivacyPolicy';
import './App.css';

const API = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8001/api';

async function apiFetch(path, opts = {}) {
  const res = await fetch(`${API}${path}`, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw Object.assign(new Error(err.error || 'API error'), { status: res.status });
  }
  return res.json();
}

export default function App() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const id = localStorage.getItem('user_id');
    const email = localStorage.getItem('user_email');
    const username = localStorage.getItem('user_username');
    if (id && email) setUser({ id, email, username });
    setLoading(false);
  }, []);

  const handleLogin = (userData) => setUser(userData);
  const handleLogout = () => {
    ['user_id', 'user_email', 'user_username'].forEach(k => localStorage.removeItem(k));
    setUser(null);
  };

  if (loading) return <div className="pc-boot">Loading... 🐔</div>;

  return (
    <BrowserRouter>
      <Routes>
        <Route path="/login" element={<Login onLogin={handleLogin} />} />
        <Route path="/auth/verify" element={<VerifyAuth onLogin={handleLogin} />} />
        <Route path="/terms" element={<TermsOfUse />} />
        <Route path="/privacy" element={<PrivacyPolicy />} />
        <Route path="/*" element={
          user ? <TournamentApp user={user} onLogout={handleLogout} />
               : <Navigate to="/login" replace />
        } />
      </Routes>
    </BrowserRouter>
  );
}

function DevBar() {
  const [clock, setClock] = useState(null);
  const [custom, setCustom] = useState('');
  const [syncing, setSyncing] = useState(false);

  const loadClock = () => apiFetch('/dev/clock').then(setClock).catch(() => {});

  useEffect(() => { loadClock(); }, []);

  const advance = async (duration) => {
    await apiFetch('/dev/clock/advance', { method: 'POST', body: JSON.stringify({ duration }) });
    await loadClock();
    window.location.reload();
  };

  const setTime = async (e) => {
    e.preventDefault();
    if (!custom) return;
    await apiFetch('/dev/clock', { method: 'POST', body: JSON.stringify({ datetime: custom }) });
    await loadClock();
    window.location.reload();
  };

  const reset = async () => {
    await apiFetch('/dev/clock', { method: 'POST', body: JSON.stringify({ datetime: null }) });
    await loadClock();
    window.location.reload();
  };

  const sync = async () => {
    setSyncing(true);
    await loadClock();
    window.location.reload();
    setSyncing(false);
  };

  if (!clock) return null;

  return (
    <div className="pc-devbar">
      <div className="pc-devbar-label">🛠 DEV</div>
      <div className="pc-devbar-clock">
        <span className={clock.simulated ? 'pc-devbar-simulated' : 'pc-devbar-real'}>
          {clock.simulated ? '⏱ ' : '🕐 '}{clock.nowHuman}
        </span>
      </div>
      <div className="pc-devbar-controls">
        {['1h','4h','12h','1d','2d','3d'].map(d => (
          <button key={d} className="pc-devbar-btn" onClick={() => advance(d)}>+{d}</button>
        ))}
      </div>
      <form className="pc-devbar-form" onSubmit={setTime}>
        <input className="pc-devbar-input" type="text" value={custom}
          onChange={e => setCustom(e.target.value)}
          placeholder="2025-03-20 16:00:00" />
        <button type="submit" className="pc-devbar-btn pc-devbar-btn-set">Set</button>
      </form>
      <button className="pc-devbar-btn pc-devbar-btn-reset" onClick={reset}>Reset</button>
      <button className="pc-devbar-btn pc-devbar-btn-sync" onClick={sync} disabled={syncing}>
        {syncing ? '...' : '↺ Sync'}
      </button>
    </div>
  );
}

function Footer() {
  return (
    <footer className="pc-footer">
      <div className="pc-footer-warning">
        <span className="pc-footer-icon">⚠</span>
        <strong>Gambling Problem?</strong> Call or text the National Problem Gambling Helpline:&nbsp;
        <a href="tel:18005224700" className="pc-footer-link">1-800-522-4700</a>
        &nbsp;· 24/7 confidential support &nbsp;·&nbsp;
        <a href="https://www.ncpgambling.org" target="_blank" rel="noopener noreferrer" className="pc-footer-link">ncpgambling.org</a>
      </div>
      <div className="pc-footer-legal">
        This site is for entertainment purposes only. No real money wagering. Must be 18+.
        Odds data is informational only.
        &nbsp;<a href="https://www.begambleaware.org" target="_blank" rel="noopener noreferrer" className="pc-footer-link">BeGambleAware</a>
        &nbsp;·&nbsp;
        <a href="https://www.gamblingtherapy.org" target="_blank" rel="noopener noreferrer" className="pc-footer-link">GamblingTherapy</a>
        &nbsp;·&nbsp;
        <Link to="/terms" className="pc-footer-link">Terms of Use</Link>
        &nbsp;·&nbsp;
        <Link to="/privacy" className="pc-footer-link">Privacy Policy</Link>
      </div>
    </footer>
  );
}

function TournamentApp({ user, onLogout }) {
  const [tab, setTab] = useState('bracket');
  const DEV_MODE = process.env.REACT_APP_DEV_MODE === 'true';
  return (
    <div className="pc-root">
      <div className="pc-masthead">
        <div>
          <div className="pc-logo">The Picken' <span>Chicken</span></div>
          <div className="pc-tagline">Can you beat pure randomness?</div>
        </div>
        <div className="pc-user-bar">
          <span>{user.username || user.email}</span>
          <button className="pc-logout-btn" onClick={onLogout}>Sign out</button>
        </div>
      </div>
      <nav className="pc-nav">
        {[['bracket','Bracket'],['my-picks','My Picks'],['leaderboard','Leaderboard']].map(([key, label]) => (
          <button key={key} className={`pc-nav-tab ${tab === key ? 'active' : ''}`} onClick={() => setTab(key)}>
            {label}
          </button>
        ))}
      </nav>
      <div className="pc-body">
        {tab === 'bracket'     && <BracketTab user={user} key={tab} />}
        {tab === 'my-picks'    && <MyPicksTab user={user} />}
        {tab === 'leaderboard' && <LeaderboardTab />}
      </div>
      {DEV_MODE && <DevBar />}
      <Footer />
    </div>
  );
}

function BracketTab({ user }) {
  const [rounds, setRounds] = useState([]);
  const [activeRound, setActiveRound] = useState(null);
  const [games, setGames] = useState([]);
  const [loadingGames, setLoadingGames] = useState(false);
  const [error, setError] = useState(null);
  const activeRoundRef = useRef(null);
  const userRef = useRef(user);

  useEffect(() => { activeRoundRef.current = activeRound; }, [activeRound]);
  useEffect(() => { userRef.current = user; }, [user]);

  useEffect(() => {
    apiFetch('/tournament/rounds')
      .then(data => {
        setRounds(data);
        const current = data.find(r => r.status === 'in_progress')
          || data.find(r => r.status === 'upcoming')
          || data[0];
        if (current) setActiveRound(current);
      })
      .catch(e => setError(e.message));
  }, []);

  const fetchGames = useCallback((round, showLoading = false) => {
    if (!round) return;
    if (showLoading) setLoadingGames(true);
    apiFetch(`/tournament/rounds/${round.id}/games?userId=${userRef.current.id}&marketKey=spreads`)
      .then(data => { setGames(data); setLoadingGames(false); })
      .catch(e => { setError(e.message); setLoadingGames(false); });
  }, []);

  useEffect(() => {
    if (!activeRound) return;
    fetchGames(activeRound, true);
  }, [activeRound, fetchGames]);

  // Poll every 15 seconds — pure read, no server-side triggers
  useEffect(() => {
    const interval = setInterval(() => {
      const round = activeRoundRef.current;
      if (!round || round.status === 'complete') return;
      fetchGames(round, false);
    }, 15000);
    return () => clearInterval(interval);
  }, [fetchGames]);

  const handlePick = useCallback(async (gameId, outcomeId) => {
    try {
      await apiFetch('/tournament/picks', {
        method: 'POST',
        body: JSON.stringify({ userId: parseInt(userRef.current.id), gameId, outcomeId }),
      });
      // Re-fetch games to get updated pick + chicken pick state
      fetchGames(activeRoundRef.current, false);
    } catch (e) {
      alert(e.status === 423 ? 'Picks are locked once the game starts.' :
            e.status === 425 ? 'Odds not yet locked — check back at 10am on game day.' :
            e.message);
    }
  }, [fetchGames]);

  const [hidePast, setHidePast] = useState(true);

  if (error) return <div className="pc-empty">Error: {error}</div>;

  const visibleGames = hidePast
    ? games.filter(g => g.status === 'scheduled' || g.status === 'in_progress')
    : games;

  const byRegion = visibleGames.reduce((acc, g) => {
    const r = g.region || 'Other';
    if (!acc[r]) acc[r] = [];
    acc[r].push(g);
    return acc;
  }, {});

  return (
    <>
      <div className="pc-round-pills">
        {rounds.map(r => (
          <button key={r.id}
            className={`pc-round-pill ${activeRound?.id === r.id ? 'active' : ''} ${r.status === 'complete' ? 'complete' : ''}`}
            onClick={() => setActiveRound(r)}>
            {r.name}
          </button>
        ))}
      </div>
      {activeRound && (
        <div className="pc-round-header">
          <span className="pc-round-title">{activeRound.name}</span>
          <span className="pc-round-meta">{games.length} games</span>
          <span className={`pc-status-badge pc-status-${activeRound.status}`}>
            {activeRound.status.replace('_', ' ')}
          </span>
          {games.some(g => g.status === 'final') && (
            <button className="pc-filter-btn" onClick={() => setHidePast(h => !h)}>
              {hidePast ? '🗓 Show all' : '⏳ Hide past'}
            </button>
          )}
        </div>
      )}
      <RoundScore games={games} />
      {activeRound?.status === 'complete' && <RoundLeaderboard round={activeRound} />}
      <ChickenPickMessage games={games} />
      <PicksToast games={games} />
      {loadingGames
        ? <div className="pc-empty">Loading games...</div>
        : Object.entries(byRegion).map(([region, regionGames]) => (
            <div key={region} className="pc-region-group">
              <div className="pc-region-label">{region}</div>
              <div className="pc-games-grid">
                {regionGames.map(g => <GameCard key={g.id} game={g} onPick={handlePick} />)}
              </div>
            </div>
          ))
      }
    </>
  );
}

function ChickenPickMessage({ games }) {
  const availableGames = games.filter(g => g.market?.isLocked && g.status === 'scheduled');
  const available = availableGames.length;
  const picked    = availableGames.filter(g => g.pick?.userOutcome).length;
  const remaining = available - picked;

  if (available === 0) return null;

  let msg, cls;
  if (picked === 0) {
    msg = "🐔  I've made my picks. Have you? Get in there!";
    cls = 'nudge';
  } else if (remaining > 0) {
    msg = `🐔  You've got ${remaining} pick${remaining !== 1 ? 's' : ''} left — don't let me win by default.`;
    cls = 'nudge';
  } else {
    msg = "🐔  All picks locked in. May the best picker win. (It'll be me.)";
    cls = 'ready';
  }

  return <div className={`pc-chicken-msg pc-chicken-msg-${cls}`}>{msg}</div>;
}

function RoundLeaderboard({ round }) {
  const [data, setData] = useState(null);

  useEffect(() => {
    if (!round || round.status !== 'complete') return;
    apiFetch(`/tournament/leaderboard?roundId=${round.id}`)
      .then(setData)
      .catch(() => {});
  }, [round?.id, round?.status]);

  if (!data) return null;

  const chicken = data.chicken;
  const all = [...data.players, chicken]
    .filter(e => e.picked > 0)
    .sort((a, b) => b.wins !== a.wins ? b.wins - a.wins : a.losses - b.losses);

  if (all.length === 0) return null;

  const record = e => `${e.wins}–${e.losses}`;
  const pct = e => e.picked > 0 ? `${Math.round((e.wins / e.picked) * 100)}%` : '—';

  return (
    <div className="pc-round-lb">
      <div className="pc-round-lb-title">Round Leaderboard</div>
      <table className="pc-lb-table">
        <thead>
          <tr>
            <th className="pc-lb-th pc-lb-th-rank">#</th>
            <th className="pc-lb-th">Player</th>
            <th className="pc-lb-th pc-lb-th-num">Record</th>
            <th className="pc-lb-th pc-lb-th-num">ATS %</th>
          </tr>
        </thead>
        <tbody>
          {all.map((e, i) => (
            <tr key={e.userId} className={`pc-lb-tr ${e.isChicken ? 'pc-lb-chicken' : ''}`}>
              <td className={`pc-lb-td pc-lb-rank ${i === 0 ? 'first' : ''}`}>{i + 1}</td>
              <td className="pc-lb-td pc-lb-name">
                {e.isChicken ? <><span className="pc-lb-chicken-icon">🐔</span>{e.username}</> : e.username}
              </td>
              <td className="pc-lb-td pc-lb-num">{record(e)}</td>
              <td className="pc-lb-td pc-lb-num">{pct(e)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RoundScore({ games }) {
  let youWins = 0, youLosses = 0, chickenWins = 0, chickenLosses = 0;
  games.forEach(g => {
    const result = g.pick?.result;
    if (!result) return;
    if (result === 'user_wins')    { youWins++;     chickenLosses++; }
    if (result === 'chicken_wins') { youLosses++;   chickenWins++;   }
    if (result === 'tie_win')      { youWins++;     chickenWins++;   }
    if (result === 'tie_loss')     { youLosses++;   chickenLosses++; }
  });

  const scored = youWins + youLosses;
  if (scored === 0) return null;

  return (
    <div className="pc-round-score">
      <span className="pc-round-score-label">You</span>
      <span className="pc-round-score-num">{youWins}–{youLosses}</span>
      <div className="pc-round-score-divider" />
      <span className="pc-round-score-label">🐔 Chicken</span>
      <span className="pc-round-score-num">{chickenWins}–{chickenLosses}</span>
    </div>
  );
}

function PicksToast({ games }) {
  const [visible, setVisible] = useState(true);
  const timerRef = useRef(null);

  const availableGames = games.filter(g => g.market?.isLocked && g.status === 'scheduled');
  const available = availableGames.length;
  const picked    = availableGames.filter(g => g.pick?.userOutcome).length;

  // Reset visibility and restart timer whenever picked count changes
  useEffect(() => {
    if (available === 0) return;
    setVisible(true);
    clearTimeout(timerRef.current);
    timerRef.current = setTimeout(() => setVisible(false), 20000);
    return () => clearTimeout(timerRef.current);
  }, [picked, available]);

  if (available === 0 || !visible) return null;

  const remaining = available - picked;
  const allDone = remaining === 0;

  return (
    <div className="pc-picks-toast" onClick={() => { clearTimeout(timerRef.current); setVisible(false); }}>
      <span className="pc-picks-toast-num">{picked}<span className="pc-picks-toast-denom">/{available}</span></span>
      <span className="pc-picks-toast-label">
        {allDone ? 'All picks in 🎉' : `${remaining} pick${remaining !== 1 ? 's' : ''} remaining`}
      </span>
    </div>
  );
}


function GameCard({ game, onPick }) {
  const locked = game.status !== 'scheduled';
  const pick = game.pick;
  const userOutcomeId = pick?.userOutcome?.id;
  const result = pick?.result;

  const teamClass = name => !game.winner ? '' : game.winner === name ? 'winner' : 'loser';

  const commenceDate = game.commenceTime
    ? new Date(game.commenceTime).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
    : '';

  const resultLabel = {
    user_wins:    '🏆 You beat the chicken!',
    chicken_wins: '🐔 Chicken wins this one',
    tie_win:      '🤝 You both covered',
    tie_loss:     '😬 You both missed',
  }[result];

  const hasMarket  = !!game.market;
  const isLocked   = game.market?.isLocked;
  const outcomes   = game.market?.outcomes || [];
  const chickenPick = game.chickenPick; // set once market is locked

  // Determine pick area state
  let pickState = 'no_odds';        // no market at all
  if (hasMarket && !isLocked) pickState = 'odds_pending_lock'; // market exists but chicken hasn't picked
  if (hasMarket && isLocked)  pickState = 'open';              // chicken has picked, user can pick
  if (locked)                 pickState = 'game_locked';        // game in progress or final

  return (
    <div className="pc-game-card">
      <div className="pc-game-region-bar">{game.region} · {commenceDate}</div>

      <div className="pc-game-teams">
        {[['away', game.awayTeam, game.awayTeamSeed], ['home', game.homeTeam, game.homeTeamSeed]].map(([side, name, seed]) => (
          <div key={side} className="pc-team-row">
            <span className="pc-seed">{seed}</span>
            <span className={`pc-team-name ${teamClass(name)}`}>{name}</span>
          </div>
        ))}
      </div>

      <hr className="pc-game-divider" />
      <div className="pc-pick-area">

        {pickState === 'no_odds' && (
          <div className="pc-odds-pending">Odds pending</div>
        )}

        {pickState === 'odds_pending_lock' && (
          <div className="pc-odds-pending">🐔 Chicken picks at 10am game day</div>
        )}

        {(pickState === 'open' || pickState === 'game_locked') && (
          <>
            <div className="pc-pick-label">
              {pickState === 'game_locked' && pick ? 'Your pick' :
               pickState === 'game_locked'          ? 'Game in progress' :
               'Pick against the spread'}
            </div>

            {(pickState === 'open' || pick) && (
              <div className="pc-pick-btns">
                {(outcomes.length ? outcomes : [pick?.userOutcome].filter(Boolean)).map(outcome => {
                  const isUserPick = userOutcomeId === outcome.id;
                  const isChickenPick = chickenPick?.id === outcome.id;
                  const btnLocked = pickState === 'game_locked';
                  const priceStr = outcome.price > 0 ? `+${outcome.price}` : `${outcome.price}`;
                  return (
                    <button key={outcome.id}
                      className={`pc-pick-btn ${isUserPick ? 'selected' : ''} ${btnLocked && !isUserPick ? 'locked' : ''}`}
                      onClick={() => !btnLocked && onPick(game.id, outcome.id)}
                      disabled={btnLocked && !isUserPick}>
                      <span className="pc-outcome-label">{outcome.label}</span>
                      <span className="pc-outcome-price">{priceStr}</span>
                      {isChickenPick && pick && <span className="pc-chicken-badge">🐔</span>}
                    </button>
                  );
                })}
              </div>
            )}

            {/* After user picks: show chicken's pick */}
            {pick && !result && chickenPick && (
              <div className="pc-result-row">
                <span className="pc-pending">⏳ Awaiting result</span>
                <span className="pc-chicken-pick">🐔 {chickenPick.label}</span>
              </div>
            )}

            {result && (
              <div className="pc-result-row">
                <span className={`pc-result pc-result-${result}`}>{resultLabel}</span>
                <span className="pc-chicken-pick">🐔 {chickenPick?.label ?? pick?.chickenOutcome?.label}</span>
              </div>
            )}
          </>
        )}

      </div>
    </div>
  );
}

function MyPicksTab({ user }) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiFetch(`/tournament/picks/${user.id}`)
      .then(d => { setData(d); setLoading(false); })
      .catch(() => setLoading(false));
  }, [user.id]);

  if (loading) return <div className="pc-empty">Loading...</div>;
  if (!data?.length) return <div className="pc-empty">No picks yet — head to the bracket to start picking!</div>;

  let wins = 0, scored = 0;
  data.forEach(r => r.picks.forEach(p => {
    if (p.result) { scored++; if (p.result === 'user_wins') wins++; }
  }));

  return (
    <div className="pc-my-picks">
      {scored > 0 && (
        <div className="pc-summary-bar">
          <div className="pc-summary-stat">
            <div className="pc-summary-num">{wins}</div>
            <div className="pc-summary-label">Chicken beaten</div>
          </div>
          <div className="pc-summary-divider" />
          <div className="pc-summary-stat">
            <div className="pc-summary-num">{scored}</div>
            <div className="pc-summary-label">Scored picks</div>
          </div>
          <div className="pc-summary-divider" />
          <div className="pc-summary-stat">
            <div className="pc-summary-num">{Math.round((wins / scored) * 100)}%</div>
            <div className="pc-summary-label">Win rate</div>
          </div>
        </div>
      )}
      {data.map(r => (
        <div key={r.round.id} className="pc-picks-round">
          <div className="pc-picks-round-title">{r.round.name}</div>
          {r.picks.map(p => (
            <div key={p.gameId} className="pc-pick-row">
              <div className="pc-pick-matchup">
                <span className="pc-pick-teams">{p.awayTeam} vs {p.homeTeam}</span>
                <span className="pc-pick-detail">
                  You: <strong>{p.userOutcome?.label || '—'}</strong>
                  {' · '}🐔 {p.chickenOutcome?.label || '—'}
                </span>
              </div>
              <div className={`pc-pick-result pc-result-${p.result || 'pending'}`}>
                {{ user_wins: '🏆 Beat the chicken', chicken_wins: '🐔 Chicken wins', tie_win: '🤝 Both covered', tie_loss: '😬 Both missed' }[p.result] || '⏳ Pending'}
              </div>
            </div>
          ))}
        </div>
      ))}
    </div>
  );
}

function ChickenTrashTalk({ all }) {
  const [talk, setTalk] = useState(null);

  useEffect(() => {
    if (!all || all.length < 2) return;
    const chicken = all.find(e => e.isChicken);
    if (!chicken) return;
    const chickenRank = all.findIndex(e => e.isChicken) + 1;
    const second = all[1];
    const last = all[all.length - 1];

    const prompt = `You are The Chicken, a smack-talking mascot for a March Madness picks game where players try to beat a chicken picking randomly.

Standings:
${all.map((e, i) => `${i+1}. ${e.isChicken ? '🐔 The Chicken' : e.username} — ${e.wins}-${e.losses}`).join('\n')}

The Chicken is ranked #${chickenRank}.

Write exactly 3 lines of trash talk, one per line:
1. General brag from the chicken (cocky, funny, 1 sentence)
2. Specific taunt at ${second?.username} (2nd place, ${second?.wins}-${second?.losses})
3. Roast of ${last?.username} (last place, ${last?.wins}-${last?.losses})

Under 100 chars each. Funny not mean. No labels, no quotes, just the 3 lines.`;

    fetch(`${API}/chicken/talk`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ standings: all }),
    })
      .then(r => r.json())
      .then(d => { if (d.lines?.length) setTalk(d.lines); })
      .catch(() => {});
  }, [JSON.stringify(all?.map(e => e.wins + e.losses))]);

  if (!talk) return (
    <div className="pc-trash-talk pc-trash-talk-loading">
      <div className="pc-trash-talk-icon">🐔</div>
      <div className="pc-trash-line">...</div>
    </div>
  );

  return (
    <div className="pc-trash-talk">
      <div className="pc-trash-talk-icon">🐔</div>
      <div className="pc-trash-talk-lines">
        {talk.map((line, i) => (
          <div key={i} className={`pc-trash-line pc-trash-line-${i}`}>{line}</div>
        ))}
      </div>
    </div>
  );
}

function LeaderboardTab() {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    apiFetch('/tournament/leaderboard')
      .then(d => { setData(d); setLoading(false); })
      .catch(() => setLoading(false));
  }, []);

  if (loading) return <div className="pc-empty">Loading...</div>;
  if (!data) return <div className="pc-empty">Failed to load leaderboard</div>;

  // Merge players + chicken into one sorted list
  const chicken = data.chicken;
  const all = [...data.players, chicken].sort((a, b) =>
    b.wins !== a.wins ? b.wins - a.wins : a.losses - b.losses
  );

  const record = e => `${e.wins}–${e.losses}`;
  const pct = e => e.picked > 0 ? `${Math.round((e.wins / e.picked) * 100)}%` : '—';

  return (
    <div className="pc-leaderboard">
      <ChickenTrashTalk all={all} />
      <div className="pc-lb-section">
        <table className="pc-lb-table">
          <thead>
            <tr>
              <th className="pc-lb-th pc-lb-th-rank">#</th>
              <th className="pc-lb-th">Player</th>
              <th className="pc-lb-th pc-lb-th-num">Record</th>
              <th className="pc-lb-th pc-lb-th-num">ATS %</th>
              <th className="pc-lb-th pc-lb-th-num">Picked</th>
            </tr>
          </thead>
          <tbody>
            {all.map((e, i) => (
              <tr key={e.userId} className={`pc-lb-tr ${e.isChicken ? 'pc-lb-chicken' : ''}`}>
                <td className={`pc-lb-td pc-lb-rank ${i === 0 ? 'first' : ''}`}>{i + 1}</td>
                <td className="pc-lb-td pc-lb-name">
                  {e.isChicken ? <><span className="pc-lb-chicken-icon">🐔</span> {e.username}</> : e.username}
                </td>
                <td className="pc-lb-td pc-lb-num">{record(e)}</td>
                <td className="pc-lb-td pc-lb-num">{pct(e)}</td>
                <td className="pc-lb-td pc-lb-num">{e.picked}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
