import AsyncStorage from "@react-native-async-storage/async-storage";
import { Infraccion } from "../types/infraccion";

const INFRACCIONES_KEY = "@agentes_transito_infracciones_v1";
const ULTIMA_SYNC_KEY = "@agentes_transito_ultima_sync_v1";

export async function obtenerInfraccionesLocales(): Promise<Infraccion[]> {
  try {
    const raw = await AsyncStorage.getItem(INFRACCIONES_KEY);
    if (!raw) return [];
    return JSON.parse(raw) as Infraccion[];
  } catch (error) {
    console.error("Error al leer infracciones locales:", error);
    return [];
  }
}

export async function guardarInfraccionLocal(infraccion: Infraccion): Promise<Infraccion[]> {
  try {
    const actuales = await obtenerInfraccionesLocales();
    const actualizadas = [infraccion, ...actuales];
    await AsyncStorage.setItem(INFRACCIONES_KEY, JSON.stringify(actualizadas));
    return actualizadas;
  } catch (error) {
    console.error("Error al guardar infracción localmente:", error);
    throw new Error("No se pudo guardar la infracción en el almacenamiento local.");
  }
}

export async function actualizarInfraccionesLocales(infracciones: Infraccion[]): Promise<void> {
  try {
    await AsyncStorage.setItem(INFRACCIONES_KEY, JSON.stringify(infracciones));
  } catch (error) {
    console.error("Error al actualizar infracciones locales:", error);
    throw error;
  }
}

export async function eliminarInfraccionLocal(id: string): Promise<Infraccion[]> {
  try {
    const actuales = await obtenerInfraccionesLocales();
    const filtradas = actuales.filter((item) => item.id !== id);
    await AsyncStorage.setItem(INFRACCIONES_KEY, JSON.stringify(filtradas));
    return filtradas;
  } catch (error) {
    console.error("Error al eliminar infracción:", error);
    throw error;
  }
}

export async function obtenerUltimaSincronizacion(): Promise<string | null> {
  try {
    return await AsyncStorage.getItem(ULTIMA_SYNC_KEY);
  } catch {
    return null;
  }
}

export async function registrarUltimaSincronizacion(): Promise<string> {
  const ahora = new Date().toISOString();
  try {
    await AsyncStorage.setItem(ULTIMA_SYNC_KEY, ahora);
  } catch (error) {
    console.error("Error al guardar última sincronización:", error);
  }
  return ahora;
}
