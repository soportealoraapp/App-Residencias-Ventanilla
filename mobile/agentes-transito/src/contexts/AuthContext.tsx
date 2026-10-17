import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { clearObsoleteAgentToken } from "../api/client";
import {
  AgentePerfil,
  clearAgentProfile,
  restoreAgentProfile,
  saveAgentProfile,
} from "../services/authProfileStorage";
import { loginAgente } from "../services/supabase";

export type AgenteUsuario = AgentePerfil;

interface AuthContextValue {
  user: AgenteUsuario | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  sessionError: string | null;
  signIn: (placa: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AgenteUsuario | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [sessionError, setSessionError] = useState<string | null>(null);

  useEffect(() => {
    async function cargarSesionGuardada() {
      try {
        const restaurada = await restoreAgentProfile(AsyncStorage, clearObsoleteAgentToken);
        setUser(restaurada.profile);
        if (restaurada.errors.length > 0) setSessionError(restaurada.errors.join(" "));
      } catch {
        setSessionError("No se pudo restaurar el perfil local. Inicie sesión nuevamente.");
      } finally {
        setIsLoading(false);
      }
    }
    cargarSesionGuardada();
  }, []);

  const signIn = async (placa: string, password: string): Promise<void> => {
    setIsLoading(true);
    setSessionError(null);
    try {
      if (!placa || !password) {
        throw new Error("Debe ingresar su número de placa/credencial y contraseña.");
      }

      const agente = await loginAgente(placa, password);
      const perfil = formatearAgente(agente);
      try {
        await saveAgentProfile(AsyncStorage, perfil);
      } catch {
        setUser(null);
        throw new Error("Credenciales aceptadas, pero no se pudo guardar el perfil en este dispositivo.");
      }
      setUser(perfil);
    } catch (error) {
      setUser(null);
      if (error instanceof Error) {
        throw error;
      }
      throw new Error("No fue posible validar las credenciales.");
    } finally {
      setIsLoading(false);
    }
  };

  const signOut = async () => {
    const erroresAlCerrar = await clearAgentProfile(AsyncStorage, clearObsoleteAgentToken);
    setUser(null);
    setSessionError(erroresAlCerrar.length > 0 ? erroresAlCerrar.join(" ") : null);
  };

  const value = useMemo(
    () => ({
      user,
      isLoading,
      isAuthenticated: !!user,
      sessionError,
      signIn,
      signOut,
      logout: signOut,
    }),
    [user, isLoading, sessionError]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth debe usarse dentro de <AuthProvider>");
  return ctx;
}

function formatearAgente(agente: {
  id: number;
  placa: string;
  nombre_completo: string;
  rol: string;
  sector: string;
}): AgenteUsuario {
  return {
    id: agente.id,
    placa: agente.placa,
    nombre: agente.nombre_completo,
    rol: agente.rol === "operativo" ? "Agente Vial Operativo" : agente.rol,
    sector: agente.sector || "Sector Centro - Uriangato, Gto.",
  };
}
