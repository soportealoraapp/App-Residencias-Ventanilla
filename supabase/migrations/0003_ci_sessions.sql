-- =====================================================
-- Tabla ci_sessions para CodeIgniter 4 DatabaseHandler
-- Permite sesiones en Vercel serverless (Supabase PostgreSQL)
-- =====================================================

CREATE TABLE IF NOT EXISTS public.ci_sessions (
    id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    timestamp TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT NOW(),
    data BYTEA NOT NULL,
    CONSTRAINT ci_sessions_pkey PRIMARY KEY (id)
);

CREATE INDEX IF NOT EXISTS idx_ci_sessions_timestamp ON public.ci_sessions (timestamp);
