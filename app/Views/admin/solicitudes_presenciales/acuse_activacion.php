<?php
/**
 * Acuse con código de activación (auditoría A1).
 *
 * Se entrega en mano a la persona que ventanilla registró sin cuenta de acceso.
 * Con este código ella reclama su cuenta en el portal; la CURP sola ya no basta.
 * Cada vez que se abre esta página se genera un código nuevo y el anterior deja
 * de servir.
 */
$__view = get_defined_vars();
/** @var object $ciudadano */
$ciudadano = $__view['ciudadano'];
$codigo    = (string) ($__view['codigo'] ?? '');
$vence     = (string) ($__view['vence'] ?? '');
$portal    = (string) ($__view['portal'] ?? '');
/** @var list<object> $solicitudes */
$solicitudes = (array) ($__view['solicitudes'] ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Acuse de registro · <?= esc((string) ($ciudadano->nombre_completo ?? '')) ?></title>
    <link rel="icon" type="image/webp" href="/logo-uri.webp">
    <style>
        :root { --tinta: #1f2933; --suave: #6b7280; --linea: #d7dce2; --acento: #0e9f6e; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 0 0 32px; font-family: "Segoe UI", Arial, Helvetica, sans-serif; font-size: 13px; line-height: 1.45; color: var(--tinta); background: #eef1f4; }
        .barra { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 18px; background: #fff; border-bottom: 1px solid var(--linea); }
        .barra a, .barra button { font: inherit; font-weight: 600; cursor: pointer; border-radius: 6px; padding: 7px 14px; border: 1px solid var(--linea); background: #fff; color: var(--tinta); text-decoration: none; }
        .barra button { background: var(--acento); border-color: var(--acento); color: #fff; }
        .hoja { width: 100%; max-width: 21.6cm; margin: 18px auto; padding: 18mm 16mm; background: #fff; border: 1px solid var(--linea); }
        .encabezado { display: flex; align-items: center; gap: 14px; border-bottom: 2px solid var(--tinta); padding-bottom: 12px; }
        .encabezado img { height: 58px; width: auto; }
        .encabezado .dependencia { font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--suave); }
        .encabezado h1 { margin: 2px 0 0; font-size: 17px; }
        h2 { font-size: 12px; letter-spacing: .07em; text-transform: uppercase; color: var(--suave); margin: 22px 0 8px; }
        .dato { margin: 4px 0; }
        .dato .etiqueta { color: var(--suave); }
        .codigo { margin: 18px 0 6px; padding: 16px; border: 2px dashed var(--tinta); border-radius: 10px; text-align: center; }
        .codigo .etiqueta { display: block; font-size: 10px; letter-spacing: .08em; text-transform: uppercase; color: var(--suave); }
        .codigo .valor { display: block; margin-top: 4px; font-family: "Consolas", "Courier New", monospace; font-size: 30px; font-weight: 700; letter-spacing: .18em; }
        .codigo .vence { display: block; margin-top: 4px; font-size: 12px; color: var(--suave); }
        ol { margin: 6px 0 0 18px; padding: 0; }
        ol li { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid var(--linea); text-align: left; }
        th { font-size: 10px; letter-spacing: .07em; text-transform: uppercase; color: var(--suave); font-weight: 600; }
        .mono { font-family: "Consolas", "Courier New", monospace; }
        .aviso { margin: 14px 0 0; padding: 9px 12px; border-left: 3px solid #c2410c; background: #fff7ed; font-size: 12px; }
        .pie { margin-top: 22px; padding-top: 10px; border-top: 1px solid var(--linea); font-size: 11px; color: var(--suave); }
        @page { size: letter; margin: 14mm; }
        @media print {
            body { background: #fff; padding: 0; font-size: 12px; }
            .no-print { display: none !important; }
            .hoja { max-width: none; width: auto; margin: 0; padding: 0; border: none; }
        }
    </style>
</head>
<body>

<div class="barra no-print">
    <a href="<?= site_url('admin/solicitudes/nueva?ciudadano_id=' . (int) $ciudadano->id) ?>">&larr; Volver</a>
    <button type="button" onclick="window.print()">Imprimir acuse</button>
</div>

<div class="hoja">
    <header class="encabezado">
        <img src="<?= base_url('logo-uri.webp') ?>" alt="Escudo de Uriangato">
        <div>
            <div class="dependencia">H. Ayuntamiento de Uriangato · Movilidad y Transporte</div>
            <h1>Acuse de registro y código de activación</h1>
        </div>
    </header>

    <h2>Persona registrada</h2>
    <p class="dato"><span class="etiqueta">Nombre:</span> <strong><?= esc((string) ($ciudadano->nombre_completo ?? '')) ?></strong></p>
    <?php if (trim((string) ($ciudadano->curp ?? '')) !== ''): ?>
        <p class="dato"><span class="etiqueta">CURP:</span> <span class="mono"><?= esc((string) $ciudadano->curp) ?></span></p>
    <?php endif; ?>
    <p class="dato"><span class="etiqueta">Fecha de emisión:</span> <?= esc(date('d/m/Y H:i')) ?></p>

    <div class="codigo">
        <span class="etiqueta">Código de activación</span>
        <span class="valor"><?= esc($codigo) ?></span>
        <span class="vence">Válido hasta el <?= esc($vence) ?></span>
    </div>

    <h2>Cómo activar tu cuenta en línea</h2>
    <ol>
        <li>Entra a <strong><?= esc($portal) ?></strong>.</li>
        <li>Llena tus datos y escribe el código de activación de este acuse.</li>
        <li>Crea tu usuario y contraseña. Tus trámites de ventanilla aparecerán en tu cuenta.</li>
    </ol>

    <?php if ($solicitudes !== []): ?>
        <h2>Trámites registrados a tu nombre</h2>
        <table>
            <thead><tr><th>Folio</th><th>Trámite</th><th>Estatus</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td class="mono"><?= esc((string) $s->folio) ?></td>
                    <td><?= esc((string) $s->tramite) ?></td>
                    <td><?= esc((string) $s->estatus) ?></td>
                    <td><?= esc(date('d/m/Y', strtotime((string) $s->fecha_solicitud) ?: time())) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="aviso">
        Guarda este acuse y no compartas el código: quien lo tenga puede activar la cuenta a tu nombre.
        Si lo pierdes o vence, pide uno nuevo en la ventanilla; el anterior deja de servir.
    </p>

    <div class="pie">Dirección de Movilidad y Transporte · Blvd. Juan Pablo II núm. 40, Uriangato, Gto. · 445 457 8000</div>
</div>

</body>
</html>
