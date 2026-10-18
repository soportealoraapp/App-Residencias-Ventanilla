<?php declare(strict_types=1);

/**
 * Variables inyectadas por el controlador del panel vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, int> $estadisticas */
$estadisticas = $__view['estadisticas'] ?? [];

/** @var array<string, int> $porTramite */
$porTramite = $__view['estadisticas']['por_tramite'] ?? [];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Inicio<?= $this->endSection() ?>
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
        <a href="<?= site_url('admin/solicitudes') ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Total Solicitudes</span>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-dark fs-3"><?= number_format($estadisticas['total']) ?></h3>
                <small class="text-muted">Registro global</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= site_url('admin/solicitudes?estatus=Recibido') ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Pendientes</span>
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-warning fs-3"><?= number_format($estadisticas['pendientes']) ?></h3>
                <small class="text-muted">Rec: <?= $estadisticas['recibido'] ?> | Rev: <?= $estadisticas['en_revision'] ?></small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= site_url('admin/solicitudes?estatus=En revisión') ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">En Revisión</span>
                    <div class="stat-icon-wrapper bg-info-subtle text-info">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-info fs-3"><?= number_format($estadisticas['en_revision']) ?></h3>
                <small class="text-muted">En dictamen técnico</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= site_url('admin/solicitudes?estatus=Pagado') ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Vigentes / Pagados</span>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-success fs-3"><?= number_format($estadisticas['pagados_vigentes']) ?></h3>
                <small class="text-muted">Trámites autorizados</small>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-4 col-lg">
        <a href="<?= site_url('admin/solicitudes?estatus=Rechazado') ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Rechazados</span>
                    <div class="stat-icon-wrapper bg-danger-subtle text-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-danger fs-3"><?= number_format($estadisticas['rechazados']) ?></h3>
                <small class="text-muted">Con observaciones</small>
            </div>
        </a>
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

<?php if (!empty($porTramite)): ?>
<div class="card mb-3 mb-md-4 border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-pie-chart me-1 text-primary"></i>Conteo por trámite</h6>
    </div>
    <div class="card-body">
        <div class="row g-2 g-md-3">
            <?php foreach ($porTramite as $clave => $cantidad): ?>
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
