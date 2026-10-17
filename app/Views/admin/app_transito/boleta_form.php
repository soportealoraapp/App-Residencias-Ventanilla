<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Editar boleta <?= esc($boleta->folio) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$valor = static fn (string $campo) => old($campo, $boleta->{$campo} ?? '');
$horaActual = substr((string) $valor('hora_infraccion'), 0, 5);
?>

<div class="mb-3">
    <a href="/admin/app-transito/boletas" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver a boletas</a>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
            <div>
                <span class="fw-bold font-monospace text-primary"><?= esc($boleta->folio) ?></span>
                <div class="small text-muted">Agente: <?= esc($boleta->agente_nombre ?? '') ?> (<?= esc($boleta->agente_placa) ?>)</div>
            </div>
            <div class="small text-muted text-end">
                Faltas y montos se conservan tal como se emitieron en campo.<br>
                Los cambios quedan registrados en auditoría.
            </div>
        </div>

        <?= form_open('/admin/app-transito/boletas/actualizar/' . (int) $boleta->id) ?>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="estado" class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                <select id="estado" name="estado" class="form-select" required>
                    <?php foreach ($estados as $clave => $etiqueta): ?>
                        <option value="<?= esc($clave) ?>" <?= $valor('estado') === $clave ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="fecha_infraccion" class="form-label fw-semibold">Fecha <span class="text-danger">*</span></label>
                <input type="date" id="fecha_infraccion" name="fecha_infraccion" class="form-control" required value="<?= esc($valor('fecha_infraccion')) ?>">
            </div>
            <div class="col-md-2">
                <label for="hora_infraccion" class="form-label fw-semibold">Hora <span class="text-danger">*</span></label>
                <input type="time" id="hora_infraccion" name="hora_infraccion" class="form-control" required value="<?= esc($horaActual) ?>">
            </div>
            <div class="col-md-4">
                <label for="lugar" class="form-label fw-semibold">Lugar <span class="text-danger">*</span></label>
                <input type="text" id="lugar" name="lugar" class="form-control" required maxlength="255" value="<?= esc($valor('lugar')) ?>">
            </div>
        </div>

        <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Infractor</h2>
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="conductor_ausente" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="conductor_ausente" name="conductor_ausente" value="1" <?= (string) $valor('conductor_ausente') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="conductor_ausente">Conductor ausente</label>
                </div>
            </div>
            <div class="col-md-5">
                <label for="infractor_nombre" class="form-label fw-semibold">Nombre</label>
                <input type="text" id="infractor_nombre" name="infractor_nombre" class="form-control" maxlength="180" value="<?= esc($valor('infractor_nombre')) ?>">
            </div>
            <div class="col-md-3">
                <label for="infractor_licencia" class="form-label fw-semibold">Licencia</label>
                <input type="text" id="infractor_licencia" name="infractor_licencia" class="form-control text-uppercase" maxlength="50" value="<?= esc($valor('infractor_licencia')) ?>">
            </div>
            <div class="col-md-4">
                <label for="infractor_domicilio" class="form-label fw-semibold">Domicilio</label>
                <input type="text" id="infractor_domicilio" name="infractor_domicilio" class="form-control" maxlength="255" value="<?= esc($valor('infractor_domicilio')) ?>">
            </div>
        </div>

        <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Vehículo</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-2">
                <label for="vehiculo_placas" class="form-label fw-semibold">Placas</label>
                <input type="text" id="vehiculo_placas" name="vehiculo_placas" class="form-control text-uppercase" maxlength="20" value="<?= esc($valor('vehiculo_placas')) ?>">
                <div class="form-check mt-1">
                    <input type="hidden" name="sin_placas" value="0">
                    <input class="form-check-input" type="checkbox" id="sin_placas" name="sin_placas" value="1" <?= (string) $valor('sin_placas') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="sin_placas">Sin placas</label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="vehiculo_marca" class="form-label fw-semibold">Marca</label>
                <input type="text" id="vehiculo_marca" name="vehiculo_marca" class="form-control" maxlength="60" value="<?= esc($valor('vehiculo_marca')) ?>">
            </div>
            <div class="col-md-3">
                <label for="vehiculo_linea" class="form-label fw-semibold">Línea / modelo</label>
                <input type="text" id="vehiculo_linea" name="vehiculo_linea" class="form-control" maxlength="80" value="<?= esc($valor('vehiculo_linea')) ?>">
            </div>
            <div class="col-md-2">
                <label for="vehiculo_color" class="form-label fw-semibold">Color</label>
                <input type="text" id="vehiculo_color" name="vehiculo_color" class="form-control" maxlength="40" value="<?= esc($valor('vehiculo_color')) ?>">
            </div>
            <div class="col-md-3">
                <label for="vehiculo_tipo" class="form-label fw-semibold">Tipo <span class="text-danger">*</span></label>
                <select id="vehiculo_tipo" name="vehiculo_tipo" class="form-select" required>
                    <?php foreach ($tipos as $clave => $etiqueta): ?>
                        <option value="<?= esc($clave) ?>" <?= $valor('vehiculo_tipo') === $clave ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <label for="hechos" class="form-label fw-semibold">Hechos</label>
            <textarea id="hechos" name="hechos" class="form-control" rows="4" maxlength="2000"><?= esc($valor('hechos')) ?></textarea>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="/admin/app-transito/boletas" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" data-loading-text="Guardando..."><i class="bi bi-save me-1"></i>Guardar cambios</button>
        </div>

        <?= form_close() ?>
    </div>
</div>

<?= $this->endSection() ?>
