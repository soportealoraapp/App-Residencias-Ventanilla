<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Panel de Inicio - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<!-- ENCABEZADO Y HERO BANNER BIENVENIDA -->
<div class="card border-0 shadow-sm mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
    <div class="card-body p-4 p-md-5 text-white">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-white bg-opacity-20 text-white mb-2 px-3 py-2 fw-semibold">
                    <i class="bi bi-shield-check me-1"></i>Ventanilla Digital Oficial · Uriangato, Gto.
                </span>
                <h1 class="h2 text-white fw-bold mb-2">¡Bienvenido(a), <?= esc(session('nombre_completo') ?? session('username') ?? 'Ciudadano') ?>!</h1>
                <p class="text-white opacity-90 mb-0 lead fs-6">
                    Gestiona tus solicitudes de transporte y movilidad municipal de forma rápida, segura y 100% digital.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-light btn-lg fw-bold text-primary shadow-sm">
                    <i class="bi bi-grid me-2"></i>Catálogo de Trámites
                </a>
            </div>
        </div>
    </div>
</div>

<!-- TARJETAS DE MÉTRICAS / ESTADÍSTICAS RÁPIDAS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 flex-shrink-0">
                    <i class="bi bi-folder2-open fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Total Solicitudes</div>
                    <div class="fs-4 fw-bold text-dark"><?= (int)($stats['total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 flex-shrink-0">
                    <i class="bi bi-hourglass-split fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">En Trámite</div>
                    <div class="fs-4 fw-bold text-dark"><?= (int)($stats['en_proceso'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 flex-shrink-0">
                    <i class="bi bi-check-circle fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Concluidos</div>
                    <div class="fs-4 fw-bold text-dark"><?= (int)($stats['concluidos'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 flex-shrink-0">
                    <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-medium">Pago Pendiente</div>
                    <div class="fs-4 fw-bold text-dark"><?= (int)($stats['pendientes'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECCIÓN: TRÁMITES DESTACADOS DISPONIBLES -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0 fw-bold text-dark"><i class="bi bi-lightning-charge text-primary me-2"></i>Trámites Disponibles para Solicitud</h2>
    <a href="<?= site_url('/portal/tramites') ?>" class="small text-primary text-decoration-none fw-semibold">
        Ver catálogo completo <i class="bi bi-arrow-right"></i>
    </a>
</div>

<div class="row g-3 mb-5">
    <!-- Card UR-01 -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-award fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-success-subtle text-success small">UR-TT-T-01</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Concesión Transporte</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Postulación de expediente técnico bajo Convocatoria Pública ($9,055.20).</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/concesion-transporte') ?>" class="btn btn-success btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Ver Convocatoria
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card UR-02 -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-paint-bucket fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary-subtle text-primary small">UR-TT-T-02</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Constancia Despintado</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Constancia e inspección física presencial de despintado de unidades ($64.90).</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/constancia-despintado') ?>" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card UR-03 -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-card-heading fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary-subtle text-primary small">UR-TT-T-03</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Orden de Plaqueo</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Orden oficial para asignación y alta de placas de servicio público ($50.00).</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/orden-plaqueo') ?>" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card UR-07 -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-truck fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary-subtle text-primary small">UR-TT-T-07</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Carga y Descarga</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Autorización para operaciones de carga y descarga en vía pública municipal.</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/carga-descarga/formulario') ?>" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card UR-04 -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-bus-front fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary-subtle text-primary small">UR-TT-T-04</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Permiso Eventual</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Permiso temporal para cubrir necesidad del servicio por descompostura ($156.94).</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/permiso-eventual') ?>" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card UR-05 -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-sign-stop fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary-subtle text-primary small">UR-TT-T-05</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Cierre de Calle</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Permiso para cierre parcial o total de calle por evento, vigencia de un día ($287.00).</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/cierre-calle') ?>" class="btn btn-primary btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Card UR-06 (feature flag) -->
    <?php if (!empty($habilitaT06)): ?>
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success text-white p-2 rounded-3 me-2 flex-shrink-0">
                            <i class="bi bi-arrow-left-right fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-success-subtle text-success small">UR-TT-T-06</span>
                            <h3 class="h6 card-title mb-0 fw-bold text-dark">Cesión de Concesión</h3>
                        </div>
                    </div>
                    <p class="card-text small text-muted mb-3">Transferencia de derechos y obligaciones de una concesión de transporte.</p>
                </div>
                <div>
                    <a href="<?= site_url('/portal/tramites/cesion-concesion') ?>" class="btn btn-success btn-sm w-100 shadow-sm fw-semibold">
                        <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- SECCIÓN: ÚLTIMAS SOLICITUDES -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fs-6 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas solicitudes registradas</h5>
                <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-outline-primary btn-sm fw-semibold">
                    <i class="bi bi-list me-1"></i>Ver todas mis solicitudes
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasSolicitudes)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 mb-2 d-block opacity-50 text-secondary"></i>
                    <p class="mb-3">No tienes solicitudes registradas en tu historial aún.</p>
                    <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i>Explorar y solicitar trámite
                    </a>
                </div>
                <?php else: ?>
                <!-- Mobile Card List (Visible en móviles) -->
                <div class="d-block d-md-none divide-y">
                    <?php foreach ($ultimasSolicitudes as $s): ?>
                        <?php
                            $estatusClass = 'bg-secondary';
                            $mapClass = [
                                'Recibido'               => 'bg-info text-dark',
                                'Cita agendada'          => 'bg-info text-dark',
                                'Evaluación comparativa' => 'bg-info text-dark',
                                'Pago pendiente'         => 'bg-warning text-dark',
                                'Pagado'                 => 'bg-primary',
                                'Verificado'             => 'bg-success',
                                'Seleccionado'           => 'bg-success',
                                'Permiso emitido'        => 'bg-success',
                                'Vigente'                => 'bg-success',
                                'Rechazado'              => 'bg-danger',
                                'No seleccionado'        => 'bg-secondary',
                            ];
                            if (isset($mapClass[$s->estatus])) $estatusClass = $mapClass[$s->estatus];
                        ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="fw-bold font-monospace text-primary"><?= esc($s->folio) ?></span>
                                    <div class="small fw-semibold"><?= esc(tramite_nombre($s->tramite)) ?></div>
                                </div>
                                <span class="badge estatus-badge <?= $estatusClass ?>"><?= esc($s->estatus) ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                                <div><i class="bi bi-calendar3 me-1"></i><?= formatear_fecha($s->fecha_solicitud) ?></div>
                                <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary py-0">Ver detalle</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Desktop Table List (Visible en pantallas medianas y grandes) -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Trámite</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Estatus</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasSolicitudes as $s): ?>
                                <?php
                                    $estatusClass = 'bg-secondary';
                                    $mapClass = [
                                        'Recibido'               => 'bg-info text-dark',
                                        'Cita agendada'          => 'bg-info text-dark',
                                        'Evaluación comparativa' => 'bg-info text-dark',
                                        'Pago pendiente'         => 'bg-warning text-dark',
                                        'Pagado'                 => 'bg-primary',
                                        'Verificado'             => 'bg-success',
                                        'Seleccionado'           => 'bg-success',
                                        'Permiso emitido'        => 'bg-success',
                                        'Vigente'                => 'bg-success',
                                        'Rechazado'              => 'bg-danger',
                                        'No seleccionado'        => 'bg-secondary',
                                    ];
                                    if (isset($mapClass[$s->estatus])) $estatusClass = $mapClass[$s->estatus];
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="fw-bold font-monospace text-decoration-none">
                                            <?= esc($s->folio) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= esc(tramite_nombre($s->tramite)) ?></div>
                                        <div class="small text-muted"><?= esc($s->tramite) ?></div>
                                    </td>
                                    <td>
                                        <div class="small text-muted"><?= formatear_fecha($s->fecha_solicitud) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= formatear_dinero((float)$s->monto) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge estatus-badge <?= $estatusClass ?> px-3 py-2"><?= esc($s->estatus) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary fw-semibold">
                                            <i class="bi bi-eye me-1"></i>Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
