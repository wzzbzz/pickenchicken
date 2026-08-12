import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { getSettings, updateSettings } from '../api/settings.js';

const PICK_STYLE_PRESETS = ['Favorites', 'Underdogs', 'Balanced', 'Analytics', 'Gut Feel'];

export default function SettingsPage() {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [saved, setSaved] = useState(false);

  const [avatarUrl, setAvatarUrl] = useState('');
  const [personalStatement, setPersonalStatement] = useState('');
  const [pickStyleChoice, setPickStyleChoice] = useState('');
  const [pickStyleCustom, setPickStyleCustom] = useState('');
  const [accountType, setAccountType] = useState('human');
  const [botId, setBotId] = useState('');
  const [activeSubscription, setActiveSubscription] = useState(null);

  useEffect(() => {
    (async () => {
      try {
        const data = await getSettings();
        setAvatarUrl(data.avatar_url ?? '');
        setPersonalStatement(data.personal_statement ?? '');
        setAccountType(data.account_type ?? 'human');
        if (data.pick_style && PICK_STYLE_PRESETS.includes(data.pick_style)) {
          setPickStyleChoice(data.pick_style);
        } else if (data.pick_style) {
          setPickStyleChoice('other');
          setPickStyleCustom(data.pick_style);
        }
        const active = (data.bot_subscriptions ?? []).find(s => s.is_active) ?? null;
        setActiveSubscription(active);
        if (active) setBotId(active.bot_id);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    })();
  }, []);

  const needsBotId = accountType === 'bot' && !activeSubscription && botId.trim() === '';

  async function handleSubmit(e) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    setSaved(false);
    try {
      const pickStyle = pickStyleChoice === 'other' ? pickStyleCustom : pickStyleChoice;
      const payload = {
        avatar_url: avatarUrl,
        personal_statement: personalStatement,
        pick_style: pickStyle,
        account_type: accountType,
      };
      if (botId.trim() !== '') payload.bot_id = botId.trim();

      const data = await updateSettings(payload);
      const active = (data.bot_subscriptions ?? []).find(s => s.is_active) ?? null;
      setActiveSubscription(active);
      setSaved(true);
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return (
      <div style={s.page}>
        <div style={s.card}>Loading…</div>
      </div>
    );
  }

  return (
    <div style={s.page}>
      <div style={s.card}>
        <button style={s.backBtn} type="button" onClick={() => navigate('/leagues')}>
          ← Back to leagues
        </button>
        <div style={s.chicken}>🐔</div>
        <h2 style={s.heading}>Settings</h2>

        <form onSubmit={handleSubmit} style={s.form}>
          {avatarUrl && <img src={avatarUrl} alt="Avatar preview" style={s.avatarPreview} />}
          <label style={s.label}>Avatar URL</label>
          <input
            style={s.input}
            type="url"
            value={avatarUrl}
            onChange={e => setAvatarUrl(e.target.value)}
            placeholder="https://example.com/avatar.png"
          />

          <label style={s.label}>Personal statement</label>
          <textarea
            style={s.textarea}
            value={personalStatement}
            onChange={e => setPersonalStatement(e.target.value)}
            placeholder="Tell other players about your picking philosophy"
            rows={3}
          />

          <label style={s.label}>Pick style</label>
          <select
            style={s.input}
            value={pickStyleChoice}
            onChange={e => setPickStyleChoice(e.target.value)}
          >
            <option value="">— None —</option>
            {PICK_STYLE_PRESETS.map(p => (
              <option key={p} value={p}>{p}</option>
            ))}
            <option value="other">Other…</option>
          </select>
          {pickStyleChoice === 'other' && (
            <input
              style={s.input}
              type="text"
              value={pickStyleCustom}
              onChange={e => setPickStyleCustom(e.target.value)}
              placeholder="Describe your pick style"
            />
          )}

          <label style={s.label}>Account type</label>
          <div style={s.radioRow}>
            <label style={s.radioLabel}>
              <input
                type="radio"
                name="accountType"
                value="human"
                checked={accountType === 'human'}
                onChange={() => setAccountType('human')}
              />
              Human
            </label>
            <label style={s.radioLabel}>
              <input
                type="radio"
                name="accountType"
                value="bot"
                checked={accountType === 'bot'}
                onChange={() => setAccountType('bot')}
              />
              Bot
            </label>
          </div>

          {accountType === 'bot' && (
            <>
              <label style={s.label}>Bot ID {activeSubscription ? '(currently subscribed)' : '(required)'}</label>
              <input
                style={s.input}
                type="text"
                value={botId}
                onChange={e => setBotId(e.target.value)}
                placeholder="e.g. ats_consensus_quad"
                required={!activeSubscription}
              />
            </>
          )}

          {activeSubscription && (
            <p style={s.sub}>Subscribed to: {activeSubscription.bot_id}</p>
          )}

          {error && <p style={s.error}>{error}</p>}
          {saved && !error && <p style={s.success}>Saved.</p>}

          <button style={s.btn} type="submit" disabled={saving || needsBotId}>
            {saving ? 'Saving…' : 'Save settings'}
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
    maxWidth: 420,
    textAlign: 'center',
  },
  backBtn: {
    background: 'none',
    border: 'none',
    color: '#777',
    fontSize: '0.85rem',
    cursor: 'pointer',
    padding: 0,
    marginBottom: '0.75rem',
    alignSelf: 'flex-start',
  },
  chicken: { fontSize: '2.5rem', marginBottom: '0.75rem' },
  heading: { margin: '0 0 1rem', fontSize: '1.3rem', fontWeight: 700, color: '#1a1a1a' },
  sub: { color: '#666', fontSize: '0.85rem', margin: '0.25rem 0' },
  form: { display: 'flex', flexDirection: 'column', gap: '0.5rem', textAlign: 'left' },
  label: { fontSize: '0.8rem', fontWeight: 600, color: '#444', marginTop: '0.5rem' },
  input: {
    background: '#fff',
    border: '1px solid #ccc',
    borderRadius: 6,
    color: '#1a1a1a',
    padding: '0.6rem 0.8rem',
    fontSize: '1rem',
    outline: 'none',
    textAlign: 'left',
    width: '100%',
    boxSizing: 'border-box',
  },
  textarea: {
    background: '#fff',
    border: '1px solid #ccc',
    borderRadius: 6,
    color: '#1a1a1a',
    padding: '0.6rem 0.8rem',
    fontSize: '1rem',
    outline: 'none',
    textAlign: 'left',
    width: '100%',
    boxSizing: 'border-box',
    fontFamily: 'inherit',
    resize: 'vertical',
  },
  radioRow: { display: 'flex', gap: '1.5rem' },
  radioLabel: { display: 'flex', alignItems: 'center', gap: '0.4rem', fontSize: '0.95rem' },
  avatarPreview: {
    width: 64,
    height: 64,
    borderRadius: '50%',
    objectFit: 'cover',
    alignSelf: 'center',
    border: '1px solid #ccc',
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
    marginTop: '0.75rem',
  },
  error: { color: '#c33', fontSize: '0.85rem', margin: 0 },
  success: { color: '#2a8', fontSize: '0.85rem', margin: 0 },
};
