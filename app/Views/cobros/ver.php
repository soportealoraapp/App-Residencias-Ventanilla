<?php declare(strict_types=1);

/**
 * Detalle de un cobro con su historial (COB-02). Variables de CobrosController::ver().
 */
$__view = get_defined_vars();
/** @var string $layout */
$layout = $__view['layout'] ?? 'layouts/admin';
/** @var string $base */
$base = $__view['base'] ?? '/admin/cobros';
/** @var object $cobro */
$cobro = $__view['cobro'];
/** @var list<object> $historial */
$historial = $__view['historial'] ?? [];
/** @var array<string, string> $lugares */
$lugares = $__view['lugares'] ?? [];

$pesos = static fn ($v): string => '$' . number_format((float) $v, 2, '.', ',');
$fecha = static fn ($v): string => $v ? date('d/m/Y H:i', strtotime((string) $v)) : '';
$acciones = [
    'crear'              => 'Cobro registrado',
    'cobrar'             => 'Cobrado',
    'mandar_a_completar' => 'Mandado a completar',
    'cancelar'           => 'Cancelado',
    'condonar'           => 'Recargo condonado',
];
$hoja = $cobro->origen === 'boleta'
    ? '/documentos/hoja-pago/boleta/' . (int) $cobro->boleta_id
    : '/documentos/hoja-pago/solicitud/' . rawurlencode((string) $cobro->solicitud_folio);
$datos = sprintf(
    'data-id="%d" data-folio="%s" data-pagador="%s" data-concepto="%s" data-total="%s" data-origen="%s"',
    (int) $cobro->id, esc($cobro->folio, 'attr'), esc($cobro->nombre_pagador, 'attr'), esc($cobro->concepto, 'attr'), esc($pesos($cobro->total), 'attr'), esc($cobro->origen, 'attr')
);
?>
<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Cobro <?= esc($cobro->folio) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php if ($layout === 'layouts/juez'): ?>
    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc((string) session('error')) ?></div><?php endif; ?>
<?php endif; ?>

<a href="<?= esc($base, 'attr') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left me-1"></i>Listado de cobros</a>

<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                    <div>
                        <div class="text-muted small"><?= $cobro->origen === 'boleta' ? 'Multa de tránsito' : 'Trámite' ?></div>
                        <h2 class="h5 mb-0"><?= esc($cobro->nombre_pagador) ?></h2>
                        <div class="small"><?= esc($cobro->concepto) ?></div>
                    </div>
                    <div class="text-end"><?= view('cobros/_estatus', ['cobro' => $cobro]) ?></div>
                </div>

                <?php if ($cobro->estatus !== 'aprobado' && ! empty($cobro->motivo)): ?>
                    <div class="alert <?= $cobro->estatus === 'cancelado' ? 'alert-secondary' : 'alert-warning' ?> py-2">
                        <strong><?= $cobro->estatus === 'cancelado' ? 'Motivo de la cancelación:' : 'Qué falta:' ?></strong> <?= esc($cobro->motivo) ?>
                    </div>
                <?php endif; ?>

                <table class="table table-sm mb-3" style="font-variant-numeric: tabular-nums">
                    <tbody>
                        <tr><td>Monto base</td><td class="text-end"><?= $pesos($cobro->monto_base) ?></td></tr>
                        <?php if ((float) $cobro->descuento > 0): ?><tr><td>Descuento por pronto pago</td><td class="text-end text-success">− <?= $pesos($cobro->descuento) ?></td></tr><?php endif; ?>
                        <?php if ((float) $cobro->recargo > 0): ?><tr><td>Recargo por mora</td><td class="text-end text-danger">+ <?= $pesos($cobro->recargo) ?></td></tr><?php endif; ?>
                        <?php if ((float) $cobro->condonado > 0): ?><tr><td>Recargo condonado</td><td class="text-end text-success">− <?= $pesos($cobro->condonado) ?></td></tr><?php endif; ?>
                        <tr class="fw-bold"><td>Total</td><td class="text-end fs-5"><?= $pesos($cobro->total) ?></td></tr>
                    </tbody>
                </table>

                <dl class="row small mb-0">
                    <dt class="col-5 col-sm-4 text-muted fw-normal">Folio del cobro</dt><dd class="col-7 col-sm-8 font-monospace"><?= esc($cobro->folio) ?></dd>
                    <?php if (! empty($cobro->referencia_bancaria)): ?>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Referencia bancaria</dt><dd class="col-7 col-sm-8 font-monospace"><?= esc($cobro->referencia_bancaria) ?></dd>
                    <?php endif; ?>
                    <?php if ($cobro->origen === 'boleta'): ?>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Boleta</dt><dd class="col-7 col-sm-8 font-monospace"><?= esc($cobro->boleta_folio) ?></dd>
                    <?php else: ?>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Solicitud</dt>
                        <dd class="col-7 col-sm-8"><span class="font-monospace"><?= esc($cobro->solicitud_folio) ?></span> · <?= esc((string) $cobro->solicitud_estatus) ?></dd>
                    <?php endif; ?>
                    <?php if ($cobro->estatus === 'aprobado'): ?>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Recibo</dt><dd class="col-7 col-sm-8 font-monospace"><?= esc($cobro->folio_recibo) ?></dd>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Fecha de cobro</dt><dd class="col-7 col-sm-8"><?= $fecha($cobro->fecha_cobro) ?></dd>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Lugar de pago</dt><dd class="col-7 col-sm-8"><?= esc($lugares[$cobro->lugar_pago] ?? 'Sin registrar') ?></dd>
                        <?php if (! empty($cobro->referencia_pago)): ?><dt class="col-5 col-sm-4 text-muted fw-normal"><?= $cobro->lugar_pago === 'banbajio' ? 'Comprobante' : 'Referencia' ?></dt><dd class="col-7 col-sm-8 font-monospace"><?= esc($cobro->referencia_pago) ?></dd><?php endif; ?>
                        <?php if (! empty($cobro->fecha_pago_banco)): ?><dt class="col-5 col-sm-4 text-muted fw-normal">Pagado en el banco</dt><dd class="col-7 col-sm-8"><?= date('d/m/Y', strtotime((string) $cobro->fecha_pago_banco)) ?> · <?= $pesos($cobro->importe_pagado) ?></dd><?php endif; ?>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Cobró</dt><dd class="col-7 col-sm-8"><?= esc($cobro->cobrado_por_nombre ?: 'Sin registrar') ?></dd>
                    <?php else: ?>
                        <dt class="col-5 col-sm-4 text-muted fw-normal">Registrado</dt><dd class="col-7 col-sm-8"><?= $fecha($cobro->created_at) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <?php if ($cobro->estatus === 'pendiente'): ?>
                <div class="card-footer bg-white d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-success" data-cobro-accion="cobrar" <?= $datos ?>><i class="bi bi-cash-coin me-1"></i>Cobrar</button>
                    <button type="button" class="btn btn-outline-warning" data-cobro-accion="completar" <?= $datos ?>>Mandar a completar</button>
                    <button type="button" class="btn btn-outline-danger" data-cobro-accion="cancelar" <?= $datos ?>>Cancelar</button>
                    <?php if (! empty($__view['puedeCondonar'])): ?>
                        <a href="<?= esc($base . '/' . (int) $cobro->id . '/condonar', 'attr') ?>" class="btn btn-outline-primary">Condonar recargo</a>
                    <?php elseif (! empty($__view['condonacion']) && $__view['condonacion']['error'] === null && (float) $__view['condonacion']['disponible'] <= 0 && \App\Libraries\CondonacionRecargo::puedeAutorizar((int) session('user_id'))): ?>
                        <span class="text-muted small align-self-center">Sin recargo disponible para condonar</span>
                    <?php endif; ?>
                    <a href="<?= esc($hoja, 'attr') ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary ms-auto"><i class="bi bi-printer me-1"></i>Hoja de pago</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h2 class="h6 mb-0"><i class="bi bi-clock-history me-2"></i>Historial</h2></div>
            <ul class="list-group list-group-flush">
                <?php foreach ($historial as $m): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= esc($acciones[$m->accion] ?? $m->accion) ?></strong>
                            <small class="text-muted text-nowrap"><?= $fecha($m->fecha) ?></small>
                        </div>
                        <?php if (! empty($m->motivo)): ?><div class="small"><?= esc($m->motivo) ?></div><?php endif; ?>
                        <?php if ($m->accion === 'condonar'): $d = json_decode((string) $m->detalle, true) ?: []; ?>
                            <div class="small text-success">Importe condonado <?= $pesos($d['importe_adicional'] ?? 0) ?> · acumulado <?= $pesos($d['condonado_nuevo'] ?? 0) ?></div>
                        <?php endif; ?>
                        <small class="text-muted"><?= esc($m->usuario_nombre ?: ($m->usuario_username ?: 'Sistema')) ?></small>
                    </li>
                <?php endforeach; ?>
                <?php if ($historial === []): ?><li class="list-group-item text-muted small">Sin movimientos.</li><?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php if ($cobro->estatus === 'pendiente'): ?>
    <?= view('cobros/_acciones', ['base' => $base, 'lugares' => $lugares, 'lugarPorDefecto' => $__view['lugarPorDefecto'] ?? 'ventanilla']) ?>
<?php endif; ?>

<?= $this->endSection() ?>
