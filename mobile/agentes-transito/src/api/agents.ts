import { apiRequest } from "./client";

interface RegisterAgentePayload {
  placa: string;
  nombreCompleto: string;
  password: string;
}

interface RegisterAgenteResponse {
  success: true;
  message: string;
}

export async function registerAgente(payload: RegisterAgentePayload): Promise<RegisterAgenteResponse> {
  return apiRequest<RegisterAgenteResponse>("/agentes/registro", {
    method: "POST",
    auth: false,
    body: {
      placa: payload.placa,
      nombre_completo: payload.nombreCompleto,
      password: payload.password,
    },
  });
}
