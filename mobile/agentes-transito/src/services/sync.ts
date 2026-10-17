import { ApiError } from "../api/client";
import { Infraccion } from "../types/infraccion";
import {
  actualizarInfraccionesLocales,
  obtenerInfraccionesLocales,
  registrarUltimaSincronizacion,
} from "./storage";
import {
  sincronizarBoleta,
  subirFotoEvidencia,
  transformarInfraccionABoleta,
} from "./supabase";

export interface SyncResultado {
  exitosos: number;
  fallidos: number;
  totalPendientes: number;
  mensaje: string;
  exito: boolean;
  totalSincronizadas: number;
  errores?: string[];
  /** El servidor rechazó el token (401): hay que volver a iniciar sesión. */
  requiereLogin?: boolean;
}

export const MENSAJE_SESION_EXPIRADA =
  "Tu sesión expiró. Inicia sesión nuevamente; las boletas pendientes se conservan en el dispositivo.";

export async function sincronizarInfraccionesLocales(): Promise<SyncResultado> {
  const todas = await obtenerInfraccionesLocales();
  const pendientes = todas.filter((item) => item.estado === "pendiente");

  if (pendientes.length === 0) {
    return {
      exitosos: 0,
      fallidos: 0,
      totalPendientes: 0,
      mensaje: "No hay boletas pendientes de sincronizar.",
      exito: true,
      totalSincronizadas: 0,
      errores: [],
    };
  }

  // Solo se guardan las boletas que cambiaron; al final se fusionan con lo que haya
  // en almacenamiento en ese momento para no perder boletas capturadas mientras
  // la sincronización estaba en curso.
  const sincronizadasPorId = new Map<string, Infraccion>();
  let exitosos = 0;
  let fallidos = 0;
  let requiereLogin = false;

  for (const item of pendientes) {
    try {
      // Subir fotos locales de evidencia al servidor municipal
      const [fotoPlaca, fotoContexto, fotoDocumento] = await Promise.all([
        subirFotoEvidencia(item.folio, "placa", item.evidencias?.fotoPlaca),
        subirFotoEvidencia(item.folio, "contexto", item.evidencias?.fotoContexto),
        subirFotoEvidencia(item.folio, "documento", item.evidencias?.fotoDocumento),
      ]);

      const itemActualizado: Infraccion = {
        ...item,
        evidencias: {
          fotoPlaca: fotoPlaca ?? null,
          fotoContexto: fotoContexto ?? null,
          fotoDocumento: fotoDocumento ?? null,
        },
      };

      const boletaPayload = transformarInfraccionABoleta(itemActualizado);
      await sincronizarBoleta(boletaPayload);

      sincronizadasPorId.set(item.id, {
        ...itemActualizado,
        estado: "sincronizada",
        sincronizadoEn: new Date().toISOString(),
      });
      exitosos++;
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        // Token ausente, vencido o agente dado de baja: no tiene caso seguir.
        // La boleta (y las restantes) permanecen pendientes.
        requiereLogin = true;
        break;
      }
      console.error("Error sincronizando boleta con el servidor:", item.folio, err);
      fallidos++;
    }
  }

  if (exitosos > 0) {
    const actuales = await obtenerInfraccionesLocales();
    await actualizarInfraccionesLocales(
      actuales.map((item) => sincronizadasPorId.get(item.id) ?? item)
    );
    await registrarUltimaSincronizacion();
  }

  if (requiereLogin) {
    const restantes = pendientes.length - exitosos;
    return {
      exitosos,
      fallidos: restantes,
      totalPendientes: pendientes.length,
      mensaje: MENSAJE_SESION_EXPIRADA,
      exito: false,
      totalSincronizadas: exitosos,
      errores: [MENSAJE_SESION_EXPIRADA],
      requiereLogin: true,
    };
  }

  return {
    exitosos,
    fallidos,
    totalPendientes: pendientes.length,
    mensaje:
      fallidos === 0
        ? `Se sincronizaron ${exitosos} boleta${exitosos === 1 ? "" : "s"} exitosamente con el servidor.`
        : `Se sincronizaron ${exitosos} boletas (${fallidos} fallaron por conexión).`,
    exito: fallidos === 0,
    totalSincronizadas: exitosos,
    errores: fallidos > 0 ? [`${fallidos} boletas no se pudieron sincronizar por conexión a internet.`] : [],
  };
}
