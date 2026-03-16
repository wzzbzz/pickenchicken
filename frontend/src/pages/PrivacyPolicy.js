import React from 'react';
import { Link } from 'react-router-dom';
import '../App.css';
import './Legal.css';

export default function PrivacyPolicy() {
  return (
    <div className="pc-legal-root">
      <div className="pc-legal-header">
        <div className="pc-legal-breadcrumb">
          <Link to="/" className="pc-legal-back">← Back to The Picken' Chicken</Link>
        </div>
        <h1 className="pc-legal-title">Privacy Policy</h1>
        <p className="pc-legal-date">Last updated: March 2026</p>
      </div>

      <div className="pc-legal-body">

        <section className="pc-legal-section">
          <h2>1. Who We Are</h2>
          <p>The Picken' Chicken ("we", "us", "our") is a free-to-play sports prediction game.
          This Privacy Policy explains what personal information we collect, how we use it, and
          your rights in relation to it. We are committed to handling your data responsibly.</p>
        </section>

        <section className="pc-legal-section">
          <h2>2. Information We Collect</h2>
          <p>We collect the following information when you use the Site:</p>
          <ul>
            <li><strong>Account data:</strong> Your email address and chosen username, provided
            when you register via our magic-link login.</li>
            <li><strong>Game data:</strong> Your picks, results, and leaderboard scores.</li>
            <li><strong>Usage data:</strong> Anonymous session data including IP address, browser
            type, and in-app events (e.g. game start, pick made). This is used only for aggregate
            analytics and improving the product.</li>
            <li><strong>Technical data:</strong> Cookies and similar technologies used to maintain
            your login session. We do not use advertising or tracking cookies.</li>
          </ul>
        </section>

        <section className="pc-legal-section">
          <h2>3. How We Use Your Information</h2>
          <p>We use your information to: operate and maintain your account; display your picks
          and leaderboard position; send you magic-link login emails (no marketing emails without
          your explicit consent); improve and debug the Site using anonymised analytics; and
          comply with legal obligations.</p>
          <p>We do not sell your personal data. We do not share it with third parties for
          advertising purposes.</p>
        </section>

        <section className="pc-legal-section">
          <h2>4. Third-Party Services</h2>
          <p>We use the following third-party services to operate the Site:</p>
          <ul>
            <li><strong>The Odds API</strong> — provides sports betting odds data. We pass no
            personal information to this service.</li>
            <li><strong>ESPN API</strong> — provides tournament bracket and game data. We pass
            no personal information to this service.</li>
            <li><strong>Transactional email provider</strong> — used solely to send magic-link
            login emails. Your email address is transmitted for this purpose only.</li>
          </ul>
        </section>

        <section className="pc-legal-section">
          <h2>5. Data Retention</h2>
          <p>We retain your account data for as long as your account is active. Game history
          and leaderboard data may be retained indefinitely for the purpose of maintaining
          historical records. Anonymous usage analytics are retained for up to 24 months.
          You may request deletion of your account and associated data at any time.</p>
        </section>

        <section className="pc-legal-section">
          <h2>6. Cookies</h2>
          <p>We use a single session cookie to keep you logged in. This cookie contains a
          random token — no personal information is stored in the cookie itself. We do not
          use advertising cookies, tracking pixels, or third-party analytics cookies.
          You can clear cookies in your browser settings at any time; doing so will log
          you out of the Site.</p>
        </section>

        <section className="pc-legal-section">
          <h2>7. Your Rights</h2>
          <p>Depending on your jurisdiction, you may have the right to: access the personal
          data we hold about you; correct inaccurate data; request deletion of your data;
          object to or restrict processing of your data; and data portability. To exercise
          any of these rights, contact us at the address below. We will respond within
          30 days.</p>
        </section>

        <section className="pc-legal-section">
          <h2>8. Children's Privacy</h2>
          <p>The Site is not directed at children under 13, and we do not knowingly collect
          personal information from children under 13. If we become aware that a child under
          13 has provided us with personal information, we will delete it promptly. If you
          believe a child has provided us with their data, please contact us immediately.</p>
        </section>

        <section className="pc-legal-section">
          <h2>9. Security</h2>
          <p>We take reasonable technical and organisational measures to protect your personal
          data, including encrypted connections (HTTPS), secure cookie flags, and hashed
          session tokens. No method of transmission over the internet is 100% secure, and
          we cannot guarantee absolute security.</p>
        </section>

        <section className="pc-legal-section">
          <h2>10. Changes to This Policy</h2>
          <p>We may update this Privacy Policy from time to time. Material changes will be
          communicated via a notice on the Site. Continued use of the Site after changes
          are posted constitutes your acceptance of the updated policy.</p>
        </section>

        <section className="pc-legal-section">
          <h2>11. Contact</h2>
          <p>For privacy-related questions or to exercise your rights, contact us at:&nbsp;
          <a href="mailto:privacy@pickenchicken.com">privacy@pickenchicken.com</a>.</p>
        </section>

      </div>
    </div>
  );
}
