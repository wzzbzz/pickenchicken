
import { BACKEND_BASE_URL } from '../config/local';

const API_URL = `${BACKEND_BASE_URL}/api`;

export const sportsAPI = {
  // Get all available sports
  getSports: async () => {
    const response = await fetch(`${API_URL}/sports`);
    if (!response.ok) throw new Error('Failed to fetch sports');
    return response.json();
  },

  // Get odds for a specific sport with basic markets (h2h, spreads, totals)
  getOdds: async (sportKey) => {
    const markets = 'h2h,spreads,totals';
    const response = await fetch(`${API_URL}/odds/${sportKey}?markets=${markets}`);
    if (!response.ok) throw new Error('Failed to fetch odds');
    return response.json();
  },

  // Get player props for a specific event
  getEventOdds: async (sportKey, eventId, markets) => {
    const response = await fetch(`${API_URL}/odds/${sportKey}/${eventId}?markets=${markets}`);
    if (!response.ok) throw new Error('Failed to fetch event odds');
    return response.json();
  },

  // Get the chicken's random pick
  getChickenPick: async (outcomes) => {
    const response = await fetch(`${API_URL}/chicken-pick`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ outcomes }),
    });
    if (!response.ok) throw new Error('Failed to get chicken pick');
    return response.json();
  }
};
