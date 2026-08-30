<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Inicio - Ventanilla Digital de Movilidad Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="row justify-content-center align-items-center py-3 py-md-5">
    <div class="col-lg-10 text-center">
        <div class="mb-4 mb-md-5">
            <div class="mb-3 mb-md-4">
                <img src="<?= base_url('logo-uri.webp') ?>" alt="Logo H. Ayuntamiento de Uriangato" style="height: 96px;">
            </div>
            <h1 class="display-5 fw-bold mb-2 mb-md-3">Ventanilla Digital de Movilidad</h1>
            <p class="lead text-muted mb-3 mb-md-4">Gobierno Digital del H. Ayuntamiento de Uriangato, Gto.</p>
            <div class="d-inline-flex flex-wrap justify-content-center gap-2 mb-2">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill"><i class="bi bi-shield-check me-1"></i> Plataforma Oficial de Trámites</span>
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill"><i class="bi bi-patch-check me-1"></i> Validez Jurídica y Folio Digital</span>
            </div>
        </div>

        <div class="row g-3 g-md-4 justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="card tramite-card h-100 border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="bg-success bg-opacity-10 d-inline-flex p-3 rounded-3 mb-3 text-success">
                                <i class="bi bi-box-arrow-in-right fs-2"></i>
                            </div>
                            <h3 class="card-title h4 mb-2">Iniciar sesión</h3>
                            <p class="text-muted small mb-4">Accede a tu cuenta para consultar o pagar tus trámites registrados.</p>
                        </div>
                        <a href="/auth/login" class="btn btn-success btn-lg w-100 shadow-sm" style="background-color: #0e9f6e; border-color: #0e9f6e;">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Entrar al portal
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-5">
                <div class="card tramite-card h-100 border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="bg-primary bg-opacity-10 d-inline-flex p-3 rounded-3 mb-3 text-primary">
                                <i class="bi bi-person-plus fs-2"></i>
                            </div>
                            <h3 class="card-title h4 mb-2">Registrarse</h3>
                            <p class="text-muted small mb-4">Crea tu cuenta de ciudadano en un minuto y solicita permisos en línea.</p>
                        </div>
                        <a href="/auth/register" class="btn btn-primary btn-lg w-100 shadow-sm">
                            <i class="bi bi-person-plus me-2"></i>Crear cuenta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

