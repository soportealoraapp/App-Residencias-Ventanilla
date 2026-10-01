import { Infraccion } from "../types/infraccion";
import {
  actualizarInfraccionesLocales,
  obtenerInfraccionesLocales,
  registrarUltimaSincronizacion,
} from "./storage";
import { sincronizarBoleta, transformarInfraccionABoleta } from "./supabase";

export interface SyncResultado {
  exitosos: number;
  fallidos: number;
  totalPendientes: number;
  mensaje: string;
}

export async function sincronizarInfraccionesLocales(): Promise<SyncResultado> {
  const todas = await obtenerInfraccionesLocales();
  const pendientes = todas.filter((item) => item.estado === "pendiente");

  if (pendientes.length === 0) {
    return {
      exitosos: 0,
      fallidos: 0,
      totalPendientes: 0,
      mensaje: "No hay boletas pendientes de sincronizar.",
    };
  }

  const actualizadas = [...todas];
  let exitosos = 0;
  let fallidos = 0;

  for (let i = 0; i < actualizadas.length; i++) {
    const item = actualizadas[i];
    if (item.estado === "pendiente") {
      try {
        const boletaPayload = transformarInfraccionABoleta(item);
        await sincronizarBoleta(boletaPayload);

        actualizadas[i] = {
          ...item,
          estado: "sincronizada",
          sincronizadoEn: new Date().toISOString(),
        };
        exitosos++;
      } catch (err) {
        console.error("Error sincronizando boleta a Supabase:", item.folio, err);
        fallidos++;
      }
    }
  }

  await actualizarInfraccionesLocales(actualizadas);
  if (exitosos > 0) {
    await registrarUltimaSincronizacion();
  }

  return {
    exitosos,
    fallidos,
    totalPendientes: pendientes.length,
    mensaje:
      fallidos === 0
        ? `Se sincronizaron ${exitosos} boleta${exitosos === 1 ? "" : "s"} exitosamente con Supabase.`
        : `Se sincronizaron ${exitosos} boletas (${fallidos} fallaron por conexión).`,
  };
}
