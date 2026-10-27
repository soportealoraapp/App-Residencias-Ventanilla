<?php
/**
 * Hoja de ayuda para el pago (COB-05).
 *
 * Documento autónomo: no extiende el layout del panel porque se imprime y se
 * entrega en mano. Todos los importes llegan ya calculados por HojaPagoService;
 * aquí solo se acomodan.
 */
$__view = get_defined_vars();
/** @var array<string, mixed> $hoja Desglose armado por HojaPagoService */
$hoja = (array) ($__view['hoja'] ?? []);
/** @var list<array{clave:string,titulo:string,detalle:string}> $lugares */
$lugares = (array) ($__view['lugares'] ?? []);
/** @var string $volver Panel al que regresa el botón de la barra */
$volver = (string) ($__view['volver'] ?? '/');
/** @var string $origen solicitud|boleta */
$origen = (string) ($__view['origen'] ?? 'solicitud');

if (! function_exists('formatear_dinero')) {
    helper('url_helper_custom');
}

$montoBase   = (float) ($hoja['monto_base'] ?? 0);
$descuento   = (float) ($hoja['descuento'] ?? 0);
$recargo     = (float) ($hoja['recargo'] ?? 0);
$condonado   = (float) ($hoja['condonado'] ?? 0);
$total       = (float) ($hoja['total'] ?? 0);
$referencia  = trim((string) ($hoja['referencia'] ?? ''));
$limiteDesc  = (string) ($hoja['fecha_limite_descuento'] ?? '');
$fechaHoja   = (string) ($hoja['fecha'] ?? '');
$estatus     = (string) ($hoja['estatus'] ?? '');
$estatusTexto = match ($estatus) {
    'aprobado'  => 'Pagado',
    'cancelado' => 'Cancelado',
    'pendiente' => 'Pendiente de pago',
    default     => 'Sin cobro generado',
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= esc((string) ($hoja['titulo'] ?? 'Hoja de ayuda para el pago')) ?> · <?= esc((string) ($hoja['folio'] ?? '')) ?></title>
    <link rel="icon" type="image/webp" href="/logo-uri.webp">
    <style>
        :root { --tinta: #1f2933; --suave: #6b7280; --linea: #d7dce2; --acento: #0e9f6e; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0 0 32px;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.45;
            color: var(--tinta);
            background: #eef1f4;
        }
        .barra {
            display: flex; align-items: center; justify-content: space-between; gap: 12px;
            padding: 10px 18px; background: #fff; border-bottom: 1px solid var(--linea);
        }
        .barra a, .barra button {
            font: inherit; font-weight: 600; cursor: pointer; border-radius: 6px;
            padding: 7px 14px; border: 1px solid var(--linea); background: #fff; color: var(--tinta);
            text-decoration: none;
        }
        .barra button { background: var(--acento); border-color: var(--acento); color: #fff; }
        .hoja {
            width: 100%; max-width: 21.6cm; margin: 18px auto; padding: 18mm 16mm;
            background: #fff; border: 1px solid var(--linea);
        }
        .encabezado { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid var(--tinta); padding-bottom: 12px; }
        .encabezado img { height: 58px; width: auto; }
        .encabezado .dependencia { font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--suave); }
        .encabezado h1 { margin: 2px 0 0; font-size: 17px; }
        .folio {
            display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px;
            margin: 16px 0 8px; padding: 10px 12px; border: 1px solid var(--linea); border-radius: 8px;
        }
        .folio .etiqueta { font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--suave); display: block; }
        .folio .valor { font-size: 15px; font-weight: 700; }
        .mono { font-family: "Consolas", "Courier New", monospace; letter-spacing: .04em; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 7px 8px; border-bottom: 1px solid var(--linea); vertical-align: top; text-align: left; }
        th { font-size: 10px; letter-spacing: .07em; text-transform: uppercase; color: var(--suave); font-weight: 600; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        tr.total td { border-top: 2px solid var(--tinta); border-bottom: none; font-size: 15px; font-weight: 700; padding-top: 10px; }
        .aviso { margin: 10px 0 0; padding: 9px 12px; border-left: 3px solid var(--acento); background: #f2fbf7; font-size: 12px; }
        .aviso.alerta { border-left-color: #c2410c; background: #fff7ed; }
        h2 { font-size: 12px; letter-spacing: .07em; text-transform: uppercase; color: var(--suave); margin: 22px 0 8px; }
        .pagos { display: flex; flex-wrap: wrap; gap: 10px; }
        .pago { flex: 1 1 45%; min-width: 220px; border: 1px solid var(--linea); border-radius: 8px; padding: 10px 12px; }
        .pago-titulo { font-weight: 700; }
        .pago-detalle { font-size: 12px; color: var(--suave); }
        .pago-ref { margin-top: 6px; font-size: 12px; }
        ul.notas { margin: 8px 0 0 18px; padding: 0; font-size: 12px; color: var(--suave); }
        ul.notas li { margin-bottom: 4px; }
        .pie { margin-top: 22px; padding-top: 10px; border-top: 1px solid var(--linea); font-size: 11px; color: var(--suave); display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        @page { size: letter; margin: 14mm; }
        @media print {
            body { background: #fff; padding: 0; font-size: 12px; }
            .no-print { display: none !important; }
            .hoja { max-width: none; width: auto; margin: 0; padding: 0; border: none; }
            a { color: inherit; text-decoration: none; }
            table { page-break-inside: avoid; }
            .pagos { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="barra no-print">
    <a href="<?= esc($volver, 'attr') ?>">&larr; Volver</a>
    <button type="button" onclick="window.print()">Imprimir hoja</button>
</div>

<div class="hoja printable-receipt">
    <header class="encabezado">
        <img src="<?= base_url('logo-uri.webp') ?>" alt="Escudo de Uriangato">
        <div>
            <div class="dependencia">H. Ayuntamiento de Uriangato · Movilidad y Transporte</div>
            <h1><?= esc((string) ($hoja['titulo'] ?? 'Hoja de ayuda para el pago')) ?></h1>
        </div>
    </header>

    <div class="folio">
        <div>
            <span class="etiqueta"><?= $origen === 'boleta' ? 'Boleta' : 'Folio del trámite' ?></span>
            <span class="valor mono"><?= esc((string) ($hoja['folio'] ?? '')) ?></span>
        </div>
        <div>
            <span class="etiqueta">Folio de cobro</span>
            <span class="valor mono"><?= esc((string) (($hoja['folio_cobro'] ?? '') ?: 'Por generar')) ?></span>
        </div>
        <div>
            <span class="etiqueta">Estatus</span>
            <span class="valor"><?= esc($estatusTexto) ?></span>
        </div>
        <div>
            <span class="etiqueta"><?= $origen === 'boleta' ? 'Fecha de la infracción' : 'Fecha de registro' ?></span>
            <span class="valor"><?= $fechaHoja !== '' ? esc(formatear_fecha($fechaHoja, 'd/m/Y')) : '—' ?></span>
        </div>
    </div>

    <table>
        <tbody>
            <tr>
                <th style="width: 26%;">A nombre de</th>
                <td><?= esc((string) ($hoja['nombre'] ?? '')) ?></td>
            </tr>
            <tr>
                <th>Concepto</th>
                <td><?= esc((string) ($hoja['concepto'] ?? '')) ?></td>
            </tr>
        </tbody>
    </table>

    <h2>Desglose</h2>
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="num">Importe</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Monto base</td>
                <td class="num"><?= formatear_dinero($montoBase) ?></td>
            </tr>
            <?php if ($descuento > 0): ?>
                <tr>
                    <td>Descuento por pronto pago (<?= esc(rtrim(rtrim(number_format((float) ($hoja['porcentaje_descuento'] ?? 0), 2, '.', ''), '0'), '.')) ?> %)</td>
                    <td class="num">&minus; <?= formatear_dinero($descuento) ?></td>
                </tr>
            <?php endif ?>
            <?php if ($recargo > 0): ?>
                <tr>
                    <td>Recargo por mora (<?= (int) ($hoja['meses_mora'] ?? 0) ?> mes(es) · <?= esc(rtrim(rtrim(number_format((float) ($hoja['porcentaje_recargo'] ?? 0), 2, '.', ''), '0'), '.')) ?> % mensual)</td>
                    <td class="num">+ <?= formatear_dinero($recargo) ?></td>
                </tr>
            <?php endif ?>
            <?php if ($condonado > 0): ?>
                <tr>
                    <td>Recargo condonado</td>
                    <td class="num">&minus; <?= formatear_dinero($condonado) ?></td>
                </tr>
            <?php endif ?>
            <tr class="total">
                <td>Total a pagar</td>
                <td class="num"><?= formatear_dinero($total) ?></td>
            </tr>
        </tbody>
    </table>

    <?php if (! empty($hoja['importes_registrados'])): ?>
        <p class="aviso">
            <strong>Importes del pago registrado</strong><?= ! empty($hoja['fecha_cobro']) ? ' el ' . esc((string) $hoja['fecha_cobro']) : '' ?>.
            No se recalculan por la fecha de impresión.
        </p>
    <?php endif ?>

    <?php if ($montoBase <= 0): ?>
        <p class="aviso alerta">
            <strong>Importe pendiente.</strong> La multa todavía no tiene monto fijado: el Juez Calificador debe calificar la boleta.
            Vuelve a imprimir esta hoja cuando esté calificada.
        </p>
    <?php elseif ($limiteDesc !== ''): ?>
        <?php if (! empty($hoja['aplica_descuento'])): ?>
            <p class="aviso">
                <strong>Conservas el descuento si pagas a más tardar el <?= esc(formatear_fecha($limiteDesc, 'd/m/Y')) ?>.</strong>
                Después de esa fecha se paga el monto completo y, pasado un mes de la infracción, se suma el recargo por mora.
            </p>
        <?php else: ?>
            <p class="aviso alerta">
                El plazo del descuento por pronto pago venció el <?= esc(formatear_fecha($limiteDesc, 'd/m/Y')) ?>.
                El importe mostrado ya incluye el ajuste que corresponde a la fecha de hoy.
            </p>
        <?php endif ?>
    <?php endif ?>

    <h2>Dónde pagar</h2>
    <div class="pagos">
        <?php foreach ($lugares as $lugar): ?>
            <div class="pago">
                <div class="pago-titulo"><?= esc((string) ($lugar['titulo'] ?? '')) ?></div>
                <div class="pago-detalle"><?= esc((string) ($lugar['detalle'] ?? '')) ?></div>
                <?php if (($lugar['clave'] ?? '') === 'banbajio'): ?>
                    <div class="pago-ref">
                        Referencia:
                        <?php if ($referencia !== ''): ?>
                            <span class="mono"><?= esc($referencia) ?></span>
                        <?php else: ?>
                            se asigna cuando se autoriza el pago; vuelve a imprimir esta hoja entonces.
                        <?php endif ?>
                    </div>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>

    <h2>Consulta tu estatus</h2>
    <p style="margin:0; font-size:12px;">
        Presenta este folio en la ventanilla de Tránsito Municipal o consúltalo en el portal de la Ventanilla Digital.
        <?php if ($referencia !== '' && $origen === 'boleta'): ?>
            Referencia de pago: <span class="mono"><?= esc($referencia) ?></span>.
        <?php endif ?>
    </p>

    <ul class="notas">
        <?php foreach ((array) ($hoja['notas'] ?? []) as $nota): ?>
            <li><?= esc((string) $nota) ?></li>
        <?php endforeach ?>
        <li>Este documento es informativo y no sustituye el recibo oficial de pago.</li>
    </ul>

    <div class="pie">
        <span>Uriangato, Guanajuato · Movilidad y Transporte</span>
        <span>Impreso el <?= esc(formatear_fecha(date('Y-m-d H:i:s'), 'd/m/Y H:i')) ?></span>
    </div>
</div>

</body>
</html>
