import { apiFetch } from './client.js';

const LS_KEY = 'pc_lsid';

export function getStoredSessionId() {
  return localStorage.getItem(LS_KEY);
}

function storeSessionId(id) {
  if (id) localStorage.setItem(LS_KEY, id);
}

export async function getCurrentLocation(competitionId) {
  const lsid = getStoredSessionId();
  const headers = {};
  if (lsid) headers['X-Location-Session'] = lsid;
  if (competitionId) headers['X-Competition-Id'] = competitionId;

  const query = competitionId ? `?competition_id=${competitionId}` : '';
  const data = await apiFetch(`/location/current${query}`, { headers });

  if (data?.session_id) storeSessionId(data.session_id);
  return data;
}
