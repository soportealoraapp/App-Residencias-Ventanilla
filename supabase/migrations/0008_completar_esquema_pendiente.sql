-- =============================================================================
-- 0008_completar_esquema_pendiente.sql
--
-- Para qué sirve: la base de datos de Supabase se construyó con los archivos
-- SQL de esta carpeta, pero el repo también tiene 22 migraciones de CodeIgniter
-- (app/Database/Migrations/) que nunca se ejecutaron ahí. Resultado: faltan
-- tablas y columnas que el panel sí consulta, y eso rompe secciones completas
-- en producción aunque las pruebas locales pasen (las pruebas levantan un
-- SQLite en memoria desde las migraciones, no desde este SQL).
--
-- Qué arregla concretamente:
--   • /admin/verificaciones  → HTTP 500  (no existe verificaciones_fisicas)
--   • /admin/convocatorias   → 404 / página vacía (no existe convocatorias)
--   • /admin/solicitudes/nueva/UR-TT-T-01 → HTTP 500 (no existe convocatorias)
--   • UR-02/03/06 no pueden vincular su concesión (no existe solicitudes.concesion_id)
--   • El desglose reporta "No hay valor de UMA vigente cargado" (no existe parametros_sistema)
--   • El catálogo oficial de infracciones y el checklist de boleta no existen
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
-- Es aditivo y transaccional: no borra ni modifica filas existentes, y puede
-- correrse más de una vez sin daño (IF NOT EXISTS / IF NOT EXISTS).
--
-- Después de ejecutarlo, verifique con:  php scripts/verificar_esquema_bd.php
--
-- Nota sobre ENUM: las migraciones de CI4 declaran 'type' => 'ENUM', pero el
-- Forge de Postgre de CodeIgniter 4.7 no sabe convertir ENUM (solo lo hace
-- bien en SQLite/MySQL). Aquí se resuelve con character varying + CHECK, que
-- es la forma equivalente en Postgres y la que el código de la app valida.
-- =============================================================================

BEGIN;

-- -----------------------------------------------------------------------------
-- 1. CONVOCATORIAS UR-01 (migración 2026-08-26-000011 + 2026-10-19-000023)
--    El CRUD del panel usa titulo / bases_archivo / bases_nombre / bases_tamano;
--    created_by deja constancia de quién la publicó (la auditoría fina va en
--    la tabla auditoria).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.convocatorias (
    id                      bigserial    NOT NULL,
    fecha_publicacion       date         NOT NULL,
    periodo_registro_inicio date         NOT NULL,
    periodo_registro_fin    date         NOT NULL,
    bases                   text         NULL,
    estatus                 character varying(30) NOT NULL DEFAULT 'Vigente',
    created_at              timestamp without time zone NULL,
    updated_at              timestamp without time zone NULL,
    titulo                  character varying(200) NOT NULL DEFAULT '',
    bases_archivo           character varying(255) NULL,
    bases_nombre            character varying(255) NULL,
    bases_mime              character varying(80)  NULL,
    bases_tamano            integer      NULL,
    created_by              bigint       NULL,
    CONSTRAINT convocatorias_pkey PRIMARY KEY (id),
    CONSTRAINT convocatorias_estatus_check
        CHECK (estatus IN ('Vigente', 'Cerrada', 'Anulada')),
    CONSTRAINT convocatorias_ventana_check
        CHECK (periodo_registro_fin >= periodo_registro_inicio)
);

-- Si la tabla ya existía con la forma antigua, se completan las columnas nuevas.
ALTER TABLE public.convocatorias ADD COLUMN IF NOT EXISTS titulo           character varying(200) NOT NULL DEFAULT '';
ALTER TABLE public.convocatorias ADD COLUMN IF NOT EXISTS bases_archivo    character varying(255) NULL;
ALTER TABLE public.convocatorias ADD COLUMN IF NOT EXISTS bases_nombre     character varying(255) NULL;
ALTER TABLE public.convocatorias ADD COLUMN IF NOT EXISTS bases_mime       character varying(80)  NULL;
ALTER TABLE public.convocatorias ADD COLUMN IF NOT EXISTS bases_tamano     integer      NULL;
ALTER TABLE public.convocatorias ADD COLUMN IF NOT EXISTS created_by       bigint       NULL;

CREATE INDEX IF NOT EXISTS convocatorias_estatus_idx ON public.convocatorias (estatus);

-- -----------------------------------------------------------------------------
-- 2. VERIFICACIONES FÍSICAS (migración 2026-08-26-000012)
--    Citas de inspección vehicular de UR-02 y UR-03.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.verificaciones_fisicas (
    id             bigserial  NOT NULL,
    solicitud_id   bigint     NOT NULL,
    fecha_cita     timestamp without time zone NOT NULL,
    resultado      character varying(20) NULL,
    observaciones  text       NULL,
    created_at     timestamp without time zone NULL,
    updated_at     timestamp without time zone NULL,
    CONSTRAINT verificaciones_fisicas_pkey PRIMARY KEY (id),
    CONSTRAINT verificaciones_fisicas_solicitud_id_foreign
        FOREIGN KEY (solicitud_id) REFERENCES public.solicitudes (id)
        ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX IF NOT EXISTS verificaciones_fisicas_solicitud_id_idx
    ON public.verificaciones_fisicas (solicitud_id);

-- -----------------------------------------------------------------------------
-- 3. SOLICITUDES: vínculo con concesión y convocatoria (2026-08-26-000013)
--    Sin estas dos columnas, UR-01 no puede saber a qué convocatoria pertenece
--    una postulación y UR-02/03/06 no pueden vincular la concesión del vehículo.
--
--    convocatoria_id se crea ON DELETE RESTRICT, no CASCADE como decía la
--    migración: borrar una convocatoria con expedientes postulados debe estar
--    prohibido también en la base de datos, no solo en el panel. Si una
--    convocatoria ya tiene postulaciones, el camino correcto es ponerla
--    Anulada. El paralelo concesion_id se queda aquí como CASCADE porque así lo
--    definió la migración original; 0009_concesion_id_sin_cascade.sql lo convierte
--    a RESTRICT, así que aplique los dos bloques en orden.
-- -----------------------------------------------------------------------------
ALTER TABLE public.solicitudes ADD COLUMN IF NOT EXISTS concesion_id      bigint NULL;
ALTER TABLE public.solicitudes ADD COLUMN IF NOT EXISTS convocatoria_id   bigint NULL;

ALTER TABLE public.solicitudes DROP CONSTRAINT IF EXISTS solicitudes_concesion_id_foreign;
ALTER TABLE public.solicitudes
    ADD CONSTRAINT solicitudes_concesion_id_foreign
    FOREIGN KEY (concesion_id) REFERENCES public.concesiones (id)
    ON DELETE CASCADE ON UPDATE SET NULL;

ALTER TABLE public.solicitudes DROP CONSTRAINT IF EXISTS solicitudes_convocatoria_id_foreign;
ALTER TABLE public.solicitudes
    ADD CONSTRAINT solicitudes_convocatoria_id_foreign
    FOREIGN KEY (convocatoria_id) REFERENCES public.convocatorias (id)
    ON DELETE RESTRICT ON UPDATE SET NULL;

-- -----------------------------------------------------------------------------
-- 4. CONCESIONES: tipo de persona del titular (2026-08-26-000014)
-- -----------------------------------------------------------------------------
ALTER TABLE public.concesiones ADD COLUMN IF NOT EXISTS tipo_persona character varying(10) NULL;

-- -----------------------------------------------------------------------------
-- 5. CATÁLOGO OFICIAL DE INFRACCIONES, PARÁMETROS DEL SISTEMA Y CHECKLIST
--    DE BOLETA (2026-09-25-000020)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.catalogo_infracciones (
    id               bigserial    NOT NULL,
    fundamento_legal character varying(60) NOT NULL,
    descripcion      text         NOT NULL,
    categoria_actor  character varying(30) NOT NULL,
    monto_min_uma    numeric(8,2) NOT NULL,
    monto_max_uma    numeric(8,2) NOT NULL,
    created_at       timestamp without time zone NULL,
    updated_at       timestamp without time zone NULL,
    CONSTRAINT catalogo_infracciones_pkey PRIMARY KEY (id),
    CONSTRAINT uq_catalogo_infracciones_fundamento UNIQUE (fundamento_legal),
    CONSTRAINT catalogo_infracciones_categoria_actor_check
        CHECK (categoria_actor IN
            ('conductor_general', 'motociclista', 'ciclista', 'concesionario_operador'))
);

CREATE TABLE IF NOT EXISTS public.parametros_sistema (
    id             bigserial  NOT NULL,
    clave          character varying(60)  NOT NULL,
    valor          character varying(60)  NOT NULL,
    descripcion    character varying(255) NULL,
    actualizado_en timestamp without time zone NULL,
    CONSTRAINT parametros_sistema_pkey PRIMARY KEY (id),
    CONSTRAINT uq_parametros_clave UNIQUE (clave)
);

CREATE TABLE IF NOT EXISTS public.boleta_checklist_item (
    id                     bigserial NOT NULL,
    categoria_boleta       character varying(60)  NOT NULL,
    etiqueta_casilla       character varying(150) NOT NULL,
    catalogo_infraccion_id bigint     NULL,
    created_at             timestamp without time zone NULL,
    CONSTRAINT boleta_checklist_item_pkey PRIMARY KEY (id),
    CONSTRAINT boleta_checklist_item_catalogo_infraccion_id_foreign
        FOREIGN KEY (catalogo_infraccion_id) REFERENCES public.catalogo_infracciones (id)
        ON DELETE SET NULL ON UPDATE CASCADE
);

-- -----------------------------------------------------------------------------
-- 6. VISTAS DE ROLES (2026-09-09-000019)
-- -----------------------------------------------------------------------------
CREATE OR REPLACE VIEW public.ciudadanos AS
    SELECT u.*
    FROM public.users u
    JOIN public.user_roles ur ON ur.user_id = u.id
    JOIN public.roles r ON r.id = ur.role_id
    WHERE r.nombre = 'ciudadano';

CREATE OR REPLACE VIEW public.administradores AS
    SELECT u.*
    FROM public.users u
    JOIN public.user_roles ur ON ur.user_id = u.id
    JOIN public.roles r ON r.id = ur.role_id
    WHERE r.nombre IN ('administrador', 'operador_ventanilla');

-- -----------------------------------------------------------------------------
-- 7. PARÁMETROS DEL SISTEMA: sembrar el valor de UMA
--    El desglose del panel lee parametros_sistema.clave = 'valor_uma_vigente'.
--    Si el parámetro de la app de tránsito ya existe, se reutiliza su valor para
--    no inventarse un número; si no existe, se deja el vigente de 2026.
-- -----------------------------------------------------------------------------
INSERT INTO public.parametros_sistema (clave, valor, descripcion, actualizado_en)
SELECT 'valor_uma_vigente',
       COALESCE(
           (SELECT pa.valor FROM public.parametros_app pa
             WHERE pa.clave = 'valor_uma_vigente' ORDER BY pa.id DESC LIMIT 1),
           '117.31'
       ),
       'Valor del UMA en curso, usado para convertir las multas de UMA a pesos.',
       CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM public.parametros_sistema ps WHERE ps.clave = 'valor_uma_vigente');

-- -----------------------------------------------------------------------------
-- 8. HISTORIAL DE MIGRACIONES
--    La tabla `migrations` estaba vacía, así que un futuro `php spark migrate`
--    intentaría rehacer tablas y columnas que ya existen y fallaría a mitad.
--    Se registra que estas migraciones ya están aplicadas: a partir de ahora
--    `spark migrate` solo correrá las que se agreguen después.
-- -----------------------------------------------------------------------------
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT v.version, v.class, 'App', '', EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 1
FROM (VALUES
    ('2026-01-01-000001_CreateRolesTable',                  'CreateRolesTable'),
    ('2026-01-01-000002_CreateUsersTable',                  'CreateUsersTable'),
    ('2026-01-01-000003_CreateUserRolesTable',              'CreateUserRolesTable'),
    ('2026-01-01-000004_CreateConcesionesTable',            'CreateConcesionesTable'),
    ('2026-01-01-000005_CreateSolicitudesTable',            'CreateSolicitudesTable'),
    ('2026-01-01-000006_CreateSolicitudDatosTable',         'CreateSolicitudDatosTable'),
    ('2026-01-01-000007_CreateDocumentosTable',             'CreateDocumentosTable'),
    ('2026-01-01-000008_CreateTarifasTable',                'CreateTarifasTable'),
    ('2026-01-01-000009_CreateHistorialEstatusTable',       'CreateHistorialEstatusTable'),
    ('2026-01-01-000010_CreateAuditoriaTable',              'CreateAuditoriaTable'),
    ('2026-08-26-000011_CreateConvocatoriasTable',          'CreateConvocatoriasTable'),
    ('2026-08-26-000012_CreateVerificacionesFisicasTable',  'CreateVerificacionesFisicasTable'),
    ('2026-08-26-000013_AddTramiteRelationsToSolicitudes',  'AddTramiteRelationsToSolicitudes'),
    ('2026-08-26-000014_AddTipoPersonaToConcesiones',       'AddTipoPersonaToConcesiones'),
    ('2026-09-02-000015_AddCiudadanoFieldsToUsers',         'AddCiudadanoFieldsToUsers'),
    ('2026-09-02-000016_CreateFormatosTramiteTable',        'CreateFormatosTramiteTable'),
    ('2026-09-02-000017_AddIneToUsers',                     'AddIneToUsers'),
    ('2026-09-09-000018_AddRegistroBorradosToAuditoria',    'AddRegistroBorradosToAuditoria'),
    ('2026-09-09-000019_CreateRoleViews',                   'CreateRoleViews'),
    ('2026-09-25-000020_CreateCatalogoInfraccionesTable',   'CreateCatalogoInfraccionesTable'),
    ('2026-09-26-000021_CreateAppTransitoTables',           'CreateAppTransitoTables'),
    ('2026-10-16-000022_CreateAgenteTokensTable',           'CreateAgenteTokensTable'),
    ('2026-10-19-000023_AddCrudFieldsToConvocatorias',      'AddCrudFieldsToConvocatorias')
) AS v(version, class)
WHERE NOT EXISTS (SELECT 1 FROM public.migrations m WHERE m.version = v.version);

COMMIT;

-- =============================================================================
-- Datos de ejemplo (OPCIONAL): una convocatoria abierta para que la sección
-- tenga algo que mostrar. Descomente solo si quiere crearla desde aquí; la
-- forma normal es usar el panel: Convocatorias UR-01 → Nueva convocatoria.
-- =============================================================================
-- INSERT INTO public.convocatorias
--     (titulo, fecha_publicacion, periodo_registro_inicio, periodo_registro_fin,
--      bases, estatus, created_at, updated_at)
-- VALUES
--     ('Convocatoria Pública de Concesiones de Ruta Urbana 2026',
--      CURRENT_DATE, CURRENT_DATE, CURRENT_DATE + 30,
--      'Podrán participar personas físicas o morales con licencia de conductor tipo C, '
--      || 'vehículo modelo 2018 en adelante y póliza de seguro vigente.',
--      'Vigente', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
