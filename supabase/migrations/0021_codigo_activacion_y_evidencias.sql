-- =============================================================================
-- 0021 · Código de activación y bucket privado de evidencias
--        (auditoría de Félix, Fase 2: hallazgos A1 y A4)
-- =============================================================================
-- 1. users.activacion_hash / activacion_expira: el reclamo de una cuenta que
--    ventanilla registró sin acceso exige el código impreso en el acuse.
--    Equivale a la migración CI4 2026-11-17-000035_CodigoActivacionCiudadano.
-- 2. Bucket privado `evidencias` en Supabase Storage para las fotos de las
--    boletas. Antes se escribían en public/uploads (sin autenticación y, en
--    Vercel, en un disco de solo lectura). Lo usa el backend con la service key.
--
-- Es idempotente: se puede correr más de una vez.
-- =============================================================================

BEGIN;

ALTER TABLE public.users ADD COLUMN IF NOT EXISTS activacion_hash   VARCHAR(64) NULL;
ALTER TABLE public.users ADD COLUMN IF NOT EXISTS activacion_expira TIMESTAMP   NULL;
CREATE INDEX IF NOT EXISTS idx_users_activacion_hash ON public.users (activacion_hash);

-- Bucket privado (public = false): solo se lee con la service key del backend.
INSERT INTO storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
VALUES ('evidencias', 'evidencias', false, 5242880, ARRAY['image/jpeg', 'image/png', 'image/webp'])
ON CONFLICT (id) DO UPDATE
SET public = false,
    file_size_limit = EXCLUDED.file_size_limit,
    allowed_mime_types = EXCLUDED.allowed_mime_types;

INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-11-17-000035_CodigoActivacionCiudadano', 'CodigoActivacionCiudadano', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-11-17-000035_CodigoActivacionCiudadano'
);

COMMIT;

-- Verificación: dos columnas nuevas y el bucket privado.
SELECT (SELECT count(*) FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'users'
          AND column_name IN ('activacion_hash', 'activacion_expira')) AS columnas_nuevas,
       (SELECT public FROM storage.buckets WHERE id = 'evidencias')     AS bucket_publico;
