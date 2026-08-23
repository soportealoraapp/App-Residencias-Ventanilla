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

<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg">
        <div class="card text-bg-primary h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-white-50 mb-1">Total Solicitudes</h6>
                        <h2 class="card-text mb-0"><?= number_format($estadisticas['total']) ?></h2>
                    </div>
                    <span class="fs-1 opacity-25">📋</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-bg-warning h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-dark-50 mb-1">Pendientes (Recibido + Rev.)</h6>
                        <h2 class="card-text mb-0 text-dark"><?= number_format($estadisticas['pendientes']) ?></h2>
                        <small class="text-dark-50">Recibido: <?= $estadisticas['recibido'] ?> | En rev: <?= $estadisticas['en_revision'] ?></small>
                    </div>
                    <span class="fs-1 opacity-25">⏳</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-bg-info h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-dark mb-1">En Revisión</h6>
                        <h2 class="card-text mb-0 text-dark"><?= number_format($estadisticas['en_revision']) ?></h2>
                    </div>
                    <span class="fs-1 opacity-25">🔍</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-bg-success h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-white-50 mb-1">Pagados / Vigentes</h6>
                        <h2 class="card-text mb-0"><?= number_format($estadisticas['pagados_vigentes']) ?></h2>
                        <small class="text-white-50">Pagado: <?= $estadisticas['pagado'] ?> | Vigente: <?= $estadisticas['vigente'] ?></small>
                    </div>
                    <span class="fs-1 opacity-25">✅</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg">
        <div class="card text-bg-danger h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-white-50 mb-1">Rechazados</h6>
                        <h2 class="card-text mb-0"><?= number_format($estadisticas['rechazados']) ?></h2>
                    </div>
                    <span class="fs-1 opacity-25">❌</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Pago pendiente</h6>
            </div>
            <div class="card-body">
                <h3 class="text-center mb-0"><?= number_format($estadisticas['pago_pendiente']) ?> solicitudes</h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Vencidos / Concluidos</h6>
            </div>
            <div class="card-body">
                <h3 class="text-center mb-0"><?= number_format($estadisticas['vencidos_concluidos']) ?></h3>
                <small class="text-muted d-block text-center">Vencido: <?= $estadisticas['vencido'] ?> | Concluido: <?= $estadisticas['concluido'] ?></small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 border-success">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Monto cobrado hoy</h6>
            </div>
            <div class="card-body">
                <h3 class="text-center text-success mb-0"><?= formatear_dinero($estadisticas['monto_hoy']) ?></h3>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($estadisticas['por_tramite'])): ?>
<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Conteo por trámite</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($estadisticas['por_tramite'] as $clave => $cantidad): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-1 text-muted"><?= tramite_nombre($clave) ?></h6>
                            <h4 class="card-text mb-0"><?= number_format($cantidad) ?> solicitudes</h4>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Últimas 10 actividades (cambios de estatus)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
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
                            <td class="text-nowrap"><?= formatear_fecha($act['historial']->fecha) ?></td>
                            <td>
                                <?php if ($act['usuario']): ?>
                                    <?= esc($act['usuario']->nombre_completo ?? $act['usuario']->username ?? '—') ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($act['solicitud']): ?>
                                    <a href="<?= site_url('admin/solicitudes/' . $act['solicitud']->folio) ?>">
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
