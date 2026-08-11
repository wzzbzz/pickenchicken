import { useParams, useNavigate } from 'react-router-dom';
import { useEffect, useState } from 'react';
import {
  getCompetition,
  getCurrentSegment,
  getSegmentGames,
  submitPick,
  lockPick,
  unlockPick,
} from '../api/competitions.js';

function formatSpread(spread, side) {
  if (spread == null) return '';
  const value = side === 'home' ? Number(spread) : -Number(spread);
  const sign = value > 0 ? '+' : '';
  return ` ${sign}${value}`;
}

export default function CompetitionPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const [competition, setCompetition] = useState(null);
  const [games, setGames] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [busyGameId, setBusyGameId] = useState(null);

  async function load() {
    setLoading(true);
    setError(null);
    try {
      const comp = await getCompetition(id);
      setCompetition(comp);

      const segment = await getCurrentSegment(id).catch(() => null);
      if (segment) {
        const gamesForSegment = await getSegmentGames(segment.id);
        setGames(gamesForSegment);
      } else {
        setGames([]);
      }
    } catch (err) {
      if (err.status === 404) {
        navigate('/leagues');
        return;
      }
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
  }, [id]);

  async function handlePick(game, side) {
    setBusyGameId(game.id);
    try {
      const pick = await submitPick(game.id, side);
      patchGame(game.id, { my_pick: pick });
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyGameId(null);
    }
  }

  async function handleLock(game) {
    setBusyGameId(game.id);
    try {
      const pick = await lockPick(game.id);
      patchGame(game.id, { my_pick: pick });
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyGameId(null);
    }
  }

  async function handleUnlock(game) {
    setBusyGameId(game.id);
    try {
      const pick = await unlockPick(game.id);
      patchGame(game.id, { my_pick: pick });
    } catch (err) {
      setError(err.message);
    } finally {
      setBusyGameId(null);
    }
  }

  function patchGame(gameId, patch) {
    setGames(gs => gs.map(g => (g.id === gameId ? { ...g, ...patch } : g)));
  }

  if (loading) return <p style={{ padding: '2rem', fontFamily: 'system-ui' }}>Loading…</p>;
  if (!competition) return null;

  return (
    <div style={styles.page}>
      <button onClick={() => navigate('/leagues')} style={styles.backBtn}>
        ← Back to leagues
      </button>
      <h1 style={styles.title}>{competition.name}</h1>

      {error && <p style={styles.error}>{error}</p>}

      {games.length === 0 ? (
        <p style={styles.muted}>No games on today's slate.</p>
      ) : (
        <div style={styles.list}>
          {games.map(game => {
            const myPick = game.my_pick;
            const busy = busyGameId === game.id;
            const canEdit = !game.locked && !(myPick && myPick.locked);

            return (
              <div key={game.id} style={styles.card}>
                <div style={styles.matchup}>
                  <span>{game.away_team}{formatSpread(game.spread, 'away')}</span>
                  <span style={styles.at}>@</span>
                  <span>{game.home_team}{formatSpread(game.spread, 'home')}</span>
                </div>

                {game.locked && game.chicken_pick && (
                  <p style={styles.chickenLine}>
                    🐔 The Chicken picked <strong>{game.chicken_pick === 'home' ? game.home_team : game.away_team}</strong>
                    {game.chicken_signal_strength != null && ` (${game.chicken_bot_id}, ${game.chicken_signal_strength}%)`}
                  </p>
                )}

                {game.locked && !myPick && (
                  <p style={styles.muted}>Picks closed — you didn't pick this one.</p>
                )}

                {!game.locked && (
                  <div style={styles.pickRow}>
                    <button
                      style={{ ...styles.pickBtn, ...(myPick?.pick === 'away' ? styles.pickBtnActive : {}) }}
                      disabled={busy || !canEdit}
                      onClick={() => handlePick(game, 'away')}
                    >
                      {game.away_team}
                    </button>
                    <button
                      style={{ ...styles.pickBtn, ...(myPick?.pick === 'home' ? styles.pickBtnActive : {}) }}
                      disabled={busy || !canEdit}
                      onClick={() => handlePick(game, 'home')}
                    >
                      {game.home_team}
                    </button>
                  </div>
                )}

                {!game.locked && myPick && (
                  myPick.locked ? (
                    <button style={styles.unlockBtn} disabled={busy} onClick={() => handleUnlock(game)}>
                      Unlock pick
                    </button>
                  ) : (
                    <button style={styles.lockBtn} disabled={busy} onClick={() => handleLock(game)}>
                      Lock pick
                    </button>
                  )
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

const styles = {
  page: {
    maxWidth: 640,
    margin: '0 auto',
    padding: '2rem 1rem',
    fontFamily: 'system-ui, sans-serif',
    background: '#fafafa',
    minHeight: '100vh',
    color: '#1a1a1a',
  },
  backBtn: { background: 'none', border: 'none', color: '#777', cursor: 'pointer', marginBottom: '1rem', padding: 0 },
  title: { margin: '0 0 1.5rem', color: '#1a1a1a' },
  muted: { color: '#777' },
  error: { color: '#c33' },
  list: { display: 'flex', flexDirection: 'column', gap: '0.75rem' },
  card: {
    background: '#fff',
    border: '1px solid #e0e0e0',
    borderRadius: 8,
    padding: '1rem 1.25rem',
    boxShadow: '0 1px 2px rgba(0,0,0,0.04)',
  },
  matchup: { display: 'flex', alignItems: 'center', gap: '0.5rem', fontWeight: 600, marginBottom: '0.5rem', color: '#1a1a1a' },
  at: { color: '#999', fontWeight: 400 },
  chickenLine: { color: '#946f00', fontSize: '0.85rem', margin: '0 0 0.5rem' },
  pickRow: { display: 'flex', gap: '0.5rem', marginBottom: '0.5rem' },
  pickBtn: {
    flex: 1,
    background: '#f5f5f5',
    border: '1px solid #ccc',
    color: '#1a1a1a',
    borderRadius: 6,
    padding: '0.5rem',
    cursor: 'pointer',
  },
  pickBtnActive: {
    background: '#caa000',
    color: '#000',
    borderColor: '#caa000',
    fontWeight: 700,
  },
  lockBtn: {
    background: '#2a8a4a',
    border: 'none',
    color: '#fff',
    borderRadius: 6,
    padding: '0.4rem 0.9rem',
    cursor: 'pointer',
    fontSize: '0.85rem',
  },
  unlockBtn: {
    background: 'none',
    border: '1px solid #ccc',
    color: '#555',
    borderRadius: 6,
    padding: '0.4rem 0.9rem',
    cursor: 'pointer',
    fontSize: '0.85rem',
  },
};
