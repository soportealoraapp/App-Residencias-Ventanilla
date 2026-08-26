<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Mis Solicitudes - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3 mb-md-4 align-items-center">
    <div class="col-12 col-sm-8 mb-2 mb-sm-0">
        <h1 class="h3 mb-1"><i class="bi bi-list-check text-primary me-2"></i>Mis Solicitudes</h1>
        <p class="text-muted small mb-0">Consulta el estatus, historial y comprobantes de tus trámites.</p>
    </div>
    <div class="col-12 col-sm-4 text-sm-end">
        <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm w-100 w-sm-auto shadow-sm">
            <i class="bi bi-plus-lg me-1"></i>Nuevo trámite
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($solicitudes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 mb-2 d-block opacity-50"></i>
            <p class="mb-3">No has iniciado ningún trámite aún.</p>
            <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm">Explorar trámites disponibles</a>
        </div>
        <?php else: ?>
        <!-- Mobile Card List (Visible on < 768px) -->
        <div class="d-block d-md-none">
            <?php foreach ($solicitudes as $s): ?>
                <?php
                    $estatusClass = 'bg-secondary';
                    $mapClass = [
                        'Recibido' => 'bg-info text-dark',
                        'Pago pendiente' => 'bg-warning text-dark',
                        'Pagado' => 'bg-primary',
                        'Permiso emitido' => 'bg-success',
                        'Vigente' => 'bg-success',
                        'Rechazado' => 'bg-danger',
                        'Vencido' => 'bg-secondary',
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
                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-eye me-1"></i>Ver detalle
                        </a>
                        <?php if ($s->estatus === 'Pago pendiente'): ?>
                            <a href="<?= site_url('/portal/tramites/carga-descarga/resumen/' . $s->folio) ?>" class="btn btn-sm btn-success flex-fill" style="background-color: #0e9f6e; border-color: #0e9f6e;">
                                <i class="bi bi-cash-coin me-1"></i>Pagar
                            </a>
                        <?php endif; ?>
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
                        <th>Fecha solicitud</th>
                        <th>Monto</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($solicitudes as $s): ?>
                    <?php
                        $estatusClass = 'bg-secondary';
                        $mapClass = [
                            'Recibido' => 'bg-info text-dark',
                            'Pago pendiente' => 'bg-warning text-dark',
                            'Pagado' => 'bg-primary',
                            'Permiso emitido' => 'bg-success',
                            'Vigente' => 'bg-success',
                            'Rechazado' => 'bg-danger',
                            'Vencido' => 'bg-secondary',
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
                        <td class="text-end text-nowrap">
                            <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-eye me-1"></i>Ver detalle
                            </a>
                            <?php if ($s->estatus === 'Pago pendiente'): ?>
                            <a href="<?= site_url('/portal/tramites/carga-descarga/resumen/' . $s->folio) ?>" class="btn btn-sm btn-success" style="background-color: #0e9f6e; border-color: #0e9f6e;">
                                <i class="bi bi-cash-coin me-1"></i>Pagar
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

