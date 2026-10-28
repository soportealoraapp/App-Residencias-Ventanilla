<?php declare(strict_types=1);
// Variables inyectadas por el controlador: se leen del ambito real para que el
// analisis estatico no las marque como indefinidas; en runtime gana el valor enviado.
$__view = get_defined_vars();
/** @var array<string, object> $formatosMap */
$formatosMap = $__view['formatosMap'] ?? [];
/** @var bool $habilitaT06 */
$habilitaT06 = $__view['habilitaT06'] ?? false;
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Trámites Disponibles - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3 mb-md-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Trámites</li>
            </ol>
        </nav>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1"><i class="bi bi-folder2-open text-primary me-2"></i>Catálogo Oficial de Trámites</h1>
                <p class="text-muted small mb-0">Selecciona el trámite de movilidad que deseas gestionar digitalmente ante el municipio.</p>
            </div>
            <div>
                <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-outline-primary btn-sm shadow-sm">
                    <i class="bi bi-list-check me-1"></i>Ver mis solicitudes en curso
                </a>
            </div>
        </div>
    </div>
</div>

<!-- GUÍA RÁPIDA DE REQUISITOS GENERALES -->
<div class="card border-0 shadow-sm mb-4 bg-primary bg-opacity-10 border-start border-4 border-primary">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start gap-3">
            <div class="bg-primary text-white p-2 rounded-circle flex-shrink-0">
                <i class="bi bi-lightbulb fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="fw-bold text-primary mb-1">Requisitos generales para agilizar tu trámite</h6>
                <p class="small text-muted mb-2">Para una resolución rápida y sin prevenciones, te recomendamos tener listos los siguientes archivos en formato PDF o fotografía legible (máximo 10 MB por archivo):</p>
                <div class="row g-2 small text-secondary">
                    <div class="col-12 col-md-4"><i class="bi bi-check2-circle text-primary me-1"></i><strong>Identificación oficial:</strong> INE o Pasaporte vigente.</div>
                    <div class="col-12 col-md-4"><i class="bi bi-check2-circle text-primary me-1"></i><strong>Comprobante de domicilio:</strong> No mayor a 3 meses.</div>
                    <div class="col-12 col-md-4"><i class="bi bi-check2-circle text-primary me-1"></i><strong>Documentación vehicular:</strong> Factura, póliza o concesión.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BARRA DE BÚSQUEDA Y FILTROS POR CATEGORÍA -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" id="filtroTextoTramites" class="form-control border-start-0" 
                        placeholder="Buscar por nombre, palabra clave o código (ej. UR-01, placas, carga)..." 
                        aria-label="Buscar trámite">
                    <button class="btn btn-outline-secondary" type="button" id="btnLimpiarFiltro" title="Limpiar búsqueda">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex flex-wrap gap-1 justify-content-md-end" id="filtrosCategorias">
                    <button type="button" class="btn btn-sm btn-primary active btn-categoria-filter" data-cat="todas">
                        Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria-filter" data-cat="concesiones">
                        <i class="bi bi-bus-front me-1"></i>Transporte
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria-filter" data-cat="vialidad">
                        <i class="bi bi-cone-striped me-1"></i>Cierre y Vialidad
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-categoria-filter" data-cat="carga">
                        <i class="bi bi-truck me-1"></i>Carga y Descarga
                    </button>
                </div>
            </div>
        </div>
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
                    <a href="<?= site_url('/portal/tramites/concesion-transporte') ?>" class="btn btn-success btn-lg w-100 shadow-sm stretched-link">
                        <i class="bi bi-play-circle me-2"></i>Ver convocatoria / Postular
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible bajo Convocatoria</span>
                    <?php if (!empty($formatosMap['UR-TT-T-01'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-01') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

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
                    <a href="<?= site_url('/portal/tramites/constancia-despintado') ?>" class="btn btn-primary btn-lg w-100 shadow-sm stretched-link">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
                    <?php if (!empty($formatosMap['UR-TT-T-02'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-02') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
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
                        <li>Costo de derechos: <strong>sin costo</strong></li>
                        <li>Validación de revista físico-mecánica</li>
                        <li>Generación de folio y expediente digital</li>
                    </ul>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/orden-plaqueo') ?>" class="btn btn-primary btn-lg w-100 shadow-sm stretched-link">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
                    <?php if (!empty($formatosMap['UR-TT-T-03'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-03') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
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
                <a href="<?= site_url('/portal/tramites/permiso-eventual') ?>" class="btn btn-primary btn-lg w-100 shadow-sm stretched-link">
                    <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                </a>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
                    <?php if (!empty($formatosMap['UR-TT-T-04'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-04') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
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
                <a href="<?= site_url('/portal/tramites/cierre-calle') ?>" class="btn btn-primary btn-lg w-100 shadow-sm stretched-link">
                    <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                </a>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
                    <?php if (!empty($formatosMap['UR-TT-T-05'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-05') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
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
                    <a href="<?= site_url('/portal/tramites/cesion-concesion') ?>" class="btn btn-success btn-lg w-100 shadow-sm stretched-link">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
                    <?php if (!empty($formatosMap['UR-TT-T-06'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-06') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
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
                    <a href="<?= site_url('/portal/tramites/carga-descarga/formulario') ?>" class="btn btn-primary btn-lg w-100 shadow-sm stretched-link">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 pb-3 px-3 px-md-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Disponible para solicitud</span>
                    <?php if (!empty($formatosMap['UR-TT-T-07'])): ?>
                        <a href="<?= site_url('/portal/formato/UR-TT-T-07') ?>" class="btn btn-sm btn-outline-secondary" style="position: relative; z-index: 2;">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i>Formato
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>

