-- =============================================================================
-- 0018 · Tarifas de las fichas oficiales (actualización 29-ene-26)
-- =============================================================================
--
-- Qué corrige:
--   - UR-TT-T-03 (orden de plaqueo) no tiene costo: $0.00 (antes $50.00).
--   - UR-TT-T-07 (carga y descarga):
--       por 1 camión:      día $191 · mes $1,151 · semestre $5,752 · año $11,503
--       empresa establecida, hasta 15 camiones:
--                          día $573 · mes $5,732 · semestre $28,662 · año $57,325
--     (antes $50/$400/$2,200/$4,000 y $120/$1,000/$5,500/$10,000 por camión).
--   - T-01, T-02, T-04, T-05 y T-06 ya coincidían; dejan de marcarse como
--     tarifa provisional.
--
-- No borra tarifas: la anterior se cierra con vigente_hasta = ayer y la nueva
-- rige desde hoy. Es idempotente.
--
-- Equivale a app/Database/Migrations/2026-10-27-000033_TarifasFichasOficiales.php.
-- =============================================================================

BEGIN;

CREATE TEMP TABLE _tarifas_oficiales (tramite VARCHAR(20), criterio VARCHAR(50), monto DECIMAL(12,2), descripcion VARCHAR(250)) ON COMMIT DROP;
INSERT INTO _tarifas_oficiales (tramite, criterio, monto, descripcion) VALUES
    ('UR-TT-T-01', 'base',                  9055.20, 'Concesión de transporte público de ruta fija'),
    ('UR-TT-T-02', 'base',                    64.90, 'Constancia de despintado'),
    ('UR-TT-T-03', 'base',                     0.00, 'Orden de plaqueo para cambio de unidad (sin costo)'),
    ('UR-TT-T-04', 'base',                   156.94, 'Permiso eventual de transporte'),
    ('UR-TT-T-05', 'base',                   287.00, 'Permiso para cierre parcial o total de calle'),
    ('UR-TT-T-06', 'cesion_concesion_base', 9055.20, 'Cesión de concesión'),
    ('UR-TT-T-07', 'particular_dia',         191.00, 'Carga y descarga: por día y por 1 camión'),
    ('UR-TT-T-07', 'particular_mes',        1151.00, 'Carga y descarga: por mes y por 1 camión'),
    ('UR-TT-T-07', 'particular_semestre',   5752.00, 'Carga y descarga: por semestre y por 1 camión'),
    ('UR-TT-T-07', 'particular_anio',      11503.00, 'Carga y descarga: por año y por 1 camión'),
    ('UR-TT-T-07', 'empresa_dia',            573.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por día'),
    ('UR-TT-T-07', 'empresa_mes',           5732.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por mes'),
    ('UR-TT-T-07', 'empresa_semestre',     28662.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por semestre'),
    ('UR-TT-T-07', 'empresa_anio',         57325.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por año');

-- 1. Cerrar las tarifas abiertas con otro monto.
UPDATE public.tarifas t
   SET vigente_hasta = CURRENT_DATE - 1, updated_at = NOW()
  FROM _tarifas_oficiales o
 WHERE t.tramite = o.tramite AND t.criterio = o.criterio
   AND (t.vigente_hasta IS NULL OR t.vigente_hasta >= CURRENT_DATE)
   AND abs(t.monto - o.monto) >= 0.005;

-- 2. Las que ya tienen el monto correcto dejan de ser provisionales.
UPDATE public.tarifas t
   SET placeholder_oficial = 0,
       descripcion = 'Ficha oficial ' || o.tramite || ' (29-ene-26): ' || o.descripcion,
       updated_at = NOW()
  FROM _tarifas_oficiales o
 WHERE t.tramite = o.tramite AND t.criterio = o.criterio
   AND (t.vigente_hasta IS NULL OR t.vigente_hasta >= CURRENT_DATE)
   AND abs(t.monto - o.monto) < 0.005;

-- 3. Abrir las que falten.
INSERT INTO public.tarifas (tramite, criterio, monto, vigente_desde, placeholder_oficial, descripcion, created_at, updated_at)
SELECT o.tramite, o.criterio, o.monto, CURRENT_DATE, 0,
       'Ficha oficial ' || o.tramite || ' (29-ene-26): ' || o.descripcion, NOW(), NOW()
  FROM _tarifas_oficiales o
 WHERE NOT EXISTS (
     SELECT 1 FROM public.tarifas t
      WHERE t.tramite = o.tramite AND t.criterio = o.criterio
        AND (t.vigente_hasta IS NULL OR t.vigente_hasta >= CURRENT_DATE)
 );

INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-27-000033_TarifasFichasOficiales', 'TarifasFichasOficiales', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m WHERE m.version = '2026-10-27-000033_TarifasFichasOficiales'
);

-- Verificación: tarifas vigentes hoy.
SELECT tramite, criterio, monto, placeholder_oficial
  FROM public.tarifas
 WHERE vigente_desde <= CURRENT_DATE AND (vigente_hasta IS NULL OR vigente_hasta >= CURRENT_DATE)
 ORDER BY tramite, criterio;

COMMIT;
