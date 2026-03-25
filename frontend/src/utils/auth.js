const AUTH_USER_KEY = 'auth_user';
const AUTH_TOKEN_KEY = 'auth_token';

export function setAuth(userPayload, token) {
  localStorage.setItem(AUTH_USER_KEY, JSON.stringify(userPayload));
  if (token) {
    localStorage.setItem(AUTH_TOKEN_KEY, token);
  }
}

export function setAuthUser(userPayload) {
  localStorage.setItem(AUTH_USER_KEY, JSON.stringify(userPayload));
}

export function getAuthUser() {
  const raw = localStorage.getItem(AUTH_USER_KEY);
  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch {
    localStorage.removeItem(AUTH_USER_KEY);
    return null;
  }
}

export function getAuthToken() {
  return localStorage.getItem(AUTH_TOKEN_KEY);
}

export function clearAuth() {
  localStorage.removeItem(AUTH_USER_KEY);
  localStorage.removeItem(AUTH_TOKEN_KEY);
}

export function clearAuthUser() {
  clearAuth();
}

export function getActorUserId() {
  const authUser = getAuthUser();
  return authUser?.id ? Number(authUser.id) : null;
}

export function getActorRole() {
  const authUser = getAuthUser();
  return authUser?.role || null;
}

export function getActorEmployeeId() {
  const authUser = getAuthUser();
  return authUser?.employee_profile?.id ? Number(authUser.employee_profile.id) : null;
}

export function isHrOrAdmin() {
  const role = getActorRole();
  return role === 'hr' || role === 'admin';
}

export function isAuthenticated() {
  return !!getAuthToken() && !!getAuthUser();
}
