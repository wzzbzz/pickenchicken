import React, { useState } from 'react';
import './GamePicker.css';

function GamePicker({ game, marketType = 'h2h', onPickMade }) {
  const [userPick, setUserPick] = useState(null);
  const [userPickOutcome, setUserPickOutcome] = useState(null);
  const [chickenPick, setChickenPick] = useState(null);
  const [chickenPickOutcome, setChickenPickOutcome] = useState(null);
  const [showChicken, setShowChicken] = useState(false);

  // Map market types to their keys in the API
  const marketKeyMap = {
    'spreads': 'spreads',
    'totals': 'totals',
    'h2h': 'h2h',
    'player_points': 'player_points',
    'player_rebounds': 'player_rebounds',
    'player_assists': 'player_assists',
    'player_threes': 'player_threes',
    'player_points_rebounds_assists': 'player_points_rebounds_assists'
  };

  // Get the market from the first bookmaker
  const getMarketOptions = () => {
    if (!game.bookmakers || game.bookmakers.length === 0) return null;
    
    const marketKey = marketKeyMap[marketType] || 'h2h';
    const market = game.bookmakers[0].markets.find(m => m.key === marketKey);
    if (!market) return null;
    
    return market.outcomes;
  };

  const handleUserPick = (outcome) => {
    setUserPick(outcome.name);
    setUserPickOutcome(outcome);
    
    // Generate chicken's random pick
    const options = getMarketOptions();
    if (options) {
      const randomIndex = Math.floor(Math.random() * options.length);
      const chickenOutcome = options[randomIndex];
      setChickenPick(chickenOutcome.name);
      setChickenPickOutcome(chickenOutcome);
    }
  };

  const formatOutcomeLabel = (outcome) => {
    // For player props, show description (player name) + Over/Under + line
    if (marketType.startsWith('player_') && outcome.description && outcome.point !== undefined) {
      return `${outcome.description} - ${outcome.name} ${outcome.point}`;
    }
    // For spreads, show the point value
    if (marketType === 'spreads' && outcome.point !== undefined) {
      const point = outcome.point > 0 ? `+${outcome.point}` : outcome.point;
      return `${outcome.name} ${point}`;
    }
    // For totals (over/under), show the line
    if (marketType === 'totals' && outcome.point !== undefined) {
      return `${outcome.name} ${outcome.point}`;
    }
    // For other markets, just show the name
    return outcome.name;
  };

  const handleReveal = () => {
    setShowChicken(true);
    if (onPickMade) {
      onPickMade({
        gameId: game.id,
        userPick,
        chickenPick,
        match: userPick === chickenPick
      });
    }
  };

  const marketOptions = getMarketOptions();

  if (!marketOptions) {
    return (
      <div className="game-picker">
        <p className="no-odds">No betting odds available for this game</p>
      </div>
    );
  }

  return (
    <div className="game-picker">
      <div className="pick-section">
        <h4>Make Your Pick:</h4>
        <div className="team-options">
          {marketOptions.map((outcome) => (
            <button
              key={outcome.name}
              className={`team-option ${userPick === outcome.name ? 'selected' : ''}`}
              onClick={() => handleUserPick(outcome)}
              disabled={userPick !== null}
            >
              <span className="team-name">{formatOutcomeLabel(outcome)}</span>
              <span className="odds">{outcome.price > 0 ? '+' : ''}{outcome.price}</span>
            </button>
          ))}
        </div>
      </div>

      {userPick && !showChicken && (
        <div className="reveal-section">
          <p className="pick-made">✓ You picked: <strong>{formatOutcomeLabel(userPickOutcome)}</strong></p>
          <button className="reveal-button" onClick={handleReveal}>
            🐔 Reveal The Chicken's Pick
          </button>
        </div>
      )}

      {showChicken && (
        <div className="chicken-reveal">
          <div className="chicken-pick">
            <p>🐔 The Chicken picked: <strong>{formatOutcomeLabel(chickenPickOutcome)}</strong></p>
          </div>
          <div className={`result ${userPick === chickenPick ? 'match' : 'different'}`}>
            {userPick === chickenPick ? (
              <>
                <p className="result-icon">🤝</p>
                <p>You both picked the same! No winner this round.</p>
              </>
            ) : (
              <>
                <p className="result-icon">⚔️</p>
                <p>Different picks! May the best picker win when results come in!</p>
              </>
            )}
          </div>
        </div>
      )}
    </div>
  );
}

export default GamePicker;
