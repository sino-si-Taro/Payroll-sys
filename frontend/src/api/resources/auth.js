import apiClient from '../client';

class AuthResource {
  login(credentials) {
    return apiClient.post('/auth/login', credentials);
  }

  me() {
    return apiClient.get('/auth/me');
  }

  changePassword(payload) {
    return apiClient.post('/auth/change-password', payload);
  }

  logout() {
    return apiClient.post('/auth/logout');
  }
}

export const authApi = new AuthResource();
