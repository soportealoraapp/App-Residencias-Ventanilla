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
        'En revisión documental' => 'bg-primary',
        'Documentos completos' => 'bg-info text-dark',
        'En estudio técnico' => 'bg-primary',
        'Dictamen favorable' => 'bg-success',
        'Pendiente de inspección' => 'bg-warning text-dark',
        'Pendiente de revista mecánica' => 'bg-warning text-dark',
        'Seguro pendiente de validación' => 'bg-warning text-dark',
        'Autorizado para pago' => 'bg-info text-dark',
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

$tramitesDisponibles = [
    'UR-TT-T-04' => 'Permiso Eventual de Transporte (T-04)',
    'UR-TT-T-07' => 'Permiso de Carga y Descarga (T-07)',
];
if (\App\Libraries\FeatureFlags::habilitarUrTtT06()) {
    $tramitesDisponibles['UR-TT-T-06'] = 'Cesión de Concesión (T-06)';
}

$estatusLista = \App\Libraries\EstadoSolicitudService::ESTATUS_MAESTRO;
?>

<div class="card mb-3 mb-md-4 border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-funnel me-1 text-primary"></i>Filtros de búsqueda</h6>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('admin/solicitudes') ?>" class="row g-2 g-md-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="tramite" class="form-label small fw-semibold">Trámite</label>
                <select name="tramite" id="tramite" class="form-select form-select-sm">
                    <option value="">— Todos los trámites —</option>
                    <?php foreach ($tramitesDisponibles as $clave => $nombre): ?>
                        <option value="<?= esc($clave) ?>" <?= ($filtros['tramite'] === $clave) ? 'selected' : '' ?>>
                            <?= esc($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="estatus" class="form-label small fw-semibold">Estatus</label>
                <select name="estatus" id="estatus" class="form-select form-select-sm">
                    <option value="">— Todos los estatus —</option>
                    <?php foreach ($estatusLista as $e): ?>
                        <option value="<?= esc($e) ?>" <?= ($filtros['estatus'] === $e) ? 'selected' : '' ?>>
                            <?= esc($e) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="q" class="form-label small fw-semibold">Búsqueda</label>
                <input type="text" name="q" id="q" class="form-control form-control-sm"
                    placeholder="folio, RFC, razón social o nombre"
                    value="<?= esc($filtros['q']) ?>">
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Solicitudes (<?= number_format($pager->getTotal()) ?> registros)</h6>
        <?php if ($filtros['tramite'] !== '' || $filtros['estatus'] !== '' || $filtros['q'] !== ''): ?>
            <a href="<?= site_url('admin/solicitudes') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-x-lg me-1"></i>Limpiar
            </a>
        <?php endif; ?>
    </div>

    <!-- Mobile Card List (< 768px) -->
    <div class="d-block d-md-none">
        <?php if (empty($solicitudes)): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-2 mb-2 d-block opacity-50"></i>
                No se encontraron solicitudes con los filtros indicados.
            </div>
        <?php else: ?>
            <?php foreach ($solicitudes as $s): ?>
                <?php
                    $datos = $solicitudDatoModel->porSolicitudAgrupado($s->id);
                    $ciudadano = !empty($s->ciudadano_id) ? $userModel->find($s->ciudadano_id) : null;
                    $rfc = $datos['rfc'] ?? $datos['RFC'] ?? ($ciudadano->rfc ?? '');
                    $nombre = $datos['razon_social_o_nombre'] ?? $datos['solicitante_nombre'] ?? ($ciudadano->nombre_completo ?? ($ciudadano->username ?? '—'));
                ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                <?= esc($s->folio) ?>
                            </a>
                            <div class="small fw-semibold"><?= esc($nombre) ?></div>
                            <?php if (!empty($rfc)): ?>
                                <div class="small text-muted"><span class="badge bg-light text-dark border">RFC: <?= esc($rfc) ?></span></div>
                            <?php endif; ?>
                        </div>
                        <span class="badge estatus-badge <?= badge_color_estatus($s->estatus) ?>">
                            <?= esc($s->estatus) ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                        <div>
                            <div><?= tramite_nombre($s->tramite) ?></div>
                            <div><i class="bi bi-calendar3 me-1"></i><?= formatear_fecha($s->fecha_solicitud) ?></div>
                        </div>
                        <div class="fw-bold text-dark fs-6"><?= formatear_dinero((float)$s->monto) ?></div>
                    </div>
                    <div class="mt-3">
                        <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i>Ver expediente
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table (>= 768px) -->
    <div class="table-responsive d-none d-md-block">
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
                    <th class="text-end">Acciones</th>
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
                                <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="fw-bold text-decoration-none font-monospace">
                                    <?= esc($s->folio) ?>
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
                            <td class="text-nowrap small"><?= formatear_fecha($s->fecha_solicitud) ?></td>
                            <td class="text-nowrap fw-semibold"><?= formatear_dinero((float)$s->monto) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($solicitudes) && $pager->getPageCount() > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-center pt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

