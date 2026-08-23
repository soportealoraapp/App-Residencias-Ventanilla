<?php declare(strict_types=1);
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-2"><i class="bi bi-speedometer2 text-primary me-2"></i>Panel de Inicio</h1>
        <p class="text-muted mb-0">Bienvenido al Portal Ciudadano de Uriangato. Desde aquí puedes gestionar tus trámites.</p>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card tramite-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded">
                            <i class="bi bi-truck fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">UR-TT-T-07</h5>
                        <h6 class="card-subtitle mb-2">Permiso de Carga y Descarga</h6>
                        <p class="card-text small text-muted">Autorización para realizar operaciones de carga y descarga en la vía pública.</p>
                        <a href="<?= site_url('/portal/tramites/carga-descarga/formulario') ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-arrow-right me-1"></i>Iniciar trámite
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($habilitaT06)): ?>
    <div class="col-md-6 col-lg-4 mb-3">
        <div class="card tramite-card h-100" style="border-left-color: #198754;">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="me-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded">
                            <i class="bi bi-arrow-left-right fs-3"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">UR-TT-T-06</h5>
                        <h6 class="card-subtitle mb-2">Cesión de Concesión</h6>
                        <p class="card-text small text-muted">Trámite para transferir derechos de concesión de transporte.</p>
                        <button class="btn btn-success btn-sm" disabled>
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
    <div class="col">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas solicitudes</h5>
                <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-list me-1"></i>Ver todas
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($ultimasSolicitudes)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 mb-3 d-block opacity-50"></i>
                    <p>No tienes solicitudes registradas aún.</p>
                    <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm mt-2">Explorar trámites</a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Folio</th>
                                <th>Trámite</th>
                                <th>Estatus</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ultimasSolicitudes as $s): ?>
                            <tr>
                                <td><code class="small"><?= esc($s->folio) ?></code></td>
                                <td><?= esc(tramite_nombre($s->tramite)) ?></td>
                                <td>
                                    <?php
                                        $estatusClass = 'bg-secondary';
                                        $mapClass = [
                                            'Recibido' => 'bg-info',
                                            'Pago pendiente' => 'bg-warning text-dark',
                                            'Pagado' => 'bg-primary',
                                            'Permiso emitido' => 'bg-success',
                                            'Vigente' => 'bg-success',
                                            'Rechazado' => 'bg-danger',
                                        ];
                                        if (isset($mapClass[$s->estatus])) $estatusClass = $mapClass[$s->estatus];
                                    ?>
                                    <span class="badge estatus-badge <?= $estatusClass ?>"><?= esc($s->estatus) ?></span>
                                </td>
                                <td class="small"><?= formatear_fecha($s->fecha_solicitud) ?></td>
                                <td><?= formatear_dinero((float)$s->monto) ?></td>
                                <td>
                                    <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-secondary">
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
