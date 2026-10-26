<?php declare(strict_types=1);

/**
 * Registrar un pago hecho en BanBajío (COB-04). Variables de CobrosController::banco().
 */
$__view = get_defined_vars();
/** @var string $layout */
$layout = $__view['layout'] ?? 'layouts/admin';
/** @var string $base */
$base = $__view['base'] ?? '/admin/cobros';
/** @var string $referencia */
$referencia = $__view['referencia'] ?? '';
/** @var object|null $cobro */
$cobro = $__view['cobro'] ?? null;
/** @var string|null $aviso */
$aviso = $__view['aviso'] ?? null;

$pesos = static fn ($v): string => '$' . number_format((float) $v, 2, '.', ',');
?>
<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Pago en BanBajío<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php if ($layout === 'layouts/juez'): ?>
    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc((string) session('error')) ?></div><?php endif; ?>
<?php endif; ?>

<a href="<?= esc($base, 'attr') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left me-1"></i>Listado de cobros</a>

<div class="row g-3">
    <div class="col-12 col-lg-5">
        <form method="get" action="<?= esc($base . '/banco', 'attr') ?>" class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h6"><i class="bi bi-bank me-2 text-primary"></i>Buscar por referencia</h2>
                <p class="small text-muted">Escriba la referencia que el ciudadano usó en el banco. Está impresa en su hoja de ayuda para el pago.</p>
                <label for="bancoReferencia" class="form-label fw-semibold">Referencia bancaria</label>
                <div class="input-group">
                    <input id="bancoReferencia" name="referencia" class="form-control font-monospace" inputmode="numeric" autocomplete="off"
                           value="<?= esc($referencia, 'attr') ?>" placeholder="12 dígitos, ej. 1000 0000 0190" required autofocus>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Buscar</button>
                </div>
                <?php if ($aviso !== null): ?><div class="alert alert-warning py-2 mt-3 mb-0 small"><?= esc($aviso) ?></div><?php endif; ?>
            </div>
            <div class="card-footer bg-white small text-muted">
                Formato provisional mientras se recibe el del convenio con BanBajío: 12 dígitos con dígito verificador.
            </div>
        </form>
    </div>

    <?php if ($cobro !== null): ?>
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                    <div>
                        <div class="text-muted small"><?= $cobro->origen === 'boleta' ? 'Multa de tránsito' : 'Trámite' ?> · <span class="font-monospace"><?= esc($cobro->folio) ?></span></div>
                        <h2 class="h5 mb-0"><?= esc($cobro->nombre_pagador) ?></h2>
                        <div class="small"><?= esc($cobro->concepto) ?></div>
                    </div>
                    <div class="text-end">
                        <?= view('cobros/_estatus', ['cobro' => $cobro]) ?>
                        <div class="fs-4 fw-bold mt-1"><?= $pesos($cobro->total) ?></div>
                        <?php if ($cobro->origen === 'boleta' && $cobro->estatus === 'pendiente'): ?><div class="small text-muted">total de hoy</div><?php endif; ?>
                    </div>
                </div>

                <?php if ($cobro->estatus === 'pendiente'): ?>
                    <?php if ($cobro->origen === 'boleta'): ?>
                        <p class="small text-muted mb-3">La multa es del <?= date('d/m/Y', strtotime((string) $cobro->fecha_infraccion)) ?>. El total a validar se calcula con la fecha del comprobante: con pronto pago lleva descuento y después de un mes lleva recargo.</p>
                    <?php endif; ?>
                    <button type="button" class="btn btn-success" data-cobro-accion="cobrar" data-lugar="banbajio"
                        data-id="<?= (int) $cobro->id ?>" data-folio="<?= esc($cobro->folio, 'attr') ?>" data-pagador="<?= esc($cobro->nombre_pagador, 'attr') ?>"
                        data-concepto="<?= esc($cobro->concepto, 'attr') ?>" data-total="<?= esc($pesos($cobro->total), 'attr') ?>" data-origen="<?= esc($cobro->origen, 'attr') ?>">
                        <i class="bi bi-receipt me-1"></i>Registrar comprobante de BanBajío
                    </button>
                <?php elseif ($cobro->estatus === 'aprobado'): ?>
                    <div class="alert alert-success py-2 mb-0 small">Este cobro ya está pagado. Recibo <span class="font-monospace"><?= esc($cobro->folio_recibo) ?></span>.</div>
                <?php else: ?>
                    <div class="alert alert-secondary py-2 mb-0 small">Este cobro está cancelado: <?= esc((string) $cobro->motivo) ?></div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white"><a href="<?= esc($base . '/' . (int) $cobro->id, 'attr') ?>" class="small">Ver el cobro y su historial</a></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= view('cobros/_acciones', ['base' => $base, 'lugares' => \App\Libraries\CobroService::LUGARES_PAGO, 'lugarPorDefecto' => 'banbajio']) ?>

<?= $this->endSection() ?>
