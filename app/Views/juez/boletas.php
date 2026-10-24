<?= $this->extend('layouts/juez') ?>
<?= $this->section('pageTitle') ?>Boletas por calificar<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-info-circle fs-2 text-primary"></i>
            <div>
                <h2 class="h5">No hay un criterio verificable para formar esta lista</h2>
                <p class="text-muted mb-2">La tabla actual de boletas tiene un campo de estado, pero COB-09 no establece cuál valor representa una calificación pendiente ni las transiciones posteriores.</p>
                <p class="mb-0">La lista permanecerá vacía hasta que COB-10 defina ese contrato. Esta pantalla no consulta, modifica ni elimina boletas.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>