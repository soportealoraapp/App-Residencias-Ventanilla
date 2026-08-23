<?php declare(strict_types=1);
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col">
        <h1 class="h3 mb-2"><i class="bi bi-folder2-open text-primary me-2"></i>Trámites Disponibles</h1>
        <p class="text-muted mb-0">Selecciona el trámite que deseas iniciar.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm">
            <div class="card-header bg-primary bg-opacity-10 border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white p-2 rounded me-3">
                        <i class="bi bi-truck fs-4"></i>
                    </div>
                    <div>
                        <div class="small text-muted">UR-TT-T-07</div>
                        <h5 class="mb-0 text-primary">Permiso de Carga y Descarga</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p class="card-text text-muted mb-3">Autorización oficial para llevar a cabo operaciones de carga y descarga de mercancías en la vía pública, con control de horarios y periodos de vigencia.</p>
                <ul class="small text-muted mb-4 ps-3">
                    <li>Para particulares y empresas</li>
                    <li>Vigencia: Día, Mes, Semestre o Año</li>
                    <li>Pago en línea BanBajío</li>
                    <li>Emisión inmediata después de pago</li>
                </ul>
                <div class="d-grid">
                    <a href="<?= site_url('/portal/tramites/carga-descarga/formulario') ?>" class="btn btn-primary">
                        <i class="bi bi-play-circle me-2"></i>Iniciar trámite
                    </a>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0">
                <span class="badge bg-success-subtle text-success">
                    <i class="bi bi-check-circle me-1"></i>Disponible
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($habilitaT06)): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card tramite-card h-100 shadow-sm" style="border-left-color: #198754;">
            <div class="card-header bg-success bg-opacity-10 border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-success text-white p-2 rounded me-3">
                        <i class="bi bi-arrow-left-right fs-4"></i>
                    </div>
                    <div>
                        <div class="small text-muted">UR-TT-T-06</div>
                        <h5 class="mb-0 text-success">Cesión de Concesión</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p class="card-text text-muted mb-3">Procedimiento administrativo para transferir los derechos y obligaciones de una concesión de transporte público a un nuevo titular.</p>
                <ul class="small text-muted mb-4 ps-3">
                    <li>Validación documental</li>
                    <li>Revisión por operador</li>
                    <li>Aprobación administrativa</li>
                </ul>
                <div class="d-grid">
                    <button class="btn btn-outline-secondary" disabled>
                        <i class="bi bi-lock me-2"></i>Próximamente
                    </button>
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0">
                <span class="badge bg-secondary-subtle text-secondary">
                    <i class="bi bi-hourglass-split me-1"></i>En desarrollo
                </span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
