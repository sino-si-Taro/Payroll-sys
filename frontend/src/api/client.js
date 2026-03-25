import axios from 'axios';
import { getAuthToken, clearAuth } from '../utils/auth';

/**
 * REST API Client configuration following the principles from:
 * https://www.souysoeng.com/2026/02/stop-designing-rest-apis-wrong.html
 */

// Step 4: API Versioning
const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

const apiClient = axios.create({
  baseURL: BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Step 6: Authentication via Sanctum Bearer Token
apiClient.interceptors.request.use((config) => {
  const token = getAuthToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
}, (error) => {
  return Promise.reject(error);
});

// Step 2, 3, 7: HTTP Status Codes, Consistent JSON, and Error Structure
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    const { response } = error;

    if (response) {
      const { status, data } = response;
      // Extract structured error message from the new error envelope
      const errorMessage = data?.error?.message || data?.message || 'An unknown error occurred';

      switch (status) {
        case 400:
          console.error('Bad Request:', errorMessage);
          break;
        case 401:
          console.error('Unauthorized:', errorMessage);
          clearAuth();
          window.location.href = '/login';
          break;
        case 403:
          console.error('Forbidden:', errorMessage);
          break;
        case 404:
          console.error('Not Found:', errorMessage);
          break;
        case 422:
          console.error('Validation Errors:', data?.error?.details || data?.errors);
          break;
        case 429:
          console.error('Rate Limited:', errorMessage);
          break;
        case 500:
          console.error('Internal Server Error:', errorMessage);
          break;
        default:
          console.error(`Error ${status}:`, errorMessage);
      }
    } else {
      console.error('Network Error: Please check your connection.');
    }

    return Promise.reject(error);
  }
);

export default apiClient;
