import { Routes, Route, Navigate } from 'react-router-dom';
import { isLoggedIn } from './api/auth.js';
import WelcomePage from './pages/WelcomePage.jsx';
import VerifyPage from './pages/VerifyPage.jsx';
import SetPasswordPage from './pages/SetPasswordPage.jsx';
import LeagueSelectorPage from './pages/LeagueSelectorPage.jsx';
import CompetitionPage from './pages/CompetitionPage.jsx';

function RequireAuth({ children }) {
  return isLoggedIn() ? children : <Navigate to="/" replace />;
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={isLoggedIn() ? <Navigate to="/leagues" replace /> : <WelcomePage />} />
      <Route path="/verify" element={<VerifyPage />} />
      <Route path="/set-password" element={<RequireAuth><SetPasswordPage /></RequireAuth>} />
      <Route path="/leagues" element={<RequireAuth><LeagueSelectorPage /></RequireAuth>} />
      <Route path="/competitions/:id" element={<RequireAuth><CompetitionPage /></RequireAuth>} />
    </Routes>
  );
}
