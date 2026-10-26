<?php declare(strict_types=1);

/**
 * Listado de cobros (COB-02). Variables de CobrosController::index().
 */
$__view = get_defined_vars();
/** @var string $layout */
$layout = $__view['layout'] ?? 'layouts/admin';
/** @var string $base */
$base = $__view['base'] ?? '/admin/cobros';
/** @var array<string, string> $filtros */
$filtros = $__view['filtros'] ?? ['q' => '', 'estatus' => '', 'origen' => '', 'desde' => '', 'hasta' => ''];
/** @var list<object> $cobros */
$cobros = $__view['cobros'] ?? [];
/** @var object|null $pager */
$pager = $__view['pager'] ?? null;
/** @var array<string, int|float> $resumen */
$resumen = $__view['resumen'] ?? [];
/** @var array<string, string> $lugares */
$lugares = $__view['lugares'] ?? [];

$pesos = static fn ($v): string => '$' . number_format((float) $v, 2, '.', ',');
$fecha = static fn ($v): string => $v ? date('d/m/Y H:i', strtotime((string) $v)) : '';
$hayFiltros = array_filter($filtros, static fn ($v) => $v !== '') !== [];
$urlEstatus = static function (string $estatus) use ($base, $filtros): string {
    $q = array_filter(array_merge($filtros, ['estatus' => $estatus]), static fn ($v) => $v !== '');
    return $base . ($q === [] ? '' : '?' . http_build_query($q));
};
?>
<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Listado de cobros<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php if ($layout === 'layouts/juez'): ?>
    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc((string) session('error')) ?></div><?php endif; ?>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <a href="<?= esc($urlEstatus('pendiente'), 'attr') ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">Pendientes de cobro</div>
                <div class="fs-4 fw-bold text-warning-emphasis"><?= (int) ($resumen['pendientes'] ?? 0) ?></div>
                <div class="small text-muted"><?= $pesos($resumen['monto_pendiente'] ?? 0) ?> por cobrar</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a href="<?= esc($urlEstatus('por_completar'), 'attr') ?>" class="card border-0 shadow-sm h-100 text-decoration-none">
            <div class="card-body">
                <div class="text-muted small">Por completar</div>
                <div class="fs-4 fw-bold text-danger-emphasis"><?= (int) ($resumen['por_completar'] ?? 0) ?></div>
                <div class="small text-muted">Les falta algún documento</div>
            </div>
        </a>
    </div>
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Cobrado hoy</div>
                <div class="fs-4 fw-bold text-success-emphasis"><?= $pesos($resumen['monto_cobrado_hoy'] ?? 0) ?></div>
                <div class="small text-muted"><?= (int) ($resumen['cobrados_hoy'] ?? 0) ?> cobro<?= (int) ($resumen['cobrados_hoy'] ?? 0) === 1 ? '' : 's' ?> registrados hoy</div>
            </div>
        </div>
    </div>
</div>

<form method="get" action="<?= esc($base, 'attr') ?>" class="card border-0 shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label for="filtroQ" class="form-label small fw-semibold mb-1">Buscar</label>
            <input id="filtroQ" name="q" class="form-control" value="<?= esc($filtros['q'], 'attr') ?>" placeholder="Nombre, concepto o folio">
        </div>
        <div class="col-6 col-md-2">
            <label for="filtroEstatus" class="form-label small fw-semibold mb-1">Estatus</label>
            <select id="filtroEstatus" name="estatus" class="form-select">
                <option value="">Todos</option>
                <?php foreach (['pendiente' => 'Pendiente', 'por_completar' => 'Por completar', 'aprobado' => 'Aprobado', 'cancelado' => 'Cancelado'] as $valor => $texto): ?>
                    <option value="<?= $valor ?>" <?= $filtros['estatus'] === $valor ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="filtroOrigen" class="form-label small fw-semibold mb-1">Tipo</label>
            <select id="filtroOrigen" name="origen" class="form-select">
                <option value="">Trámites y multas</option>
                <option value="solicitud" <?= $filtros['origen'] === 'solicitud' ? 'selected' : '' ?>>Trámites</option>
                <option value="boleta" <?= $filtros['origen'] === 'boleta' ? 'selected' : '' ?>>Multas</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label for="filtroDesde" class="form-label small fw-semibold mb-1">Desde</label>
            <input id="filtroDesde" type="date" name="desde" class="form-control" value="<?= esc($filtros['desde'], 'attr') ?>">
        </div>
        <div class="col-6 col-md-2">
            <label for="filtroHasta" class="form-label small fw-semibold mb-1">Hasta</label>
            <input id="filtroHasta" type="date" name="hasta" class="form-control" value="<?= esc($filtros['hasta'], 'attr') ?>">
        </div>
        <div class="col-12 d-flex gap-2 justify-content-end">
            <?php if ($hayFiltros): ?><a href="<?= esc($base, 'attr') ?>" class="btn btn-outline-secondary">Quitar filtros</a><?php endif; ?>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
        </div>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h2 class="h6 mb-0"><i class="bi bi-cash-stack me-2 text-primary"></i>Cobros (<?= number_format($pager ? $pager->getTotal() : count($cobros)) ?>)</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Nombre del ciudadano</th>
                    <th>Concepto</th>
                    <th class="text-end">Total</th>
                    <th>Estatus</th>
                    <th>Fecha de cobro</th>
                    <th class="text-end pe-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($cobros === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-5"><?= $hayFiltros ? 'Ningún cobro coincide con los filtros.' : 'Todavía no hay cobros. Se crean solos cuando un trámite llega a "Pago pendiente" o el juez califica una multa.' ?></td></tr>
            <?php endif; ?>
            <?php foreach ($cobros as $c): ?>
                <?php
                $hoja = $c->origen === 'boleta'
                    ? '/documentos/hoja-pago/boleta/' . (int) $c->boleta_id
                    : '/documentos/hoja-pago/solicitud/' . rawurlencode((string) $c->solicitud_folio);
                $datos = sprintf(
                    'data-id="%d" data-folio="%s" data-pagador="%s" data-concepto="%s" data-total="%s" data-origen="%s"',
                    (int) $c->id, esc($c->folio, 'attr'), esc($c->nombre_pagador, 'attr'), esc($c->concepto, 'attr'), esc($pesos($c->total), 'attr'), esc($c->origen, 'attr')
                );
                ?>
                <tr>
                    <td class="ps-3">
                        <div class="fw-semibold"><?= esc($c->nombre_pagador) ?></div>
                        <a href="<?= esc($base . '/' . (int) $c->id, 'attr') ?>" class="small font-monospace text-decoration-none"><?= esc($c->folio) ?></a>
                    </td>
                    <td>
                        <span class="badge text-bg-light border me-1"><?= $c->origen === 'boleta' ? 'Multa' : 'Trámite' ?></span>
                        <span class="small"><?= esc($c->concepto) ?></span>
                    </td>
                    <td class="text-end fw-semibold text-nowrap" style="font-variant-numeric: tabular-nums"><?= $pesos($c->total) ?></td>
                    <td>
                        <?= view('cobros/_estatus', ['cobro' => $c]) ?>
                        <?php if ($c->estatus !== 'aprobado' && ! empty($c->motivo)): ?>
                            <div class="small text-muted text-truncate" style="max-width: 16rem" title="<?= esc($c->motivo, 'attr') ?>"><?= esc($c->motivo) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small text-nowrap">
                        <?php if ($c->fecha_cobro): ?>
                            <?= $fecha($c->fecha_cobro) ?><div class="text-muted"><?= esc($lugares[$c->lugar_pago] ?? '') ?></div>
                        <?php else: ?>
                            <span class="text-muted">Registrado <?= $fecha($c->created_at) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                        <?php if ($c->estatus === 'pendiente'): ?>
                            <button type="button" class="btn btn-sm btn-success" data-cobro-accion="cobrar" <?= $datos ?>><i class="bi bi-cash-coin me-1"></i>Cobrar</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" data-cobro-accion="completar" <?= $datos ?>>Mandar a completar</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-cobro-accion="cancelar" <?= $datos ?>>Cancelar</button>
                            <a href="<?= esc($hoja, 'attr') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Hoja de ayuda para el pago"><i class="bi bi-printer"></i></a>
                        <?php elseif ($c->estatus === 'aprobado'): ?>
                            <span class="small text-muted me-1">Recibo <span class="font-monospace"><?= esc($c->folio_recibo) ?></span></span>
                            <a href="<?= esc($base . '/' . (int) $c->id, 'attr') ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                        <?php else: ?>
                            <a href="<?= esc($base . '/' . (int) $c->id, 'attr') ?>" class="btn btn-sm btn-outline-secondary">Ver motivo</a>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pager && $pager->getPageCount() > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-center pt-3"><?= $pager->links('default', 'bootstrap') ?></div>
    <?php endif; ?>
</div>

<?= view('cobros/_acciones', ['base' => $base, 'lugares' => $lugares, 'lugarPorDefecto' => $__view['lugarPorDefecto'] ?? 'ventanilla']) ?>

<?= $this->endSection() ?>
