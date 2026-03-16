// Curated list of sports to display
// Add or remove sport keys here to customize what's shown
export const FEATURED_SPORTS = [
  // Basketball
  'basketball_nba',
  'basketball_ncaab',
  'basketball_wnba',
  
  // Football
  'americanfootball_nfl',
  'americanfootball_ncaaf',
  
  // Hockey
  'icehockey_nhl',
  
  // Baseball
  'baseball_mlb',
  
  // Soccer - Major Leagues
  'soccer_epl',                    // English Premier League
  'soccer_usa_mls',                // MLS
  'soccer_spain_la_liga',          // La Liga
  'soccer_germany_bundesliga',     // Bundesliga
  'soccer_italy_serie_a',          // Serie A
  'soccer_uefa_champs_league',    // Champions League
  
  // Combat Sports
  'mma_mixed_martial_arts',
  'boxing_boxing',
];

// Basic markets available for all sports (shown inline on game cards)
export const BASIC_MARKETS = [
  {
    key: 'h2h',
    displayName: 'Moneyline',
    icon: '💰',
    description: 'Pick the winner straight up'
  },
  {
    key: 'spreads',
    displayName: 'Spread',
    icon: '📊',
    description: 'Bet on the point spread'
  },
  {
    key: 'totals',
    displayName: 'Total',
    icon: '🎯',
    description: 'Over or Under the total score'
  }
];

// Player prop markets (available per-event only)
export const PLAYER_PROP_MARKETS = [
  {
    key: 'player_points',
    displayName: 'Player Points',
    icon: '🏀',
    description: 'Over/under on individual player points'
  },
  {
    key: 'player_rebounds',
    displayName: 'Player Rebounds',
    icon: '🔄',
    description: 'Over/under on individual player rebounds'
  },
  {
    key: 'player_assists',
    displayName: 'Player Assists',
    icon: '🤝',
    description: 'Over/under on individual player assists'
  }
];

export default FEATURED_SPORTS;
