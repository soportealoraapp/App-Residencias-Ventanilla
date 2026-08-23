<?php declare(strict_types=1);
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col d-flex justify-content-between align-items-start">
        <div>
            <h1 class="h3 mb-2"><i class="bi bi-list-check text-primary me-2"></i>Mis Solicitudes</h1>
            <p class="text-muted mb-0">Consulta el estatus y detalle de tus trámites registrados.</p>
        </div>
        <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Nuevo trámite
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($solicitudes)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 mb-3 d-block opacity-50"></i>
            <p class="mb-3">No has iniciado ningún trámite aún.</p>
            <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary">Explorar trámites disponibles</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
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
                    <tr>
                        <td><code class="small"><?= esc($s->folio) ?></code></td>
                        <td><?= esc(tramite_nombre($s->tramite)) ?></td>
                        <td>
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
                            <span class="badge estatus-badge <?= $estatusClass ?>"><?= esc($s->estatus) ?></span>
                        </td>
                        <td class="small"><?= formatear_fecha($s->fecha_solicitud) ?></td>
                        <td><?= formatear_dinero((float)$s->monto) ?></td>
                        <td class="text-end">
                            <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Ver detalle
                            </a>
                            <?php if ($s->estatus === 'Pago pendiente'): ?>
                            <a href="<?= site_url('/portal/tramites/carga-descarga/resumen/' . $s->folio) ?>" class="btn btn-sm btn-outline-success">
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
