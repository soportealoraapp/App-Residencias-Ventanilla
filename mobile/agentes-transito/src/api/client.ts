import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";
import { API_BASE_URL } from "../constants/config";

/** Clave de un esquema anterior; solo se borra, nunca se usa para autenticar. */
const AUTH_TOKEN_KEY = "auth_token";
/** Token Bearer emitido por POST /api/agentes/login. */
const AGENT_TOKEN_KEY = "agent_api_token_v1";

export class ApiError extends Error {
  constructor(public status: number, message: string, public body?: unknown) {
    super(message);
    this.name = "ApiError";
  }
}

// En web no hay almacenamiento seguro: el token vive en sessionStorage y se pierde
// al cerrar la pestaña. En nativo se guarda cifrado con expo-secure-store.
function webSessionStorage(): Storage | null {
  return typeof sessionStorage !== "undefined" ? sessionStorage : null;
}

export async function getAgentToken(): Promise<string | null> {
  if (Platform.OS === "web") return webSessionStorage()?.getItem(AGENT_TOKEN_KEY) ?? null;
  return SecureStore.getItemAsync(AGENT_TOKEN_KEY);
}

export async function saveAgentToken(token: string): Promise<void> {
  if (Platform.OS === "web") {
    webSessionStorage()?.setItem(AGENT_TOKEN_KEY, token);
    return;
  }
  await SecureStore.setItemAsync(AGENT_TOKEN_KEY, token);
}

export async function clearAgentToken(): Promise<void> {
  if (Platform.OS === "web") {
    webSessionStorage()?.removeItem(AGENT_TOKEN_KEY);
  } else {
    await SecureStore.deleteItemAsync(AGENT_TOKEN_KEY);
  }
  await clearObsoleteAgentToken();
}

export async function clearObsoleteAgentToken(): Promise<void> {
  if (Platform.OS === "web") {
    if (typeof localStorage !== "undefined") {
      localStorage.removeItem(AUTH_TOKEN_KEY);
    }
    return;
  }
  await SecureStore.deleteItemAsync(AUTH_TOKEN_KEY);
}

type RequestOptions = Omit<RequestInit, "body"> & {
  body?: unknown;
  timeoutMs?: number;
  auth?: boolean; // adjunta el Bearer token, true por default
};


export async function apiRequest<T>(
  path: string,
  { auth = true, body, headers, timeoutMs, signal, ...options }: RequestOptions = {}
): Promise<T> {
  const isFormData = typeof FormData !== "undefined" && body instanceof FormData;
  const finalHeaders: Record<string, string> = {
    Accept: "application/json",
    ...(body && !isFormData ? { "Content-Type": "application/json" } : {}),
    ...(headers as Record<string, string>),
  };

  if (auth) {
    const token = await getAgentToken();
    if (token) finalHeaders.Authorization = `Bearer ${token}`;
  }

  const baseUrl = (API_BASE_URL || "").replace(/\/+$/, "");
  const normalizedPath = path.startsWith("/") ? path : `/${path}`;
  const fullUrl = baseUrl.endsWith("/api") && normalizedPath.startsWith("/api/")
    ? `${baseUrl}${normalizedPath.slice(4)}`
    : `${baseUrl}${normalizedPath}`;

  const controller = timeoutMs ? new AbortController() : undefined;
  const relayAbort = () => controller?.abort();
  if (signal?.aborted) {
    controller?.abort();
  } else if (controller) {
    signal?.addEventListener("abort", relayAbort, { once: true });
  }
  const timeout = controller
    ? setTimeout(() => controller.abort(), timeoutMs)
    : undefined;

  try {
    const response = await fetch(fullUrl, {
      ...options,
      headers: finalHeaders,
      signal: controller?.signal ?? signal,
      body: body ? (isFormData ? (body as any) : JSON.stringify(body)) : undefined,
    });

    const contentType = response.headers.get("content-type") ?? "";
    const data = contentType.includes("application/json")
      ? await response.json()
      : await response.text();

    if (!response.ok) {
      throw new ApiError(response.status, response.statusText, data);
    }

    return data as T;
  } catch (error) {
    if (controller?.signal.aborted && !signal?.aborted) {
      throw new Error(`La solicitud agotó el tiempo de espera (${timeoutMs} ms).`);
    }
    throw error;
  } finally {
    if (timeout) clearTimeout(timeout);
    signal?.removeEventListener("abort", relayAbort);
  }
}
