-- =============================================================================
-- 0016 · Cobros de las solicitudes que ya estaban en "Pago pendiente" (COB-02)
-- =============================================================================
--
-- Por qué: el cobro se crea solo cuando una solicitud llega a "Pago pendiente".
--   Las que ya estaban en ese estatus antes de 0012 (incluidos los datos demo)
--   no tienen cobro y no aparecen en el listado de cobros.
--
-- Qué hace: crea el cobro pendiente de cada solicitud en "Pago pendiente" que
--   no tenga uno pendiente o aprobado, con su folio COB-AAAA-NNNNNN y su
--   movimiento "crear" en el historial. No toca solicitudes en otro estatus.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Requiere antes 0012_cobros.sql. Es idempotente: si se corre otra vez, no
--   duplica cobros.
--
-- Equivale a app/Database/Migrations/2026-10-25-000031_CobrosDeSolicitudesPendientes.php.
-- =============================================================================

BEGIN;

CREATE TEMP TABLE _cobros_nuevos (id BIGINT) ON COMMIT DROP;

WITH sin_cobro AS (
    SELECT s.*
    FROM public.solicitudes s
    WHERE s.estatus = 'Pago pendiente'
      AND NOT EXISTS (
          SELECT 1 FROM public.cobros c
          WHERE c.solicitud_id = s.id AND c.estatus IN ('pendiente', 'aprobado')
      )
),
insertados AS (
    INSERT INTO public.cobros (origen, solicitud_id, ciudadano_id, nombre_pagador, concepto,
                               monto_base, descuento, recargo, condonado, total, estatus,
                               pendiente_documentos, created_at, updated_at)
    SELECT 'solicitud', s.id, s.ciudadano_id,
           COALESCE(
               NULLIF(btrim(u.nombre_completo), ''),
               (SELECT NULLIF(btrim(d.valor), '') FROM public.solicitud_datos d
                 WHERE d.solicitud_id = s.id
                   AND d.clave IN ('nombre_solicitante', 'nombre_razon_social', 'razon_social_o_nombre',
                                   'nombre_titular', 'nombre_concesionario', 'solicitante_nombre')
                   AND NULLIF(btrim(d.valor), '') IS NOT NULL
                 ORDER BY d.id LIMIT 1),
               'Solicitante del folio ' || s.folio
           ),
           CASE s.tramite
               WHEN 'UR-TT-T-01' THEN 'Concesión de Transporte'
               WHEN 'UR-TT-T-02' THEN 'Constancia de Despintado'
               WHEN 'UR-TT-T-03' THEN 'Orden de Plaqueo'
               WHEN 'UR-TT-T-04' THEN 'Permiso Eventual de Transporte'
               WHEN 'UR-TT-T-05' THEN 'Permiso para Cierre de Calle'
               WHEN 'UR-TT-T-06' THEN 'Cesión de Concesión'
               WHEN 'UR-TT-T-07' THEN 'Permiso de Carga y Descarga'
               ELSE s.tramite
           END || ' · folio ' || s.folio,
           ROUND(COALESCE(s.monto, 0), 2), 0, 0, 0, ROUND(COALESCE(s.monto, 0), 2), 'pendiente',
           0, NOW(), NOW()
    FROM sin_cobro s
    LEFT JOIN public.users u ON u.id = s.ciudadano_id
    RETURNING id, total
)
INSERT INTO _cobros_nuevos (id) SELECT id FROM insertados;

UPDATE public.cobros c
   SET folio = 'COB-' || to_char(NOW(), 'YYYY') || '-' || lpad(c.id::text, 6, '0')
 WHERE c.id IN (SELECT id FROM _cobros_nuevos);

INSERT INTO public.cobro_movimientos (cobro_id, accion, estatus_anterior, estatus_nuevo, detalle, fecha, created_at, updated_at)
SELECT c.id, 'crear', NULL, 'pendiente',
       json_build_object('total', c.total, 'origen', 'solicitud que ya estaba en Pago pendiente')::text,
       NOW(), NOW(), NOW()
FROM public.cobros c
WHERE c.id IN (SELECT id FROM _cobros_nuevos);

-- Dejar registrada la migración CI4 equivalente, para que `spark migrate`
-- no intente volver a aplicarla.
INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-25-000031_CobrosDeSolicitudesPendientes', 'CobrosDeSolicitudesPendientes', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-25-000031_CobrosDeSolicitudesPendientes'
);

-- Verificación: cuántos cobros se crearon en esta ejecución.
SELECT COUNT(*) AS cobros_creados FROM _cobros_nuevos;

COMMIT;
