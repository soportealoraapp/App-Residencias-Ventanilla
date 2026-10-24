<?= $this->extend('layouts/juez') ?>
<?= $this->section('pageTitle') ?>Inicio del juez calificador<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="mb-4">
    <p class="text-muted mb-0">Espacio de trabajo para revisar y, cuando los módulos correspondientes estén disponibles, calificar boletas.</p>
</div>

<div class="alert alert-warning border-warning shadow-sm" role="alert">
    <h2 class="h6 fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Criterio de calificación pendiente</h2>
    <p class="mb-0">El sistema aún no define qué estado identifica una boleta “por calificar”. Para evitar una clasificación incorrecta, esta entrega no cuenta ni presenta boletas como pendientes. El criterio se conectará con COB-10.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-receipt-cutoff fs-2 text-primary"></i>
                <h2 class="h5 mt-3">Boletas por calificar</h2>
                <p class="text-muted small">Consulta de solo lectura; no incluye edición, eliminación ni acciones administrativas.</p>
                <a href="/juez/boletas" class="btn btn-outline-primary">Ver situación actual</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-cash-coin fs-2 text-secondary"></i>
                <h2 class="h5 mt-3">Cobros</h2>
                <p class="text-muted small mb-0">Función pendiente de COB-01. No hay acciones de cobro habilitadas en esta entrega.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-file-earmark-minus fs-2 text-secondary"></i>
                <h2 class="h5 mt-3">Condonaciones</h2>
                <p class="text-muted small mb-0">Función pendiente de COB-11. No hay acciones de condonación habilitadas en esta entrega.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>