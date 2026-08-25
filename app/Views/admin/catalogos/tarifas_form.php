<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?><?= $modo === 'nuevo' ? 'Nueva Tarifa' : 'Editar Tarifa #' . $tarifa->id ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <a href="/admin/tarifas" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver al listado</a>
    </div>
</div>

<?php if ($modo === 'editar' && $tarifa->placeholder_oficial == 1): ?>
    <div class="alert alert-warning mb-4">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Placeholder oficial:</strong> Marcar como placeholder cuando el monto no esté confirmado con la Dirección de Movilidad.
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php if ($modo === 'nuevo'): ?>
            <?= form_open('/admin/tarifas/guardar') ?>
        <?php else: ?>
            <?= form_open('/admin/tarifas/actualizar/' . $tarifa->id) ?>
                <input type="hidden" name="id" value="<?= $tarifa->id ?>">
        <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Trámite <span class="text-danger">*</span></label>
                    <select name="tramite" class="form-select" required>
                        <option value="">Selecciona un trámite</option>
                        <?php foreach ($tramites as $t): ?>
                            <option value="<?= esc($t) ?>" <?= old('tramite', $tarifa->tramite) === $t ? 'selected' : '' ?>>
                                <?= esc($t) ?> - <?= esc(tramite_nombre($t)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Criterio <span class="text-danger">*</span></label>
                    <input type="text" name="criterio" class="form-control" maxlength="50" required
                        value="<?= esc(old('criterio', $tarifa->criterio)) ?>"
                        placeholder="ej: particular_dia o cesion_concesion_base">
                    <div class="form-text small">Identificador único del concepto de cobro.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Monto <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" name="monto" class="form-control" step="0.01" min="0" required
                            value="<?= esc(old('monto', $tarifa->monto)) ?>"
                            placeholder="0.00">
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Vigente desde <span class="text-danger">*</span></label>
                    <input type="date" name="vigente_desde" class="form-control" required
                        value="<?= esc(old('vigente_desde', $tarifa->vigente_desde)) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Vigente hasta</label>
                    <input type="date" name="vigente_hasta" class="form-control"
                        value="<?= esc(old('vigente_hasta', $tarifa->vigente_hasta)) ?>">
                    <div class="form-text small">Vacío = vigencia indefinida.</div>
                </div>

                <div class="col-md-9">
                    <label class="form-label fw-semibold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2" maxlength="250"
                        placeholder="Descripción breve del concepto..."><?= esc(old('descripcion', $tarifa->descripcion)) ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">&nbsp;</label>
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="placeholder_oficial"
                            name="placeholder_oficial" value="1"
                            <?= (old('placeholder_oficial', $tarifa->placeholder_oficial) == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="placeholder_oficial">
                            Placeholder oficial
                        </label>
                    </div>
                    <div class="form-text small">Activo = monto no confirmado con Movilidad.</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center">
                <a href="/admin/tarifas" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>
                    <?= $modo === 'nuevo' ? 'Crear tarifa' : 'Guardar cambios' ?>
                </button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<?= $this->endSection() ?>
