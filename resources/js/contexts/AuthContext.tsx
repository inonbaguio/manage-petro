import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import { createApiClient, ApiResponse } from '../lib/api';
import { useTenant } from '../lib/useTenant';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  tenant_id: number;
}

interface AuthContextType {
  user: User | null;
  token: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  isAuthenticated: boolean;
  isLoading: boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const tenant = useTenant();
  const navigate = useNavigate();
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const api = createApiClient(tenant);

  useEffect(() => {
    // Load user and token from localStorage on mount
    const storedToken = localStorage.getItem(`token_${tenant}`);
    const storedUser = localStorage.getItem(`user_${tenant}`);

    if (storedToken && storedUser) {
      setToken(storedToken);
      setUser(JSON.parse(storedUser));
    }

    setIsLoading(false);
  }, [tenant]);

  const login = async (email: string, password: string) => {
    try {
      const response = await api.post<ApiResponse<{ user: User; token: string }>>(
        '/auth/login',
        { email, password }
      );

      const { user: userData, token: userToken } = response.data.data;

      setUser(userData);
      setToken(userToken);

      localStorage.setItem(`token_${tenant}`, userToken);
      localStorage.setItem(`user_${tenant}`, JSON.stringify(userData));

      navigate(`/${tenant}/dashboard`);
    } catch (error: any) {
      const message = error.response?.data?.message || 'Login failed';
      throw new Error(message);
    }
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setToken(null);
      localStorage.removeItem(`token_${tenant}`);
      localStorage.removeItem(`user_${tenant}`);
      navigate(`/${tenant}/login`);
    }
  };

  const value: AuthContextType = {
    user,
    token,
    login,
    logout,
    isAuthenticated: !!user && !!token,
    isLoading,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = (): AuthContextType => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
