<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Trámites Disponibles - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3 mb-md-4">
    <div class="col">
        <h1 class="h3 mb-1"><i class="bi bi-folder2-open text-primary me-2"></i>Trámites Disponibles</h1>
        <p class="text-muted small mb-0">Selecciona el trámite de movilidad que deseas gestionar en línea.</p>
    </div>
</div>

<div class="row g-3 g-md-4">

    <!-- T-01: Concesión de Transporte -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-success bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-award fs-4"></i>
                    </div>
                    <div>
                        <div class="badge bg-success-subtle text-success mb-1">UR-TT-T-01</div>
                        <h2 class="h5 mb-0 text-success fw-bold">Concesión de Transporte</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Postulación de expediente técnico para el otorgamiento de nuevas concesiones de transporte público en convocatoria abierta.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Sujeto a Convocatoria Pública Activa</li>
                        <li>Costo de derechos: <strong>$9,055.20</strong></li>
                        <li>Expediente técnico y legal completo</li>
                        <li>Evaluación comparativa de candidatos</li>
                    </ul>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/concesion-transporte') ?>" class="btn btn-success btn-lg w-100 shadow-sm">
                        <i class="bi bi-play-circle me-2"></i>Ver convocatoria / Postular
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible bajo Convocatoria</span>
            </div>
        </div>
    </div>

    <!-- T-02: Constancia de Despintado -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-paint-bucket fs-4"></i>
                    </div>
                    <div>
                        <div class="badge bg-primary-subtle text-primary mb-1">UR-TT-T-02</div>
                        <h2 class="h5 mb-0 text-primary fw-bold">Constancia de Despintado</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Constancia oficial e inspección física para certificar el despintado total y retiro de cromática de unidades desincorporadas.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Costo de derechos: <strong>$64.90</strong></li>
                        <li>Agendado de cita de inspección física en línea</li>
                        <li>Revisión en patio municipal de control</li>
                        <li>Emisión oficial tras verificación conforme</li>
                    </ul>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/constancia-despintado') ?>" class="btn btn-primary btn-lg w-100 shadow-sm">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
            </div>
        </div>
    </div>

    <!-- T-03: Orden de Plaqueo -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-card-heading fs-4"></i>
                    </div>
                    <div>
                        <div class="badge bg-primary-subtle text-primary mb-1">UR-TT-T-03</div>
                        <h2 class="h5 mb-0 text-primary fw-bold">Orden de Plaqueo</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Expedición de orden oficial para asignación y alta de placas de servicio público de transporte.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Personas Físicas y Morales</li>
                        <li>Costo de derechos: <strong>$50.00</strong></li>
                        <li>Validación de revista físico-mecánica</li>
                        <li>Generación de folio y expediente digital</li>
                    </ul>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/orden-plaqueo') ?>" class="btn btn-primary btn-lg w-100 shadow-sm">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
            </div>
        </div>
    </div>

    <!-- T-04: Permiso Eventual de Transporte -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-bus-front fs-4"></i>
                    </div>
                    <div>
                        <div class="badge bg-primary-subtle text-primary mb-1">UR-TT-T-04</div>
                        <h2 class="h5 mb-0 text-primary fw-bold">Permiso Eventual de Transporte</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Permiso temporal para cubrir la necesidad del servicio por descompostura o falta de unidades.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Solicitud con documentos</li>
                        <li>Revisión administrativa</li>
                        <li>Costo de referencia: <strong>$156.94</strong></li>
                    </ul>
                </div>
                <a href="<?= site_url('/portal/tramites/permiso-eventual') ?>" class="btn btn-primary btn-lg w-100 shadow-sm">
                    <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                </a>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
            </div>
        </div>
    </div>

    <!-- T-05: Permiso para Cierre de Calle -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0"><i class="bi bi-sign-stop fs-4"></i></div>
                    <div>
                        <div class="badge bg-primary-subtle text-primary mb-1">UR-TT-T-05</div>
                        <h2 class="h5 mb-0 text-primary fw-bold">Permiso para Cierre de Calle</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Permiso para cierre parcial o total de una calle por un evento, con vigencia de un día.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Solicitud escrita</li>
                        <li>Respuesta inmediata</li>
                        <li>Costo de referencia: <strong>$287.00</strong></li>
                    </ul>
                </div>
                <a href="<?= site_url('/portal/tramites/cierre-calle') ?>" class="btn btn-primary btn-lg w-100 shadow-sm">
                    <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                </a>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
            </div>
        </div>
    </div>

    <!-- T-06: Cesión de Concesión (feature flag) -->
    <?php if (!empty($habilitaT06)): ?>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-success bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-arrow-left-right fs-4"></i>
                    </div>
                    <div>
                        <div class="badge bg-success-subtle text-success mb-1">UR-TT-T-06</div>
                        <h2 class="h5 mb-0 text-success fw-bold">Cesión de Concesión</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Procedimiento administrativo para transferir los derechos y obligaciones de una concesión de transporte público a un nuevo titular.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Validación documental</li>
                        <li>Revisión por operador de ventanilla</li>
                        <li>Aprobación administrativa</li>
                    </ul>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/cesion-concesion') ?>" class="btn btn-success btn-lg w-100 shadow-sm">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- T-07: Permiso de Carga y Descarga -->
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-2 p-md-3 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                    <div>
                        <div class="badge bg-primary-subtle text-primary mb-1">UR-TT-T-07</div>
                        <h2 class="h5 mb-0 text-primary fw-bold">Permiso de Carga y Descarga</h2>
                    </div>
                </div>
            </div>
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <p class="card-text text-muted small mb-3">Autorización oficial para llevar a cabo operaciones de carga y descarga de mercancías en la vía pública, con control de horarios y periodos de vigencia.</p>
                    <ul class="small text-muted mb-4 ps-3">
                        <li>Para particulares y empresas</li>
                        <li>Vigencia: Día, Mes, Semestre o Año</li>
                        <li>Pago en línea disponible (BanBajío) o en ventanilla</li>
                        <li>Emisión digital oficial con código QR de autenticidad</li>
                    </ul>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/carga-descarga/formulario') ?>" class="btn btn-primary btn-lg w-100 shadow-sm">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

