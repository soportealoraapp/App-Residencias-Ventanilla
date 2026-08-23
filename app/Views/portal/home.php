<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center align-items-center min-vh-75 py-5">
    <div class="col-lg-10 text-center">
        <div class="mb-5">
            <div class="bg-primary bg-opacity-10 d-inline-block p-4 rounded-5 mb-4">
                <i class="bi bi-bank2 display-1 text-primary"></i>
            </div>
            <h1 class="display-5 fw-bold mb-3">Ventanilla Digital de Movilidad y Transporte</h1>
            <p class="lead text-muted mb-5">Gobierno Digital del H. Ayuntamiento de Uriangato, Gto.</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="card tramite-card h-100 border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="bg-success bg-opacity-10 d-inline-block p-3 rounded-3 mb-4">
                            <i class="bi bi-box-arrow-in-right display-4 text-success"></i>
                        </div>
                        <h3 class="card-title mb-3">Iniciar sesión</h3>
                        <p class="text-muted mb-4">Accede a tu cuenta para gestionar tus trámites y solicitudes.</p>
                        <a href="/auth/login" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar al portal
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card tramite-card h-100 border-0 shadow-lg">
                    <div class="card-body p-5">
                        <div class="bg-primary bg-opacity-10 d-inline-block p-3 rounded-3 mb-4">
                            <i class="bi bi-person-plus display-4 text-primary"></i>
                        </div>
                        <h3 class="card-title mb-3">Registrarse</h3>
                        <p class="text-muted mb-4">Crea una cuenta nueva para comenzar a usar la ventanilla digital.</p>
                        <a href="/auth/register" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-person-plus me-2"></i>Crear cuenta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
