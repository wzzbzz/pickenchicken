import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { requestLogin, loginWithPassword } from '../api/auth.js';

export default function WelcomePage() {
  const navigate = useNavigate();
  const [mode, setMode] = useState('welcome'); // welcome | magic | password
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [sent, setSent] = useState(false);
  const [error, setError] = useState(null);
  const [loading, setLoading] = useState(false);

  async function handleMagicLink(e) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await requestLogin(email);
      setSent(true);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handlePasswordLogin(e) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await loginWithPassword(email, password);
      navigate('/leagues');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div style={s.page}>
      <h1 style={s.title}>PickenChicken.com</h1>

      <div style={s.authPanel}>
        {sent ? (
          <p style={s.sentMsg}>Check your email — a login link is on its way to <strong>{email}</strong>.</p>
        ) : mode === 'welcome' ? (
          <>
            <button style={s.primaryBtn} onClick={() => setMode('magic')}>
              Log in / Sign up
            </button>
          </>
        ) : mode === 'magic' ? (
          <form onSubmit={handleMagicLink} style={s.form}>
            <label style={s.label}>Email</label>
            <input
              style={s.input}
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              placeholder="you@example.com"
              required
              autoFocus
            />
            {error && <p style={s.error}>{error}</p>}
            <button style={s.primaryBtn} type="submit" disabled={loading}>
              {loading ? 'Sending…' : 'Send magic link'}
            </button>
            <button style={s.linkBtn} type="button" onClick={() => setMode('password')}>
              Log in with password instead
            </button>
            <button style={s.linkBtn} type="button" onClick={() => setMode('welcome')}>
              ← Back
            </button>
          </form>
        ) : (
          <form onSubmit={handlePasswordLogin} style={s.form}>
            <label style={s.label}>Email</label>
            <input
              style={s.input}
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              placeholder="you@example.com"
              required
              autoFocus
            />
            <label style={s.label}>Password</label>
            <input
              style={s.input}
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              placeholder="••••••••"
              required
            />
            {error && <p style={s.error}>{error}</p>}
            <button style={s.primaryBtn} type="submit" disabled={loading}>
              {loading ? 'Logging in…' : 'Log in'}
            </button>
            <button style={s.linkBtn} type="button" onClick={() => setMode('magic')}>
              Send a magic link instead
            </button>
            <button style={s.linkBtn} type="button" onClick={() => setMode('welcome')}>
              ← Back
            </button>
          </form>
        )}
      </div>
    </div>
  );
}

const s = {
  page: {
    minHeight: '100vh',
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '2rem 1rem',
    fontFamily: 'system-ui, sans-serif',
    background: '#fafafa',
    color: '#1a1a1a',
  },
  title: {
    fontFamily: '"TRS-80", monospace',
    fontSize: '2rem',
    fontWeight: 'normal',
    margin: '0 0 1.5rem',
    textAlign: 'center',
    letterSpacing: '0.05em',
    color: '#1a1a1a',
  },
  authPanel: {
    width: '100%',
    maxWidth: 360,
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'stretch',
    gap: '0.75rem',
  },
  form: {
    display: 'flex',
    flexDirection: 'column',
    gap: '0.6rem',
  },
  label: {
    fontSize: '0.8rem',
    color: '#666',
  },
  input: {
    background: '#fff',
    border: '1px solid #ccc',
    borderRadius: 6,
    color: '#1a1a1a',
    padding: '0.6rem 0.8rem',
    fontSize: '1rem',
    outline: 'none',
  },
  primaryBtn: {
    background: '#caa000',
    color: '#000',
    border: 'none',
    borderRadius: 6,
    padding: '0.7rem',
    fontWeight: 700,
    fontSize: '1rem',
    cursor: 'pointer',
    marginTop: '0.25rem',
  },
  linkBtn: {
    background: 'none',
    border: 'none',
    color: '#777',
    fontSize: '0.85rem',
    cursor: 'pointer',
    padding: '0.25rem 0',
    textAlign: 'center',
  },
  sentMsg: {
    color: '#444',
    textAlign: 'center',
    fontSize: '0.95rem',
  },
  error: {
    color: '#c33',
    fontSize: '0.85rem',
    margin: 0,
  },
};
