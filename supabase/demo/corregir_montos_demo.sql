-- =============================================================================
-- Corrige los montos de los datos demo ya cargados en Supabase
-- =============================================================================
-- demo_funcionamiento.sql se generó el 22-oct con las tarifas provisionales de
-- entonces. Las fichas oficiales (29-ene-26, las de
-- https://uriangato.gob.mx/web/tramites/dependencia/22) dicen:
--   UR-TT-T-03 Orden de plaqueo ................... sin costo   (demo tenía $50)
--   UR-TT-T-07 Carga y descarga, particular, mes ... $1,151.00   (demo tenía $400)
--
-- Solo toca las solicitudes demo (folios fijos) que siguen con el monto viejo y
-- sus cobros pendientes. Un cobro ya aprobado no se modifica.
-- Pegar completo en Supabase → SQL Editor → Run. Es idempotente.
-- =============================================================================

BEGIN;

UPDATE public.solicitudes SET monto = 0, updated_at = NOW()
WHERE tramite = 'UR-TT-T-03' AND monto = 50
  AND folio IN ('6AF6465E79C8DF130A3D', '092B4271758A3DA61DBD', '678B4D9F1BCB53C1443C', 'CBCD0B85513BE6C07548');

UPDATE public.solicitudes SET monto = 1151, updated_at = NOW()
WHERE tramite = 'UR-TT-T-07' AND monto = 400
  AND folio IN ('DF3D160D4C7CB6DC69E0', 'B86AAA21C50EB1927357', 'DE2C59C93BC32C741AFD', '78EDE5AB0DE06582A789', '00756351708952DD25C4');

-- Cobros pendientes de esas solicitudes: se ajustan al monto corregido.
UPDATE public.cobros c
SET monto_base = s.monto, total = s.monto - c.descuento + c.recargo - c.condonado, updated_at = NOW()
FROM public.solicitudes s
WHERE c.solicitud_id = s.id
  AND c.estatus = 'pendiente'
  AND c.monto_base <> s.monto
  AND s.folio IN ('6AF6465E79C8DF130A3D', '092B4271758A3DA61DBD', '678B4D9F1BCB53C1443C', 'CBCD0B85513BE6C07548',
                  'DF3D160D4C7CB6DC69E0', 'B86AAA21C50EB1927357', 'DE2C59C93BC32C741AFD', '78EDE5AB0DE06582A789', '00756351708952DD25C4');

COMMIT;

-- Verificación: UR-TT-T-03 en 0 y UR-TT-T-07 en 1151, con su cobro si lo tiene.
SELECT s.folio, s.tramite, s.estatus, s.monto, c.folio AS cobro, c.estatus AS cobro_estatus, c.total
FROM public.solicitudes s
LEFT JOIN public.cobros c ON c.solicitud_id = s.id
WHERE s.tramite IN ('UR-TT-T-03', 'UR-TT-T-07')
  AND s.folio IN ('6AF6465E79C8DF130A3D', '092B4271758A3DA61DBD', '678B4D9F1BCB53C1443C', 'CBCD0B85513BE6C07548',
                  'DF3D160D4C7CB6DC69E0', 'B86AAA21C50EB1927357', 'DE2C59C93BC32C741AFD', '78EDE5AB0DE06582A789', '00756351708952DD25C4')
ORDER BY s.tramite, s.folio;
