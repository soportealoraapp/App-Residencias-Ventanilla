import React, { createContext, useContext, useEffect, useMemo, useState } from "react";
import { Infraccion, FaltaCatalogo } from "../types/infraccion";
import {
  actualizarInfraccionesLocales,
  eliminarInfraccionLocal,
  guardarInfraccionLocal,
  obtenerInfraccionesLocales,
  obtenerUltimaSincronizacion,
} from "../services/storage";
import { sincronizarInfraccionesLocales, SyncResultado } from "../services/sync";
import {
  fetchCatalogoInfracciones,
  fetchParametros,
} from "../services/supabase";
import { CATALOGO_FALTAS_URIANGATO } from "../constants/catalogoInfracciones";

interface InfraccionesContextValue {
  infracciones: Infraccion[];
  pendientes: Infraccion[];
  sincronizadas: Infraccion[];
  totalPendientes: number;
  totalSincronizadas: number;
  isLoading: boolean;
  isSyncing: boolean;
  ultimaSincronizacion: string | null;
  valorUma: number;
  catalogo: FaltaCatalogo[];
  guardarInfraccion: (infraccion: Infraccion) => Promise<void>;
  sincronizar: () => Promise<SyncResultado>;
  eliminar: (id: string) => Promise<void>;
  recargar: () => Promise<void>;
}

const InfraccionesContext = createContext<InfraccionesContextValue | undefined>(undefined);

// Infracciones muestra iniciales representativas de Uriangato
const DATOS_INICIALES_DEMO: Infraccion[] = [
  {
    id: "demo-inf-01",
    folio: "BOLETA-URI-2026-0038",
    agente: {
      placa: "AGT-204",
      nombre: "Oficial Carlos Mendoza Ruiz",
      rol: "Agente Vial",
    },
    generales: {
      fecha: "2026-09-30",
      hora: "10:15",
      lugar: "Av. Hidalgo esq. Salvador Urrutia, Zona Centro, Uriangato, Gto.",
      coordenadas: {
        latitud: 20.1419,
        longitud: -101.1764,
      },
      referencia: "Frente al Mercado Municipal",
    },
    infractor: {
      conductorAusente: false,
      nombre: "Roberto García Zavala",
      domicilio: "Calle Mina #124, Uriangato, Gto.",
      numeroLicencia: "GTO-LIC-98231",
    },
    vehiculo: {
      placas: "GTC-441-E",
      sinPlacas: false,
      marca: "Nissan",
      lineaModelo: "Versa 2021",
      color: "Plata",
      tipo: "particular",
    },
    falta: CATALOGO_FALTAS_URIANGATO[0],
    hechos: "El conductor del vehículo particular no respetó la señal de alto preventivo/fijo con luz roja del semáforo en el crucero señalado, cruzando con flujo peatonal activo.",
    garantiasRetenidas: ["licencia"],
    evidencias: {
      fotoPlaca: "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=500&q=80",
      fotoContexto: "https://images.unsplash.com/photo-1517524008697-84bbe3c3fd98?w=500&q=80",
      fotoDocumento: "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=500&q=80",
    },
    estado: "pendiente",
    creadoEn: "2026-09-30T10:15:00.000Z",
    sincronizadoEn: null,
  },
  {
    id: "demo-inf-02",
    folio: "BOLETA-URI-2026-0037",
    agente: {
      placa: "AGT-204",
      nombre: "Oficial Carlos Mendoza Ruiz",
      rol: "Agente Vial",
    },
    generales: {
      fecha: "2026-09-29",
      hora: "16:40",
      lugar: "Calle 16 de Septiembre frente a rampa escolar, Uriangato, Gto.",
      coordenadas: {
        latitud: 20.1432,
        longitud: -101.1788,
      },
      referencia: "Junto a Escuela Primaria Benito Juárez",
    },
    infractor: {
      conductorAusente: true,
      nombre: "Conductor Ausente",
      domicilio: "No disponible en sitio",
    },
    vehiculo: {
      placas: "GNX-902-B",
      sinPlacas: false,
      marca: "Chevrolet",
      lineaModelo: "Aveo 2018",
      color: "Rojo Tinto",
      tipo: "particular",
    },
    falta: CATALOGO_FALTAS_URIANGATO[3],
    hechos: "Vehículo estacionado obstruyendo completamente la rampa de acceso peatonal y personas con discapacidad, sin conductor a bordo tras 15 minutos de aviso sonoro.",
    garantiasRetenidas: ["placa"],
    evidencias: {
      fotoPlaca: "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=500&q=80",
      fotoContexto: "https://images.unsplash.com/photo-1506521781263-d8422e82f27a?w=500&q=80",
      fotoDocumento: "https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=500&q=80",
    },
    estado: "sincronizada",
    creadoEn: "2026-09-29T16:40:00.000Z",
    sincronizadoEn: "2026-09-29T18:00:00.000Z",
  },
];

export function InfraccionesProvider({ children }: { children: React.ReactNode }) {
  const [infracciones, setInfracciones] = useState<Infraccion[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isSyncing, setIsSyncing] = useState(false);
  const [ultimaSincronizacion, setUltimaSincronizacion] = useState<string | null>(null);
  const [valorUma, setValorUma] = useState<number>(117.31);
  const [catalogo, setCatalogo] = useState<FaltaCatalogo[]>(CATALOGO_FALTAS_URIANGATO);

  const cargarDatos = async () => {
    setIsLoading(true);
    try {
      // 1. Cargar infracciones locales
      let items = await obtenerInfraccionesLocales();
      if (!items || items.length === 0) {
        await actualizarInfraccionesLocales(DATOS_INICIALES_DEMO);
        items = DATOS_INICIALES_DEMO;
      }
      setInfracciones(items);

      const ultimaSync = await obtenerUltimaSincronizacion();
      setUltimaSincronizacion(ultimaSync);

      // 2. Cargar UMA y parámetros desde Supabase en background
      fetchParametros()
        .then((params) => {
          if (params.valor_uma_vigente) {
            const parsed = parseFloat(params.valor_uma_vigente);
            if (!isNaN(parsed) && parsed > 0) {
              setValorUma(parsed);
            }
          }
        })
        .catch(() => {});

      // 3. Cargar catálogo oficial desde Supabase
      fetchCatalogoInfracciones()
        .then((dbFaltas) => {
          if (dbFaltas && dbFaltas.length > 0) {
            const mapped: FaltaCatalogo[] = dbFaltas.map((f) => ({
              id: `falta-${f.id}`,
              fundamentoLegal: f.fundamento_legal,
              descripcion: f.descripcion,
              categoria: f.categoria,
              montoMinUma: Number(f.monto_min_uma),
              montoMaxUma: Number(f.monto_max_uma),
            }));
            setCatalogo(mapped);
          }
        })
        .catch(() => {});
    } catch (err) {
      console.error("Error cargando infracciones:", err);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    cargarDatos();
  }, []);

  const guardarInfraccion = async (nueva: Infraccion) => {
    const listaActualizada = await guardarInfraccionLocal(nueva);
    setInfracciones(listaActualizada);
  };

  const sincronizar = async (): Promise<SyncResultado> => {
    setIsSyncing(true);
    try {
      const res = await sincronizarInfraccionesLocales();
      const actualizadas = await obtenerInfraccionesLocales();
      setInfracciones(actualizadas);
      const ultima = await obtenerUltimaSincronizacion();
      setUltimaSincronizacion(ultima);
      return res;
    } finally {
      setIsSyncing(false);
    }
  };

  const eliminar = async (id: string) => {
    const restantes = await eliminarInfraccionLocal(id);
    setInfracciones(restantes);
  };

  const pendientes = useMemo(
    () => infracciones.filter((i) => i.estado === "pendiente"),
    [infracciones]
  );

  const sincronizadas = useMemo(
    () => infracciones.filter((i) => i.estado === "sincronizada"),
    [infracciones]
  );

  const value = useMemo(
    () => ({
      infracciones,
      pendientes,
      sincronizadas,
      totalPendientes: pendientes.length,
      totalSincronizadas: sincronizadas.length,
      isLoading,
      isSyncing,
      ultimaSincronizacion,
      valorUma,
      catalogo,
      guardarInfraccion,
      sincronizar,
      eliminar,
      recargar: cargarDatos,
    }),
    [infracciones, pendientes, sincronizadas, isLoading, isSyncing, ultimaSincronizacion, valorUma, catalogo]
  );

  return (
    <InfraccionesContext.Provider value={value}>
      {children}
    </InfraccionesContext.Provider>
  );
}

export function useInfracciones(): InfraccionesContextValue {
  const ctx = useContext(InfraccionesContext);
  if (!ctx) throw new Error("useInfracciones debe usarse dentro de <InfraccionesProvider>");
  return ctx;
}
