<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>UR-TT-T-05 Permiso para Cierre de Calle - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cierre de Calle</li>
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
                        <i class="bi bi-sign-stop fs-3"></i>
                    </div>
                    <div>
                        <div class="badge bg-light text-primary mb-1 fw-bold">UR-TT-T-05</div>
                        <h1 class="h4 mb-0 text-white">Permiso para Cierre de Calle</h1>
                        <div class="small opacity-75">Autorización para cierre temporal total o parcial de vía pública por evento</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="alert alert-primary bg-primary bg-opacity-10 border-0 small mb-4">
                    <i class="bi bi-info-circle me-2"></i>Completa la solicitud con anticipación para la evaluación técnica y coordinación de rutas alternas.
                </div>

                <?= form_open_multipart('/portal/tramites/cierre-calle/guardar', ['id' => 'formCierreCalle', 'novalidate' => 'novalidate']) ?>

                <!-- SECCIÓN 1: Solicitante -->
                <fieldset class="mb-4">
                    <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                        <i class="bi bi-person-vcard me-2 fs-5"></i>1. Datos del Solicitante
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="nombre_solicitante" class="form-label fw-semibold">
                                Nombre completo del solicitante o responsable <span class="text-danger">*</span>
                            </label>
                            <input id="nombre_solicitante" class="form-control" name="nombre_solicitante" value="<?= old('nombre_solicitante') ?>" maxlength="180" placeholder="Nombre completo" required>
                            <?= validation_show_error('nombre_solicitante') ?>
                        </div>
                        <div class="col-md-5">
                            <label for="domicilio" class="form-label fw-semibold">
                                Domicilio en Uriangato <span class="text-danger">*</span>
                            </label>
                            <input id="domicilio" class="form-control" name="domicilio" value="<?= old('domicilio') ?>" maxlength="250" placeholder="Calle, número, colonia" required>
                            <?= validation_show_error('domicilio') ?>
                        </div>
                    </div>
                </fieldset>

                <!-- SECCIÓN 2: Evento y Cierre -->
                <fieldset class="mb-4">
                    <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                        <i class="bi bi-calendar-event me-2 fs-5"></i>2. Datos del Cierre y Vía Pública
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="fecha_cierre" class="form-label fw-semibold">
                                Fecha solicitada <span class="text-danger">*</span>
                            </label>
                            <input id="fecha_cierre" type="date" class="form-control" name="fecha_cierre" value="<?= old('fecha_cierre') ?>" required>
                            <?= validation_show_error('fecha_cierre') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="hora_inicio" class="form-label fw-semibold">
                                Hora de inicio <span class="text-danger">*</span>
                            </label>
                            <input id="hora_inicio" type="time" class="form-control" name="hora_inicio" value="<?= old('hora_inicio') ?>" required>
                            <?= validation_show_error('hora_inicio') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="hora_fin" class="form-label fw-semibold">
                                Hora de término <span class="text-danger">*</span>
                            </label>
                            <input id="hora_fin" type="time" class="form-control" name="hora_fin" value="<?= old('hora_fin') ?>" required>
                            <?= validation_show_error('hora_fin') ?>
                        </div>
                        <div class="col-md-8">
                            <label for="calle_tramo" class="form-label fw-semibold">
                                Calle o tramo a cerrar <span class="text-danger">*</span>
                            </label>
                            <input id="calle_tramo" class="form-control" name="calle_tramo" value="<?= old('calle_tramo') ?>" placeholder="Calle principal y entre qué calles" required>
                            <?= validation_show_error('calle_tramo') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="colonia" class="form-label fw-semibold">
                                Colonia o Fraccionamiento <span class="text-danger">*</span>
                            </label>
                            <input id="colonia" class="form-control" name="colonia" value="<?= old('colonia') ?>" placeholder="Colonia" required>
                            <?= validation_show_error('colonia') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo_cierre" class="form-label fw-semibold">
                                Tipo de cierre <span class="text-danger">*</span>
                            </label>
                            <select id="tipo_cierre" class="form-select" name="tipo_cierre" required>
                                <option value="">Selecciona...</option>
                                <option value="parcial" <?= old('tipo_cierre') === 'parcial' ? 'selected' : '' ?>>Parcial (un carril)</option>
                                <option value="total" <?= old('tipo_cierre') === 'total' ? 'selected' : '' ?>>Total (vía completa)</option>
                            </select>
                            <?= validation_show_error('tipo_cierre') ?>
                        </div>
                        <div class="col-md-8">
                            <label for="motivo_evento" class="form-label fw-semibold">
                                Motivo del evento o actividad <span class="text-danger">*</span>
                            </label>
                            <input id="motivo_evento" class="form-control" name="motivo_evento" value="<?= old('motivo_evento') ?>" placeholder="Ej. Evento cívico, deportivo, cultural o particular" required>
                            <?= validation_show_error('motivo_evento') ?>
                        </div>
                        <div class="col-12">
                            <label for="descripcion_evento" class="form-label fw-semibold">
                                Descripción detallada de la solicitud <span class="text-danger">*</span>
                            </label>
                            <textarea id="descripcion_evento" class="form-control" name="descripcion_evento" rows="3" maxlength="500" placeholder="Detalles de la logística, colocación de toldos, sonido u objetos en vía pública" required><?= old('descripcion_evento') ?></textarea>
                            <?= validation_show_error('descripcion_evento') ?>
                        </div>
                    </div>
                </fieldset>

                <!-- SECCIÓN 3: Documentación -->
                <fieldset class="mb-4">
                    <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                        <i class="bi bi-file-earmark-arrow-up me-2 fs-5"></i>3. Documentación Digital
                        <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx. 10 MB)</span>
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 h-100 bg-light bg-opacity-25">
                                <label for="identificacion_oficial" class="form-label fw-semibold">
                                    <i class="bi bi-person-badge text-primary me-1"></i>1. Identificación Oficial <span class="text-danger">*</span>
                                </label>
                                <input id="identificacion_oficial" type="file" class="form-control" name="identificacion_oficial" accept="application/pdf,image/jpeg,image/png" required>
                                <div class="form-text small">INE, pasaporte o cédula profesional del solicitante.</div>
                                <?= validation_show_error('identificacion_oficial') ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 border rounded-3 h-100 bg-light bg-opacity-25">
                                <label for="solicitud_escrita" class="form-label fw-semibold">
                                    <i class="bi bi-file-text text-primary me-1"></i>2. Solicitud Escrita / Croquis <span class="text-danger">*</span>
                                </label>
                                <input id="solicitud_escrita" type="file" class="form-control" name="solicitud_escrita" accept="application/pdf,image/jpeg,image/png" required>
                                <div class="form-text small">Escrito firmado y/o croquis de ubicación del tramo.</div>
                                <?= validation_show_error('solicitud_escrita') ?>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                    <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </a>
                    <button class="btn btn-primary btn-lg shadow-sm" type="submit">
                        <i class="bi bi-send-check me-2"></i>Enviar Solicitud
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
                <h2 class="h5 mb-0 text-primary fw-bold">
                    <i class="bi bi-info-circle me-2"></i>Información del Trámite
                </h2>
            </div>
            <div class="card-body text-center p-4">
                <div class="small text-muted text-uppercase fw-semibold mb-1">Costo de Derechos</div>
                <div class="mb-3">
                    <span class="badge bg-primary rounded-pill d-inline-block shadow-sm tramite-cost-badge">
                        <?= formatear_dinero((float) $tarifaMonto) ?>
                    </span>
                </div>
                <div class="alert alert-info small py-2 text-start" role="alert">
                    <i class="bi bi-info-circle me-1"></i>Tarifa oficial conforme a la Ley de Ingresos Municipal.
                </div>
                <div class="small text-muted border-top pt-3 text-start">
                    <div class="fw-semibold text-dark mb-2">
                        <i class="bi bi-list-check text-primary me-1"></i>Flujo del Trámite:
                    </div>
                    <ol class="ps-3 mb-0">
                        <li>Registro de solicitud y documentos.</li>
                        <li>Validación de viabilidad y seguridad vial.</li>
                        <li>Notificación y orden de pago.</li>
                        <li>Emisión oficial del permiso de cierre.</li>
                    </ol>
                </div>
            </div>
            <div class="card-footer bg-light border-0 p-3">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-shield-check text-primary fs-4 me-2"></i>
                    <span>Dirección de Movilidad y Transporte · H. Ayuntamiento de Uriangato, Gto.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
