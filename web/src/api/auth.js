import { apiFetch, clearSession } from './client.js';

export async function requestLogin(email) {
  return apiFetch('/auth/request-login', {
    method: 'POST',
    body: JSON.stringify({ email }),
  });
}

export async function verifyToken(token) {
  const data = await apiFetch('/auth/verify-token', {
    method: 'POST',
    body: JSON.stringify({ token }),
  });

  if (data) {
    localStorage.setItem('sessionToken', data.sessionToken);
    localStorage.setItem('userId', data.user.id);
    localStorage.setItem('userEmail', data.user.email);
    localStorage.setItem('userUsername', data.user.username ?? '');
    localStorage.setItem('userHasPassword', data.user.has_password ? '1' : '');
  }

  return data;
}

export async function logout() {
  await apiFetch('/auth/logout', { method: 'POST' }).catch(() => {});
  clearSession();
}

export async function me() {
  return apiFetch('/auth/me');
}

export async function updateUsername(username) {
  return apiFetch('/auth/update-username', {
    method: 'PATCH',
    body: JSON.stringify({ username }),
  });
}

export async function loginWithPassword(email, password) {
  const data = await apiFetch('/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });

  if (data) {
    localStorage.setItem('sessionToken', data.sessionToken);
    localStorage.setItem('userId', data.user.id);
    localStorage.setItem('userEmail', data.user.email);
    localStorage.setItem('userUsername', data.user.username ?? '');
    localStorage.setItem('userHasPassword', data.user.has_password ? '1' : '');
  }

  return data;
}

export async function setPassword(password) {
  return apiFetch('/auth/set-password', {
    method: 'POST',
    body: JSON.stringify({ password }),
  });
}

export function getStoredUser() {
  const id = localStorage.getItem('userId');
  if (!id) return null;
  return {
    id: parseInt(id, 10),
    email: localStorage.getItem('userEmail'),
    username: localStorage.getItem('userUsername') || null,
    hasPassword: localStorage.getItem('userHasPassword') === '1',
  };
}

export function isLoggedIn() {
  return !!localStorage.getItem('sessionToken');
}
