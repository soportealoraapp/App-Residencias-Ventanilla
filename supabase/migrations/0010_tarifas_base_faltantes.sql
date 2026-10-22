-- =============================================================================
-- 0010 · Tarifas base faltantes de UR-TT-T-01 a UR-TT-T-05
-- =============================================================================
--
-- Por qué: 0001_init_schema.sql solo sembró las tarifas de UR-TT-T-06 y
--   UR-TT-T-07. Las de T-01 a T-05 existían únicamente en el TarifasSeeder
--   local, así que en la base hosteada esos trámites no tenían tarifa vigente y
--   el registro presencial (Panel → Nueva solicitud) dejaba deshabilitado el
--   botón "Registrar solicitud".
--
-- Qué hace: inserta la tarifa 'base' de cada trámite SOLO si no existe ninguna
--   para ese trámite. No modifica tarifas ya capturadas por el municipio. Los
--   montos son los provisionales del prototipo; corríjalos en Panel → Tarifario.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Es idempotente y transaccional.
--
-- Equivale a app/Database/Migrations/2026-10-21-000025_SeedTarifasBaseFaltantes.php.
-- =============================================================================

BEGIN;

-- 1. Si los datos se importaron con ids explícitos, la secuencia puede ir
--    atrasada y el INSERT chocaría con un id existente.
SELECT setval(pg_get_serial_sequence('public.tarifas', 'id'),
              COALESCE((SELECT MAX(id) FROM public.tarifas), 0) + 1, false);

-- 2. Insertar solo las que faltan.
INSERT INTO public.tarifas (tramite, criterio, monto, vigente_desde, placeholder_oficial, descripcion, created_at, updated_at)
SELECT v.tramite, 'base', v.monto, DATE '2026-01-01', 1, v.descripcion, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM (VALUES
    ('UR-TT-T-01', 9055.20, 'Tarifa provisional del prototipo: Concesión de Transporte'),
    ('UR-TT-T-02',   64.90, 'Tarifa provisional del prototipo: Constancia de Despintado'),
    ('UR-TT-T-03',   50.00, 'Tarifa provisional del prototipo: Orden de Plaqueo'),
    ('UR-TT-T-04',  156.94, 'Tarifa provisional del prototipo: Permiso Eventual de Transporte'),
    ('UR-TT-T-05',  287.00, 'Tarifa provisional del prototipo: Cierre de Calle')
) AS v(tramite, monto, descripcion)
WHERE NOT EXISTS (
    SELECT 1 FROM public.tarifas t
    WHERE t.tramite = v.tramite AND t.criterio = 'base'
);

-- 3. Dejar registrada la migración CI4 equivalente, para que `spark migrate`
--    no intente volver a aplicarla.
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-21-000025_SeedTarifasBaseFaltantes', 'SeedTarifasBaseFaltantes', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-21-000025_SeedTarifasBaseFaltantes'
);

COMMIT;

-- 4. Verificación: debe listar una tarifa vigente 'base' para T-01 a T-05.
SELECT tramite, criterio, monto, vigente_desde, vigente_hasta
FROM public.tarifas
WHERE criterio = 'base'
ORDER BY tramite;
