-- =============================================================================
-- 0011 · Resincronizar las secuencias de id de todas las tablas
-- =============================================================================
--
-- Por qué: cuando se copian o sincronizan datos con su id explícito (por
--   ejemplo, al pasar la base local a Supabase), las secuencias que generan los
--   id no avanzan. El siguiente INSERT recibe un id que ya existe y falla con
--   "duplicate key value violates unique constraint ..._pkey". Desde el panel
--   se ve como que "no se puede crear" un agente, una solicitud, un usuario, etc.
--
-- Qué hace: para cada columna de public.* que tenga secuencia (SERIAL,
--   BIGSERIAL o IDENTITY), mueve la secuencia a MAX(id) + 1. No modifica datos.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Es idempotente: conviene volver a correrlo después de cada sincronización
--   o importación de datos.
--
-- Equivale a app/Database/Migrations/2026-10-22-000026_ResyncSecuenciasPostgres.php.
-- =============================================================================

DO $$
DECLARE
    r   record;
    seq text;
BEGIN
    FOR r IN
        SELECT c.table_name, c.column_name
        FROM information_schema.columns c
        JOIN information_schema.tables t
          ON t.table_schema = c.table_schema AND t.table_name = c.table_name
        WHERE c.table_schema = 'public' AND t.table_type = 'BASE TABLE'
    LOOP
        seq := pg_get_serial_sequence(format('public.%I', r.table_name), r.column_name);
        IF seq IS NOT NULL THEN
            EXECUTE format(
                'SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM public.%I), 0) + 1, false)',
                seq, r.column_name, r.table_name
            );
        END IF;
    END LOOP;
END $$;

INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-22-000026_ResyncSecuenciasPostgres', 'ResyncSecuenciasPostgres', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-22-000026_ResyncSecuenciasPostgres'
);

-- Verificación: la columna "siguiente_id" debe ser mayor que "max_id" en cada tabla.
SELECT 'agentes_transito' AS tabla, (SELECT MAX(id) FROM public.agentes_transito) AS max_id,
       (SELECT last_value FROM public.agentes_transito_id_seq) AS siguiente_id
UNION ALL
SELECT 'solicitudes', (SELECT MAX(id) FROM public.solicitudes),
       (SELECT last_value FROM public.solicitudes_id_seq)
UNION ALL
SELECT 'users', (SELECT MAX(id) FROM public.users),
       (SELECT last_value FROM public.users_id_seq);
