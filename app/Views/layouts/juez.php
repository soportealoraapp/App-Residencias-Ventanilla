<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<?php $__tituloPagina = trim($this->renderSection('pageTitle', true)); ?>
    <title><?= $__tituloPagina !== '' ? esc($__tituloPagina) . ' - Juez calificador' : 'Juez calificador - Ventanilla Uriangato' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/webp" href="/logo-uri.webp">
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="has-admin-bottom-nav">
<div class="admin-wrapper">
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
    <aside class="sidebar-admin" id="sidebarAdmin" aria-label="Navegación del juez calificador">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center sidebar-brand-content">
                <img src="<?= base_url('logo-uri.webp') ?>" alt="Logo Uriangato" class="me-2 sidebar-logo" style="height:32px">
                <span class="sidebar-text">Panel del Juez</span>
            </div>
            <button type="button" class="btn-close btn-close-white d-md-none" id="btnCloseSidebar" aria-label="Cerrar menú"></button>
        </div>

        <div class="sidebar-alcance">
            <span class="alcance-nombre rol-chip rol-admin">Juez calificador</span>
            <p class="alcance-detalle">Consulta de boletas y acceso reservado a las funciones de calificación.</p>
        </div>

        <div class="sidebar-section-title">Boletas</div>
        <a href="/juez" class="sidebar-link <?= uri_string() === 'juez' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Inicio</span>
        </a>
        <a href="/juez/boletas" class="sidebar-link <?= str_starts_with(uri_string(), 'juez/boletas') ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Boletas por calificar</span>
        </a>

        <div class="sidebar-section-title mt-3">Cobros</div>
        <a href="/juez/cobros" class="sidebar-link <?= str_starts_with(uri_string(), 'juez/cobros') && ! str_starts_with(uri_string(), 'juez/cobros/corte') ? 'active' : '' ?>">
            <i class="bi bi-cash-coin me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Listado de cobros</span>
        </a>
        <a href="/juez/cobros/corte" class="sidebar-link <?= str_starts_with(uri_string(), 'juez/cobros/corte') ? 'active' : '' ?>">
            <i class="bi bi-calculator me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Corte de caja</span>
        </a>

        <div class="sidebar-section-title mt-3">Próximas funciones</div>
        <div class="sidebar-link text-white-50" aria-disabled="true">
            <i class="bi bi-file-earmark-minus me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Condonaciones · pendiente</span>
        </div>

        <div class="sidebar-section-title mt-3">Sesión</div>
        <div class="px-3 py-2 text-white-50 small sidebar-user-info">
            <div class="fw-semibold text-white text-truncate sidebar-text"><?= esc(session('nombre_completo') ?? session('username') ?? 'Usuario') ?></div>
        </div>
        <a href="/auth/logout" class="sidebar-link text-danger mt-2">
            <i class="bi bi-box-arrow-right me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Cerrar sesión</span>
        </a>
    </aside>

    <div class="main-admin">
        <header class="topbar-admin">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm sidebar-toggle-btn" id="btnSidebarToggle" aria-label="Abrir menú" aria-controls="sidebarAdmin" aria-expanded="false">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="h5 mb-0 text-truncate"><?= $this->renderSection('pageTitle') ?></h1>
            </div>
            <a href="/auth/logout" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Salir</a>
        </header>
        <?= $this->renderSection('content') ?>
    </div>
</div>

<nav class="admin-bottom-bar" aria-label="Navegación rápida del juez">
    <a href="/juez" class="admin-bottom-item <?= uri_string() === 'juez' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2" aria-hidden="true"></i><span>Inicio</span>
    </a>
    <a href="/juez/boletas" class="admin-bottom-item <?= str_starts_with(uri_string(), 'juez/boletas') ? 'active' : '' ?>">
        <i class="bi bi-receipt-cutoff" aria-hidden="true"></i><span>Boletas</span>
    </a>
    <a href="/juez/cobros" class="admin-bottom-item <?= str_starts_with(uri_string(), 'juez/cobros') ? 'active' : '' ?>">
        <i class="bi bi-cash-coin" aria-hidden="true"></i><span>Cobros</span>
    </a>
    <button type="button" class="admin-bottom-item" id="btnBottomMenu" aria-controls="sidebarAdmin" aria-expanded="false">
        <i class="bi bi-list" aria-hidden="true"></i><span>Menú</span>
    </button>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebarAdmin');
    const backdrop = document.getElementById('sidebarBackdrop');
    const buttons = [document.getElementById('btnSidebarToggle'), document.getElementById('btnBottomMenu')];
    const close = function () {
        sidebar && sidebar.classList.remove('show');
        backdrop && backdrop.classList.remove('show');
        document.body.classList.remove('sidebar-open-no-scroll');
        buttons.forEach(function (button) { button && button.setAttribute('aria-expanded', 'false'); });
    };
    const toggle = function () {
        const open = sidebar && sidebar.classList.toggle('show');
        backdrop && backdrop.classList.toggle('show', Boolean(open));
        document.body.classList.toggle('sidebar-open-no-scroll', Boolean(open));
        buttons.forEach(function (button) { button && button.setAttribute('aria-expanded', String(Boolean(open))); });
    };
    buttons.forEach(function (button) { button && button.addEventListener('click', toggle); });
    document.getElementById('btnCloseSidebar')?.addEventListener('click', close);
    backdrop?.addEventListener('click', close);
});
</script>
</body>
</html>
