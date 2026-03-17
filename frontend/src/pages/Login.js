import React, { useState } from 'react';
import './Login.css';
import { BACKEND_BASE_URL } from '../config/local';

function Login({ onLogin }) {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      // Use Doink's auth API
      const apiUrl = window.location.hostname === 'localhost' 
        ? BACKEND_BASE_URL
        : 'https://api.pickenchicken.com';
        
      const response = await fetch(`${apiUrl}/auth/request-login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email }),
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || 'Failed to send login link');
      }

      setSent(true);
    } catch (err) {
      setError(err.message);
      setLoading(false);
    }
  };

  if (sent) {
    return (
      <div className="login-container">
        <div className="login-card">
          <h1>🐔 Check Your Email!</h1>
          <p>We've sent a magic login link to:</p>
          <p className="email-sent">{email}</p>
          <p className="help-text">Click the link in the email to log in. The link expires in 1 hour.</p>

        </div>
      </div>
    );
  }

  return (
    <div className="login-container">
      <div className="login-card">
        <h1>🐔 The Picken' Chicken</h1>
        <p className="tagline">Your guess is as good as mine</p>
        
        <form onSubmit={handleSubmit}>
          <div className="form-group">
            <label htmlFor="email">Enter your email to get started</label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="your@email.com"
              required
              disabled={loading}
            />
          </div>
          
          {error && <div className="error-message">{error}</div>}
          
          <button type="submit" disabled={loading} className="magic-button">
            {loading ? 'Sending...' : 'Send Magic Link 🪄'}
          </button>
        </form>
        
        <p className="privacy-note">
          No password needed! We'll send you a secure login link.
        </p>
      </div>
    </div>
  );
}

export default Login;
