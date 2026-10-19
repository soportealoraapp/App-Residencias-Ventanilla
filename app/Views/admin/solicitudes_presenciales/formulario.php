<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Nueva Solicitud Presencial<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$__view = get_defined_vars();
/** @var string $tramite */
$tramite = $__view['tramite'] ?? '';
/** @var array{nombre:string,icono:string} $tramiteInfo */
$tramiteInfo = $__view['tramiteInfo'] ?? ['nombre' => $tramite, 'icono' => 'bi-file-earmark'];
/** @var list<array{name:string,label:string,type:string,required:bool,options?:array<string,string>,max_length?:int}> $campos */
$campos = $__view['campos'] ?? [];
/** @var object|null $ciudadano */
$ciudadano = $__view['ciudadano'] ?? null;
/** @var int $ciudadanoId */
$ciudadanoId = $__view['ciudadanoId'] ?? 0;
/** @var array $extras */
$extras = $__view['extras'] ?? [];
/** @var float|null $tarifaMonto */
$tarifaMonto = $__view['tarifaMonto'] ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">
        <i class="bi <?= esc($tramiteInfo['icono']) ?> me-2 text-primary"></i>
        <?= esc($tramiteInfo['nombre']) ?>
        <span class="badge bg-light text-dark border ms-2"><?= esc($tramite) ?></span>
    </h5>
    <a href="/admin/solicitudes/nueva" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Cambiar trámite
    </a>
</div>

<!-- Contexto del ciudadano -->
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body d-flex align-items-center gap-3 py-2">
        <?php if ($ciudadano): ?>
            <div class="flex-grow-1">
                <span class="small text-muted">Solicitante:</span>
                <strong><?= esc($ciudadano->nombre_completo ?? $ciudadano->username) ?></strong>
                <span class="small text-muted ms-2"><?= esc($ciudadano->email ?? '') ?></span>
            </div>
            <span class="badge bg-primary">Cuenta vinculada</span>
        <?php else: ?>
            <div class="flex-grow-1">
                <span class="small text-muted">
                    <i class="bi bi-person me-1"></i>Atención presencial sin cuenta del portal vinculada.
                </span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tarifa -->
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-body py-2 d-flex align-items-center gap-2">
        <i class="bi bi-cash-coin text-success fs-5"></i>
        <span class="small">Monto según tarifario vigente:</span>
        <strong><?= $tarifaMonto !== null ? formatear_dinero($tarifaMonto) : 'Sin tarifa configurada' ?></strong>
        <?php if ($tarifaMonto === null): ?>
            <span class="badge bg-warning text-dark ms-2">Debes configurar la tarifa antes de registrar</span>
        <?php endif; ?>
    </div>
</div>

<!-- Formulario -->
<form method="post" action="/admin/solicitudes/nueva/guardar" enctype="multipart/form-data" id="formPresencial">
    <?= csrf_field() ?>
    <input type="hidden" name="tramite" value="<?= esc($tramite) ?>">
    <input type="hidden" name="ciudadano_id" value="<?= $ciudadanoId ?>">

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0 small fw-bold"><i class="bi bi-ui-checks me-1 text-primary"></i>Datos del trámite</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($campos as $campo): ?>
                <div class="col-12 <?= in_array($campo['type'], ['textarea'], true) ? '' : 'col-md-6' ?>">
                    <label for="campo_<?= esc($campo['name']) ?>" class="form-label small fw-semibold">
                        <?= esc($campo['label']) ?>
                        <?php if ($campo['required']): ?><span class="text-danger">*</span><?php endif; ?>
                    </label>

                    <?php if ($campo['type'] === 'select'): ?>
                        <select name="<?= esc($campo['name']) ?>" id="campo_<?= esc($campo['name']) ?>"
                            class="form-select form-select-sm" <?= $campo['required'] ? 'required' : '' ?>>
                            <option value="">— Seleccionar —</option>
                            <?php foreach (($campo['options'] ?? []) as $val => $lbl): ?>
                                <option value="<?= esc($val) ?>"><?= esc($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($campo['type'] === 'textarea'): ?>
                        <textarea name="<?= esc($campo['name']) ?>" id="campo_<?= esc($campo['name']) ?>"
                            class="form-control form-control-sm" rows="3"
                            <?= $campo['required'] ? 'required' : '' ?>
                            <?= isset($campo['max_length']) ? 'maxlength="' . (int)$campo['max_length'] . '"' : '' ?>
                            placeholder="<?= esc($campo['label']) ?>..."></textarea>

                    <?php else: ?>
                        <input type="<?= esc($campo['type']) ?>"
                            name="<?= esc($campo['name']) ?>" id="campo_<?= esc($campo['name']) ?>"
                            class="form-control form-control-sm"
                            <?= $campo['required'] ? 'required' : '' ?>
                            <?= isset($campo['max_length']) ? 'maxlength="' . (int)$campo['max_length'] . '"' : '' ?>
                            <?= $campo['type'] === 'number' ? 'min="1"' : '' ?>
                            placeholder="<?= esc($campo['label']) ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- Campo especial: convocatoria para UR-01 -->
                <?php if ($tramite === 'UR-TT-T-01' && !empty($extras['convocatorias'])): ?>
                <div class="col-12">
                    <label for="convocatoria_id" class="form-label small fw-semibold">
                        Convocatoria vigente <span class="text-danger">*</span>
                    </label>
                    <select name="convocatoria_id" id="convocatoria_id" class="form-select form-select-sm" required>
                        <option value="">— Seleccionar convocatoria —</option>
                        <?php foreach ($extras['convocatorias'] as $conv): ?>
                            <option value="<?= (int)$conv->id ?>">
                                #<?= (int)$conv->id ?> — <?= esc($conv->titulo ?? 'Convocatoria') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php elseif ($tramite === 'UR-TT-T-01'): ?>
                <div class="col-12">
                    <div class="alert alert-danger py-2 small mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>No hay convocatorias abiertas hoy.</strong> Publica una convocatoria con estatus Vigente y periodo de registro activo, o <a href="/admin/convocatorias" class="alert-link">revisa las existentes</a>.
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Documentos opcionales -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0 small fw-bold">
                <i class="bi bi-paperclip me-1 text-primary"></i>Documentos (opcionales)
                <small class="text-muted fw-normal ms-1">— adjuntar si el ciudadano los entrega en el momento</small>
            </h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <?php
                $docsConfig = match($tramite) {
                    'UR-TT-T-01' => ['doc_acta' => 'Acta de nacimiento/constitutiva', 'doc_rfc' => 'Constancia de situación fiscal', 'doc_escritura' => 'Acreditación de capacidad', 'doc_residencia' => 'Constancia de residencia', 'doc_antecedentes' => 'No antecedentes penales', 'doc_proyecto' => 'Proyecto técnico de rutas', 'doc_vehiculos' => 'Documentación de vehículos'],
                    'UR-TT-T-02' => ['doc_identificacion' => 'Identificación oficial', 'doc_factura' => 'Factura del vehículo'],
                    'UR-TT-T-03' => ['doc_solicitud' => 'Solicitud firmada', 'doc_titulo_concesion' => 'Título de concesión', 'doc_identificacion' => 'Identificación oficial', 'doc_factura' => 'Factura del vehículo', 'doc_revista' => 'Revista física-mecánica'],
                    'UR-TT-T-04' => ['doc_solicitud_escrita' => 'Solicitud por escrito', 'doc_proyecto_vehiculos' => 'Proyecto de vehículos', 'doc_identidad' => 'Documento de identidad', 'doc_poliza' => 'Póliza de seguro'],
                    'UR-TT-T-05' => ['doc_identificacion' => 'Identificación oficial', 'doc_solicitud_escrita' => 'Solicitud escrita'],
                    'UR-TT-T-06' => ['doc_titulo_concesion' => 'Título de concesión', 'doc_capacidad' => 'Documentos de capacidad'],
                    'UR-TT-T-07' => ['doc_identificacion' => 'Identificación oficial', 'doc_tarjeta_circulacion' => 'Tarjeta de circulación', 'doc_comprobante' => 'Comprobante de domicilio'],
                    default => ['doc_identificacion' => 'Identificación oficial'],
                };
                foreach ($docsConfig as $name => $label): ?>
                <div class="col-12 col-md-6">
                    <label class="form-label small fw-semibold"><?= esc($label) ?></label>
                    <input type="file" name="<?= esc($name) ?>" class="form-control form-control-sm"
                        accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <?php endforeach; ?>
            </div>
            <p class="small text-muted mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Formato aceptado: PDF, JPG o PNG (máx. 10 MB). Puedes registrar la solicitud y recibir documentos después.
            </p>
        </div>
    </div>

    <!-- Botones -->
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <a href="/admin/solicitudes/nueva" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-lg me-1"></i>Cancelar
        </a>
        <button type="submit" class="btn btn-primary <?= $tarifaMonto === null ? 'disabled' : '' ?>"
            <?= $tarifaMonto === null ? 'disabled' : '' ?>
            data-loading-text="Guardando...">
            <i class="bi bi-check-lg me-1"></i>Registrar Solicitud
        </button>
    </div>
</form>

<?= $this->section('scripts') ?>
<script>
document.getElementById('formPresencial')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('button[type="submit"]');
    if (btn && !btn.disabled) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando...';
        btn.disabled = true;
    }
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
