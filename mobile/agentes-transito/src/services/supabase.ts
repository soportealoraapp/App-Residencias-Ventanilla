import { createClient } from "@supabase/supabase-js";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { Infraccion } from "../types/infraccion";
import { ApiError, apiRequest } from "../api/client";

// Las credenciales de Supabase pueden estar en .env (EXPO_PUBLIC_*) si se usa como réplica secundaria
const SUPABASE_URL = process.env.EXPO_PUBLIC_SUPABASE_URL ?? "https://placeholder.supabase.co";
const SUPABASE_ANON_KEY = process.env.EXPO_PUBLIC_SUPABASE_ANON_KEY ?? "placeholder";

export const supabase = createClient(SUPABASE_URL, SUPABASE_ANON_KEY, {
  auth: {
    storage: AsyncStorage as any,
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: false,
  },
});

// ─── Types from DB ─────────────────────────────────────────────────────────────

export interface AgenteRow {
  id: number;
  placa: string;
  nombre_completo: string;
  rol: string;
  sector: string;
  activo: number;
}

export interface CatalogoInfraccionRow {
  id: number;
  fundamento_legal: string;
  descripcion: string;
  categoria: string;
  monto_min_uma: number;
  monto_max_uma: number;
  activo: number;
  orden: number;
}

export interface ParametroRow {
  id: number;
  clave: string;
  valor: string;
  descripcion: string;
  tipo: string;
}

export interface BoletaInsert {
  folio: string;
  agente_placa: string;
  agente_nombre: string;
  fecha_infraccion: string;
  hora_infraccion: string;
  lugar: string;
  latitud?: number | null;
  longitud?: number | null;
  conductor_ausente: boolean;
  infractor_nombre?: string | null;
  infractor_domicilio?: string | null;
  infractor_licencia?: string | null;
  vehiculo_placas?: string | null;
  sin_placas: boolean;
  vehiculo_marca?: string | null;
  vehiculo_linea?: string | null;
  vehiculo_color?: string | null;
  vehiculo_tipo: string;
  /** JSON serializado del array completo de FaltaCatalogo[] (multi-infracciones) */
  faltas_json: string;
  /** Fundamento legal de la falta PRINCIPAL (primera del array) — columna existente en DB */
  falta_fundamento_legal: string;
  /** Descripción de la falta PRINCIPAL (primera del array) */
  falta_descripcion: string;
  /** Categoría de la falta PRINCIPAL (primera del array) */
  falta_categoria: string;
  /** Suma total de montos mínimos en UMA de TODAS las faltas */
  falta_monto_min_uma: number;
  /** Suma total de montos máximos en UMA de TODAS las faltas */
  falta_monto_max_uma: number;
  hechos: string;
  garantias_retenidas: string[];
  inventario_grua?: string | null;
  foto_placa_url?: string | null;
  foto_contexto_url?: string | null;
  foto_documento_url?: string | null;
  estado: string;
  creado_en_dispositivo: string;
}

// ─── Transformer ────────────────────────────────────────────────────────────────

export function transformarInfraccionABoleta(inf: Infraccion): BoletaInsert {
  const faltas = inf.faltas ?? [];
  const montoMinTotal = faltas.reduce((sum, f) => sum + Number(f.montoMinUma), 0);
  const montoMaxTotal = faltas.reduce((sum, f) => sum + Number(f.montoMaxUma), 0);
  // Resumen de la falta principal (primera) para columnas planas de consulta rápida
  const principal = faltas[0];

  return {
    folio: inf.folio,
    agente_placa: inf.agente.placa,
    agente_nombre: inf.agente.nombre,
    fecha_infraccion: inf.generales.fecha,
    hora_infraccion: inf.generales.hora,
    lugar: inf.generales.lugar,
    latitud: inf.generales.coordenadas?.latitud ?? null,
    longitud: inf.generales.coordenadas?.longitud ?? null,
    conductor_ausente: !!inf.infractor.conductorAusente,
    infractor_nombre: inf.infractor.nombre || null,
    infractor_domicilio: inf.infractor.domicilio || null,
    infractor_licencia: inf.infractor.numeroLicencia || null,
    vehiculo_placas: inf.vehiculo.placas || null,
    sin_placas: !!inf.vehiculo.sinPlacas,
    vehiculo_marca: inf.vehiculo.marca || null,
    vehiculo_linea: inf.vehiculo.lineaModelo || null,
    vehiculo_color: inf.vehiculo.color || null,
    vehiculo_tipo: inf.vehiculo.tipo || "particular",
    faltas_json: JSON.stringify(faltas),
    falta_fundamento_legal: principal?.fundamentoLegal ?? "",
    falta_descripcion: principal?.descripcion ?? "",
    falta_categoria: principal?.categoria ?? "",
    falta_monto_min_uma: montoMinTotal,
    falta_monto_max_uma: montoMaxTotal,
    hechos: inf.hechos || "",
    garantias_retenidas: (inf.garantiasRetenidas as string[]) || [],
    inventario_grua: inf.detalleGarantia?.inventarioGrua || null,
    foto_placa_url: inf.evidencias?.fotoPlaca || null,
    foto_contexto_url: inf.evidencias?.fotoContexto || null,
    foto_documento_url: inf.evidencias?.fotoDocumento || null,
    estado: "recibida",
    creado_en_dispositivo: inf.creadoEn,
  };
}


// ─── API helpers ────────────────────────────────────────────────────────────────

/** Autentica al agente únicamente mediante el backend municipal. */
export async function loginAgente(placa: string, password: string): Promise<AgenteRow> {
  const placaLimpia = placa.toUpperCase().trim();
  try {
    const res = await apiRequest<{
      success: boolean;
      agente: AgenteRow;
    }>("/api/agentes/login", {
      method: "POST",
      body: { placa: placaLimpia, password },
      auth: false,
      timeoutMs: 10000,
    });

    if (!res?.success || !res.agente) {
      throw new Error("El servidor devolvió una respuesta de autenticación incompleta.");
    }
    return res.agente;
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      throw new Error("Credenciales incorrectas o agente inactivo.");
    }
    if (error instanceof ApiError && error.status === 422) {
      throw new Error("Ingrese una placa y contraseña válidas.");
    }
    if (error instanceof ApiError) {
      throw new Error("El servicio de autenticación no está disponible. Inténtelo más tarde.");
    }
    throw new Error("No se pudo conectar con el servidor. Revise su conexión e inténtelo de nuevo.");
  }
}

/** Cargar catálogo oficial de infracciones desde CodeIgniter 4 (o caché local) */
export async function fetchCatalogoInfracciones(): Promise<CatalogoInfraccionRow[]> {
  // 1. Intento primario: CI4 checklist oficial (15 categorías, 64 casillas de Uriangato)
  try {
    const res = await apiRequest<{
      success: boolean;
      categorias: Record<string, any[]>;
    }>("/api/checklist-infracciones", {
      auth: false,
    });

    if (res?.success && res.categorias) {
      const faltas: CatalogoInfraccionRow[] = [];
      let orden = 1;
      for (const [catName, items] of Object.entries(res.categorias)) {
        for (const item of items) {
          faltas.push({
            id: item.checklist_item_id || orden,
            fundamento_legal: item.fundamento_legal || item.etiqueta_casilla,
            descripcion: item.descripcion_oficial || item.etiqueta_casilla,
            categoria: catName,
            monto_min_uma: item.monto_min_uma ?? 2.0,
            monto_max_uma: item.monto_max_uma ?? 5.0,
            activo: 1,
            orden: orden++,
          });
        }
      }

      if (faltas.length > 0) {
        await AsyncStorage.setItem("@catalogo_infracciones_cache_v1", JSON.stringify(faltas));
        return faltas;
      }
    }
  } catch (ci4Err) {
    console.warn("[catalogo] Error al consultar /api/checklist-infracciones, probando alternativas:", ci4Err);
  }

  // 2. Intento secundario: Supabase
  try {
    const { data, error } = await supabase
      .from("catalogo_infracciones_app")
      .select("*")
      .eq("activo", 1)
      .order("orden", { ascending: true });

    if (!error && data && data.length > 0) {
      await AsyncStorage.setItem("@catalogo_infracciones_cache_v1", JSON.stringify(data));
      return data as CatalogoInfraccionRow[];
    }
  } catch (err) {
    console.warn("Error cargando catálogo desde Supabase, usando cache:", err);
  }

  // 3. Fallback de caché local en el dispositivo
  try {
    const cached = await AsyncStorage.getItem("@catalogo_infracciones_cache_v1");
    if (cached) return JSON.parse(cached);
  } catch {}

  return [];
}

/** Cargar parámetros del sistema (UMA vigente, versión, fotos) desde CI4 */
export async function fetchParametros(): Promise<Record<string, string>> {
  // 1. Intento primario: CI4 /api/parametros
  try {
    const res = await apiRequest<{ success: boolean; parametros: Record<string, string> }>("/api/parametros", {
      auth: false,
    });
    if (res?.success && res.parametros) {
      await AsyncStorage.setItem("@parametros_app_cache_v1", JSON.stringify(res.parametros));
      return res.parametros;
    }
  } catch (ci4Err) {
    console.warn("[params] Error CI4 /api/parametros, probando alternativas:", ci4Err);
  }

  // 2. Intento secundario: Supabase
  try {
    const { data, error } = await supabase
      .from("parametros_app")
      .select("clave, valor");

    if (!error && data && data.length > 0) {
      const map: Record<string, string> = {};
      data.forEach((p: any) => { map[p.clave] = p.valor; });
      await AsyncStorage.setItem("@parametros_app_cache_v1", JSON.stringify(map));
      return map;
    }
  } catch (err) {
    console.warn("Error cargando parámetros desde Supabase, usando cache:", err);
  }

  // 3. Fallback de caché local en el dispositivo
  try {
    const cached = await AsyncStorage.getItem("@parametros_app_cache_v1");
    if (cached) return JSON.parse(cached);
  } catch {}

  return {
    valor_uma_vigente: "117.31",
    fotos_obligatorias: "3",
    app_version: "1.0.0",
  };
}

/** Sincronizar una boleta pendiente al servidor municipal CodeIgniter 4 */
export async function sincronizarBoleta(boleta: BoletaInsert): Promise<void> {
  // 1. Intento primario: Enviar a CI4 /api/infracciones/sincronizar
  try {
    const res = await apiRequest<{ success: boolean; message?: string }>("/api/infracciones/sincronizar", {
      method: "POST",
      body: boleta,
      auth: true,
    });
    if (res?.success) {
      return;
    }
  } catch (ci4Err) {
    console.warn("[sync] Error enviando boleta a CI4, probando Supabase como respaldo:", ci4Err);
  }

  // 2. Respaldo secundario si CI4 no respondió
  const { error } = await supabase
    .from("boletas_infracciones")
    .upsert(boleta, { onConflict: "folio" });

  if (error) {
    console.error("Error upsert boleta Supabase:", error);
    throw new Error(error.message);
  }
}

/** Subir foto de evidencia al servidor municipal (o Supabase si aplica) */
export async function subirFotoEvidencia(
  folio: string,
  tipo: "placa" | "contexto" | "documento",
  uri: string | null | undefined
): Promise<string | null> {
  if (!uri) return null;

  // Si ya es una URL remota HTTP pública válida, la conservamos
  if (uri.startsWith("http://") || uri.startsWith("https://")) {
    return uri;
  }

  // 1. Intento primario: Subida a CodeIgniter 4 servidor local
  try {
    const formData = new FormData();
    formData.append("folio", folio);
    formData.append("tipo", tipo);
    const filename = `${tipo}_${Date.now()}.jpg`;
    formData.append("foto", {
      uri,
      name: filename,
      type: "image/jpeg",
    } as any);

    const res = await apiRequest<{ success: boolean; url: string }>("/api/infracciones/evidencias", {
      method: "POST",
      body: formData,
      auth: true,
    });

    if (res?.success && res.url) {
      return res.url;
    }
  } catch (ci4Err) {
    console.warn(`[storage] Error subiendo evidencia a CI4, probando Supabase:`, ci4Err);
  }

  // 2. Respaldo secundario: Supabase Storage
  try {
    const res = await fetch(uri);
    const blob = await res.blob();
    const mime = blob.type || "image/jpeg";
    const ext = mime.includes("png") ? "png" : "jpg";
    const folioLimpio = folio.replace(/[^A-Za-z0-9_-]/g, "_");
    const filePath = `${folioLimpio}/${tipo}_${Date.now()}.${ext}`;

    const { error: uploadError } = await supabase.storage
      .from("evidencias")
      .upload(filePath, blob, {
        contentType: mime,
        upsert: true,
      });

    if (!uploadError) {
      const { data } = supabase.storage.from("evidencias").getPublicUrl(filePath);
      return data.publicUrl;
    }
  } catch (err) {
    console.warn(`[storage] Excepción subiendo evidencia ${tipo}:`, err);
  }

  return uri;
}
