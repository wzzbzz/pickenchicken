import React from 'react';
import { Link } from 'react-router-dom';
import '../App.css';
import './Legal.css';

export default function TermsOfUse() {
  return (
    <div className="pc-legal-root">
      <div className="pc-legal-header">
        <div className="pc-legal-breadcrumb">
          <Link to="/" className="pc-legal-back">← Back to The Picken' Chicken</Link>
        </div>
        <h1 className="pc-legal-title">Terms of Use</h1>
        <p className="pc-legal-date">Last updated: March 2026</p>
      </div>

      <div className="pc-legal-body">

        <div className="pc-legal-warning-box">
          <strong>⚠ Important:</strong> This site is for entertainment purposes only. It does not
          facilitate real-money wagering. If you have a gambling problem, call the National Problem
          Gambling Helpline: <a href="tel:18005224700">1-800-522-4700</a> (24/7, free, confidential).
        </div>

        <section className="pc-legal-section">
          <h2>1. Acceptance of Terms</h2>
          <p>By accessing or using The Picken' Chicken ("the Site", "we", "us"), you agree to be
          bound by these Terms of Use. If you do not agree, please do not use the Site. We reserve
          the right to update these terms at any time; continued use constitutes acceptance of
          any changes.</p>
        </section>

        <section className="pc-legal-section">
          <h2>2. Entertainment Purposes Only</h2>
          <p>The Picken' Chicken is a free-to-play prediction game. No real money is wagered, won,
          or lost on this platform. Odds data is displayed for informational and entertainment
          purposes only and does not constitute an offer or invitation to place any wager. We
          are not a licensed gambling operator.</p>
        </section>

        <section className="pc-legal-section">
          <h2>3. Eligibility</h2>
          <p>You must be at least 18 years of age (or the age of majority in your jurisdiction,
          whichever is higher) to use this Site. By using the Site you represent and warrant
          that you meet this requirement. We reserve the right to terminate any account we
          reasonably believe is operated by a minor.</p>
        </section>

        <section className="pc-legal-section">
          <h2>4. Responsible Gambling</h2>
          <p>While no real money is involved on this Site, we take problem gambling seriously.
          Engagement with sports odds content — even in a free-play context — may not be
          appropriate for everyone. If you find yourself preoccupied with odds, spreads, or
          outcomes in a way that is causing distress, please seek help:</p>
          <ul>
            <li><strong>National Problem Gambling Helpline:</strong> <a href="tel:18005224700">1-800-522-4700</a> · <a href="https://www.ncpgambling.org" target="_blank" rel="noopener noreferrer">ncpgambling.org</a></li>
            <li><strong>BeGambleAware:</strong> <a href="https://www.begambleaware.org" target="_blank" rel="noopener noreferrer">begambleaware.org</a></li>
            <li><strong>Gambling Therapy:</strong> <a href="https://www.gamblingtherapy.org" target="_blank" rel="noopener noreferrer">gamblingtherapy.org</a></li>
          </ul>
        </section>

        <section className="pc-legal-section">
          <h2>5. User Accounts</h2>
          <p>You may register for an account using your email address. You are responsible for
          maintaining the confidentiality of your account and for all activity that occurs under
          it. You agree to notify us immediately of any unauthorised use. We reserve the right
          to suspend or terminate accounts at our discretion.</p>
        </section>

        <section className="pc-legal-section">
          <h2>6. Prohibited Conduct</h2>
          <p>You agree not to: use the Site for any unlawful purpose; attempt to manipulate game
          outcomes or leaderboards through automated means, multiple accounts, or collusion;
          scrape, harvest, or systematically collect data from the Site; attempt to reverse-engineer
          or interfere with the Site's systems; or use the Site in any way that could harm other
          users or third parties.</p>
        </section>

        <section className="pc-legal-section">
          <h2>7. Intellectual Property</h2>
          <p>All content on this Site — including the name "The Picken' Chicken", design, graphics,
          and underlying software — is owned by or licensed to us. You may not reproduce, distribute,
          or create derivative works without our prior written consent. Odds data is sourced from
          The Odds API and is subject to their terms of use.</p>
        </section>

        <section className="pc-legal-section">
          <h2>8. Disclaimers</h2>
          <p>The Site is provided "as is" without warranties of any kind. We do not warrant that
          the Site will be uninterrupted, error-free, or that odds data will be accurate or
          complete. Sports data is provided by third-party sources and may contain errors. We are
          not responsible for any decisions made based on information displayed on the Site.</p>
        </section>

        <section className="pc-legal-section">
          <h2>9. Limitation of Liability</h2>
          <p>To the fullest extent permitted by law, we shall not be liable for any indirect,
          incidental, special, or consequential damages arising from your use of the Site.
          Since no real money is involved, our total liability for any claim arising from use
          of the Site shall not exceed zero dollars.</p>
        </section>

        <section className="pc-legal-section">
          <h2>10. Governing Law</h2>
          <p>These Terms shall be governed by and construed in accordance with applicable law.
          Any disputes shall be resolved through binding arbitration or in the courts of the
          jurisdiction in which we operate, as appropriate.</p>
        </section>

        <section className="pc-legal-section">
          <h2>11. Contact</h2>
          <p>Questions about these Terms? Contact us at <a href="mailto:legal@pickenchicken.com">legal@pickenchicken.com</a>.</p>
        </section>

      </div>
    </div>
  );
}
