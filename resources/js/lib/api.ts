import axios, { AxiosInstance } from 'axios';

export interface ApiResponse<T = any> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string[]>;
}

export const createApiClient = (tenant: string): AxiosInstance => {
  const client = axios.create({
    baseURL: `/api/${tenant}`,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    withCredentials: true,
  });

  // Add token from localStorage to all requests
  client.interceptors.request.use((config) => {
    const token = localStorage.getItem(`token_${tenant}`);
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  });

  // Handle 401 errors (unauthenticated)
  client.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        localStorage.removeItem(`token_${tenant}`);
        localStorage.removeItem(`user_${tenant}`);
        window.location.href = `/${tenant}/login`;
      }
      return Promise.reject(error);
    }
  );

  return client;
};
