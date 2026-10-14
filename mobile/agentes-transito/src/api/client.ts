import * as SecureStore from "expo-secure-store";
import { API_BASE_URL } from "../constants/config";

const AUTH_TOKEN_KEY = "auth_token";

export class ApiError extends Error {
  constructor(public status: number, message: string, public body?: unknown) {
    super(message);
    this.name = "ApiError";
  }
}

async function getToken(): Promise<string | null> {
  return SecureStore.getItemAsync(AUTH_TOKEN_KEY);
}

export async function setToken(token: string | null): Promise<void> {
  if (token) {
    await SecureStore.setItemAsync(AUTH_TOKEN_KEY, token);
  } else {
    await SecureStore.deleteItemAsync(AUTH_TOKEN_KEY);
  }
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
    const token = await getToken();
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
