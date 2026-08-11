import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { setPassword } from '../api/auth.js';

export default function SetPasswordPage() {
  const navigate = useNavigate();
  const [pw, setPw] = useState('');
  const [confirm, setConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  async function handleSubmit(e) {
    e.preventDefault();
    if (pw !== confirm) {
      setError('Passwords do not match.');
      return;
    }
    setLoading(true);
    setError(null);
    try {
      await setPassword(pw);
      localStorage.setItem('userHasPassword', '1');
      navigate('/leagues');
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div style={s.page}>
      <div style={s.card}>
        <div style={s.chicken}>🐔</div>
        <h2 style={s.heading}>Create a password</h2>
        <p style={s.sub}>
          Set a password so you can log in without a magic link next time.
        </p>
        <form onSubmit={handleSubmit} style={s.form}>
          <input
            style={s.input}
            type="password"
            value={pw}
            onChange={e => setPw(e.target.value)}
            placeholder="Password (8+ characters)"
            required
            minLength={8}
            autoFocus
          />
          <input
            style={s.input}
            type="password"
            value={confirm}
            onChange={e => setConfirm(e.target.value)}
            placeholder="Confirm password"
            required
          />
          {error && <p style={s.error}>{error}</p>}
          <button style={s.btn} type="submit" disabled={loading}>
            {loading ? 'Saving…' : 'Set password'}
          </button>
          <button style={s.skipBtn} type="button" onClick={() => navigate('/leagues')}>
            Skip for now
          </button>
        </form>
      </div>
    </div>
  );
}

const s = {
  page: {
    minHeight: '100vh',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    background: '#fafafa',
    fontFamily: 'system-ui, sans-serif',
    padding: '1rem',
  },
  card: {
    background: '#fff',
    border: '1px solid #e0e0e0',
    borderRadius: 12,
    padding: '2rem',
    width: '100%',
    maxWidth: 380,
    textAlign: 'center',
  },
  chicken: { fontSize: '2.5rem', marginBottom: '0.75rem' },
  heading: { margin: '0 0 0.5rem', fontSize: '1.3rem', fontWeight: 700, color: '#1a1a1a' },
  sub: { color: '#666', fontSize: '0.9rem', margin: '0 0 1.5rem' },
  form: { display: 'flex', flexDirection: 'column', gap: '0.75rem' },
  input: {
    background: '#fff',
    border: '1px solid #ccc',
    borderRadius: 6,
    color: '#1a1a1a',
    padding: '0.6rem 0.8rem',
    fontSize: '1rem',
    outline: 'none',
    textAlign: 'left',
  },
  btn: {
    background: '#caa000',
    color: '#000',
    border: 'none',
    borderRadius: 6,
    padding: '0.7rem',
    fontWeight: 700,
    fontSize: '1rem',
    cursor: 'pointer',
  },
  skipBtn: {
    background: 'none',
    border: 'none',
    color: '#777',
    fontSize: '0.85rem',
    cursor: 'pointer',
    padding: '0.25rem',
  },
  error: { color: '#c33', fontSize: '0.85rem', margin: 0 },
};
