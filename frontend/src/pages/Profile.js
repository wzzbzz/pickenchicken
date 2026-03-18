import React, { useState, useEffect } from 'react';
import './Profile.css';

const API = process.env.REACT_APP_API_URL || 'http://127.0.0.1:8001/api';

async function apiFetch(path, opts = {}) {
  const res = await fetch(`${API}${path}`, {
    headers: { 'Content-Type': 'application/json' },
    ...opts,
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw Object.assign(new Error(err.message || 'API error'), { status: res.status });
  }
  return res.json();
}

export default function Profile({ user, onUsernameUpdate }) {
  const [profile, setProfile]     = useState(null);
  const [loading, setLoading]     = useState(true);
  const [editing, setEditing]     = useState(false);
  const [username, setUsername]   = useState('');
  const [saving, setSaving]       = useState(false);
  const [error, setError]         = useState(null);
  const [success, setSuccess]     = useState(false);

  useEffect(() => {
    apiFetch(`/auth/me/${user.id}`)
      .then(data => {
        setProfile(data);
        setUsername(data.username || '');
        setLoading(false);
      })
      .catch(() => setLoading(false));
  }, [user.id]);

  const handleSave = async () => {
    setError(null);
    setSaving(true);
    try {
      const data = await apiFetch('/auth/update-username', {
        method: 'POST',
        body: JSON.stringify({ userId: parseInt(user.id), username }),
      });
      setProfile(p => ({ ...p, username: data.user.username }));
      setEditing(false);
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
      if (onUsernameUpdate) onUsernameUpdate(data.user.username);
    } catch (e) {
      setError(e.message);
    } finally {
      setSaving(false);
    }
  };

  if (loading) return <div className="pc-empty">Loading...</div>;
  if (!profile) return <div className="pc-empty">Could not load profile.</div>;

  const { record } = profile;
  const scored = record.wins + record.losses;
  const pct = scored > 0 ? Math.round((record.wins / scored) * 100) : null;
  const memberYear = profile.memberSince ? profile.memberSince.slice(0, 4) : null;

  const vsChicken = () => {
    if (!scored) return null;
    if (record.wins > record.losses) return 'Ahead of the Chicken';
    if (record.wins < record.losses) return 'Behind the Chicken';
    return 'Even with the Chicken';
  };

  return (
    <div className="pc-profile">

      <div className="pc-profile-header">
        <div className="pc-profile-avatar">🐔</div>
        <div className="pc-profile-identity">
          {editing ? (
            <div className="pc-profile-edit-row">
              <input
                className="pc-profile-username-input"
                value={username}
                onChange={e => setUsername(e.target.value)}
                maxLength={20}
                autoFocus
                onKeyDown={e => { if (e.key === 'Enter') handleSave(); if (e.key === 'Escape') setEditing(false); }}
              />
              <button className="pc-profile-save-btn" onClick={handleSave} disabled={saving}>
                {saving ? '...' : 'Save'}
              </button>
              <button className="pc-profile-cancel-btn" onClick={() => { setEditing(false); setError(null); setUsername(profile.username || ''); }}>
                Cancel
              </button>
            </div>
          ) : (
            <div className="pc-profile-name-row">
              <span className="pc-profile-username">{profile.username || profile.email}</span>
              <button className="pc-profile-edit-btn" onClick={() => setEditing(true)}>Edit</button>
            </div>
          )}
          {error && <div className="pc-profile-error">{error}</div>}
          {success && <div className="pc-profile-success">Username updated.</div>}
          <div className="pc-profile-meta">
            {profile.email}
            {memberYear && <> · Member since {memberYear}</>}
          </div>
        </div>
      </div>

      <div className="pc-profile-divider" />

      <div className="pc-profile-section-title">vs. The Chicken</div>

      {scored === 0 ? (
        <div className="pc-empty" style={{ marginTop: 0 }}>No scored picks yet — get in the bracket.</div>
      ) : (
        <>
          <div className="pc-profile-record-bar">
            <div className="pc-profile-stat">
              <div className="pc-profile-stat-num pc-profile-wins">{record.wins}</div>
              <div className="pc-profile-stat-label">Wins</div>
            </div>
            <div className="pc-profile-stat-divider" />
            <div className="pc-profile-stat">
              <div className="pc-profile-stat-num pc-profile-losses">{record.losses}</div>
              <div className="pc-profile-stat-label">Losses</div>
            </div>
            <div className="pc-profile-stat-divider" />
            <div className="pc-profile-stat">
              <div className="pc-profile-stat-num">{pct}%</div>
              <div className="pc-profile-stat-label">ATS Win %</div>
            </div>
            {record.pending > 0 && (
              <>
                <div className="pc-profile-stat-divider" />
                <div className="pc-profile-stat">
                  <div className="pc-profile-stat-num pc-profile-pending">{record.pending}</div>
                  <div className="pc-profile-stat-label">Pending</div>
                </div>
              </>
            )}
          </div>
          <div className="pc-profile-verdict">{vsChicken()}</div>
        </>
      )}
    </div>
  );
}
