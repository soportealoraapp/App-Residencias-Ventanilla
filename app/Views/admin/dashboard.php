<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
function badge_color_estatus(string $estatus): string {
    return match($estatus) {
        'Recibido' => 'bg-secondary',
        'En revisión' => 'bg-primary',
        'Prevención' => 'bg-warning text-dark',
        'Pago pendiente' => 'bg-info text-dark',
        'Pagado' => 'bg-success',
        'Vigente' => 'bg-success',
        'Vencido' => 'bg-danger',
        'Rechazado' => 'bg-danger',
        'Concluido' => 'bg-dark',
        default => 'bg-secondary',
    };
}
?>

<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-6 col-md-4 col-lg">
        <div class="card text-bg-primary h-100 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-white-50 small mb-1">Total Solicitudes</h6>
                        <h2 class="card-text mb-0 fs-3 fw-bold"><?= number_format($estadisticas['total']) ?></h2>
                    </div>
                    <i class="bi bi-clipboard-data fs-2 opacity-50 d-none d-sm-inline" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card text-bg-warning h-100 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-dark-50 small mb-1">Pendientes</h6>
                        <h2 class="card-text mb-0 fs-3 fw-bold text-dark"><?= number_format($estadisticas['pendientes']) ?></h2>
                        <small class="text-dark-50 d-none d-sm-inline">Rec: <?= $estadisticas['recibido'] ?> | Rev: <?= $estadisticas['en_revision'] ?></small>
                    </div>
                    <i class="bi bi-hourglass-split fs-2 opacity-50 d-none d-sm-inline" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card text-bg-info h-100 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-dark small mb-1">En Revisión</h6>
                        <h2 class="card-text mb-0 fs-3 fw-bold text-dark"><?= number_format($estadisticas['en_revision']) ?></h2>
                    </div>
                    <i class="bi bi-search fs-2 opacity-50 d-none d-sm-inline" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <div class="card text-bg-success h-100 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-white-50 small mb-1">Vigentes / Pagados</h6>
                        <h2 class="card-text mb-0 fs-3 fw-bold"><?= number_format($estadisticas['pagados_vigentes']) ?></h2>
                    </div>
                    <i class="bi bi-check-circle fs-2 opacity-50 d-none d-sm-inline" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 col-lg">
        <div class="card text-bg-danger h-100 shadow-sm border-0">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-white-50 small mb-1">Rechazados</h6>
                        <h2 class="card-text mb-0 fs-3 fw-bold"><?= number_format($estadisticas['rechazados']) ?></h2>
                    </div>
                    <i class="bi bi-x-circle fs-2 opacity-50 d-none d-sm-inline" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-12 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0 small fw-bold"><i class="bi bi-hourglass-split me-1 text-warning"></i>Pago pendiente</h6>
            </div>
            <div class="card-body text-center py-3">
                <h3 class="mb-0 text-warning fw-bold"><?= number_format($estadisticas['pago_pendiente']) ?></h3>
                <small class="text-muted">solicitudes esperando cobro</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-2">
                <h6 class="mb-0 small fw-bold"><i class="bi bi-archive me-1 text-secondary"></i>Vencidos / Concluidos</h6>
            </div>
            <div class="card-body text-center py-3">
                <h3 class="mb-0 text-dark fw-bold"><?= number_format($estadisticas['vencidos_concluidos']) ?></h3>
                <small class="text-muted">Vencidos: <?= $estadisticas['vencido'] ?> | Concluidos: <?= $estadisticas['concluido'] ?></small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-success text-white py-2">
                <h6 class="mb-0 small fw-bold"><i class="bi bi-cash-coin me-1"></i>Monto cobrado hoy</h6>
            </div>
            <div class="card-body text-center py-3">
                <h3 class="text-success mb-0 fw-bold"><?= formatear_dinero($estadisticas['monto_hoy']) ?></h3>
                <small class="text-muted">ingresos del día</small>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($estadisticas['por_tramite'])): ?>
<div class="card mb-3 mb-md-4 border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-pie-chart me-1 text-primary"></i>Conteo por trámite</h6>
    </div>
    <div class="card-body">
        <div class="row g-2 g-md-3">
            <?php foreach ($estadisticas['por_tramite'] as $clave => $cantidad): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="small text-muted mb-1"><?= tramite_nombre($clave) ?></div>
                        <h4 class="mb-0 fw-bold text-primary"><?= number_format($cantidad) ?> <span class="fs-6 fw-normal text-muted">solicitudes</span></h4>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas 10 actividades</h6>
    </div>
    
    <!-- Mobile Card Feed (< 768px) -->
    <div class="d-block d-md-none divide-y">
        <?php if (empty($ultimasActividades)): ?>
            <div class="text-center text-muted py-4">Sin actividades recientes</div>
        <?php else: ?>
            <?php foreach ($ultimasActividades as $act): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="small text-muted"><?= formatear_fecha($act['historial']->fecha) ?></span>
                        <span class="badge estatus-badge <?= badge_color_estatus($act['historial']->estatus_nuevo) ?>">
                            <?= esc($act['historial']->estatus_nuevo) ?>
                        </span>
                    </div>
                    <div class="fw-semibold small mb-1">
                        <?= esc($act['usuario']->nombre_completo ?? $act['usuario']->username ?? 'Sistema') ?>
                    </div>
                    <?php if ($act['solicitud']): ?>
                        <div class="small mb-1">
                            <a href="<?= site_url('admin/solicitudes/' . $act['solicitud']->folio) ?>" class="fw-bold font-monospace">
                                <?= esc($act['solicitud']->folio) ?>
                            </a>
                            <span class="text-muted">· <?= tramite_nombre($act['solicitud']->tramite) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($act['historial']->comentario)): ?>
                        <div class="small text-muted bg-light p-2 rounded mt-2">
                            <?= esc($act['historial']->comentario) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table (>= 768px) -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Solicitud</th>
                    <th>Estatus nuevo</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimasActividades)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Sin actividades recientes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimasActividades as $act): ?>
                        <tr>
                            <td class="text-nowrap small"><?= formatear_fecha($act['historial']->fecha) ?></td>
                            <td>
                                <?php if ($act['usuario']): ?>
                                    <span class="fw-semibold small"><?= esc($act['usuario']->nombre_completo ?? $act['usuario']->username ?? '—') ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($act['solicitud']): ?>
                                    <a href="<?= site_url('admin/solicitudes/' . $act['solicitud']->folio) ?>" class="fw-bold text-decoration-none">
                                        <code><?= esc($act['solicitud']->folio) ?></code>
                                    </a>
                                    <div class="small text-muted"><?= tramite_nombre($act['solicitud']->tramite) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($act['historial']->estatus_anterior)): ?>
                                    <span class="badge estatus-badge bg-light text-dark border text-decoration-line-through me-1">
                                        <?= esc($act['historial']->estatus_anterior) ?>
                                    </span>
                                    <span class="text-muted mx-1">→</span>
                                <?php endif; ?>
                                <span class="badge estatus-badge <?= badge_color_estatus($act['historial']->estatus_nuevo) ?>">
                                    <?= esc($act['historial']->estatus_nuevo) ?>
                                </span>
                            </td>
                            <td class="small">
                                <?= !empty($act['historial']->comentario) ? esc($act['historial']->comentario) : '<span class="text-muted">—</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?= $this->endSection() ?>
