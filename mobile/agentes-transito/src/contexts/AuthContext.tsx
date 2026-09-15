import React, { createContext, useContext, useMemo, useState } from "react";
import { login as loginRequest, logout as logoutRequest, LoginResponse } from "../api/auth";

interface AuthContextValue {
  user: LoginResponse["user"] | null;
  isLoading: boolean;
  signIn: (usuario: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<LoginResponse["user"] | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  const signIn = async (usuario: string, password: string) => {
    setIsLoading(true);
    try {
      const data = await loginRequest({ usuario, password });
      setUser(data.user);
    } finally {
      setIsLoading(false);
    }
  };

  const signOut = async () => {
    await logoutRequest();
    setUser(null);
  };

  const value = useMemo(
    () => ({ user, isLoading, signIn, signOut }),
    [user, isLoading]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth debe usarse dentro de <AuthProvider>");
  return ctx;
}
