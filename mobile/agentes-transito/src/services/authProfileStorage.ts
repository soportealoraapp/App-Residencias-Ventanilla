export interface AgentePerfil {
  id: number;
  placa: string;
  nombre: string;
  rol: string;
  sector: string;
}

export interface KeyValueStorage {
  getItem(key: string): Promise<string | null>;
  setItem(key: string, value: string): Promise<void>;
  removeItem(key: string): Promise<void>;
}

export const AUTH_PROFILE_STORAGE_KEY = "@agente_perfil_autenticado_v2";
export const LEGACY_AUTH_STORAGE_KEY = "@agente_sesion_activa_v1";

export async function restoreAgentProfile(
  storage: KeyValueStorage,
  clearObsoleteToken: () => Promise<void>
): Promise<{ profile: AgentePerfil | null; errors: string[] }> {
  const errors: string[] = [];

  try {
    await storage.removeItem(LEGACY_AUTH_STORAGE_KEY);
  } catch {
    errors.push("No se pudo eliminar un perfil antiguo del dispositivo.");
  }

  try {
    await clearObsoleteToken();
  } catch {
    errors.push("No se pudo eliminar una credencial de acceso antigua.");
  }

  let profile: AgentePerfil | null = null;
  try {
    const storedProfile = await storage.getItem(AUTH_PROFILE_STORAGE_KEY);
    if (storedProfile !== null) {
      const parsed: unknown = JSON.parse(storedProfile);
      if (isAgentProfile(parsed)) {
        profile = parsed;
      } else {
        await storage.removeItem(AUTH_PROFILE_STORAGE_KEY);
        errors.push("El perfil local no es válido; inicie sesión nuevamente.");
      }
    }
  } catch {
    errors.push("No se pudo restaurar el perfil local. Inicie sesión nuevamente.");
  }

  return { profile, errors };
}

export function saveAgentProfile(
  storage: KeyValueStorage,
  profile: AgentePerfil
): Promise<void> {
  return storage.setItem(AUTH_PROFILE_STORAGE_KEY, JSON.stringify(profile));
}

export async function clearAgentProfile(
  storage: KeyValueStorage,
  clearObsoleteToken: () => Promise<void>
): Promise<string[]> {
  const errors: string[] = [];

  try {
    await storage.removeItem(AUTH_PROFILE_STORAGE_KEY);
  } catch {
    errors.push("No se pudo eliminar el perfil local del dispositivo.");
  }
  try {
    await storage.removeItem(LEGACY_AUTH_STORAGE_KEY);
  } catch {
    errors.push("No se pudo eliminar un perfil antiguo del dispositivo.");
  }
  try {
    await clearObsoleteToken();
  } catch {
    errors.push("No se pudo eliminar una credencial antigua del dispositivo.");
  }

  return errors;
}

function isAgentProfile(value: unknown): value is AgentePerfil {
  if (value === null || typeof value !== "object") return false;
  const profile = value as Record<string, unknown>;
  return (
    typeof profile.id === "number" &&
    typeof profile.placa === "string" &&
    typeof profile.nombre === "string" &&
    typeof profile.rol === "string" &&
    typeof profile.sector === "string"
  );
}
