<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?><?= $modo === 'nuevo' ? 'Nueva Concesión (stub)' : 'Editar Concesión #' . $concesion->id ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <a href="/admin/concesiones" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver al listado</a>
    </div>
</div>

<div class="alert alert-warning mb-4">
    <i class="bi bi-info-circle me-2"></i>
    <strong>Catálogo provisorio.</strong> No corresponde al padrón real de concesiones del municipio.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php if ($modo === 'nuevo'): ?>
            <?= form_open('/admin/concesiones/guardar') ?>
        <?php else: ?>
            <?= form_open('/admin/concesiones/actualizar/' . $concesion->id) ?>
                <input type="hidden" name="id" value="<?= $concesion->id ?>">
        <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Número de título <span class="text-danger">*</span></label>
                    <input type="text" name="numero_titulo" class="form-control" maxlength="50" required
                        value="<?= esc(old('numero_titulo', $concesion->numero_titulo)) ?>"
                        placeholder="ej: CONC-2024-0001">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Titular actual <span class="text-danger">*</span></label>
                    <input type="text" name="titular_actual" class="form-control" maxlength="180" required
                        value="<?= esc(old('titular_actual', $concesion->titular_actual)) ?>"
                        placeholder="Nombre completo del titular">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Placas vehículo</label>
                    <input type="text" name="vehiculo_placas" class="form-control" maxlength="10"
                        value="<?= esc(old('vehiculo_placas', $concesion->vehiculo_placas)) ?>"
                        placeholder="GTO-123-45">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Número de serie vehículo</label>
                    <input type="text" name="vehiculo_num_serie" class="form-control" maxlength="20"
                        value="<?= esc(old('vehiculo_num_serie', $concesion->vehiculo_num_serie)) ?>"
                        placeholder="VIN o número de serie">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tipo de persona</label>
                    <select name="tipo_persona" class="form-select">
                        <?php $tipoPersona = old('tipo_persona', $concesion->tipo_persona ?? ''); ?>
                        <option value="">Sin definir</option>
                        <option value="fisica" <?= $tipoPersona === 'fisica' ? 'selected' : '' ?>>Física</option>
                        <option value="moral" <?= $tipoPersona === 'moral' ? 'selected' : '' ?>>Moral</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Vigencia inicio <span class="text-danger">*</span></label>
                    <input type="date" name="vigencia_inicio" class="form-control" required
                        value="<?= esc(old('vigencia_inicio', $concesion->vigencia_inicio)) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Estatus <span class="text-danger">*</span></label>
                    <select name="estatus" class="form-select" required>
                        <?php foreach ($estatusOpciones as $valor => $etiqueta): ?>
                            <option value="<?= esc($valor) ?>" <?= old('estatus', $concesion->estatus) === $valor ? 'selected' : '' ?>>
                                <?= esc($etiqueta) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Vigencia fin <span class="text-danger">*</span></label>
                    <input type="date" name="vigencia_fin" class="form-control" required
                        value="<?= esc(old('vigencia_fin', $concesion->vigencia_fin)) ?>">
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center">
                <a href="/admin/concesiones" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>
                    <?= $modo === 'nuevo' ? 'Crear concesión (stub)' : 'Guardar cambios' ?>
                </button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<?= $this->endSection() ?>
