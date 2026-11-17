-- =============================================================================
-- 0020 · RLS en todas las tablas de public (auditoría de Félix, hallazgo C1)
-- =============================================================================
-- La llave anon de Supabase viaja dentro del bundle público de la app de agentes
-- (/agentes). Con la Data API activa, cualquier tabla de `public` sin RLS se
-- podía leer y escribir con esa llave (users, user_roles, cobros, ci_sessions…).
--
-- Qué hace:
--   1. Activa RLS en TODAS las tablas de `public`. Sin políticas, anon y
--      authenticated no ven ni modifican nada.
--   2. Quita a anon/authenticated todos los privilegios sobre tablas,
--      secuencias y funciones de `public`, y evita que las tablas futuras
--      se los vuelvan a dar.
--   3. Deja solo lo que la app de agentes lee con la llave anon:
--      SELECT en catalogo_infracciones_app y parametros_app (con sus políticas
--      anon_read_* de la migración 0005).
--
-- Qué NO afecta:
--   - El backend PHP entra con conexión directa como `postgres` (dueño de las
--     tablas), que no está sujeto a RLS.
--   - Supabase Storage lo usa el backend con la service_role key.
--
-- Es idempotente: se puede correr más de una vez.
-- Es solo de Supabase: en SQLite/CI4 no existe RLS, no hay migración CI4 gemela.
-- =============================================================================

BEGIN;

-- 1. RLS en todas las tablas de public.
DO $$
DECLARE
    t record;
BEGIN
    FOR t IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', t.tablename);
    END LOOP;
END $$;

-- Políticas de la app de agentes que ya no deben existir (0007 las borró,
-- se repite por si alguna base se quedó a medias).
DROP POLICY IF EXISTS "anon_insert_boletas" ON public.boletas_infracciones;
DROP POLICY IF EXISTS "anon_update_boletas" ON public.boletas_infracciones;
DROP POLICY IF EXISTS "anon_select_boletas" ON public.boletas_infracciones;
DROP POLICY IF EXISTS "anon_read_agentes"   ON public.agentes_transito;

-- 2 y 3. Privilegios de los roles de la Data API (solo si existen; en un
--        Postgres que no es Supabase no hay roles anon/authenticated).
DO $$
DECLARE
    rol text;
BEGIN
    FOREACH rol IN ARRAY ARRAY['anon', 'authenticated'] LOOP
        IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = rol) THEN
            EXECUTE format('REVOKE ALL ON ALL TABLES    IN SCHEMA public FROM %I', rol);
            EXECUTE format('REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM %I', rol);
            EXECUTE format('REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM %I', rol);
            EXECUTE format('ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON TABLES    FROM %I', rol);
            EXECUTE format('ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON SEQUENCES FROM %I', rol);
            EXECUTE format('ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public REVOKE ALL ON FUNCTIONS FROM %I', rol);
        END IF;
    END LOOP;

    IF EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'anon') THEN
        GRANT SELECT ON public.catalogo_infracciones_app TO anon;
        GRANT SELECT ON public.parametros_app            TO anon;
    END IF;
END $$;

COMMIT;

-- 4. Verificación.
--    a) Tablas de public sin RLS: debe salir vacío.
SELECT tablename AS tabla_sin_rls
FROM pg_tables
WHERE schemaname = 'public' AND NOT rowsecurity;

--    b) Privilegios que conservan anon/authenticated: solo SELECT en
--       catalogo_infracciones_app y parametros_app para anon.
SELECT grantee, table_name, string_agg(privilege_type, ', ' ORDER BY privilege_type) AS privilegios
FROM information_schema.role_table_grants
WHERE table_schema = 'public' AND grantee IN ('anon', 'authenticated')
GROUP BY grantee, table_name
ORDER BY grantee, table_name;
