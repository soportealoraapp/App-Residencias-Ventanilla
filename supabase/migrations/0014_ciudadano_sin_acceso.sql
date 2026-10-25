-- =============================================================================
-- 0014 · Ciudadano sin acceso (COB-08)
-- =============================================================================
-- Qué hace: permite que `users.password_hash` sea NULL. La ventanilla registra
--   ciudadanos que solo dan su nombre: la cuenta existe y es dueña de sus
--   trámites, pero no tiene usuario ni contraseña hasta que reclama la cuenta.
--   Un hash NULL no se puede verificar, así que esas cuentas no pueden iniciar
--   sesión.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Es idempotente.
--
-- Equivale a app/Database/Migrations/2026-10-25-000029_PermitirCiudadanoSinAcceso.php.
-- =============================================================================
BEGIN;

ALTER TABLE public.users ALTER COLUMN password_hash DROP NOT NULL;

-- Dejar registrada la migración CI4 equivalente, para que `spark migrate` no
-- intente volver a aplicarla.
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-25-000029_PermitirCiudadanoSinAcceso', 'PermitirCiudadanoSinAcceso', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-25-000029_PermitirCiudadanoSinAcceso'
);

COMMIT;

-- Verificación: is_nullable debe decir YES.
SELECT column_name, is_nullable, data_type
FROM information_schema.columns
WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'password_hash';
