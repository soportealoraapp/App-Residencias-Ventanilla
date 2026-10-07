import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { loginAgente } from "../services/supabase";

export interface AgenteUsuario {
  id: number;
  placa: string;
  nombre: string;
  rol: string;
  sector: string;
}

interface AuthContextValue {
  user: AgenteUsuario | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  signIn: (placa: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  logout: () => Promise<void>;
}

const AUTH_STORAGE_KEY = "@agente_sesion_activa_v1";

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AgenteUsuario | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Restaurar sesión guardada localmente al iniciar
  useEffect(() => {
    async function cargarSesionGuardada() {
      try {
        const sesion = await AsyncStorage.getItem(AUTH_STORAGE_KEY);
        if (sesion) {
          setUser(JSON.parse(sesion));
        }
      } catch (err) {
        console.error("Error al cargar sesión local del agente:", err);
      } finally {
        setIsLoading(false);
      }
    }
    cargarSesionGuardada();
  }, []);

  const signIn = async (placa: string, password: string): Promise<void> => {
    setIsLoading(true);
    try {
      if (!placa || !password) {
        throw new Error("Debe ingresar su número de placa/credencial y contraseña.");
      }

      if (password.length < 3) {
        throw new Error("La contraseña debe tener al menos 3 caracteres.");
      }

      // Autenticación con Supabase en tiempo real (con fallback offline automático)
      const agente = await loginAgente(placa, password);

      const agenteFormateado: AgenteUsuario = {
        id: agente.id,
        placa: agente.placa,
        nombre: agente.nombre_completo,
        rol: agente.rol === "operativo" ? "Agente Vial Operativo" : agente.rol,
        sector: agente.sector || "Sector Centro - Uriangato, Gto.",
      };

      await AsyncStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(agenteFormateado));
      setUser(agenteFormateado);
    } finally {
      setIsLoading(false);
    }
  };

  const signOut = async () => {
    try {
      console.log("[LOGOUT] intentando eliminar sesión");
      await AsyncStorage.removeItem(AUTH_STORAGE_KEY);
      console.log("[LOGOUT] sesión eliminada de AsyncStorage");
      setUser(null);
      console.log("[LOGOUT] user establecido en null");
    } catch (err) {
      console.log("[LOGOUT] ERROR:", err);
      console.error("Error al cerrar sesión:", err);
    }
  };

  const value = useMemo(
    () => ({
      user,
      isLoading,
      isAuthenticated: !!user,
      signIn,
      signOut,
      logout: signOut,
    }),
    [user, isLoading]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth debe usarse dentro de <AuthProvider>");
  return ctx;
}
