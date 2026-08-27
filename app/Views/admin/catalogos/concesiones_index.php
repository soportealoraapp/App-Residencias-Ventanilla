<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Catálogo de Concesiones<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$filtros = isset($filtros) && is_array($filtros) ? $filtros : ['q' => '', 'estatus' => ''];
$filtros['q'] = $filtros['q'] ?? '';
$filtros['estatus'] = $filtros['estatus'] ?? '';
$estatusOpciones = isset($estatusOpciones) && is_array($estatusOpciones)
    ? $estatusOpciones
    : ['' => 'Todos', 'vigente' => 'Vigente', 'vencida' => 'Vencida', 'en_transmision' => 'En trámite de transmisión'];
?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Buscar</label>
                <input type="text" name="q" class="form-control form-control-sm"
                    value="<?= esc($filtros['q']) ?>"
                    placeholder="Núm. título, titular, placas...">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Estatus</label>
                <select name="estatus" class="form-select form-select-sm">
                    <?php foreach ($estatusOpciones as $valor => $etiqueta): ?>
                        <option value="<?= esc($valor) ?>" <?= $filtros['estatus'] === $valor ? 'selected' : '' ?>>
                            <?= esc($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search me-1"></i> Buscar</button>
                <?php if (!empty($filtros['q']) || !empty($filtros['estatus'])): ?>
                    <a href="/admin/concesiones" class="btn btn-outline-secondary btn-sm ms-1"><i class="bi bi-x-lg me-1"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <a href="/admin/concesiones/nuevo" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i> Nueva concesión</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Número título</th>
                        <th>Titular actual</th>
                        <th>Tipo persona</th>
                        <th>Placas</th>
                        <th>Núm. Serie</th>
                        <th>Vigencia inicio</th>
                        <th>Vigencia fin</th>
                        <th>Estatus</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($concesiones)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No hay concesiones registradas</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($concesiones as $c): ?>
                            <tr>
                                <td class="fw-semibold">#<?= $c->id ?></td>
                                <td><code class="text-dark"><?= esc($c->numero_titulo) ?></code></td>
                                <td><?= esc($c->titular_actual) ?></td>
                                <td>
                                    <?php if (!empty($c->tipo_persona)): ?>
                                        <span class="badge <?= $c->tipo_persona === 'fisica' ? 'bg-secondary' : 'bg-dark' ?>">
                                            <?= esc($c->tipo_persona === 'fisica' ? 'Física' : 'Moral') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c->vehiculo_placas)): ?>
                                        <span class="badge bg-info-subtle text-info"><?= esc($c->vehiculo_placas) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($c->vehiculo_num_serie) ? esc($c->vehiculo_num_serie) : '-' ?>
                                </td>
                                <td class="text-nowrap"><?= formatear_fecha($c->vigencia_inicio, 'd/m/Y') ?></td>
                                <td class="text-nowrap"><?= formatear_fecha($c->vigencia_fin, 'd/m/Y') ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($c->estatus) {
                                        'vigente'        => 'bg-success',
                                        'vencida'        => 'bg-secondary',
                                        'en_transmision' => 'bg-warning text-dark',
                                        default          => 'bg-light text-dark',
                                    };
                                    $estatusLabel = match($c->estatus) {
                                        'vigente'        => 'Vigente',
                                        'vencida'        => 'Vencida',
                                        'en_transmision' => 'En transmisión',
                                        default          => $c->estatus,
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= esc($estatusLabel) ?></span>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="/admin/concesiones/editar/<?= $c->id ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmarEliminar(<?= $c->id ?>)"><i class="bi bi-trash"></i></button>
                                    <form id="form-eliminar-<?= $c->id ?>" action="/admin/concesiones/eliminar/<?= $c->id ?>" method="post" class="d-none"></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($pager)): ?>
    <div class="mt-4 d-flex justify-content-center">
        <?= $pager->links('default', 'bootstrap') ?>
    </div>
<?php endif; ?>

<script>
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de eliminar esta concesión? Los cambios se registrarán en auditoría y no afectarán solicitudes ya creadas.')) {
        document.getElementById('form-eliminar-' + id).submit();
    }
}
</script>

<?= $this->endSection() ?>
