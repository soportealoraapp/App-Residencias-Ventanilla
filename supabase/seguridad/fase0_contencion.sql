-- =============================================================================
-- Fase 0 · Contención (auditoría de Félix, hallazgos C1, C2 y C3)
-- =============================================================================
-- Script de UNA SOLA VEZ para correr en el SQL Editor de Supabase, DESPUÉS de
-- supabase/migrations/0020_rls_todas_las_tablas.sql. No es una migración.
--
-- Qué hace:
--   1. Cambia la contraseña de TODA cuenta (usuarios web y agentes de tránsito)
--      que todavía use una contraseña conocida: 12345678, password, Demo2026,
--      Admin2024!, Operador2024! o transito2026. Todas quedan con la clave nueva.
--   2. Revoca todos los tokens vigentes de la app de agentes (tendrán que
--      volver a iniciar sesión con la clave nueva).
--   3. Borra todas las sesiones web (ci_sessions): todos vuelven a iniciar sesión.
--   4. Muestra qué cuentas se cambiaron.
--
-- ANTES DE CORRER:
--   - Escribe la clave nueva en el paso 0 (mínimo 10 caracteres). No la subas
--     al repositorio: edítala solo en el SQL Editor.
--   - Avisa a quienes prueban la demo (Movilidad, Félix, Laura): los botones de
--     acceso rápido del login seguirán mostrando 12345678 hasta que se haga el
--     punto 1.1 del plan, y ya no van a funcionar.
--
-- Requiere pgcrypto (en Supabase viene activado en el esquema `extensions`).
-- Los hashes $2a$ que genera pgcrypto los acepta password_verify() de PHP.
-- =============================================================================

BEGIN;

DROP TABLE IF EXISTS pg_temp._fase0, pg_temp._claves_conocidas, pg_temp._cuentas_cambiadas;

-- 0. ✏️  Clave nueva para las cuentas afectadas.
CREATE TEMP TABLE _fase0 (clave_nueva text NOT NULL);
INSERT INTO _fase0 VALUES ('ESCRIBE_AQUI_LA_CLAVE_NUEVA');

DO $$
DECLARE
    c text := (SELECT clave_nueva FROM _fase0);
BEGIN
    IF c LIKE 'ESCRIBE\_AQUI%' OR length(c) < 10 THEN
        RAISE EXCEPTION 'Escribe una clave nueva de al menos 10 caracteres en el paso 0.';
    END IF;
    IF c IN ('12345678', 'password', 'Demo2026', 'Admin2024!', 'Operador2024!', 'transito2026') THEN
        RAISE EXCEPTION 'La clave nueva no puede ser una de las contraseñas conocidas.';
    END IF;
END $$;

-- Contraseñas conocidas que se buscan en los hashes actuales.
CREATE TEMP TABLE _claves_conocidas (clave text);
INSERT INTO _claves_conocidas VALUES
    ('12345678'), ('password'), ('Demo2026'), ('Admin2024!'), ('Operador2024!'), ('transito2026');

-- PHP genera $2y$; pgcrypto solo entiende $2a$ (mismo algoritmo).
CREATE OR REPLACE FUNCTION pg_temp.clave_debil(h text) RETURNS boolean
LANGUAGE sql AS $$
    SELECT h ~ '^\$2[aby]\$'
       AND EXISTS (
           SELECT 1 FROM _claves_conocidas k
           WHERE extensions.crypt(k.clave, overlay(h PLACING '$2a$' FROM 1 FOR 4))
               = overlay(h PLACING '$2a$' FROM 1 FOR 4)
       );
$$;

CREATE TEMP TABLE _cuentas_cambiadas (tipo text, cuenta text);

-- 1a. Usuarios web.
WITH cambiados AS (
    UPDATE public.users u
    SET password_hash = extensions.crypt((SELECT clave_nueva FROM _fase0), extensions.gen_salt('bf', 10)),
        updated_at    = CURRENT_TIMESTAMP
    WHERE u.password_hash IS NOT NULL
      AND pg_temp.clave_debil(u.password_hash)
    RETURNING u.username
)
INSERT INTO _cuentas_cambiadas SELECT 'usuario web', username FROM cambiados;

-- 1b. Agentes de tránsito.
WITH cambiados AS (
    UPDATE public.agentes_transito a
    SET password_hash = extensions.crypt((SELECT clave_nueva FROM _fase0), extensions.gen_salt('bf', 10)),
        updated_at    = NOW()
    WHERE pg_temp.clave_debil(a.password_hash)
    RETURNING a.placa
)
INSERT INTO _cuentas_cambiadas SELECT 'agente', placa FROM cambiados;

-- 2. Revocar tokens vigentes de agentes.
UPDATE public.agentes_transito_tokens
SET revocado_en = CURRENT_TIMESTAMP
WHERE revocado_en IS NULL;

-- 3. Cerrar todas las sesiones web.
DELETE FROM public.ci_sessions;

COMMIT;

-- 4. Resultado: cuentas a las que se les cambió la clave.
SELECT tipo, cuenta FROM _cuentas_cambiadas ORDER BY tipo, cuenta;
