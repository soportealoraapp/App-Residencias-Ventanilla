<?php declare(strict_types=1);

/**
 * Corte de caja (COB-03). Variables de CobrosController::corte().
 */
$__view = get_defined_vars();
/** @var string $layout */
$layout = $__view['layout'] ?? 'layouts/admin';
/** @var string $base */
$base = $__view['base'] ?? '/admin/cobros';
/** @var array<string, string> $filtros */
$filtros = $__view['filtros'] ?? [];
/** @var array{desde: DateTimeImmutable, hasta: DateTimeImmutable, etiqueta: string} $rango */
$rango = $__view['rango'];
/** @var array<string, mixed> $corte */
$corte = $__view['corte'];
/** @var list<object> $cobradores */
$cobradores = $__view['cobradores'] ?? [];
/** @var array<string, string> $periodos */
$periodos = $__view['periodos'] ?? [];

$pesos = static fn ($v): string => '$' . number_format((float) $v, 2, '.', ',');
$nombreCobrador = '';
foreach ($cobradores as $u) {
    if ((string) $u->id === ($filtros['cobrador'] ?? '')) {
        $nombreCobrador = $u->nombre_completo ?: $u->username;
    }
}
$consulta = http_build_query(array_filter($filtros, static fn ($v) => $v !== ''));
?>
<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Corte de caja<?= $this->endSection() ?>
<?= $this->section('content') ?>

<style>
    .solo-impresion { display: none; }
    .corte-cifras { font-variant-numeric: tabular-nums; }
    @media print {
        .solo-impresion { display: block; }
        .card { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; }
        .firmas { display: flex !important; }
        a { text-decoration: none; color: #000; }
    }
</style>

<div class="solo-impresion mb-3">
    <h1 class="h4 mb-0">Corte de caja · Ventanilla de Movilidad de Uriangato</h1>
    <div><?= esc($rango['etiqueta']) ?><?= $nombreCobrador !== '' ? ' · Cobró: ' . esc($nombreCobrador) : '' ?></div>
    <small>Impreso el <?= date('d/m/Y H:i') ?> por <?= esc(session('nombre_completo') ?? session('username') ?? '') ?></small>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
    <a href="<?= esc($base, 'attr') ?>" class="btn btn-sm btn-link px-0"><i class="bi bi-arrow-left me-1"></i>Listado de cobros</a>
    <div class="d-flex gap-2">
        <a href="<?= esc($base . '/corte/csv' . ($consulta !== '' ? '?' . $consulta : ''), 'attr') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>Descargar CSV</a>
        <button type="button" class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir corte</button>
    </div>
</div>

<form method="get" action="<?= esc($base . '/corte', 'attr') ?>" class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label for="cortePeriodo" class="form-label small fw-semibold mb-1">Periodo</label>
            <select id="cortePeriodo" name="periodo" class="form-select">
                <?php foreach ($periodos as $clave => $texto): ?>
                    <option value="<?= $clave ?>" <?= ($filtros['periodo'] ?? 'dia') === $clave ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3" data-corte="fecha">
            <label for="corteFecha" class="form-label small fw-semibold mb-1">Día de referencia</label>
            <input id="corteFecha" type="date" name="fecha" class="form-control" value="<?= esc($filtros['fecha'] !== '' ? $filtros['fecha'] : $rango['desde']->format('Y-m-d'), 'attr') ?>">
        </div>
        <div class="col-6 col-md-2" data-corte="rango">
            <label for="corteDesde" class="form-label small fw-semibold mb-1">Desde</label>
            <input id="corteDesde" type="date" name="desde" class="form-control" value="<?= esc($filtros['desde'] ?? '', 'attr') ?>">
        </div>
        <div class="col-6 col-md-2" data-corte="rango">
            <label for="corteHasta" class="form-label small fw-semibold mb-1">Hasta</label>
            <input id="corteHasta" type="date" name="hasta" class="form-control" value="<?= esc($filtros['hasta'] ?? '', 'attr') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label for="corteCobrador" class="form-label small fw-semibold mb-1">Cobró</label>
            <select id="corteCobrador" name="cobrador" class="form-select">
                <option value="">Todas las personas</option>
                <?php foreach ($cobradores as $u): ?>
                    <option value="<?= (int) $u->id ?>" <?= (string) $u->id === ($filtros['cobrador'] ?? '') ? 'selected' : '' ?>><?= esc($u->nombre_completo ?: $u->username) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto ms-md-auto">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-calculator me-1"></i>Ver corte</button>
        </div>
    </div>
</form>

<h2 class="h5 mb-3 no-print"><?= esc($rango['etiqueta']) ?><?= $nombreCobrador !== '' ? ' · ' . esc($nombreCobrador) : '' ?></h2>

<div class="row g-3 mb-3 corte-cifras">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total cobrado</div>
            <div class="fs-3 fw-bold text-success-emphasis"><?= $pesos($corte['total']) ?></div>
            <div class="small text-muted"><?= (int) $corte['cantidad'] ?> recibo<?= (int) $corte['cantidad'] === 1 ? '' : 's' ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body small">
            <div class="d-flex justify-content-between"><span class="text-muted">Monto base</span><span><?= $pesos($corte['monto_base']) ?></span></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Descuentos</span><span class="text-success">− <?= $pesos($corte['descuentos']) ?></span></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Recargos</span><span class="text-danger">+ <?= $pesos($corte['recargos']) ?></span></div>
            <div class="d-flex justify-content-between"><span class="text-muted">Recargo condonado (aplicado)</span><span class="text-success">− <?= $pesos($corte['condonado']) ?></span></div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Cancelados en el periodo</div>
            <div class="fs-4 fw-bold text-secondary"><?= (int) $corte['cancelados']['cantidad'] ?></div>
            <div class="small text-muted"><?= $pesos($corte['cancelados']['total']) ?> que no se cobraron</div>
        </div></div>
    </div>
</div>

<div class="row g-3 mb-3 corte-cifras">
    <?php foreach (['por_concepto' => 'Por concepto', 'por_lugar' => 'Por lugar de pago', 'por_cobrador' => 'Por quién cobró'] as $grupo => $titulo): ?>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><h3 class="h6 mb-0"><?= $titulo ?></h3></div>
                <table class="table table-sm mb-0">
                    <tbody>
                    <?php foreach ($corte[$grupo] as $nombre => $fila): ?>
                        <tr><td class="ps-3"><?= esc($nombre) ?> <span class="text-muted small">(<?= (int) $fila['cantidad'] ?>)</span></td><td class="text-end pe-3"><?= $pesos($fila['total']) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if ($corte[$grupo] === []): ?><tr><td class="ps-3 text-muted small" colspan="2">Sin cobros en el periodo.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><h3 class="h6 mb-0"><i class="bi bi-receipt me-2"></i>Recibos del periodo</h3></div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0 corte-cifras">
            <thead class="table-light">
                <tr><th class="ps-3">Recibo</th><th>Fecha</th><th>Nombre</th><th>Concepto</th><th>Lugar</th><th>Cobró</th><th class="text-end pe-3">Total</th></tr>
            </thead>
            <tbody>
            <?php foreach ($corte['cobros'] as $c): ?>
                <tr>
                    <td class="ps-3 font-monospace small"><a href="<?= esc($base . '/' . (int) $c->id, 'attr') ?>"><?= esc($c->folio_recibo) ?></a></td>
                    <td class="small text-nowrap"><?= date('d/m/Y H:i', strtotime((string) $c->fecha_cobro)) ?></td>
                    <td><?= esc($c->nombre_pagador) ?></td>
                    <td class="small"><?= esc($c->tipo_concepto) ?></td>
                    <td class="small"><?= esc($c->lugar) ?><?= ! empty($c->referencia_pago) ? '<div class="text-muted font-monospace">' . esc($c->referencia_pago) . '</div>' : '' ?></td>
                    <td class="small"><?= esc($c->cobrador) ?></td>
                    <td class="text-end pe-3"><?= $pesos($c->total) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($corte['cobros'] === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No se cobró nada en este periodo.</td></tr>
            <?php else: ?>
                <tr class="fw-bold"><td colspan="6" class="ps-3 text-end">Total del corte</td><td class="text-end pe-3"><?= $pesos($corte['total']) ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="firmas d-none justify-content-around mt-5 pt-4 text-center small">
    <div style="width: 40%"><div class="border-top border-dark pt-1">Entregó</div></div>
    <div style="width: 40%"><div class="border-top border-dark pt-1">Recibió</div></div>
</div>

<script>
(function () {
    var periodo = document.getElementById('cortePeriodo');
    function ajustar() {
        var rango = periodo.value === 'rango';
        document.querySelectorAll('[data-corte="rango"]').forEach(function (el) { el.hidden = !rango; });
        document.querySelectorAll('[data-corte="fecha"]').forEach(function (el) { el.hidden = rango; });
    }
    periodo.addEventListener('change', ajustar);
    ajustar();
})();
</script>

<?= $this->endSection() ?>
