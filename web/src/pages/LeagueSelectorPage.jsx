import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getCompetitions } from '../api/competitions.js';
import { logout, getStoredUser } from '../api/auth.js';

const SPORT_DISPLAY = {
  baseball_mlb: { name: 'MLB', icon: '⚾' },
  basketball_nba: { name: 'NBA', icon: '🏀' },
  basketball_wnba: { name: 'WNBA', icon: '🏀' },
  icehockey_nhl: { name: 'NHL', icon: '🏒' },
  americanfootball_nfl: { name: 'NFL', icon: '🏈' },
};

function sportDisplay(sportKey) {
  return SPORT_DISPLAY[sportKey] ?? { name: sportKey.toUpperCase(), icon: '🏆' };
}

export default function LeagueSelectorPage() {
  const [leagues, setLeagues] = useState([]);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();
  const user = getStoredUser();

  useEffect(() => {
    getCompetitions()
      .then(setLeagues)
      .finally(() => setLoading(false));
  }, []);

  async function handleLogout() {
    await logout();
    navigate('/');
  }

  return (
    <div style={styles.page}>
      <header style={styles.header}>
        <h1 style={styles.title}>Beat The Chicken</h1>
        <div style={styles.headerRight}>
          <span style={styles.username}>{user?.username ?? user?.email}</span>
          <button style={styles.settingsBtn} onClick={() => navigate('/settings')}>Settings</button>
          <button style={styles.logoutBtn} onClick={handleLogout}>Log out</button>
        </div>
      </header>

      <p style={styles.subtitle}>Select a league to see today's slate.</p>

      {loading ? (
        <p style={styles.muted}>Loading…</p>
      ) : leagues.length === 0 ? (
        <p style={styles.muted}>No active leagues right now.</p>
      ) : (
        <div style={styles.grid}>
          {leagues.map(league => {
            const display = sportDisplay(league.sport_key);
            return (
              <button
                key={league.id}
                style={styles.card}
                onClick={() => navigate(`/competitions/${league.id}`)}
              >
                <span style={styles.cardIcon}>{display.icon}</span>
                <span style={styles.cardName}>{display.name}</span>
              </button>
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
  header: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: '0.5rem',
  },
  title: {
    margin: 0,
    fontSize: '1.75rem',
    fontWeight: 700,
    color: '#1a1a1a',
  },
  headerRight: {
    display: 'flex',
    alignItems: 'center',
    gap: '1rem',
  },
  username: {
    fontSize: '0.875rem',
    color: '#666',
  },
  settingsBtn: {
    background: 'none',
    border: '1px solid #ccc',
    color: '#555',
    padding: '0.25rem 0.75rem',
    borderRadius: 4,
    cursor: 'pointer',
    fontSize: '0.8rem',
  },
  logoutBtn: {
    background: 'none',
    border: '1px solid #ccc',
    color: '#555',
    padding: '0.25rem 0.75rem',
    borderRadius: 4,
    cursor: 'pointer',
    fontSize: '0.8rem',
  },
  subtitle: {
    color: '#666',
    marginBottom: '2rem',
  },
  muted: {
    color: '#777',
  },
  grid: {
    display: 'flex',
    gap: '0.75rem',
    flexWrap: 'wrap',
  },
  card: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    gap: '0.5rem',
    background: '#fff',
    border: '1px solid #e0e0e0',
    borderRadius: 8,
    padding: '1.25rem 2rem',
    cursor: 'pointer',
    color: '#1a1a1a',
    boxShadow: '0 1px 2px rgba(0,0,0,0.04)',
  },
  cardIcon: {
    fontSize: '2.5rem',
  },
  cardName: {
    fontSize: '1.1rem',
    fontWeight: 600,
  },
};
