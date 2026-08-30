<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#111827">
    <title><?= $this->renderSection('pageTitle') ? esc($this->renderSection('pageTitle')) . ' - Panel Admin' : 'Panel Administrativo - Ventanilla Uriangato' ?></title>
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Mobile-First Stylesheet -->
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper">
    <!-- Mobile Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar-admin" id="sidebarAdmin">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center">
                <img src="<?= base_url('logo-uri.webp') ?>" alt="Logo Uriangato" class="me-2" style="height: 32px;">
                <span>Panel Admin</span>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" id="btnCloseSidebar" aria-label="Cerrar"></button>
        </div>

        <?php
            $qTramite = $_GET['tramite'] ?? '';
            $isAllSolicitudes = uri_string() === 'admin/solicitudes' && $qTramite === '';
        ?>
        <div class="sidebar-section-title">Operaciones</div>
        <a href="/admin/dashboard" class="sidebar-link <?= uri_string() === 'admin/dashboard' || uri_string() === 'admin' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="/admin/solicitudes" class="sidebar-link <?= $isAllSolicitudes ? 'active' : '' ?>">
            <i class="bi bi-folder2-open me-2"></i> Todas las solicitudes
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-01" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-01' ? 'active' : '' ?>">
            <i class="bi bi-award me-2"></i> T-01: Concesiones
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-02" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-02' ? 'active' : '' ?>">
            <i class="bi bi-paint-bucket me-2"></i> T-02: Despintado
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-03" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-03' ? 'active' : '' ?>">
            <i class="bi bi-card-heading me-2"></i> T-03: Plaqueo
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-04" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-04' ? 'active' : '' ?>">
            <i class="bi bi-bus-front me-2"></i> T-04: P. Eventual
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-05" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-05' ? 'active' : '' ?>">
            <i class="bi bi-sign-stop me-2"></i> T-05: Cierre Calle
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-07" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-07' ? 'active' : '' ?>">
            <i class="bi bi-truck me-2"></i> T-07: Carga/Descarga
        </a>
        <?php if (getenv('APP_ENABLE_UR_TT_T_06') === 'true'): ?>
            <a href="/admin/solicitudes?tramite=UR-TT-T-06" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-06' ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right me-2"></i> T-06: Cesión
            </a>
        <?php endif; ?>

        <div class="sidebar-section-title mt-3">Catálogos</div>
        <a href="/admin/tarifas" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/tarifas') ? 'active' : '' ?>">
            <i class="bi bi-cash-coin me-2"></i> Tarifario
        </a>
        <a href="/admin/concesiones" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/concesiones') ? 'active' : '' ?>">
            <i class="bi bi-card-list me-2"></i> Concesiones
        </a>
        <a href="/admin/convocatorias/1/evaluacion" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/convocatorias') ? 'active' : '' ?>">
            <i class="bi bi-award-fill me-2"></i> Convocatorias UR-01
        </a>

        <div class="sidebar-section-title mt-3">Portal</div>
        <a href="/portal/dashboard" class="sidebar-link text-info">
            <i class="bi bi-box-arrow-up-right me-2"></i> Ver Portal Ciudadano
        </a>

        <div class="sidebar-section-title mt-3">Sesión</div>
        <div class="px-3 py-2 text-white-50 small">
            <div class="fw-semibold text-white text-truncate"><?= esc(session('nombre_completo') ?? session('username') ?? 'Usuario') ?></div>
            <div class="badge bg-secondary-subtle text-white mt-1"><?= implode(', ', (array)(session('roles') ?? [])) ?></div>
        </div>
        <a href="/auth/logout" class="sidebar-link text-danger mt-2">
            <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
        </a>
    </aside>

    <!-- Main Content Area -->
    <div class="main-admin">
        <header class="topbar-admin">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm d-lg-none" id="btnSidebarToggle" aria-label="Menu">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="h5 mb-0 text-truncate"><?= $this->renderSection('pageTitle') ?></h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary d-none d-sm-inline-block">
                    <?= implode(', ', (array)(session('roles') ?? [])) ?>
                </span>
                <a href="/auth/logout" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center" title="Cerrar sesión">
                    <i class="bi bi-box-arrow-right"></i>
                    <span class="d-none d-md-inline ms-1">Salir</span>
                </a>
            </div>
        </header>

        <?php if (session('message')): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                <div><?= esc(session('message')) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <div class="d-flex align-items-center mb-1">
                    <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                    <strong>Por favor corrige los siguientes errores:</strong>
                </div>
                <ul class="mb-0 ps-3 small">
                    <?php foreach ((array)session('errors') as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarAdmin');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('btnSidebarToggle');
    const closeBtn = document.getElementById('btnCloseSidebar');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>


