import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import AsyncStorage from "@react-native-async-storage/async-storage";

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
      // Simulación de autenticación offline/online robusta para agentes de campo
      // Permite acceso con cualquier placa válida o credenciales de Uriangato
      if (!placa || !password) {
        throw new Error("Debe ingresar su número de placa/credencial y contraseña.");
      }

      if (password.length < 3) {
        throw new Error("La contraseña debe tener al menos 3 caracteres.");
      }

      const agenteFormateado: AgenteUsuario = {
        id: 104,
        placa: placa.toUpperCase().trim(),
        nombre: placa.toUpperCase().includes("204")
          ? "Oficial Carlos Mendoza Ruiz"
          : `Oficial ${placa.toUpperCase().trim()}`,
        rol: "Agente Vial Operativo",
        sector: "Sector Centro - Uriangato, Gto.",
      };

      await AsyncStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(agenteFormateado));
      setUser(agenteFormateado);
    } finally {
      setIsLoading(false);
    }
  };

  const signOut = async () => {
    try {
      await AsyncStorage.removeItem(AUTH_STORAGE_KEY);
      setUser(null);
    } catch (err) {
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
