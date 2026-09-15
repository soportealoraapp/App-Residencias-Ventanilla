// Config centralizada de la app, alimentada por variables EXPO_PUBLIC_* (ver .env.example)
export const API_BASE_URL =
  process.env.EXPO_PUBLIC_API_BASE_URL ?? "http://localhost:8080/api";

export const APP_ENV = process.env.EXPO_PUBLIC_APP_ENV ?? "development";
