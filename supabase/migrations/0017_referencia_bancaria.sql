-- =============================================================================
-- 0017 · Referencia bancaria de los cobros (COB-04)
-- =============================================================================
--
-- Qué hace:
--   - Agrega a cobros: referencia_bancaria (la que se imprime en la hoja de
--     pago para pagar en BanBajío), fecha_pago_banco e importe_pagado (lo que
--     dice el comprobante al registrarlo).
--   - Asigna la referencia a los cobros que ya existían.
--
-- Formato provisional, mientras llega el del convenio con BanBajío: 12 dígitos =
--   tipo (1 trámite, 2 multa) + id del cobro en 9 dígitos + 2 dígitos
--   verificadores MOD 97-10. Es el mismo cálculo de
--   app/Libraries/ReferenciaBancariaProvisional.php.
--
-- Cómo usarlo: péguelo completo en el SQL Editor de Supabase y ejecútelo.
--   Requiere antes 0012_cobros.sql. Es idempotente.
--
-- Equivale a app/Database/Migrations/2026-10-26-000032_AddReferenciaBancariaToCobros.php.
-- =============================================================================

BEGIN;

ALTER TABLE public.cobros ADD COLUMN IF NOT EXISTS referencia_bancaria VARCHAR(30) NULL;
ALTER TABLE public.cobros ADD COLUMN IF NOT EXISTS fecha_pago_banco DATE NULL;
ALTER TABLE public.cobros ADD COLUMN IF NOT EXISTS importe_pagado DECIMAL(12,2) NULL;
CREATE UNIQUE INDEX IF NOT EXISTS cobros_referencia_bancaria ON public.cobros (referencia_bancaria);

UPDATE public.cobros c
   SET referencia_bancaria = r.base::text || lpad((98 - ((r.base * 100) % 97))::text, 2, '0')
  FROM (
      SELECT id, (CASE origen WHEN 'boleta' THEN 2 ELSE 1 END) * 1000000000::bigint + id AS base
      FROM public.cobros
  ) r
 WHERE r.id = c.id
   AND c.referencia_bancaria IS NULL;

INSERT INTO public.migrations (version, class, "group", namespace, time, batch)
SELECT '2026-10-26-000032_AddReferenciaBancariaToCobros', 'AddReferenciaBancariaToCobros', 'App', '',
       EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::bigint, 2
WHERE NOT EXISTS (
    SELECT 1 FROM public.migrations m
    WHERE m.version = '2026-10-26-000032_AddReferenciaBancariaToCobros'
);

COMMIT;

-- Verificación: ningún cobro debe quedar sin referencia.
SELECT COUNT(*) AS cobros, COUNT(referencia_bancaria) AS con_referencia FROM public.cobros;
