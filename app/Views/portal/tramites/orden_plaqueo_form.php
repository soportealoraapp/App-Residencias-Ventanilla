<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$oldTipoPersona = old('tipo_persona', 'fisica');
$oldNumTitulo = old('numero_titulo_concesion', '');
$oldNombreConcesionario = old('nombre_concesionario', '');
$oldNumFactura = old('numero_factura', '');
$oldPlacas = old('vehiculo_placas', '');
$oldNumSerie = old('vehiculo_num_serie', '');
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>UR-TT-T-03 Orden de Plaqueo - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Orden de Plaqueo</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-primary p-2 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-card-heading fs-3"></i>
                    </div>
                    <div>
                        <div class="badge bg-light text-primary mb-1 fw-bold">UR-TT-T-03</div>
                        <h1 class="h4 mb-0 text-white">Orden de Plaqueo</h1>
                        <div class="small opacity-75">Solicitud para alta y asignación oficial de placas de transporte público</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <?= form_open_multipart('/portal/tramites/orden-plaqueo/guardar', ['id' => 'formOrdenPlaqueo', 'novalidate' => 'novalidate']) ?>

                    <!-- SECCIÓN 1: Datos de Concesión y Titular -->
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                            <i class="bi bi-person-vcard me-2 fs-5"></i>1. Datos de la Concesión y Titular
                        </legend>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de Persona <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_persona" id="tp_fisica" value="fisica" <?= $oldTipoPersona === 'fisica' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-medium" for="tp_fisica">
                                        <i class="bi bi-person me-1 text-primary"></i>Persona Física
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_persona" id="tp_moral" value="moral" <?= $oldTipoPersona === 'moral' ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-medium" for="tp_moral">
                                        <i class="bi bi-building me-1 text-primary"></i>Persona Moral (Empresa / Sociedad)
                                    </label>
                                </div>
                            </div>
                            <?php if ($validation->getError('tipo_persona')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('tipo_persona') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="numero_titulo_concesion" class="form-label fw-semibold">
                                    Número de Título de Concesión <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control font-monospace" id="numero_titulo_concesion" name="numero_titulo_concesion" required maxlength="50" value="<?= esc($oldNumTitulo) ?>" placeholder="Ej: CONC-URI-2024-0001">
                                <div class="form-text small">Ingresa el folio oficial del título vigente.</div>
                                <?php if ($validation->getError('numero_titulo_concesion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('numero_titulo_concesion') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7">
                                <label for="nombre_concesionario" class="form-label fw-semibold">
                                    <span id="lblNombreTitular">Nombre completo del concesionario</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre_concesionario" name="nombre_concesionario" required maxlength="180" value="<?= esc($oldNombreConcesionario) ?>" placeholder="Nombre o denominación social">
                                <?php if ($validation->getError('nombre_concesionario')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('nombre_concesionario') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SECCIÓN 2: Datos del Vehículo -->
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                            <i class="bi bi-truck me-2 fs-5"></i>2. Datos del Vehículo
                        </legend>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="numero_factura" class="form-label fw-semibold">
                                    Número de Factura <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="numero_factura" name="numero_factura" required maxlength="60" value="<?= esc($oldNumFactura) ?>" placeholder="Ej: FAC-98234">
                                <?php if ($validation->getError('numero_factura')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('numero_factura') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="vehiculo_placas" class="form-label fw-semibold">Placas asignadas previas / trámite</label>
                                <input type="text" class="form-control text-uppercase" id="vehiculo_placas" name="vehiculo_placas" maxlength="20" value="<?= esc($oldPlacas) ?>" placeholder="Ej: GTO-123-A">
                                <?php if ($validation->getError('vehiculo_placas')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('vehiculo_placas') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="vehiculo_num_serie" class="form-label fw-semibold">Número de Serie (VIN)</label>
                                <input type="text" class="form-control text-uppercase" id="vehiculo_num_serie" name="vehiculo_num_serie" maxlength="30" value="<?= esc($oldNumSerie) ?>" placeholder="Ej: 3VWSK7AN1RM000001">
                                <?php if ($validation->getError('vehiculo_num_serie')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('vehiculo_num_serie') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SECCIÓN 3: Documentación Requerida -->
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                            <i class="bi bi-file-earmark-arrow-up me-2 fs-5"></i>3. Documentación Requerida
                            <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx 10 MB)</span>
                        </legend>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="doc_solicitud" class="form-label fw-semibold">
                                    <i class="bi bi-file-text me-1 text-primary"></i>1. Solicitud Oficial Firmada <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_solicitud" name="doc_solicitud" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">Formato de solicitud de orden de plaqueo debidamente requisitado.</div>
                                <?php if ($validation->getError('doc_solicitud')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_solicitud') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="doc_titulo_concesion" class="form-label fw-semibold">
                                    <i class="bi bi-award me-1 text-primary"></i>2. Título de Concesión <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_titulo_concesion" name="doc_titulo_concesion" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">Copia legible del título de concesión vigente.</div>
                                <?php if ($validation->getError('doc_titulo_concesion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_titulo_concesion') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="doc_identificacion" class="form-label fw-semibold">
                                    <i class="bi bi-person-badge me-1 text-primary"></i>3. Identificación Oficial <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_identificacion" name="doc_identificacion" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">INE, pasaporte o cédula profesional del titular o representante.</div>
                                <?php if ($validation->getError('doc_identificacion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_identificacion') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="doc_factura" class="form-label fw-semibold">
                                    <i class="bi bi-receipt me-1 text-primary"></i>4. Factura del Vehículo <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_factura" name="doc_factura" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">Factura o carta factura endosada a nombre del concesionario.</div>
                                <?php if ($validation->getError('doc_factura')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_factura') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="doc_revista" class="form-label fw-semibold">
                                    <i class="bi bi-clipboard-check me-1 text-primary"></i>5. Revista Físico-Mecánica <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_revista" name="doc_revista" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">Constancia de aprobación de la inspección físico-mecánica reciente.</div>
                                <?php if ($validation->getError('doc_revista')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_revista') ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Documento condicional para Persona Moral -->
                            <div class="col-md-6" id="div_acta_constitutiva" style="display: <?= $oldTipoPersona === 'moral' ? 'block' : 'none' ?>;">
                                <div class="p-3 bg-light border border-primary border-opacity-25 rounded-3">
                                    <label for="doc_acta_constitutiva" class="form-label fw-semibold text-primary">
                                        <i class="bi bi-building me-1"></i>6. Acta Constitutiva y Poder Notarial <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_acta_constitutiva" name="doc_acta_constitutiva" accept="image/png,image/jpeg,application/pdf" <?= $oldTipoPersona === 'moral' ? 'required' : '' ?>>
                                    <div class="form-text small">Requerido exclusivamente para personas morales o sociedades.</div>
                                    <?php if ($validation->getError('doc_acta_constitutiva')): ?>
                                        <div class="text-danger small mt-1"><?= $validation->getError('doc_acta_constitutiva') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                        <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm" id="btnEnviar">
                            <i class="bi bi-send-check me-2"></i>Enviar Solicitud
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Columna Lateral Sticky: Resumen de Derechos y Requisitos -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 tramite-sidebar-sticky">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary fw-bold">
                    <i class="bi bi-cash-coin me-2"></i>Costo de Derechos
                </h5>
            </div>
            <div class="card-body text-center p-4">
                <div class="small text-muted mb-1 text-uppercase fw-semibold">Tarifa Oficial Vigente</div>
                <div class="mb-3">
                    <span class="badge bg-primary rounded-pill d-inline-block shadow-sm tramite-cost-badge">
                        $ <?= number_format((float) $tarifaMonto, 2) ?>
                    </span>
                </div>
                <div class="alert alert-info small mb-3 text-start py-2" role="alert">
                    <i class="bi bi-info-circle me-1"></i>Tarifa oficial conforme a la Ley de Ingresos Municipal.
                </div>
                <div class="small text-muted border-top pt-3 text-start">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-check2-circle text-success me-1"></i>Incluye:</div>
                    <ul class="ps-3 mb-0 text-muted">
                        <li>Emisión de oficio de orden de plaqueo.</li>
                        <li>Registro en el padrón municipal de transporte.</li>
                        <li>Validación de expediente digital.</li>
                    </ul>
                </div>
            </div>

            <div class="card-footer bg-light border-0 p-3">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-shield-lock-fill text-primary fs-4 me-2"></i>
                    <span>Tus documentos son resguardados con cifrado SHA-256 para auditoría.</span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-3 small">
                <div class="fw-bold mb-2 text-dark"><i class="bi bi-clock-history me-1 text-primary"></i>Tiempos de Respuesta</div>
                <p class="text-muted mb-0">El personal de ventanilla revisará tu expediente en un plazo estimado de 2 a 3 días hábiles. Podrás consultar el avance con tu <strong>folio único</strong> en cualquier momento.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const radioFisica = document.getElementById('tp_fisica');
    const radioMoral = document.getElementById('tp_moral');
    const divActa = document.getElementById('div_acta_constitutiva');
    const inputActa = document.getElementById('doc_acta_constitutiva');
    const lblNombre = document.getElementById('lblNombreTitular');

    function actualizarTipoPersona() {
        if (radioMoral && radioMoral.checked) {
            divActa.style.display = 'block';
            inputActa.setAttribute('required', 'required');
            lblNombre.textContent = 'Razón Social / Denominación de la Empresa';
        } else {
            divActa.style.display = 'none';
            inputActa.removeAttribute('required');
            lblNombre.textContent = 'Nombre completo del concesionario';
        }
    }

    if (radioFisica) radioFisica.addEventListener('change', actualizarTipoPersona);
    if (radioMoral) radioMoral.addEventListener('change', actualizarTipoPersona);
    actualizarTipoPersona();
});
</script>
<?= $this->endSection() ?>
