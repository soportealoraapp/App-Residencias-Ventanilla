<?= $this->extend('layouts/juez') ?>
<?= $this->section('pageTitle') ?>Inicio del juez calificador<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="mb-4">
    <p class="text-muted mb-0">Revisa las boletas recibidas, fija las UMA de cada falta y genera su cobro pendiente.</p>
</div>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-receipt-cutoff fs-2 text-primary"></i>
                <h2 class="h5 mt-3">Boletas por calificar</h2>
                <div class="display-6 fw-bold mb-2"><?= (int) ($pendientes ?? 0) ?></div>
                <p class="text-muted small">Solo incluye boletas con estado recibida.</p>
                <a href="/juez/boletas" class="btn btn-primary">Abrir listado</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-cash-coin fs-2 text-secondary"></i>
                <h2 class="h5 mt-3">Cobros</h2>
                <p class="text-muted small mb-0">Al confirmar una calificación se genera un cobro pendiente. La pantalla general de cobros pertenece a COB-02.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
