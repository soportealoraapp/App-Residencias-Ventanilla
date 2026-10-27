<?php declare(strict_types=1);

/**
 * Condonación de recargo de una multa (COB-11). Variables de CobrosController::formularioCondonar().
 */
$__view = get_defined_vars();
$layout = $__view['layout'] ?? 'layouts/admin';
$base   = $__view['base'] ?? '/admin/cobros';
/** @var array<string, mixed> $vista */
$vista  = $__view['vista'];
$cobro  = $__view['cobro'];
$boleta = $__view['boleta'];

$pesos = static fn ($v): string => '$' . number_format((float) $v, 2, '.', ',');
$sinDisponible = (float) $vista['disponible'] <= 0;
?>
<?= $this->extend($layout) ?>
<?= $this->section('pageTitle') ?>Condonar recargo<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php if ($layout === 'layouts/juez'): ?>
    <?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif; ?>
    <?php if (session('error')): ?><div class="alert alert-danger"><?= esc((string) session('error')) ?></div><?php endif; ?>
<?php endif; ?>

<a href="<?= esc($base . '/' . (int) $cobro->id, 'attr') ?>" class="btn btn-sm btn-link px-0 mb-2"><i class="bi bi-arrow-left me-1"></i>Volver al cobro</a>

<div class="row g-3">
    <div class="col-12 col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h2 class="h6 mb-0">Datos del cobro</h2></div>
            <div class="card-body">
                <dl class="row small mb-2">
                    <dt class="col-5 text-muted fw-normal">Boleta</dt><dd class="col-7 font-monospace"><?= esc((string) ($boleta->folio ?? '')) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Cobro</dt><dd class="col-7 font-monospace"><?= esc($cobro->folio) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Infractor</dt><dd class="col-7"><?= esc($cobro->nombre_pagador) ?></dd>
                    <dt class="col-5 text-muted fw-normal">Fecha de cálculo</dt><dd class="col-7"><?= esc(date('d/m/Y H:i', strtotime((string) $vista['fecha_calculo']))) ?></dd>
                </dl>
                <table class="table table-sm mb-0" style="font-variant-numeric: tabular-nums">
                    <tbody>
                        <tr><td>Monto base</td><td class="text-end"><?= $pesos($vista['monto_base']) ?></td></tr>
                        <tr><td>Recargo vigente</td><td class="text-end text-danger"><?= $pesos($vista['recargo']) ?></td></tr>
                        <tr><td>Condonado previamente</td><td class="text-end"><?= $pesos($vista['autorizado']) ?></td></tr>
                        <tr><td>Disponible para condonar</td><td class="text-end fw-semibold"><?= $pesos($vista['disponible']) ?></td></tr>
                        <tr class="fw-bold"><td>Total a pagar actual</td><td class="text-end"><?= $pesos($vista['total']) ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <form method="post" action="<?= esc($base . '/' . (int) $cobro->id . '/condonar', 'attr') ?>" class="card border-0 shadow-sm" id="formCondonar">
            <?= csrf_field() ?>
            <input type="hidden" name="version" value="<?= esc((string) $vista['version'], 'attr') ?>">
            <div class="card-header bg-white"><h2 class="h6 mb-0">Condonar recargo</h2></div>
            <div class="card-body">
                <?php if (! empty($vista['excede_recargo'])): ?>
                    <div class="alert alert-warning py-2 small">Lo autorizado antes ($<?= number_format((float) $vista['autorizado'], 2) ?>) supera el recargo vigente: solo se aplica la parte que el recargo cubre. No se modificó ningún dato.</div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="importeCondonar" class="form-label fw-semibold">Importe adicional a condonar (pesos)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input id="importeCondonar" name="importe" type="text" inputmode="decimal" class="form-control" required autocomplete="off"
                               value="<?= esc((string) old('importe'), 'attr') ?>" <?= $sinDisponible ? 'disabled' : '' ?>>
                    </div>
                    <div class="form-text">Máximo $<?= number_format((float) $vista['disponible'], 2) ?>, con punto decimal y hasta dos decimales.</div>
                </div>
                <div class="mb-3">
                    <label for="motivoCondonar" class="form-label fw-semibold">Motivo (obligatorio)</label>
                    <textarea id="motivoCondonar" name="motivo" class="form-control" rows="3" required maxlength="500" <?= $sinDisponible ? 'disabled' : '' ?>><?= esc((string) old('motivo')) ?></textarea>
                </div>
                <p class="small mb-1">Total resultante: <strong id="totalResultante" data-total="<?= esc((string) $vista['total'], 'attr') ?>"><?= $pesos($vista['total']) ?></strong></p>
                <div class="alert alert-info py-2 small">
                    La condonación cubre únicamente el importe fijado aquí. Si el recargo sigue creciendo, la parte adicional se cobra de forma normal.
                    No reduce la multa base ni se puede editar o revertir después.
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="confirmar" value="1" id="confirmarCondonar" required <?= $sinDisponible ? 'disabled' : '' ?>>
                    <label class="form-check-label" for="confirmarCondonar">Confirmo que estoy autorizado y que el importe y el motivo son correctos.</label>
                </div>
                <button type="submit" class="btn btn-primary" <?= $sinDisponible ? 'disabled' : '' ?>>Registrar condonación</button>
                <a href="<?= esc($base . '/' . (int) $cobro->id, 'attr') ?>" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var importe = document.getElementById('importeCondonar');
    var salida = document.getElementById('totalResultante');
    var form = document.getElementById('formCondonar');
    if (!importe || !salida) { return; }
    var actual = parseFloat(salida.dataset.total) || 0;
    importe.addEventListener('input', function () {
        var v = /^\d{1,9}(\.\d{1,2})?$/.test(importe.value.trim()) ? parseFloat(importe.value) : 0;
        salida.textContent = '$' + Math.max(0, actual - v).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });
    form.addEventListener('submit', function () {
        var boton = form.querySelector('button[type="submit"]');
        if (boton) { boton.disabled = true; }
    });
})();
</script>

<?= $this->endSection() ?>
