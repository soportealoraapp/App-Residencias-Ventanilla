import Constants from "expo-constants";

// Config centralizada de la app, alimentada por variables EXPO_PUBLIC_*

const PUERTO_BACKEND_LOCAL = "8080";

/** localhost o una IP de red privada: indica un backend local de desarrollo. */
function esHostLocal(host: string): boolean {
  return (
    host === "localhost" ||
    host === "127.0.0.1" ||
    /^10\./.test(host) ||
    /^192\.168\./.test(host) ||
    /^172\.(1[6-9]|2\d|3[01])\./.test(host)
  );
}

/** Host de una URL (sin protocolo, puerto ni ruta). */
function hostDe(url: string): string {
  return /^[a-z]+:\/\/([^/:?#]+)/i.exec(url)?.[1] ?? "";
}

/** Cambia el host de una URL conservando protocolo, puerto y ruta. */
function conHost(url: string, host: string): string {
  return url.replace(/^([a-z]+:\/\/)[^/:?#]+/i, `$1${host}`);
}

/**
 * IP de la PC que ejecuta `npx expo start`, tal como la ve el teléfono
 * (p. ej. "192.168.0.105:8081" → "192.168.0.105"). Solo existe en desarrollo.
 */
function hostDelServidorExpo(): string | null {
  const hostUri = Constants.expoConfig?.hostUri ?? null;
  const host = hostUri?.split(":")[0];
  return host ? host : null;
}

const resolveApiBaseUrl = (): string => {
  const envUrl = (process.env.EXPO_PUBLIC_API_BASE_URL || "").trim();
  const urlLocal = envUrl || `http://localhost:${PUERTO_BACKEND_LOCAL}`;
  const hostEnv = hostDe(urlLocal);

  // Web
  if (typeof window !== "undefined" && window.location?.origin) {
    if (window.location.port === "8081") {
      // Expo web en desarrollo: si el .env apunta a un backend local, se usa el mismo
      // host con el que se abrió la página (localhost en la PC). Así no depende de
      // que la IP del .env coincida con la red actual.
      return (esHostLocal(hostEnv) ? conHost(urlLocal, window.location.hostname) : urlLocal).replace(/\/+$/, "");
    }
    return window.location.origin.replace(/\/+$/, "");
  }

  // Teléfono / emulador en desarrollo: si el .env apunta a un backend local, se usa
  // la IP de la PC que sirve Expo, que se detecta sola aunque cambie de red.
  const hostExpo = __DEV__ ? hostDelServidorExpo() : null;
  if (hostExpo && esHostLocal(hostExpo) && esHostLocal(hostEnv)) {
    return conHost(urlLocal, hostExpo).replace(/\/+$/, "");
  }

  return urlLocal.replace(/\/+$/, "");
};

export const API_BASE_URL = resolveApiBaseUrl();
export const APP_ENV = process.env.EXPO_PUBLIC_APP_ENV ?? "development";

if (__DEV__) {
  console.log(`[config] API_BASE_URL = ${API_BASE_URL}`);
}
