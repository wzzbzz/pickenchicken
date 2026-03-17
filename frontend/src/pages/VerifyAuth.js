import React, { useEffect, useState, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { BACKEND_BASE_URL } from '../config/local';
import './VerifyAuth.css';

function VerifyAuth({ onLogin }) {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [status, setStatus] = useState('verifying');
  const [error, setError] = useState('');
  const [user, setUser] = useState(null);
  const [nickname, setNickname] = useState('');
  const [nicknameError, setNicknameError] = useState('');
  const [savingNickname, setSavingNickname] = useState(false);
  const verificationAttempted = useRef(false);

  useEffect(() => {
    const verifyToken = async () => {
      // Prevent duplicate calls
      if (verificationAttempted.current) {
        return;
      }
      verificationAttempted.current = true;

      const token = searchParams.get('token');
      
      if (!token) {
        setStatus('error');
        setError('No token provided');
        return;
      }

      try {
        // Use PickenChicken's auth API
        const apiUrl = window.location.hostname === 'localhost'
          ? BACKEND_BASE_URL
          : 'https://api.pickenchicken.com';
          
        const response = await fetch(`${apiUrl}/auth/verify-token`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ token }),
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.message || 'Invalid token');
        }

        // Save user info
        localStorage.setItem('user_id', data.user.id);
        localStorage.setItem('user_email', data.user.email);
        if (data.user.username) {
          localStorage.setItem('user_username', data.user.username);
        }

        setUser(data.user);

        if (!data.user.username) {
          // Prompt for nickname before redirecting
          setStatus('set-nickname');
        } else {
          setStatus('success');
          if (onLogin) onLogin(data.user);
          setTimeout(() => navigate('/'), 2000);
        }

      } catch (err) {
        setStatus('error');
        setError(err.message);
      }
    };

    verifyToken();
  }, [searchParams, navigate, onLogin]);

  const saveNickname = async () => {
    if (!nickname.trim()) {
      setNicknameError('Please enter a nickname');
      return;
    }
    if (!/^[a-zA-Z0-9_]{3,20}$/.test(nickname)) {
      setNicknameError('3-20 characters, letters/numbers/underscore only');
      return;
    }
    setSavingNickname(true);
    try {
      const apiUrl = window.location.hostname === 'localhost'
        ? BACKEND_BASE_URL
        : 'https://api.pickenchicken.com';
      const response = await fetch(`${apiUrl}/auth/update-username`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ userId: user.id, username: nickname }),
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.message || 'Failed to save nickname');
      localStorage.setItem('user_username', nickname);
      if (onLogin) onLogin({ ...user, username: nickname });
      setStatus('success');
      setTimeout(() => navigate('/'), 2000);
    } catch (err) {
      setNicknameError(err.message);
      setSavingNickname(false);
    }
  };

  return (
    <div className="verify-container">
      <div className="verify-card">
        {status === 'verifying' && (
          <>
            <div className="spinner"></div>
            <h2>Verifying your login...</h2>
          </>
        )}

        {status === 'set-nickname' && (
          <>
            <div className="success-icon">✓</div>
            <h2>One more thing...</h2>
            <p>Pick a nickname to show on the leaderboard:</p>
            <input
              type="text"
              value={nickname}
              onChange={e => { setNickname(e.target.value); setNicknameError(''); }}
              placeholder="e.g. ChickenSlayer99"
              maxLength={20}
              className="nickname-input"
              onKeyDown={e => e.key === 'Enter' && saveNickname()}
              autoFocus
            />
            {nicknameError && <p className="error-text">{nicknameError}</p>}
            <button onClick={saveNickname} disabled={savingNickname} className="magic-button">
              {savingNickname ? 'Saving...' : "Let's go! 🐔"}
            </button>
          </>
        )}

        {status === 'success' && (
          <>
            <div className="success-icon">✓</div>
            <h2>Success!</h2>
            <p>You're logged in. Redirecting...</p>
          </>
        )}

        {status === 'error' && (
          <>
            <div className="error-icon">✗</div>
            <h2>Verification Failed</h2>
            <p className="error-text">{error}</p>
            <button onClick={() => navigate('/login')} className="retry-button">
              Try Again
            </button>
          </>
        )}
      </div>
    </div>
  );
}

export default VerifyAuth;
