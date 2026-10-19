-- =============================================================================
-- 0009 · solicitudes.concesion_id deja de ser ON DELETE CASCADE
-- =============================================================================
--
-- Por qué: 0008 recreó las FK tal como las definieron las migraciones CI4, y
--   esas decían CASCADE para concesion_id. En Postgres eso significa que un
--   DELETE sobre public.concesiones se lleva por delante los expedientes
--   UR-02 / UR-03 / UR-06 vinculados, sin aviso y sin rollback posible: el
--   ciudadano pierde su historial y el padrón queda sin sustento documental.
--
--   El panel ya se lo impide a la operadora (CatalogoConcesionesController::
--   eliminar rechaza la operación si hay solicitudes), pero la defensa real es
--   la base de datos, porque un borrado desde SQL Editor o desde un script
--   externo no pasa por el controlador.
--
-- Qué hace: reemplaza la restricción por ON DELETE RESTRICT. Desde ahora el
--   motor rechaza borrar una concesión con solicitudes vinculadas.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Es idempotente (DROP CONSTRAINT IF EXISTS) y transaccional: si algo falla,
--   no deja la tabla a medias.
--
-- Equivale a app/Database/Migrations/2026-10-19-000024_SwitchConcesionFkToRestrict.php.
-- =============================================================================

BEGIN;

-- 1. Antes de cambiar la política conviene saber si ya hay filas que dependen
--    de la concesión. Este conteo es informativo: no bloquea nada.
SELECT
    count(*) AS solicitudes_con_concesion_vinculada
FROM public.solicitudes
WHERE concesion_id IS NOT NULL;

-- 2. Reconvertir la FK.
ALTER TABLE public.solicitudes DROP CONSTRAINT IF EXISTS solicitudes_concesion_id_foreign;

ALTER TABLE public.solicitudes
    ADD CONSTRAINT solicitudes_concesion_id_foreign
    FOREIGN KEY (concesion_id) REFERENCES public.concesiones (id)
    ON DELETE RESTRICT ON UPDATE SET NULL;

-- 3. Dejar registrada la migración CI4 equivalente, para que `spark migrate`
--    no intente volver a aplicarla sobre una base que ya quedó corregida.
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-19-000024_SwitchConcesionFkToRestrict', 'SwitchConcesionFkToRestrict', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-19-000024_SwitchConcesionFkToRestrict'
);

COMMIT;

-- 4. Verificación: debe reportar confdeltype = 'r' (restrict) y no 'c' (cascade).
SELECT
    con.conname AS restriccion,
    CASE con.confdeltype
        WHEN 'a' THEN 'NO ACTION'
        WHEN 'r' THEN 'RESTRICT'
        WHEN 'c' THEN 'CASCADE'
        WHEN 'n' THEN 'SET NULL'
        WHEN 'd' THEN 'SET DEFAULT'
    END AS on_delete
FROM pg_constraint con
JOIN pg_class rel ON rel.oid = con.conrelid
WHERE rel.relname = 'solicitudes'
  AND con.conname = 'solicitudes_concesion_id_foreign';
