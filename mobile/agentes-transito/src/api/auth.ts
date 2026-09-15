import { apiRequest, setToken } from "./client";

export interface LoginPayload {
  usuario: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: {
    id: number;
    nombre: string;
    rol: string;
  };
}

export async function login(payload: LoginPayload): Promise<LoginResponse> {
  const data = await apiRequest<LoginResponse>("/auth/login", {
    method: "POST",
    body: payload,
    auth: false,
  });
  await setToken(data.token);
  return data;
}

export async function logout(): Promise<void> {
  await setToken(null);
}
