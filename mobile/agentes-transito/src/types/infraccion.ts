export type TipoVehiculo =
  | "particular"
  | "transporte_publico"
  | "carga"
  | "motocicleta"
  | "otro";

export type GarantiaRetenida =
  | "licencia"
  | "placa"
  | "tarjeta_circulacion"
  | "vehiculo";

export interface FaltaCatalogo {
  id: string;
  fundamentoLegal: string;
  descripcion: string;
  categoria: string;
  montoMinUma: number;
  montoMaxUma: number;
}

export interface InfraccionEvidencias {
  fotoPlaca: string;     // URI o base64
  fotoContexto: string;  // URI o base64
  fotoDocumento: string; // URI o base64
}

export interface Infraccion {
  id: string;
  folio: string;
  agente: {
    id?: number;
    placa: string;
    nombre: string;
    rol?: string;
  };
  generales: {
    fecha: string;       // Formato YYYY-MM-DD o DD/MM/YYYY
    hora: string;        // Formato HH:mm
    lugar: string;
    coordenadas?: {
      latitud: number;
      longitud: number;
    } | null;
    referencia?: string;
  };
  infractor: {
    conductorAusente: boolean;
    nombre: string;
    domicilio: string;
    numeroLicencia?: string;
  };
  vehiculo: {
    placas: string;
    sinPlacas: boolean;
    marca: string;
    lineaModelo: string;
    color: string;
    tipo: TipoVehiculo;
  };
  falta: FaltaCatalogo;
  hechos: string;
  garantiasRetenidas: GarantiaRetenida[];
  detalleGarantia?: {
    inventarioGrua?: string;
    observaciones?: string;
  };
  evidencias: InfraccionEvidencias;
  estado: "pendiente" | "sincronizada" | "error_sincronizacion";
  creadoEn: string;
  sincronizadoEn?: string | null;
}
