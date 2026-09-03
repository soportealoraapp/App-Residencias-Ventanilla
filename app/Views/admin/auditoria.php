<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Auditoría del sistema<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="card mb-3 mb-md-4 border-0 shadow-sm">
    <div class="card-header bg-white py-3">
        <h2 class="h6 mb-1 fw-bold"><i class="bi bi-shield-check me-2 text-primary"></i>Registro de auditoría</h2>
        <p class="small text-muted mb-0">Consulta las acciones registradas sobre usuarios, trámites y catálogos.</p>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('admin/auditoria') ?>" class="row g-2 g-md-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="entidad" class="form-label small fw-semibold">Entidad</label>
                <select name="entidad" id="entidad" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach (['users' => 'Usuarios', 'user_roles' => 'Roles de usuario', 'solicitudes' => 'Solicitudes', 'tarifas' => 'Tarifas', 'concesiones' => 'Concesiones'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $filtros['entidad'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="accion" class="form-label small fw-semibold">Acción</label>
                <input type="text" name="accion" id="accion" class="form-control form-control-sm" value="<?= esc($filtros['accion']) ?>" placeholder="crear, editar, login...">
            </div>
            <div class="col-12 col-md-2">
                <label for="usuario_id" class="form-label small fw-semibold">ID usuario</label>
                <input type="number" min="1" name="usuario_id" id="usuario_id" class="form-control form-control-sm" value="<?= esc($filtros['usuario_id']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label for="fecha_desde" class="form-label small fw-semibold">Desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="<?= esc($filtros['fecha_desde']) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label for="fecha_hasta" class="form-label small fw-semibold">Hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="<?= esc($filtros['fecha_hasta']) ?>">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="<?= site_url('admin/auditoria') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Limpiar</a>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Eventos registrados</h2>
        <span class="small text-muted"><?= number_format($pager->getTotal()) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Fecha</th><th>Usuario</th><th>Entidad</th><th>ID</th><th>Acción</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php if (empty($registros)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No se encontraron eventos de auditoría.</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $registro): ?>
                    <?php $detalle = json_decode((string) ($registro->detalle ?? ''), true); ?>
                    <tr>
                        <td class="text-nowrap small"><?= formatear_fecha($registro->fecha) ?></td>
                        <td><div class="fw-semibold small"><?= esc($registro->nombre_completo ?: $registro->username ?: 'Sistema') ?></div><div class="small text-muted">ID: <?= esc((string) ($registro->usuario_id ?? '-')) ?></div></td>
                        <td><span class="badge bg-light text-dark border"><?= esc($registro->entidad) ?></span></td>
                        <td class="font-monospace"><?= esc((string) ($registro->entidad_id ?? '-')) ?></td>
                        <td><span class="badge bg-primary-subtle text-primary"><?= esc($registro->accion) ?></span></td>
                        <td class="small"><details><summary class="text-primary">Ver detalle</summary><pre class="bg-light border rounded p-2 mt-2 mb-0 small" style="max-width: 420px; white-space: pre-wrap;"><?= esc($detalle !== null ? (string) json_encode($detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) ($registro->detalle ?? '-')) ?></pre></details></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pager->getPageCount() > 1): ?><div class="card-footer bg-white d-flex justify-content-center pt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
<?= $this->endSection() ?>
