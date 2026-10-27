<?php declare(strict_types=1);

/**
 * Reporte de condonaciones de recargo (COB-11). Variables de CobrosController::condonaciones().
 */
$__view = get_defined_vars();
$layout = $__view['layout'] ?? 'layouts/admin';
$base   = $__view['base'] ?? '/admin/cobros';
$filtros = $__view['filtros'] ?? [];
$rango   = $__view['rango'];
$reporte = $__view['reporte'];
$autorizadores = $__view['autorizadores'] ?? [];
$periodos = $__view['periodos'] ?? [];

$pesos = static fn ($v): string => '$' . number_format((float) $v, 2, '.', ',');
$estados = ['pendiente' => 'Pendientes', 'aprobado' => 'Aprobados (cobrados)', 'cancelado' => 'Cancelados'];
?>
<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Condonaciones<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php if ($layout === 'layouts/juez'): ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc((string) session('error')) ?></div><?php endif; ?>
<?php endif; ?>

<a href="<?= esc($base, 'attr') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left me-1"></i>Listado de cobros</a>

<form method="get" action="<?= esc($base . '/condonaciones', 'attr') ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-6 col-md-2">
            <label for="condPeriodo" class="form-label small fw-semibold mb-1">Periodo</label>
            <select id="condPeriodo" name="periodo" class="form-select">
                <?php foreach ($periodos as $clave => $texto): ?>
                    <option value="<?= esc((string) $clave, 'attr') ?>" <?= ($filtros['periodo'] ?? 'dia') === $clave ? 'selected' : '' ?>><?= esc((string) $texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3" data-corte="fecha">
            <label for="condFecha" class="form-label small fw-semibold mb-1">Día de referencia</label>
            <input id="condFecha" type="date" name="fecha" class="form-control" value="<?= esc($filtros['fecha'] !== '' ? $filtros['fecha'] : $rango['desde']->format('Y-m-d'), 'attr') ?>">
        </div>
        <div class="col-6 col-md-2" data-corte="rango">
            <label for="condDesde" class="form-label small fw-semibold mb-1">Desde</label>
            <input id="condDesde" type="date" name="desde" class="form-control" value="<?= esc($filtros['desde'] ?? '', 'attr') ?>">
        </div>
        <div class="col-6 col-md-2" data-corte="rango">
            <label for="condHasta" class="form-label small fw-semibold mb-1">Hasta</label>
            <input id="condHasta" type="date" name="hasta" class="form-control" value="<?= esc($filtros['hasta'] ?? '', 'attr') ?>">
        </div>
        <div class="col-12 col-md-3">
            <label for="condAutorizo" class="form-label small fw-semibold mb-1">Autorizó</label>
            <select id="condAutorizo" name="autorizo" class="form-select">
                <option value="">Todas las personas</option>
                <?php foreach ($autorizadores as $u): ?>
                    <option value="<?= (int) $u->id ?>" <?= (string) $u->id === ($filtros['autorizo'] ?? '') ? 'selected' : '' ?>><?= esc($u->nombre_completo ?: $u->username) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto ms-md-auto">
            <button class="btn btn-primary w-100" type="submit">Ver condonaciones</button>
        </div>
    </div>
</form>

<h2 class="h5 mb-3"><?= esc($rango['etiqueta']) ?> · por fecha de autorización</h2>

<div class="row g-3 mb-3" style="font-variant-numeric: tabular-nums">
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <div class="text-muted small">Total condonado en el periodo</div>
            <div class="fs-3 fw-bold"><?= $pesos($reporte['total']) ?></div>
            <div class="small text-muted"><?= (int) $reporte['cantidad'] ?> autorización<?= (int) $reporte['cantidad'] === 1 ? '' : 'es' ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body small">
            <div class="text-muted mb-1">Según el estado actual del cobro</div>
            <?php foreach ($estados as $clave => $texto): ?>
                <div class="d-flex justify-content-between"><span><?= $texto ?></span><span><?= $pesos($reporte['por_estado'][$clave] ?? 0) ?></span></div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100"><div class="card-body small">
            <div class="text-muted mb-1">Por quien autorizó</div>
            <?php foreach ($reporte['por_autorizador'] as $nombre => $fila): ?>
                <div class="d-flex justify-content-between"><span><?= esc((string) $nombre) ?> (<?= (int) $fila['cantidad'] ?>)</span><span><?= $pesos($fila['total']) ?></span></div>
            <?php endforeach; ?>
            <?php if ($reporte['por_autorizador'] === []): ?><span class="text-muted">Sin condonaciones.</span><?php endif; ?>
        </div></div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" style="font-variant-numeric: tabular-nums">
            <thead class="table-light">
                <tr><th class="ps-3">Fecha</th><th>Boleta</th><th>Cobro</th><th class="text-end">Importe</th><th>Motivo</th><th>Autorizó</th><th class="pe-3">Estado del cobro</th></tr>
            </thead>
            <tbody>
            <?php foreach ($reporte['filas'] as $f): ?>
                <tr>
                    <td class="ps-3 small text-nowrap"><?= esc(date('d/m/Y H:i', strtotime((string) $f->fecha))) ?></td>
                    <td class="font-monospace small"><?= esc((string) $f->boleta_folio) ?></td>
                    <td class="font-monospace small"><a href="<?= esc($base . '/' . (int) $f->cobro_id, 'attr') ?>"><?= esc((string) $f->cobro_folio) ?></a></td>
                    <td class="text-end"><?= $pesos($f->importe) ?></td>
                    <td class="small"><?= esc((string) $f->motivo) ?></td>
                    <td class="small"><?= esc((string) $f->autorizador) ?></td>
                    <td class="small pe-3"><?= esc($estados[$f->cobro_estatus] ?? (string) $f->cobro_estatus) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if ($reporte['filas'] === []): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay condonaciones en este periodo.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var periodo = document.getElementById('condPeriodo');
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
