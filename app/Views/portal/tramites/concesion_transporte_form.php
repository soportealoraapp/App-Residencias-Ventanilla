<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$oldNombre = old('solicitante_nombre', '');
$oldTipoPersona = old('tipo_persona', 'fisica');
$oldRfc = old('rfc', '');
$oldDomicilio = old('domicilio', '');
$oldServicio = old('tipo_servicio', '');
$oldNumVehiculos = old('num_vehiculos', '1');
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>UR-TT-T-01 Otorgamiento de Concesión de Transporte - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Concesión de Transporte Público</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (empty($convocatoria)): ?>
    <!-- PANTALLA BLOQUEANTE SI NO HAY CONVOCATORIA VIGENTE -->
    <div class="row justify-content-center py-4">
        <div class="col-lg-8">
            <div class="card shadow border-0 text-center p-4 p-md-5">
                <div class="mb-4">
                    <div class="bg-warning bg-opacity-10 text-warning d-inline-block p-4 rounded-circle">
                        <i class="bi bi-megaphone fs-1"></i>
                    </div>
                </div>
                <h2 class="h3 fw-bold text-dark mb-2">No hay Convocatoria Vigente Abierta</h2>
                <p class="text-muted lead fs-6 mb-4">
                    El trámite <strong>UR-TT-T-01 (Otorgamiento de Concesión)</strong> únicamente puede iniciarse cuando el H. Ayuntamiento de Uriangato publica una <strong>Convocatoria Pública Abierta</strong>.
                </p>
                <div class="alert alert-light border small text-muted text-start mb-4">
                    <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle text-primary me-1"></i>Fundamento Legal:</div>
                    De conformidad con la reglamentación municipal de movilidad, las concesiones de transporte público se asignan mediante concurso público comparativo de expedientes durante periodos oficiales de registro.
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-lg shadow-sm">
                        <i class="bi bi-arrow-left me-2"></i>Ver otros trámites disponibles
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- FORMULARIO DE POSTULACIÓN A CONVOCATORIA VIGENTE -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-success text-white py-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-success p-2 rounded-3 me-3 flex-shrink-0">
                            <i class="bi bi-award fs-3"></i>
                        </div>
                        <div>
                            <div class="badge bg-light text-success mb-1 fw-bold">CONVOCATORIA VIGENTE DE OTORGAMIENTO</div>
                            <h1 class="h4 mb-0 text-white">UR-TT-T-01 · Concesión de Transporte Público</h1>
                            <div class="small opacity-75">Periodo de registro activo hasta el <?= date('d/m/Y', strtotime($convocatoria->periodo_registro_fin)) ?></div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 p-md-4">
                    <div class="alert alert-success bg-success bg-opacity-10 border-0 mb-4">
                        <h6 class="fw-bold text-success mb-1"><i class="bi bi-journal-text me-2"></i>Bases de la Convocatoria Activa</h6>
                        <p class="small text-muted mb-1"><?= esc($convocatoria->bases) ?></p>
                        <div class="small text-success fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>Publicada: <?= date('d/m/Y', strtotime($convocatoria->fecha_publicacion)) ?> · 
                            Registro: <?= date('d/m/Y', strtotime($convocatoria->periodo_registro_inicio)) ?> al <?= date('d/m/Y', strtotime($convocatoria->periodo_registro_fin)) ?>
                        </div>
                    </div>

                    <?= form_open_multipart('/portal/tramites/concesion-transporte/guardar', ['id' => 'formConcesion', 'novalidate' => 'novalidate']) ?>
                        <input type="hidden" name="convocatoria_id" value="<?= esc($convocatoria->id) ?>">

                        <!-- SECCIÓN 1: Datos del Postulante -->
                        <fieldset class="mb-4">
                            <legend class="h6 border-bottom pb-2 mb-3 text-success d-flex align-items-center">
                                <i class="bi bi-person-vcard me-2 fs-5"></i>1. Datos Generales del Postulante
                            </legend>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tipo de Persona <span class="text-danger">*</span></label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_persona" id="tp_fisica" value="fisica" <?= $oldTipoPersona === 'fisica' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-medium" for="tp_fisica">Persona Física</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="tipo_persona" id="tp_moral" value="moral" <?= $oldTipoPersona === 'moral' ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-medium" for="tp_moral">Persona Moral (Sociedad/Empresa)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label for="solicitante_nombre" class="form-label fw-semibold">
                                        Nombre completo o Razón Social <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="solicitante_nombre" name="solicitante_nombre" required maxlength="180" value="<?= esc($oldNombre) ?>" placeholder="Nombre completo del participante">
                                    <?php if ($validation->getError('solicitante_nombre')): ?>
                                        <div class="text-danger small mt-1"><?= $validation->getError('solicitante_nombre') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-5">
                                    <label for="rfc" class="form-label fw-semibold">
                                        RFC con Homoclave <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" required maxlength="13" value="<?= esc($oldRfc) ?>" placeholder="XAXX010101XXX">
                                    <?php if ($validation->getError('rfc')): ?>
                                        <div class="text-danger small mt-1"><?= $validation->getError('rfc') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="domicilio" class="form-label fw-semibold">
                                    Domicilio Legal en Uriangato <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="domicilio" name="domicilio" rows="2" required maxlength="250" placeholder="Calle, número, colonia, CP, Uriangato, Gto."><?= esc($oldDomicilio) ?></textarea>
                                <?php if ($validation->getError('domicilio')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('domicilio') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label for="tipo_servicio" class="form-label fw-semibold">
                                        Tipo de Servicio Pretendido <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="tipo_servicio" name="tipo_servicio" required maxlength="100" value="<?= esc($oldServicio) ?>" placeholder="Ej: Colectivo Urbano, Ruta 4, Taxi Colectivo">
                                    <?php if ($validation->getError('tipo_servicio')): ?>
                                        <div class="text-danger small mt-1"><?= $validation->getError('tipo_servicio') ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-5">
                                    <label for="num_vehiculos" class="form-label fw-semibold">
                                        Número de Unidades Propuestas <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="num_vehiculos" name="num_vehiculos" required min="1" max="50" value="<?= esc($oldNumVehiculos) ?>">
                                    <?php if ($validation->getError('num_vehiculos')): ?>
                                        <div class="text-danger small mt-1"><?= $validation->getError('num_vehiculos') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </fieldset>

                        <!-- SECCIÓN 2: Expediente Completo -->
                        <fieldset class="mb-4">
                            <legend class="h6 border-bottom pb-2 mb-3 text-success d-flex align-items-center">
                                <i class="bi bi-folder-check me-2 fs-5"></i>2. Expediente Documental de la Postulación
                                <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx 10 MB cada uno)</span>
                            </legend>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="doc_acta" class="form-label fw-semibold">
                                        1. Acta de Nacimiento / Acta Constitutiva <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_acta" name="doc_acta" accept="image/png,image/jpeg,application/pdf" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="doc_rfc" class="form-label fw-semibold">
                                        2. Constancia de Situación Fiscal (RFC) <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_rfc" name="doc_rfc" accept="image/png,image/jpeg,application/pdf" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="doc_escritura" class="form-label fw-semibold">
                                        3. Acreditación de Capacidad Financiera <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_escritura" name="doc_escritura" accept="image/png,image/jpeg,application/pdf" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="doc_residencia" class="form-label fw-semibold">
                                        4. Constancia de Residencia Municipal <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_residencia" name="doc_residencia" accept="image/png,image/jpeg,application/pdf" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="doc_antecedentes" class="form-label fw-semibold">
                                        5. Antecedentes No Penales <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_antecedentes" name="doc_antecedentes" accept="image/png,image/jpeg,application/pdf" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="doc_proyecto" class="form-label fw-semibold">
                                        6. Proyecto Técnico de Rutas y Horarios <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_proyecto" name="doc_proyecto" accept="image/png,image/jpeg,application/pdf" required>
                                </div>

                                <div class="col-12">
                                    <label for="doc_vehiculos" class="form-label fw-semibold">
                                        7. Fichas Técnicas y Facturas de Vehículos Propuestos <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" id="doc_vehiculos" name="doc_vehiculos" accept="image/png,image/jpeg,application/pdf" required>
                                </div>
                            </div>
                        </fieldset>

                        <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                            <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-x me-1"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-success btn-lg shadow-sm">
                                <i class="bi bi-send-check me-2"></i>Enviar Postulación a la Convocatoria
                            </button>
                        </div>
                    <?= form_close() ?>
                </div>
            </div>
        </div>

    <!-- Columna Lateral -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-3 text-center">
                <div class="fw-semibold text-dark mb-2"><i class="bi bi-file-earmark-arrow-down text-primary me-1"></i>¿Qué necesitas para este trámite?</div>
                <p class="small text-muted mb-3">Descarga el formato oficial para conocer los documentos y requisitos antes de iniciar.</p>
                <a href="<?= site_url('/portal/formato/UR-TT-T-01') ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-download me-1"></i>Descargar formato
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 tramite-sidebar-card">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success fw-bold">
                        <i class="bi bi-cash-coin me-2"></i>Derecho de Otorgamiento
                    </h5>
                </div>
                <div class="card-body text-center p-4">
                    <div class="small text-muted mb-1 text-uppercase fw-semibold">Tarifa Oficial Vigente</div>
                    <div class="mb-3">
                        <span class="badge bg-success rounded-pill d-inline-block shadow-sm tramite-cost-badge">
                            $ <?= number_format((float) $tarifaMonto, 2) ?>
                        </span>
                    </div>
                    <div class="alert alert-info small mb-3 text-start py-2" role="alert">
                        <i class="bi bi-info-circle me-1"></i>Tarifa oficial conforme a la Ley de Ingresos Municipal.
                    </div>
                    <div class="small text-muted border-top pt-3 text-start">
                        <div class="fw-semibold text-dark mb-1"><i class="bi bi-check2-circle text-success me-1"></i>Proceso Comparativo:</div>
                        <ul class="ps-3 mb-0 text-muted">
                            <li>El dictamen final evalúa técnicamente a todos los postulantes.</li>
                            <li>La resolución oficial se publica conforme al calendario de la convocatoria.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
