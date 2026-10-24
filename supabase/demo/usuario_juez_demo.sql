-- =============================================================================
-- Cuenta demo del juez calificador (botón "Juez calificador" del login)
-- =============================================================================
-- Equivale a UsuariosDemoSeeder::juezDemo. Usuario juez1, misma contraseña
-- de prueba que admin, operador1 y ciudadano1.
--
-- Requiere antes: supabase/migrations/0013_juez_calificador_role.sql
-- Pegar completo en Supabase → SQL Editor → Run. Es idempotente.
-- =============================================================================

BEGIN;

INSERT INTO public.users (username, email, nombre_completo, password_hash, activo, created_at, updated_at)
SELECT 'juez1', 'juez1@uriangato.gob.mx', 'Juez Calificador Demo',
       '$2y$10$8QAkVGXTOOwGTbb/X4af7eULqN9CTALnWkQ5MONjZHq2oV.W1YWsi', 1, NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM public.users WHERE username = 'juez1' OR email = 'juez1@uriangato.gob.mx'
);

INSERT INTO public.user_roles (user_id, role_id, created_at, updated_at)
SELECT u.id, r.id, NOW(), NOW()
FROM public.users u CROSS JOIN public.roles r
WHERE u.username = 'juez1' AND r.nombre = 'juez_calificador'
  AND NOT EXISTS (SELECT 1 FROM public.user_roles x WHERE x.user_id = u.id AND x.role_id = r.id);

COMMIT;

-- Verificación: debe mostrar juez1 con el rol juez_calificador.
SELECT u.username, r.nombre AS rol
FROM public.users u
JOIN public.user_roles ur ON ur.user_id = u.id
JOIN public.roles r ON r.id = ur.role_id
WHERE u.username = 'juez1';
