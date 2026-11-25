import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { api, getToken, setSessionExpiredHandler, setToken } from '../api/client';
import type { LoginResponse, Me } from '../api/types';

interface AuthContextValue {
  user: Me | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
  isLoginOpen: boolean;
  openLogin: () => void;
  closeLogin: () => void;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<Me | null>(null);
  const [isLoginOpen, setLoginOpen] = useState(false);

  const logout = useCallback(() => {
    setToken(null);
    setUser(null);
  }, []);

  useEffect(() => {
    setSessionExpiredHandler(() => setUser(null));
    // A token kept from a previous visit: reload the user it belongs to.
    if (getToken()) {
      api<Me>('/user').then(setUser).catch(logout);
    }
  }, [logout]);

  const login = useCallback(async (email: string, password: string) => {
    const response = await api<LoginResponse>('/login_check', { method: 'POST', body: { email, password } });
    setToken(response.token);
    setUser(response.user);
    setLoginOpen(false);
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      login,
      logout,
      isLoginOpen,
      openLogin: () => setLoginOpen(true),
      closeLogin: () => setLoginOpen(false),
    }),
    [user, login, logout, isLoginOpen],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used inside <AuthProvider>.');
  }

  return context;
}
