<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Panel de Inicio - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3 mb-md-4 align-items-center">
    <div class="col">
        <h1 class="h3 mb-1"><i class="bi bi-speedometer2 text-primary me-2"></i>Panel de Inicio</h1>
        <p class="text-muted small mb-0">Bienvenido al Portal Ciudadano de Uriangato. Gestiona tus permisos y trámites.</p>
    </div>
</div>

<div class="row g-3 mb-4 mb-md-5">
    <div class="col-12 col-md-6 col-lg-6">
        <div class="card tramite-card h-100 shadow-sm border-0">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 flex-shrink-0">
                        <i class="bi bi-truck fs-3"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="badge bg-primary-subtle text-primary mb-1">UR-TT-T-07</div>
                        <h2 class="h5 card-title mb-1 fw-bold">Permiso de Carga y Descarga</h2>
                        <p class="card-text small text-muted mb-3">Autorización para operaciones de carga y descarga en vía pública con control de horarios.</p>
                        <a href="<?= site_url('/portal/tramites/carga-descarga/formulario') ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                            <i class="bi bi-play-circle me-1"></i>Iniciar trámite
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($habilitaT06)): ?>
    <div class="col-12 col-md-6 col-lg-6">
        <div class="card tramite-card h-100 shadow-sm border-0" style="border-left-color: #198754;">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 flex-shrink-0">
                        <i class="bi bi-arrow-left-right fs-3"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="badge bg-success-subtle text-success mb-1">UR-TT-T-06</div>
                        <h2 class="h5 card-title mb-1 fw-bold">Cesión de Concesión</h2>
                        <p class="card-text small text-muted mb-3">Trámite para transferir derechos de concesión de transporte público.</p>
                        <button class="btn btn-outline-secondary btn-sm" disabled>
                            <i class="bi bi-lock me-1"></i>Próximamente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 fs-6 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas solicitudes</h5>
                <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-list me-1"></i>Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasSolicitudes)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 mb-2 d-block opacity-50"></i>
                    <p class="mb-2">No tienes solicitudes registradas aún.</p>
                    <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm">Explorar trámites</a>
                </div>
                <?php else: ?>
                <!-- Mobile Card List (Visible on < 768px) -->
                <div class="d-block d-md-none divide-y">
                    <?php foreach ($ultimasSolicitudes as $s): ?>
                        <?php
                            $estatusClass = 'bg-secondary';
                            $mapClass = [
                                'Recibido' => 'bg-info text-dark',
                                'Pago pendiente' => 'bg-warning text-dark',
                                'Pagado' => 'bg-primary',
                                'Permiso emitido' => 'bg-success',
                                'Vigente' => 'bg-success',
                                'Rechazado' => 'bg-danger',
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
                                <div class="fw-bold text-dark fs-6"><?= formatear_dinero((float)$s->monto) ?></div>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary w-100">
                                    <i class="bi bi-eye me-1"></i>Ver detalle
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Desktop/Tablet Table (Visible on >= 768px) -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Trámite</th>
                                <th>Estatus</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ultimasSolicitudes as $s): ?>
                            <?php
                                $estatusClass = 'bg-secondary';
                                $mapClass = [
                                    'Recibido' => 'bg-info text-dark',
                                    'Pago pendiente' => 'bg-warning text-dark',
                                    'Pagado' => 'bg-primary',
                                    'Permiso emitido' => 'bg-success',
                                    'Vigente' => 'bg-success',
                                    'Rechazado' => 'bg-danger',
                                ];
                                if (isset($mapClass[$s->estatus])) $estatusClass = $mapClass[$s->estatus];
                            ?>
                            <tr>
                                <td><code class="fw-bold text-primary"><?= esc($s->folio) ?></code></td>
                                <td><?= esc(tramite_nombre($s->tramite)) ?></td>
                                <td>
                                    <span class="badge estatus-badge <?= $estatusClass ?>"><?= esc($s->estatus) ?></span>
                                </td>
                                <td class="small text-nowrap"><?= formatear_fecha($s->fecha_solicitud) ?></td>
                                <td class="fw-semibold"><?= formatear_dinero((float)$s->monto) ?></td>
                                <td class="text-end">
                                    <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>Ver
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

