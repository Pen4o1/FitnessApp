import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';

import {
  ApiError,
  getUser,
  login,
  logout,
  register,
  updateProfile as updateProfileRequest,
  type RegisterPayload,
  type UpdateProfilePayload,
  type User,
} from '@/lib/api';
import { clearToken, getToken } from '@/lib/auth-storage';

type AuthContextValue = {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signUp: (payload: RegisterPayload) => Promise<void>;
  signOut: () => Promise<void>;
  refreshUser: () => Promise<User>;
  updateProfile: (payload: UpdateProfilePayload) => Promise<User>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [token, setTokenState] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let isMounted = true;

    async function restoreSession() {
      try {
        const storedToken = await getToken();
        if (!storedToken) {
          return;
        }

        const currentUser = await getUser();
        if (!isMounted) {
          return;
        }

        setTokenState(storedToken);
        setUser(currentUser);
      } catch {
        await clearToken();
        if (isMounted) {
          setTokenState(null);
          setUser(null);
        }
      } finally {
        if (isMounted) {
          setIsLoading(false);
        }
      }
    }

    restoreSession();

    return () => {
      isMounted = false;
    };
  }, []);

  const signIn = useCallback(async (email: string, password: string) => {
    const response = await login(email, password);
    setTokenState(response.token);
    setUser(response.user);
  }, []);

  const signUp = useCallback(async (payload: RegisterPayload) => {
    const response = await register(payload);
    setTokenState(response.token);
    setUser(response.user);
  }, []);

  const signOut = useCallback(async () => {
    try {
      await logout();
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 401) {
        throw error;
      }
      await clearToken();
    } finally {
      setTokenState(null);
      setUser(null);
    }
  }, []);

  const refreshUser = useCallback(async () => {
    const currentUser = await getUser();
    setUser(currentUser);
    return currentUser;
  }, []);

  const updateProfile = useCallback(async (payload: UpdateProfilePayload) => {
    const updatedUser = await updateProfileRequest(payload);
    setUser(updatedUser);
    return updatedUser;
  }, []);

  const value = useMemo(
    () => ({
      user,
      token,
      isLoading,
      isAuthenticated: user !== null && token !== null,
      signIn,
      signUp,
      signOut,
      refreshUser,
      updateProfile,
    }),
    [user, token, isLoading, signIn, signUp, signOut, refreshUser, updateProfile],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
