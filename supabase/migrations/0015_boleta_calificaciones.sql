-- COB-10: calificación judicial de boletas y generación segura de su cobro.
-- Alternativa independiente a la migración CI4 000030. No ejecutar ambas.
-- No agrega registros al historial public.migrations.
BEGIN;

CREATE TABLE IF NOT EXISTS public.boleta_calificaciones (
    id BIGSERIAL PRIMARY KEY,
    boleta_id BIGINT NOT NULL UNIQUE REFERENCES public.boletas_infracciones(id) ON DELETE RESTRICT,
    juez_id BIGINT REFERENCES public.users(id) ON DELETE SET NULL,
    cobro_id BIGINT UNIQUE REFERENCES public.cobros(id) ON DELETE RESTRICT,
    nombre_infractor VARCHAR(180) NOT NULL CHECK (btrim(nombre_infractor) <> ''),
    valor_uma NUMERIC(10,2) NOT NULL CHECK (valor_uma > 0),
    total_uma NUMERIC(10,2) NOT NULL CHECK (total_uma >= 0),
    monto_base NUMERIC(12,2) NOT NULL CHECK (monto_base > 0),
    calificada_en TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_boleta_calificaciones_juez ON public.boleta_calificaciones(juez_id);
CREATE INDEX IF NOT EXISTS idx_boleta_calificaciones_fecha ON public.boleta_calificaciones(calificada_en);

CREATE TABLE IF NOT EXISTS public.boleta_calificacion_detalles (
    id BIGSERIAL PRIMARY KEY,
    calificacion_id BIGINT NOT NULL REFERENCES public.boleta_calificaciones(id) ON DELETE CASCADE,
    orden SMALLINT NOT NULL CHECK (orden > 0),
    falta_id_origen VARCHAR(80),
    fundamento_legal VARCHAR(255) NOT NULL,
    descripcion TEXT,
    categoria VARCHAR(100),
    monto_min_uma NUMERIC(10,2) NOT NULL CHECK (monto_min_uma >= 0),
    monto_max_uma NUMERIC(10,2) NOT NULL CHECK (monto_max_uma >= monto_min_uma),
    uma_asignada NUMERIC(10,2) NOT NULL CHECK (uma_asignada BETWEEN monto_min_uma AND monto_max_uma),
    subtotal_pesos NUMERIC(12,2) NOT NULL CHECK (subtotal_pesos >= 0),
    CONSTRAINT uq_boleta_calificacion_detalle_orden UNIQUE (calificacion_id, orden)
);

CREATE INDEX IF NOT EXISTS idx_boleta_calificacion_detalles_calificacion
    ON public.boleta_calificacion_detalles(calificacion_id);

CREATE UNIQUE INDEX IF NOT EXISTS uq_cobros_boleta_vigente
    ON public.cobros(boleta_id)
    WHERE boleta_id IS NOT NULL AND estatus IN ('pendiente', 'aprobado');

COMMIT;
