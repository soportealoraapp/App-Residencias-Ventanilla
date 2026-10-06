-- =============================================================================
-- Migración 0006: Soporte para múltiples infracciones por boleta
-- =============================================================================
-- CONTEXTO:
--   La app móvil ahora permite registrar VARIAS faltas en una sola boleta.
--   Se agrega una columna JSONB para almacenar el array completo de faltas.
--
--   Las columnas originales (falta_fundamento_legal, falta_descripcion, etc.)
--   se mantienen y se reutilizan como RESUMEN de la falta principal (la primera
--   del array), facilitando consultas rápidas sin parsear JSON.
--
--   Los montos min/max existentes ahora almacenan la SUMA TOTAL de todas las
--   faltas, no solo de una.
-- =============================================================================

-- 1. Agregar columna para el JSON completo de todas las faltas
ALTER TABLE public.boletas_infracciones
    ADD COLUMN IF NOT EXISTS faltas_json JSONB DEFAULT '[]';

-- 2. Comentarios descriptivos para documentar el cambio de semántica
COMMENT ON COLUMN public.boletas_infracciones.faltas_json IS
    'Array JSON con todas las faltas de la boleta: [{fundamentoLegal, descripcion, categoria, montoMinUma, montoMaxUma}, ...]';

COMMENT ON COLUMN public.boletas_infracciones.falta_fundamento_legal IS
    'Fundamento legal de la falta PRINCIPAL (primera del array). Usado para consultas rápidas y reportes.';

COMMENT ON COLUMN public.boletas_infracciones.falta_monto_min_uma IS
    'Suma total de montos mínimos UMA de TODAS las faltas de la boleta.';

COMMENT ON COLUMN public.boletas_infracciones.falta_monto_max_uma IS
    'Suma total de montos máximos UMA de TODAS las faltas de la boleta.';

-- 3. Índice GIN para consultas eficientes sobre el JSON de faltas
CREATE INDEX IF NOT EXISTS idx_boletas_faltas_json
    ON public.boletas_infracciones USING GIN (faltas_json);
