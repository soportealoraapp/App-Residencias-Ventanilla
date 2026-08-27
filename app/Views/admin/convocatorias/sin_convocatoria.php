<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Convocatorias · Concesión de Transporte<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row justify-content-center py-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 text-center p-4 p-md-5">
            <div class="mb-4">
                <div class="bg-warning bg-opacity-10 text-warning d-inline-block p-4 rounded-circle">
                    <i class="bi bi-award fs-1"></i>
                </div>
            </div>
            <h2 class="h4 fw-bold text-dark mb-2">No se encontraron Convocatorias Registradas</h2>
            <p class="text-muted lead fs-6 mb-4">
                Actualmente no hay ninguna convocatoria de otorgamiento de concesión registrada en la base de datos o la convocatoria solicitada no existe.
            </p>

            <div class="alert alert-light border text-start small text-muted mb-4">
                <div class="fw-bold text-dark mb-2"><i class="bi bi-info-circle text-primary me-1"></i>Para publicar una convocatoria en el sistema:</div>
                <ol class="mb-0 ps-3">
                    <li>La convocatoria debe tener fecha de publicación, periodo de registro (inicio y fin), bases oficiales y estatus <code>Vigente</code>.</li>
                    <li>Una vez activa, los ciudadanos podrán postular sus expedientes en el portal ciudadano.</li>
                </ol>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <a href="<?= site_url('/admin/solicitudes?tramite=UR-TT-T-01') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-folder2 me-1"></i>Ver Solicitudes UR-01
                </a>
                <a href="<?= site_url('/admin/dashboard') ?>" class="btn btn-primary">
                    <i class="bi bi-speedometer2 me-1"></i>Volver al Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
