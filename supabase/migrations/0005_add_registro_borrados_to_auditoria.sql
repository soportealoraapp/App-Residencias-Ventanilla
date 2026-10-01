-- =====================================================
-- Migracion: Agregar columna registro_borrados a auditoria
-- Previene errores en AuditoriaModel al auditar acciones
-- =====================================================

ALTER TABLE public.auditoria ADD COLUMN IF NOT EXISTS registro_borrados TEXT NULL;
