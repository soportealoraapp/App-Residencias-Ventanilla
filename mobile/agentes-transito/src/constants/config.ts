// Config centralizada de la app, alimentada por variables EXPO_PUBLIC_*
const resolveApiBaseUrl = (): string => {
  if (typeof window !== "undefined" && window.location?.origin) {
    if (window.location.port === "8081") {
      return (process.env.EXPO_PUBLIC_API_BASE_URL || "http://localhost:8080").replace(/\/+$/, "");
    }
    return window.location.origin.replace(/\/+$/, "");
  }

  const envUrl = process.env.EXPO_PUBLIC_API_BASE_URL;
  if (envUrl) {
    return envUrl.replace(/\/+$/, "");
  }

  return "http://localhost:8080";
};

export const API_BASE_URL = resolveApiBaseUrl();
export const APP_ENV = process.env.EXPO_PUBLIC_APP_ENV ?? "development";
