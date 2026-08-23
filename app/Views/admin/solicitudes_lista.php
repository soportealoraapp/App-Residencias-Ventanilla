<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Listado de Solicitudes<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
helper('url');
$solicitudDatoModel = new \App\Models\SolicitudDatoModel();
$userModel = new \App\Models\UserModel();

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

$tramitesDisponibles = ['UR-TT-T-07' => 'Permiso de Carga y Descarga (T-07)'];
if (\App\Libraries\FeatureFlags::habilitarUrTtT06()) {
    $tramitesDisponibles['UR-TT-T-06'] = 'Cesión de Concesión (T-06)';
}

$estatusLista = \App\Libraries\EstadoSolicitudService::ESTATUS_MAESTRO;
?>

<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Filtros</h6>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('admin/solicitudes') ?>" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="tramite" class="form-label">Trámite</label>
                <select name="tramite" id="tramite" class="form-select">
                    <option value="">— Todos los trámites —</option>
                    <?php foreach ($tramitesDisponibles as $clave => $nombre): ?>
                        <option value="<?= esc($clave) ?>" <?= ($filtros['tramite'] === $clave) ? 'selected' : '' ?>>
                            <?= esc($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="estatus" class="form-label">Estatus</label>
                <select name="estatus" id="estatus" class="form-select">
                    <option value="">— Todos los estatus —</option>
                    <?php foreach ($estatusLista as $e): ?>
                        <option value="<?= esc($e) ?>" <?= ($filtros['estatus'] === $e) ? 'selected' : '' ?>>
                            <?= esc($e) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="q" class="form-label">Búsqueda</label>
                <input type="text" name="q" id="q" class="form-control"
                    placeholder="folio, RFC, razón social o nombre"
                    value="<?= esc($filtros['q']) ?>">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary">Aplicar filtros</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Solicitudes (<?= number_format($pager->getTotal()) ?> registros)</h6>
        <?php if ($filtros['tramite'] !== '' || $filtros['estatus'] !== '' || $filtros['q'] !== ''): ?>
            <a href="<?= site_url('admin/solicitudes') ?>" class="btn btn-outline-secondary btn-sm">Limpiar filtros</a>
        <?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Folio</th>
                    <th>Trámite</th>
                    <th>Ciudadano</th>
                    <th>RFC</th>
                    <th>Estatus</th>
                    <th>Fecha solicitud</th>
                    <th>Monto</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            No se encontraron solicitudes con los filtros indicados
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $s): ?>
                        <?php
                            $datos = $solicitudDatoModel->porSolicitudAgrupado($s->id);
                            $ciudadano = !empty($s->ciudadano_id) ? $userModel->find($s->ciudadano_id) : null;
                            $rfc = $datos['rfc'] ?? $datos['RFC'] ?? ($ciudadano->rfc ?? '');
                            $nombre = $datos['razon_social_o_nombre'] ?? $datos['solicitante_nombre'] ?? ($ciudadano->nombre_completo ?? ($ciudadano->username ?? '—'));
                        ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="fw-bold text-decoration-none">
                                    <code><?= esc($s->folio) ?></code>
                                </a>
                            </td>
                            <td>
                                <div><?= tramite_nombre($s->tramite) ?></div>
                                <small class="text-muted"><?= esc($s->tramite) ?></small>
                            </td>
                            <td><?= esc($nombre) ?></td>
                            <td><?= !empty($rfc) ? esc($rfc) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <span class="badge estatus-badge <?= badge_color_estatus($s->estatus) ?>">
                                    <?= esc($s->estatus) ?>
                                </span>
                            </td>
                            <td class="text-nowrap"><?= formatear_fecha($s->fecha_solicitud) ?></td>
                            <td class="text-nowrap"><?= formatear_dinero((float)$s->monto) ?></td>
                            <td class="text-nowrap">
                                <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>"
                                    class="btn btn-outline-primary btn-sm">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($solicitudes) && $pager->getPageCount() > 1): ?>
        <div class="card-footer d-flex justify-content-center pt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
