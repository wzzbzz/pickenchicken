const BASE_URL = import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8001/api';

function getSessionToken() {
  return localStorage.getItem('sessionToken');
}

function getLocationSessionId() {
  return localStorage.getItem('pc_lsid');
}

export function clearSession() {
  ['sessionToken', 'userId', 'userEmail', 'userUsername', 'userHasPassword'].forEach(k =>
    localStorage.removeItem(k)
  );
}

export async function apiFetch(path, options = {}) {
  const token = getSessionToken();
  const lsid = getLocationSessionId();

  const headers = {
    'Content-Type': 'application/json',
    ...(options.headers ?? {}),
  };

  if (token) headers['X-Session-Token'] = token;
  if (lsid && !headers['X-Location-Session']) headers['X-Location-Session'] = lsid;

  const res = await fetch(`${BASE_URL}${path}`, { ...options, headers });

  if (res.status === 401) {
    clearSession();
    window.location.href = '/login';
    return;
  }

  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    const err = new Error(body.message ?? `HTTP ${res.status}`);
    err.status = res.status;
    err.body = body;
    throw err;
  }

  if (res.status === 204) return null;
  return res.json();
}
