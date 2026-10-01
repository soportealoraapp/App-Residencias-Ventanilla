-- =====================================================================
-- MIGRACIÓN: App Agentes de Tránsito - Tablas de soporte
-- Proyecto Supabase: kuwxjtwjjefqpzubtrlc
-- =====================================================================

-- -----------------------------------------------------------------------
-- TABLA: agentes_transito
-- Agentes operativos que usan la app móvil (vinculados a users si aplica)
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.agentes_transito (
    id BIGSERIAL PRIMARY KEY,
    placa VARCHAR(20) NOT NULL UNIQUE,          -- Ej: AGT-204
    nombre_completo VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,        -- bcrypt de contraseña
    rol VARCHAR(50) NOT NULL DEFAULT 'operativo',
    sector VARCHAR(100) DEFAULT 'Zona Centro',
    activo SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- -----------------------------------------------------------------------
-- TABLA: catalogo_infracciones_app
-- Catálogo oficial de faltas del Reglamento de Movilidad de Uriangato
-- Administrable desde el panel admin del portal web
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.catalogo_infracciones_app (
    id BIGSERIAL PRIMARY KEY,
    fundamento_legal VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NOT NULL,
    categoria VARCHAR(100) NOT NULL DEFAULT 'GENERAL',
    monto_min_uma DECIMAL(8,2) NOT NULL DEFAULT 3.0,
    monto_max_uma DECIMAL(8,2) NOT NULL DEFAULT 10.0,
    activo SMALLINT NOT NULL DEFAULT 1,
    orden INT DEFAULT 0,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- -----------------------------------------------------------------------
-- TABLA: parametros_app
-- Parámetros configurables desde el panel admin (UMA, versión, etc.)
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.parametros_app (
    id BIGSERIAL PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion VARCHAR(250),
    tipo VARCHAR(30) DEFAULT 'texto',           -- texto, numero, booleano, json
    editable SMALLINT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- -----------------------------------------------------------------------
-- TABLA: boletas_infracciones
-- Boletas levantadas por los agentes (recibidas vía sincronización offline)
-- -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.boletas_infracciones (
    id BIGSERIAL PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    agente_placa VARCHAR(20) NOT NULL,
    agente_nombre VARCHAR(180),
    -- Generales
    fecha_infraccion DATE NOT NULL,
    hora_infraccion TIME NOT NULL,
    lugar TEXT NOT NULL,
    latitud DECIMAL(10,7),
    longitud DECIMAL(11,7),
    -- Infractor
    conductor_ausente BOOLEAN DEFAULT FALSE,
    infractor_nombre VARCHAR(180),
    infractor_domicilio TEXT,
    infractor_licencia VARCHAR(50),
    -- Vehículo
    vehiculo_placas VARCHAR(20),
    sin_placas BOOLEAN DEFAULT FALSE,
    vehiculo_marca VARCHAR(80),
    vehiculo_linea VARCHAR(80),
    vehiculo_color VARCHAR(60),
    vehiculo_tipo VARCHAR(40) DEFAULT 'particular',
    -- Falta
    falta_fundamento_legal VARCHAR(100),
    falta_descripcion TEXT,
    falta_categoria VARCHAR(100),
    falta_monto_min_uma DECIMAL(8,2),
    falta_monto_max_uma DECIMAL(8,2),
    -- Hechos
    hechos TEXT,
    -- Garantías
    garantias_retenidas JSONB DEFAULT '[]',
    inventario_grua VARCHAR(100),
    -- Evidencias (URLs en Supabase Storage)
    foto_placa_url TEXT,
    foto_contexto_url TEXT,
    foto_documento_url TEXT,
    -- Control
    estado VARCHAR(30) DEFAULT 'recibida',      -- recibida, procesada, archivada
    sincronizado_en TIMESTAMPTZ DEFAULT NOW(),
    creado_en_dispositivo TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    -- FK opcional a agente
    CONSTRAINT fk_agente_placa FOREIGN KEY (agente_placa)
        REFERENCES public.agentes_transito(placa)
        ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_boletas_agente ON public.boletas_infracciones(agente_placa);
CREATE INDEX IF NOT EXISTS idx_boletas_fecha ON public.boletas_infracciones(fecha_infraccion DESC);
CREATE INDEX IF NOT EXISTS idx_boletas_estado ON public.boletas_infracciones(estado);
CREATE INDEX IF NOT EXISTS idx_boletas_folio ON public.boletas_infracciones(folio);

-- -----------------------------------------------------------------------
-- RLS (Row Level Security) - Política básica: servicio tiene acceso total
-- -----------------------------------------------------------------------
ALTER TABLE public.agentes_transito    ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.boletas_infracciones ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.catalogo_infracciones_app ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.parametros_app      ENABLE ROW LEVEL SECURITY;

-- Políticas permisivas para service_role (backend PHP + sync app móvil)
CREATE POLICY "service_role_all_agentes"        ON public.agentes_transito
    FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "service_role_all_boletas"        ON public.boletas_infracciones
    FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "service_role_all_catalogo"       ON public.catalogo_infracciones_app
    FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY "service_role_all_parametros"     ON public.parametros_app
    FOR ALL TO service_role USING (true) WITH CHECK (true);

-- Lectura pública anon para catálogo, parámetros y agentes (login app móvil)
CREATE POLICY "anon_read_catalogo"   ON public.catalogo_infracciones_app
    FOR SELECT TO anon USING (activo = 1);
CREATE POLICY "anon_read_parametros" ON public.parametros_app
    FOR SELECT TO anon USING (editable >= 0);
CREATE POLICY "anon_read_agentes"    ON public.agentes_transito
    FOR SELECT TO anon USING (activo = 1);

-- Escritura/lectura anon para sincronización de boletas desde la app móvil
CREATE POLICY "anon_insert_boletas"  ON public.boletas_infracciones
    FOR INSERT TO anon WITH CHECK (true);
CREATE POLICY "anon_update_boletas"  ON public.boletas_infracciones
    FOR UPDATE TO anon USING (true) WITH CHECK (true);
CREATE POLICY "anon_select_boletas"  ON public.boletas_infracciones
    FOR SELECT TO anon USING (true);

-- -----------------------------------------------------------------------
-- DATOS INICIALES: Parámetros del sistema
-- -----------------------------------------------------------------------
INSERT INTO public.parametros_app (clave, valor, descripcion, tipo) VALUES
    ('valor_uma_vigente',        '117.31',   'Valor de la UMA vigente en pesos MXN (INEGI)', 'numero'),
    ('app_version',              '1.0.0',    'Versión actual de la app de agentes',           'texto'),
    ('app_nombre',               'Sistema de Boletas de Infracción — Uriangato', 'Nombre de la app', 'texto'),
    ('fotos_obligatorias',       '3',        'Número de fotografías obligatorias por boleta', 'numero'),
    ('municipio',                'Uriangato, Guanajuato', 'Nombre del municipio',             'texto'),
    ('sync_intervalo_segundos',  '300',      'Intervalo de sincronización automática',         'numero'),
    ('modo_estricto_fotos',      'true',     'Exigir las 3 fotos obligatorias para guardar',  'booleano')
ON CONFLICT (clave) DO NOTHING;

-- -----------------------------------------------------------------------
-- DATOS INICIALES: Agentes de prueba
-- password_hash corresponde a 'transito2026' con bcrypt cost 10
-- -----------------------------------------------------------------------
INSERT INTO public.agentes_transito (placa, nombre_completo, password_hash, rol, sector) VALUES
    ('AGT-204', 'Oficial Carlos Mendoza Ruiz',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operativo', 'Sector Centro'),
    ('AGT-107', 'Oficial María Hernández López', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'operativo', 'Sector Norte'),
    ('JEF-001', 'Coordinador Javier Reyes Cruz', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'coordinador', 'Dirección de Movilidad')
ON CONFLICT (placa) DO NOTHING;

-- -----------------------------------------------------------------------
-- DATOS INICIALES: Catálogo de infracciones (14 faltas del Reglamento)
-- -----------------------------------------------------------------------
INSERT INTO public.catalogo_infracciones_app
    (fundamento_legal, descripcion, categoria, monto_min_uma, monto_max_uma, orden)
VALUES
    ('Art. 39 Frac. IX',    'No obedecer la señal de alto cuando la luz del semáforo esté en rojo', 'MANEJO Y VIALIDAD', 15, 25, 1),
    ('Art. 82',             'Conducir utilizando teléfonos celulares o dispositivos de comunicación móvil en movimiento', 'MANEJO Y VIALIDAD', 10, 20, 2),
    ('Art. 39 Frac. III',   'No respetar los límites de velocidad establecidos en la zona urbana o escolar', 'VELOCIDAD', 30, 50, 3),
    ('Art. 95 / Art. 87 Frac. II', 'Estacionarse en lugar prohibido, doble fila o rampas reservadas para personas con discapacidad', 'ESTACIONAMIENTO', 10, 20, 4),
    ('Art. 102',            'No portar casco protector certificado el conductor y/o acompañante de motocicleta', 'MOTOCICLETAS', 5, 10, 5),
    ('Art. 87 Frac. I a)',  'Circular sin placas de circulación, con placas alteradas, ocultas o no visibles', 'PLACAS Y REGISTRO', 10, 15, 6),
    ('Art. 39 Frac. V',     'No utilizar el cinturón de seguridad el conductor y/o acompañantes', 'SEGURIDAD PASIVA', 5, 10, 7),
    ('Art. 39 Frac. IV',    'Circular en sentido opuesto al que indica la vialidad o señalamiento oficial', 'MANEJO Y VIALIDAD', 3, 5, 8),
    ('Art. 87 Frac. III',   'Falta de licencia de conducir para el tipo de vehículo o presentar licencia vencida', 'DOCUMENTACIÓN', 5, 10, 9),
    ('Art. 87 Frac. IV',    'Falta de tarjeta de circulación vigente del vehículo', 'DOCUMENTACIÓN', 5, 10, 10),
    ('Art. 39 Frac. XI',    'No respetar el derecho de preferencia de paso a peatones y/o ciclistas en cruces marcados', 'PROTECCIÓN PEATONAL', 5, 10, 11),
    ('Art. 115',            'Conducir vehículos bajo el influjo de bebidas alcohólicas, drogas o sustancias tóxicas', 'SEGURIDAD PÚBLICA', 50, 100, 12),
    ('Art. 39 Frac. VII',   'Circular sin faros delanteros o luces posteriores en horario nocturno o condiciones adversas', 'EQUIPAMIENTO', 5, 10, 13),
    ('Art. 39 Frac. XXV',   'Circular con puertas abiertas o permitir que pasajeros viajen en estribos del vehículo', 'TRANSPORTE Y PASAJE', 15, 30, 14)
ON CONFLICT (fundamento_legal) DO NOTHING;
