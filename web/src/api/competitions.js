import { apiFetch } from './client.js';

export function getCompetitions() {
  return apiFetch('/competitions');
}

export function getCompetition(id) {
  return apiFetch(`/competitions/${id}`);
}

export function getCurrentSegment(competitionId) {
  return apiFetch(`/competitions/${competitionId}/segments/current`);
}

export function getSegmentGames(segmentId) {
  return apiFetch(`/segments/${segmentId}/games`);
}

export function submitPick(gameId, pick) {
  return apiFetch(`/games/${gameId}/pick`, {
    method: 'POST',
    body: JSON.stringify({ pick }),
  });
}

export function lockPick(gameId) {
  return apiFetch(`/games/${gameId}/pick/lock`, { method: 'POST' });
}

export function unlockPick(gameId) {
  return apiFetch(`/games/${gameId}/pick/unlock`, { method: 'POST' });
}
