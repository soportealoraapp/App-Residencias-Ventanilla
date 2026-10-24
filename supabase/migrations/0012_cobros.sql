-- =============================================================================
-- 0012 · Módulo de cobros (COB-01)
-- =============================================================================
--
-- Qué hace: crea `cobros` (un cobro por trámite o por multa, con su desglose y
--   estatus pendiente / aprobado / cancelado) y `cobro_movimientos` (historial
--   de cada cobro). No modifica datos existentes.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Es idempotente.
--
-- Equivale a app/Database/Migrations/2026-10-24-000027_CreateCobrosTables.php.
-- =============================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS public.cobros (
    id                   BIGSERIAL PRIMARY KEY,
    folio                VARCHAR(30) NULL UNIQUE,
    origen               VARCHAR(20) NOT NULL CHECK (origen IN ('solicitud', 'boleta')),
    -- Un cobro es un registro contable: borrar la solicitud o la boleta no lo borra.
    solicitud_id         BIGINT NULL REFERENCES public.solicitudes(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    boleta_id            BIGINT NULL REFERENCES public.boletas_infracciones(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    ciudadano_id         BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL ON UPDATE RESTRICT,
    nombre_pagador       VARCHAR(180) NOT NULL,
    concepto             VARCHAR(255) NOT NULL,
    monto_base           DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento            DECIMAL(12,2) NOT NULL DEFAULT 0,
    recargo              DECIMAL(12,2) NOT NULL DEFAULT 0,
    condonado            DECIMAL(12,2) NOT NULL DEFAULT 0,
    total                DECIMAL(12,2) NOT NULL DEFAULT 0,
    detalle_calculo      TEXT NULL,
    estatus              VARCHAR(20) NOT NULL DEFAULT 'pendiente' CHECK (estatus IN ('pendiente', 'aprobado', 'cancelado')),
    pendiente_documentos SMALLINT NOT NULL DEFAULT 0,
    motivo               TEXT NULL,
    lugar_pago           VARCHAR(30) NULL,
    referencia_pago      VARCHAR(100) NULL,
    folio_recibo         VARCHAR(30) NULL UNIQUE,
    cobrado_por          BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL ON UPDATE RESTRICT,
    fecha_cobro          TIMESTAMP NULL,
    created_by           BIGINT NULL,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS cobros_estatus ON public.cobros (estatus);
CREATE INDEX IF NOT EXISTS cobros_origen ON public.cobros (origen);
CREATE INDEX IF NOT EXISTS cobros_solicitud_id ON public.cobros (solicitud_id);
CREATE INDEX IF NOT EXISTS cobros_boleta_id ON public.cobros (boleta_id);
CREATE INDEX IF NOT EXISTS cobros_fecha_cobro ON public.cobros (fecha_cobro);

CREATE TABLE IF NOT EXISTS public.cobro_movimientos (
    id               BIGSERIAL PRIMARY KEY,
    cobro_id         BIGINT NOT NULL REFERENCES public.cobros(id) ON DELETE CASCADE ON UPDATE CASCADE,
    accion           VARCHAR(40) NOT NULL,
    estatus_anterior VARCHAR(20) NULL,
    estatus_nuevo    VARCHAR(20) NOT NULL,
    motivo           TEXT NULL,
    detalle          TEXT NULL,
    usuario_id       BIGINT NULL REFERENCES public.users(id) ON DELETE SET NULL ON UPDATE RESTRICT,
    fecha            TIMESTAMP NOT NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL
);
CREATE INDEX IF NOT EXISTS cobro_movimientos_cobro_id ON public.cobro_movimientos (cobro_id);

-- Dejar registrada la migración CI4 equivalente, para que `spark migrate`
-- no intente volver a aplicarla.
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-24-000027_CreateCobrosTables', 'CreateCobrosTables', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-24-000027_CreateCobrosTables'
);

COMMIT;

-- Verificación: debe listar las dos tablas.
SELECT table_name FROM information_schema.tables
WHERE table_schema = 'public' AND table_name IN ('cobros', 'cobro_movimientos');
