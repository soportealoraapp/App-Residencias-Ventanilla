<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Catálogo de Infracciones - App Agentes<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/app-transito" class="text-decoration-none text-muted small">&larr; Volver a Configuración</a>
                <span class="text-muted small">/</span>
                <span class="badge bg-warning-subtle text-warning">Reglamento Uriangato</span>
            </div>
            <h2 class="h3 fw-bold mt-1 mb-0 text-dark">Catálogo Oficial de Infracciones</h2>
            <p class="text-muted small mb-0">Faltas viales disponibles en el catálogo de la app móvil para emisión de boletas en campo.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border p-2">
                <i class="bi bi-currency-dollar me-1 text-primary"></i> 1 UMA = <strong>$<?= number_format($valorUma, 2) ?> MXN</strong>
            </span>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center shadow-sm"
                data-bs-toggle="modal" data-bs-target="#modalNuevaFalta">
                <i class="bi bi-plus-circle me-2"></i> + Nueva Falta
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th class="ps-4">Fundamento Legal</th>
                            <th>Descripción Oficial</th>
                            <th>Categoría</th>
                            <th>Monto en UMA</th>
                            <th>Equivalente en Pesos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    No hay faltas registradas en el catálogo.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $minPesos = (float)$item->monto_min_uma * $valorUma;
                                    $maxPesos = (float)$item->monto_max_uma * $valorUma;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold font-monospace text-primary"><?= esc($item->fundamento_legal) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small" style="max-width: 380px;">
                                            <?= esc($item->descripcion) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border small">
                                            <?= esc($item->categoria) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small">
                                            <?= number_format((float)$item->monto_min_uma, 1) ?> - <?= number_format((float)$item->monto_max_uma, 1) ?>
                                        </div>
                                        <small class="text-muted">UMAs</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success small">
                                            $<?= number_format($minPesos, 2) ?> - $<?= number_format($maxPesos, 2) ?>
                                        </div>
                                        <small class="text-muted">MXN</small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->activo == 1): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                            onclick='editarFalta(<?= json_encode($item) ?>)'>
                                            Editar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVA/EDITAR FALTA -->
<div class="modal fade" id="modalNuevaFalta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalFaltaTitulo">Registrar Falta en Catálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/app-transito/catalogo/guardar" method="POST" id="formFalta">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="falta_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fundamento_legal" class="form-label small fw-semibold">Fundamento Legal (Artículo) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fundamento_legal" name="fundamento_legal" placeholder="Ej. Art. 39 Frac. IX" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label small fw-semibold">Descripción Oficial <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Texto descriptivo de la infracción" required></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="categoria" class="form-label small fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="categoria" name="categoria" placeholder="Ej. MANEJO Y VIALIDAD" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="orden" class="form-label small fw-semibold">Orden de Despliegue</label>
                            <input type="number" class="form-control" id="orden" name="orden" value="0">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="monto_min_uma" class="form-label small fw-semibold">Monto Mínimo (UMAs) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" min="0" class="form-control" id="monto_min_uma" name="monto_min_uma" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="monto_max_uma" class="form-label small fw-semibold">Monto Máximo (UMAs) <span class="text-danger">*</span></label>
                            <input type="number" step="0.1" min="0" class="form-control" id="monto_max_uma" name="monto_max_uma" required>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="activo">Falta Activa en la App Móvil</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Infracción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarFalta(item) {
    document.getElementById('modalFaltaTitulo').innerText = 'Editar Infracción';
    document.getElementById('falta_id').value = item.id;
    document.getElementById('fundamento_legal').value = item.fundamento_legal;
    document.getElementById('descripcion').value = item.descripcion;
    document.getElementById('categoria').value = item.categoria;
    document.getElementById('monto_min_uma').value = item.monto_min_uma;
    document.getElementById('monto_max_uma').value = item.monto_max_uma;
    document.getElementById('orden').value = item.orden ?? 0;
    document.getElementById('activo').checked = item.activo == 1;

    const modal = new bootstrap.Modal(document.getElementById('modalNuevaFalta'));
    modal.show();
}

document.getElementById('modalNuevaFalta')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalFaltaTitulo').innerText = 'Registrar Falta en Catálogo';
    document.getElementById('formFalta').reset();
    document.getElementById('falta_id').value = '';
    document.getElementById('activo').checked = true;
});
</script>

<?= $this->endSection() ?>
