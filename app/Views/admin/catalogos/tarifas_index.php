<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\Catalogos\TarifasController::index() vía
 * view(..., $data). Se leen desde get_defined_vars() para que Intelephense
 * no las marque como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<int, string> $tramites */
$tramites = $__view['tramites'] ?? [];

/** @var array{tramite?: string} $filtros */
$filtros = $__view['filtros'] ?? [];

/** @var array<int, object> $tarifas */
$tarifas = $__view['tarifas'] ?? [];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Catálogo de Tarifas<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Tarifario de trámites',
    'texto'  => 'Define el costo oficial vigente por trámite. Los montos se aplican automáticamente a las nuevas solicitudes según el tarifario activo.',
]) ?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Filtrar por trámite</label>
                <select name="tramite" class="form-select form-select-sm">
                    <option value="">Todos los trámites</option>
                    <?php foreach ($tramites as $t): ?>
                        <option value="<?= esc($t) ?>" <?= $filtros['tramite'] === $t ? 'selected' : '' ?>>
                            <?= esc($t) ?> - <?= esc(tramite_nombre($t)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <?php if (!empty($filtros['tramite'])): ?>
                    <a href="/admin/tarifas" class="btn btn-outline-secondary btn-sm ms-1"><i class="bi bi-x-lg me-1"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <a href="/admin/tarifas/nuevo" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i> Nueva tarifa</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Trámite</th>
                        <th>Criterio</th>
                        <th>Monto</th>
                        <th>Vigente desde</th>
                        <th>Vigente hasta</th>
                        <th>Placeholder</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tarifas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No hay tarifas registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tarifas as $tarifa): ?>
                            <tr>
                                <td class="fw-semibold">#<?= $tarifa->id ?></td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary"><?= esc($tarifa->tramite) ?></span>
                                    <div class="small text-muted"><?= esc(tramite_nombre($tarifa->tramite)) ?></div>
                                </td>
                                <td>
                                    <code class="text-dark"><?= esc($tarifa->criterio) ?></code>
                                    <?php if (!empty($tarifa->descripcion)): ?>
                                        <div class="small text-muted"><?= esc($tarifa->descripcion) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap fw-bold text-success"><?= formatear_dinero((float)$tarifa->monto) ?></td>
                                <td class="text-nowrap"><?= formatear_fecha($tarifa->vigente_desde, 'd/m/Y') ?></td>
                                <td class="text-nowrap">
                                    <?php if ($tarifa->vigente_hasta === null || $tarifa->vigente_hasta === ''): ?>
                                        <span class="badge bg-success">Vigente</span>
                                    <?php else: ?>
                                        <?= formatear_fecha($tarifa->vigente_hasta, 'd/m/Y') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($tarifa->placeholder_oficial == 1): ?>
                                        <span class="badge bg-warning text-dark">NO OFICIAL</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Verificado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="/admin/tarifas/editar/<?= $tarifa->id ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Editar tarifa" aria-label="Editar tarifa"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" data-bs-title="Eliminar tarifa" aria-label="Eliminar tarifa" onclick="confirmarEliminar(<?= $tarifa->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    <form id="form-eliminar-<?= $tarifa->id ?>" action="/admin/tarifas/eliminar/<?= $tarifa->id ?>" method="post" class="d-none"></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de eliminar esta tarifa? No afectará solicitudes ya procesadas.')) {
        document.getElementById('form-eliminar-' + id).submit();
    }
}
</script>

<?= $this->endSection() ?>
