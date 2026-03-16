import React, { useEffect, useState, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { BACKEND_BASE_URL } from '../config/local';
import './VerifyAuth.css';

function VerifyAuth({ onLogin }) {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [status, setStatus] = useState('verifying');
  const [error, setError] = useState('');
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
          
        const response = await fetch(`${apiUrl}/api/auth/verify-token`, {
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

        setStatus('success');
        
        // Call parent onLogin callback
        if (onLogin) {
          onLogin(data.user);
        }

        // Redirect to main app after 2 seconds
        setTimeout(() => {
          navigate('/');
        }, 2000);

      } catch (err) {
        setStatus('error');
        setError(err.message);
      }
    };

    verifyToken();
  }, [searchParams, navigate, onLogin]);

  return (
    <div className="verify-container">
      <div className="verify-card">
        {status === 'verifying' && (
          <>
            <div className="spinner"></div>
            <h2>Verifying your login...</h2>
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
