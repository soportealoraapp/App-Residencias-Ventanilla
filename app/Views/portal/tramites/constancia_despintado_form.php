<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$oldNumTitulo = old('numero_titulo_concesion', '');
$oldNombreTitular = old('nombre_titular', '');
$oldPlacas = old('vehiculo_placas', '');
$oldNumSerie = old('vehiculo_num_serie', '');
$oldMotivo = old('motivo_despintado', '');
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>UR-TT-T-02 Constancia de Despintado - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Constancia de Despintado</li>
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
                        <i class="bi bi-paint-bucket fs-3"></i>
                    </div>
                    <div>
                        <div class="badge bg-light text-primary mb-1 fw-bold">UR-TT-T-02</div>
                        <h1 class="h4 mb-0 text-white">Constancia de Despintado y Retiro de Franjas</h1>
                        <div class="small opacity-75">Solicitud de inspección física para desincorporar vehículo de transporte público</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <?= form_open_multipart('/portal/tramites/constancia-despintado/guardar', ['id' => 'formDespintado', 'novalidate' => 'novalidate']) ?>

                    <!-- SECCIÓN 1: Datos de la Concesión y Titular -->
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                            <i class="bi bi-person-vcard me-2 fs-5"></i>1. Concesión y Titular
                        </legend>

                        <div class="row g-3">
                            <div class="col-md-5">
                                <label for="numero_titulo_concesion" class="form-label fw-semibold">
                                    Número de Título de Concesión <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control font-monospace" id="numero_titulo_concesion" name="numero_titulo_concesion" required maxlength="50" value="<?= esc($oldNumTitulo) ?>" placeholder="Ej: CONC-URI-2024-0001">
                                <?php if ($validation->getError('numero_titulo_concesion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('numero_titulo_concesion') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-7">
                                <label for="nombre_titular" class="form-label fw-semibold">
                                    Nombre completo del titular / concesionario <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nombre_titular" name="nombre_titular" required maxlength="180" value="<?= esc($oldNombreTitular) ?>" placeholder="Nombre completo">
                                <?php if ($validation->getError('nombre_titular')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('nombre_titular') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <!-- SECCIÓN 2: Datos de la Unidad a Desincorporar -->
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                            <i class="bi bi-car-front me-2 fs-5"></i>2. Unidad Vehicular a Desincorporar
                        </legend>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="vehiculo_placas" class="form-label fw-semibold">
                                    Placas actuales del vehículo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase" id="vehiculo_placas" name="vehiculo_placas" required maxlength="20" value="<?= esc($oldPlacas) ?>" placeholder="Ej: GTO-543-A">
                                <?php if ($validation->getError('vehiculo_placas')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('vehiculo_placas') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="vehiculo_num_serie" class="form-label fw-semibold">
                                    Número de Serie (VIN) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control text-uppercase" id="vehiculo_num_serie" name="vehiculo_num_serie" required maxlength="30" value="<?= esc($oldNumSerie) ?>" placeholder="Ej: 3VWSK7AN1RM000001">
                                <?php if ($validation->getError('vehiculo_num_serie')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('vehiculo_num_serie') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="motivo_despintado" class="form-label fw-semibold">
                                Motivo de desincorporación / despintado <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="motivo_despintado" name="motivo_despintado" rows="3" required maxlength="250" placeholder="Describe brevemente el motivo (ej. sustitución de unidad, baja de servicio, venta del vehículo, etc.)"><?= esc($oldMotivo) ?></textarea>
                            <?php if ($validation->getError('motivo_despintado')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('motivo_despintado') ?></div>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <!-- SECCIÓN 3: Documentación Requerida -->
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                            <i class="bi bi-file-earmark-arrow-up me-2 fs-5"></i>3. Documentación Digital
                            <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx 10 MB)</span>
                        </legend>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="doc_identificacion" class="form-label fw-semibold">
                                    <i class="bi bi-person-badge me-1 text-primary"></i>1. Identificación Oficial <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_identificacion" name="doc_identificacion" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">INE, pasaporte o documento oficial vigente del titular.</div>
                                <?php if ($validation->getError('doc_identificacion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_identificacion') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="doc_factura" class="form-label fw-semibold">
                                    <i class="bi bi-receipt me-1 text-primary"></i>2. Factura del Vehículo <span class="text-danger">*</span>
                                </label>
                                <input type="file" class="form-control" id="doc_factura" name="doc_factura" accept="image/png,image/jpeg,application/pdf" required>
                                <div class="form-text small">Factura o título de propiedad que acredite la unidad.</div>
                                <?php if ($validation->getError('doc_factura')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('doc_factura') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                        <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                            <i class="bi bi-arrow-right-circle me-2"></i>Continuar a Agendar Cita
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Columna Lateral -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 tramite-sidebar-card">
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
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-calendar-check text-primary me-1"></i>Flujo del Trámite:</div>
                    <ol class="ps-3 mb-0 text-muted">
                        <li>Registro de solicitud y documentos.</li>
                        <li><strong>Agendado de cita física</strong> de inspección.</li>
                        <li>Verificación en taller/patio municipal.</li>
                        <li>Emisión oficial de la constancia de despintado.</li>
                    </ol>
                </div>
            </div>

            <div class="card-footer bg-light border-0 p-3">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-tools text-primary fs-4 me-2"></i>
                    <span>La unidad deberá presentarse totalmente despintada y sin cromática oficial el día de la cita.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
