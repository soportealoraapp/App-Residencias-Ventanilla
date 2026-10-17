-- =====================================================================
-- MIGRACIÓN: Tokens de acceso de la app de agentes de tránsito
-- Equivalente a app/Database/Migrations/2026-10-16-000022_CreateAgenteTokensTable.php
--
-- Desde esta versión la app móvil solo escribe boletas a través del backend
-- CodeIgniter (POST /api/infracciones/sincronizar con token Bearer), así que
-- también se retiran los permisos anónimos que permitían escribir boletas y
-- leer la tabla de agentes (incluido password_hash) con la llave anon.
-- =====================================================================

CREATE TABLE IF NOT EXISTS public.agentes_transito_tokens (
    id BIGSERIAL PRIMARY KEY,
    agente_id BIGINT NOT NULL REFERENCES public.agentes_transito(id) ON DELETE CASCADE,
    token_hash CHAR(64) NOT NULL,               -- SHA-256 del token; el token en claro nunca se guarda
    expira_en TIMESTAMP NOT NULL,
    ultimo_uso_en TIMESTAMP NULL,
    revocado_en TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT uq_agentes_transito_tokens_hash UNIQUE (token_hash)
);

CREATE INDEX IF NOT EXISTS idx_agentes_transito_tokens_agente
    ON public.agentes_transito_tokens(agente_id);

-- Sin políticas para anon/authenticated: solo el backend (service_role) accede.
ALTER TABLE public.agentes_transito_tokens ENABLE ROW LEVEL SECURITY;
CREATE POLICY "service_role_all_agentes_tokens" ON public.agentes_transito_tokens
    FOR ALL TO service_role USING (true) WITH CHECK (true);

-- Retirar accesos anónimos que permitían suplantar a otros agentes.
DROP POLICY IF EXISTS "anon_insert_boletas" ON public.boletas_infracciones;
DROP POLICY IF EXISTS "anon_update_boletas" ON public.boletas_infracciones;
DROP POLICY IF EXISTS "anon_select_boletas" ON public.boletas_infracciones;
DROP POLICY IF EXISTS "anon_read_agentes"   ON public.agentes_transito;
