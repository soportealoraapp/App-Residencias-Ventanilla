<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\ConvocatoriasController::formNuevo()/formEditar()
 * vía view(..., $data). Se leen desde get_defined_vars() para que Intelephense
 * no las marque como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var string $modo */
$modo = $__view['modo'] ?? 'nuevo';
/** @var object $convocatoria */
$convocatoria = $__view['convocatoria'] ?? (object) [];

$estatusOpciones = \App\Models\ConvocatoriaModel::ESTATUS_OPCIONES;
$tieneBases       = ! empty($convocatoria->bases_archivo);
$volverUrl         = '/admin/convocatorias';
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?><?= $modo === 'nuevo' ? 'Nueva convocatoria' : 'Editar convocatoria #' . (int) $convocatoria->id ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => ($modo === 'nuevo' ? 'Publicar' : 'Editar') . ' convocatoria UR-01',
    'texto'  => 'El periodo de registro es la ventana en la que el portal ciudadano acepta expedientes: solo con estatus Vigente y fecha de hoy dentro de esa ventana la convocatoria será visible para los solicitantes. Las bases oficiales se pueden adjuntar en PDF y quedarán publicadas junto con la convocatoria.',
]) ?>

<div class="row mb-3">
    <div class="col">
        <a href="<?= esc($volverUrl) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver al listado</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?php if ($modo === 'nuevo'): ?>
            <?= form_open_multipart('/admin/convocatorias/guardar') ?>
        <?php else: ?>
            <?= form_open_multipart('/admin/convocatorias/actualizar/' . (int) $convocatoria->id) ?>
        <?php endif; ?>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold" for="titulo">Título de la convocatoria <span class="text-danger">*</span></label>
                    <input autocomplete="off" spellcheck="false" type="text" name="titulo" id="titulo" class="form-control" maxlength="200" required
                        value="<?= esc(old('titulo', $convocatoria->titulo ?? '')) ?>"
                        placeholder="Ej: Convocatoria de otorgamiento de concesiones 2026 - Ruta Urbana">
                    <div class="form-text">Aparecerá tal cual en el portal ciudadano y en la app.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="fecha_publicacion">Fecha de publicación <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_publicacion" id="fecha_publicacion" class="form-control" required
                        value="<?= esc(old('fecha_publicacion', substr((string) ($convocatoria->fecha_publicacion ?? ''), 0, 10))) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="periodo_registro_inicio">Inicio de registro <span class="text-danger">*</span></label>
                    <input type="date" name="periodo_registro_inicio" id="periodo_registro_inicio" class="form-control" required
                        value="<?= esc(old('periodo_registro_inicio', substr((string) ($convocatoria->periodo_registro_inicio ?? ''), 0, 10))) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="periodo_registro_fin">Fin de registro <span class="text-danger">*</span></label>
                    <input type="date" name="periodo_registro_fin" id="periodo_registro_fin" class="form-control" required
                        value="<?= esc(old('periodo_registro_fin', substr((string) ($convocatoria->periodo_registro_fin ?? ''), 0, 10))) ?>">
                    <div class="form-text">No puede ser anterior al inicio.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold" for="estatus">Estatus <span class="text-danger">*</span></label>
                    <?php $estatusActual = old('estatus', $convocatoria->estatus ?? \App\Models\ConvocatoriaModel::VIGENTE); ?>
                    <select name="estatus" id="estatus" class="form-select" required>
                        <?php foreach ($estatusOpciones as $valor => $etiqueta): ?>
                            <option value="<?= esc($valor) ?>" <?= $estatusActual === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Anulada retira la convocatoria del portal sin borrar sus postulaciones.</div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="bases">Bases de la convocatoria (resumen en pantalla)</label>
                    <textarea name="bases" id="bases" class="form-control" rows="6" maxlength="5000"
                        placeholder="Requisitos, documentación exigida, criterios de desempate y número de concesiones que se otorgan."><?= esc(old('bases', $convocatoria->bases ?? '')) ?></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="bases_pdf">Bases oficiales en PDF</label>
                    <?php if ($tieneBases): ?>
                        <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 py-2 mb-2">
                            <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i>
                            <span class="small">Actual: <strong><?= esc($convocatoria->bases_nombre ?: 'documento sin nombre') ?></strong>
                                (<?= number_format(((int) ($convocatoria->bases_tamano ?? 0)) / 1024, 0) ?> KB)</span>
                            <a class="btn btn-outline-danger btn-sm" href="/admin/convocatorias/<?= (int) $convocatoria->id ?>/bases" target="_blank" rel="noopener">Abrir</a>
                            <?php if ($modo === 'editar'): ?>
                                <div class="form-check ms-auto mb-0">
                                    <input class="form-check-input" type="checkbox" name="quitar_bases_pdf" value="1" id="quitar_bases_pdf">
                                    <label class="form-check-label small" for="quitar_bases_pdf">Quitar el PDF</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="bases_pdf" id="bases_pdf" class="form-control" accept="application/pdf">
                    <div class="form-text">Opcional, máximo 10 MB. Al adjuntar uno nuevo se reemplaza el anterior.</div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center">
                <a href="<?= esc($volverUrl) ?>" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>
                    <?= $modo === 'nuevo' ? 'Publicar convocatoria' : 'Guardar cambios' ?>
                </button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<?php if ($modo === 'editar'): ?>
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white py-3">
            <h2 class="h6 mb-0 fw-bold"><i class="bi bi-people me-2 text-primary"></i>Evaluación de esta convocatoria</h2>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                El cuadro comparativo de expedientes y la designación del ganador se hacen en su propia pantalla.
            </p>
            <a href="/admin/convocatorias/<?= (int) $convocatoria->id ?>/evaluacion" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-check me-1"></i>Ir a evaluar postulaciones
            </a>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
