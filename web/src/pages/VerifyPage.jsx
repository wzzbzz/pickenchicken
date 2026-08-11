import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { verifyToken } from '../api/auth.js';

export default function VerifyPage() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [error, setError] = useState(null);

  useEffect(() => {
    const token = searchParams.get('token');
    if (!token) {
      setError('No token provided.');
      return;
    }

    verifyToken(token)
      .then(data => {
        if (data && !data.user.has_password) {
          navigate('/set-password');
        } else {
          navigate('/leagues');
        }
      })
      .catch(err => setError(err.message));
  }, []);

  if (error) {
    return (
      <div>
        <p style={{ color: 'red' }}>{error}</p>
        <a href="/login">Try again</a>
      </div>
    );
  }

  return <p>Verifying…</p>;
}
