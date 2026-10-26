<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#111827">
<?php
// renderSection() consume la seccion la primera vez que la lee. Se pide aqui con
// $saveData = true para no dejar vacio el <h1> de la barra superior, que la
// reutiliza mas abajo.
$__tituloPagina = trim($this->renderSection('pageTitle', true));
?>
    <title><?= $__tituloPagina !== '' ? esc($__tituloPagina) . ' - Panel Admin' : 'Panel Administrativo - Ventanilla Uriangato' ?></title>
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Mobile-First Stylesheet -->
    <link rel="icon" type="image/webp" href="/logo-uri.webp">
    <link rel="apple-touch-icon" href="/logo-uri.webp">
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="has-admin-bottom-nav">

<div class="admin-wrapper">
    <!-- Mobile Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>

    <!-- Sidebar Navigation -->
    <aside class="sidebar-admin" id="sidebarAdmin" aria-label="Navegación principal" role="navigation">
        <div class="sidebar-brand">
            <div class="d-flex align-items-center sidebar-brand-content">
                <img src="<?= base_url('logo-uri.webp') ?>" alt="Logo Uriangato" class="me-2 sidebar-logo" style="height: 32px;">
                <span class="sidebar-text">Panel Admin</span>
            </div>
            <button type="button" class="btn-close btn-close-white d-md-none" id="btnCloseSidebar" aria-label="Cerrar menú"></button>
        </div>

        <?php
            $qTramite = $_GET['tramite'] ?? '';
            $isAllSolicitudes = uri_string() === 'admin/solicitudes' && $qTramite === '';

            // Contexto de rol: separa con claridad Administrador vs Ventanilla.
            $rolesUsuario = (array) (session('roles') ?? []);
            $esAdministrador = in_array('administrador', $rolesUsuario, true);
            $esVentanilla = in_array('operador_ventanilla', $rolesUsuario, true);
            if ($esAdministrador) {
                $rolChip = 'rol-admin';
                $rolNombre = 'Administrador';
                $rolAlcance = 'Acceso completo: operación, catálogos y configuración.';
            } elseif ($esVentanilla) {
                $rolChip = 'rol-ventanilla';
                $rolNombre = 'Ventanilla';
                $rolAlcance = 'Operación diaria según los permisos que te otorgó el administrador.';
            } else {
                $rolChip = 'rol-ciudadano';
                $rolNombre = implode(', ', $rolesUsuario) ?: 'Usuario';
                $rolAlcance = 'Sesión autenticada del panel.';
            }

            // Permisos del operador (los define el administrador en su pantalla).
            $permisosOp = \App\Libraries\PermisosOperador::todos();
            $permitido = static function (string $clave) use ($esAdministrador, $esVentanilla, $permisosOp): bool {
                if ($esAdministrador || !$esVentanilla) {
                    return true;
                }
                return $permisosOp[$clave] ?? true;
            };
            $hayAppTransito = $permitido('app_transito_config') || $permitido('app_transito_boletas') || $permitido('app_transito_catalogo') || $permitido('app_transito_sesiones');
            $hayCatalogos = $permitido('tarifas') || $permitido('concesiones') || $permitido('formatos') || $permitido('auditoria') || $permitido('documentos') || $permitido('verificaciones') || $permitido('convocatorias');
        ?>

        <div class="sidebar-alcance">
            <span class="alcance-nombre rol-chip <?= $rolChip ?>"><?= esc($rolNombre) ?></span>
            <p class="alcance-detalle"><?= esc($rolAlcance) ?></p>
        </div>

        <div class="sidebar-section-title">Operaciones</div>
        <a href="/admin/dashboard" class="sidebar-link <?= uri_string() === 'admin/dashboard' || uri_string() === 'admin' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Inicio</span>
        </a>
        <?php if ($esAdministrador): ?>
        <a href="/admin/desglose" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/desglose') ? 'active' : '' ?>">
            <i class="bi bi-graph-up-arrow me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Desglose del sistema</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('solicitudes')): ?>
        <a href="/admin/solicitudes" class="sidebar-link <?= $isAllSolicitudes ? 'active' : '' ?>">
            <i class="bi bi-folder2-open me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Todas las solicitudes</span>
        </a>
        <a href="/admin/solicitudes/nueva" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/solicitudes/nueva') ? 'active' : '' ?>">
            <i class="bi bi-plus-circle me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Nueva solicitud presencial</span>
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-01" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-01' ? 'active' : '' ?>">
            <i class="bi bi-award me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">T-01: Concesiones</span>
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-02" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-02' ? 'active' : '' ?>">
            <i class="bi bi-paint-bucket me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">T-02: Despintado</span>
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-03" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-03' ? 'active' : '' ?>">
            <i class="bi bi-card-heading me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">T-03: Plaqueo</span>
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-04" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-04' ? 'active' : '' ?>">
            <i class="bi bi-bus-front me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">T-04: P. Eventual</span>
        </a>
        <a href="/admin/solicitudes?tramite=UR-TT-T-05" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-05' ? 'active' : '' ?>">
            <i class="bi bi-sign-stop me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">T-05: Cierre Calle</span>
        </a>
        <?php if (getenv('APP_ENABLE_UR_TT_T_06') === 'true'): ?>
            <a href="/admin/solicitudes?tramite=UR-TT-T-06" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-06' ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right me-2 sidebar-link-icon"></i>
                <span class="sidebar-text">T-06: Cesión</span>
            </a>
        <?php endif; ?>
        <a href="/admin/solicitudes?tramite=UR-TT-T-07" class="sidebar-link ms-2 small <?= $qTramite === 'UR-TT-T-07' ? 'active' : '' ?>">
            <i class="bi bi-truck me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">T-07: Carga/Descarga</span>
        </a>
        <?php endif; ?>

        <?php if ($permitido('cobros')): ?>
        <a href="/admin/cobros" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/cobros') && ! str_starts_with(uri_string(), 'admin/cobros/corte') ? 'active' : '' ?>">
            <i class="bi bi-cash-stack me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Cobros</span>
        </a>
        <a href="/admin/cobros/corte" class="sidebar-link ms-2 small <?= str_starts_with(uri_string(), 'admin/cobros/corte') ? 'active' : '' ?>">
            <i class="bi bi-calculator me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Corte de caja</span>
        </a>
        <?php endif; ?>

        <?php if ($permitido('usuarios')): ?>
        <div class="sidebar-section-title mt-3">Ventanilla</div>
        <a href="/admin/usuarios" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/usuarios') ? 'active' : '' ?>">
            <i class="bi bi-people me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Usuarios y ciudadanos</span>
        </a>
        <?php endif; ?>

        <?php if ($hayCatalogos): ?>
        <div class="sidebar-section-title mt-3">Catálogos</div>
        <?php if ($permitido('tarifas')): ?>
        <a href="/admin/tarifas" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/tarifas') ? 'active' : '' ?>">
            <i class="bi bi-cash-coin me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Tarifario</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('concesiones')): ?>
        <a href="/admin/concesiones" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/concesiones') ? 'active' : '' ?>">
            <i class="bi bi-card-list me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Concesiones</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('formatos')): ?>
        <a href="/admin/formatos" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/formatos') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-richtext me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Formatos de trámite</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('auditoria')): ?>
        <a href="/admin/auditoria" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/auditoria') ? 'active' : '' ?>">
            <i class="bi bi-shield-check me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Auditoría</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('documentos')): ?>
        <a href="/admin/documentos" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/documentos') ? 'active' : '' ?>">
            <i class="bi bi-file-earmark me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Documentos recibidos</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('verificaciones')): ?>
        <a href="/admin/verificaciones" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/verificaciones') ? 'active' : '' ?>">
            <i class="bi bi-clipboard2-check me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Verificaciones físicas</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('convocatorias')): ?>
        <a href="/admin/convocatorias" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/convocatorias') ? 'active' : '' ?>">
            <i class="bi bi-award-fill me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Convocatorias UR-01</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($hayAppTransito): ?>
        <div class="sidebar-section-title mt-3">App Tránsito</div>
        <a href="/agentes" target="_blank" rel="noopener" class="sidebar-link">
            <i class="bi bi-phone me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Abrir app de campo</span>
        </a>
        <?php if ($permitido('app_transito_config')): ?>
        <a href="/admin/app-transito" class="sidebar-link <?= uri_string() === 'admin/app-transito' ? 'active' : '' ?>">
            <i class="bi bi-sliders me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Configuración & UMA</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('app_transito_boletas')): ?>
        <a href="/admin/app-transito/boletas" class="sidebar-link <?= uri_string() === 'admin/app-transito/boletas' ? 'active' : '' ?>">
            <i class="bi bi-receipt-cutoff me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Boletas de Campo</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('app_transito_catalogo')): ?>
        <a href="/admin/app-transito/catalogo" class="sidebar-link <?= uri_string() === 'admin/app-transito/catalogo' ? 'active' : '' ?>">
            <i class="bi bi-journal-bookmark me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Catálogo de Faltas</span>
        </a>
        <?php endif; ?>
        <?php if ($permitido('app_transito_sesiones')): ?>
        <a href="/admin/sesiones-app" class="sidebar-link <?= uri_string() === 'admin/sesiones-app' ? 'active' : '' ?>">
            <i class="bi bi-key me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Sesiones de la app</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($esAdministrador): ?>
        <div class="sidebar-section-title mt-3">Seguridad</div>
        <a href="/admin/permisos-operador" class="sidebar-link <?= str_starts_with(uri_string(), 'admin/permisos-operador') ? 'active' : '' ?>">
            <i class="bi bi-shield-lock me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Permisos del Operador</span>
        </a>
        <?php endif; ?>

        <div class="sidebar-section-title mt-3">Sesión</div>
        <div class="px-3 py-2 text-white-50 small sidebar-user-info">
            <div class="fw-semibold text-white text-truncate sidebar-text"><?= esc(session('nombre_completo') ?? session('username') ?? 'Usuario') ?></div>
            <div class="mt-1 sidebar-text"><span class="rol-chip <?= $rolChip ?>"><?= esc($rolNombre) ?></span></div>
        </div>
        <a href="/auth/logout" class="sidebar-link text-danger mt-2">
            <i class="bi bi-box-arrow-right me-2 sidebar-link-icon"></i>
            <span class="sidebar-text">Cerrar sesión</span>
        </a>
    </aside>

    <!-- Main Content Area -->
    <div class="main-admin">
        <header class="topbar-admin">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm sidebar-toggle-btn" id="btnSidebarToggle" aria-label="Abrir menú" aria-controls="sidebarAdmin" aria-expanded="false">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="h5 mb-0 text-truncate" title="<?= esc($__tituloPagina) ?>"><?= $this->renderSection('pageTitle') ?></h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="rol-chip <?= $rolChip ?> d-none d-sm-inline-flex" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= esc($rolAlcance) ?>">
                    <?= esc($rolNombre) ?>
                </span>
                <button type="button" class="btn-ayuda-rol" data-bs-toggle="tooltip" data-bs-placement="bottom"
                        title="Panel de <?= esc($rolNombre) ?>. Pasa el cursor sobre los iconos &#63; para ver ayuda de cada sección.">
                    <i class="bi bi-question-lg" aria-hidden="true"></i>
                    <span class="visually-hidden">Ayuda</span>
                </button>
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

        <?php if (session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="bi bi-x-circle-fill fs-5 me-2 text-danger"></i>
                <div><?= esc((string) session('error')) ?></div>
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

<?php
// Navegación rápida del panel en teléfono: el cajón lateral obliga a abrir y
// cerrar, así que las cuatro rutas del día a día se quedan siempre visibles.
$uriActual     = uri_string();
$esNuevaSol    = str_starts_with($uriActual, 'admin/solicitudes/nueva');
$activarInicio = $uriActual === 'admin/dashboard' || $uriActual === 'admin';
$activarLista  = str_starts_with($uriActual, 'admin/solicitudes') && ! $esNuevaSol;
$activarDesglose = str_starts_with($uriActual, 'admin/desglose');
?>
<nav class="admin-bottom-bar" aria-label="Navegación rápida del panel">
    <a href="/admin/dashboard" class="admin-bottom-item <?= $activarInicio ? 'active' : '' ?>" <?= $activarInicio ? 'aria-current="page"' : '' ?>>
        <i class="bi bi-speedometer2" aria-hidden="true"></i>
        <span>Inicio</span>
    </a>
    <?php if ($permitido('solicitudes')): ?>
    <a href="/admin/solicitudes" class="admin-bottom-item <?= $activarLista ? 'active' : '' ?>" <?= $activarLista ? 'aria-current="page"' : '' ?>>
        <i class="bi bi-folder2-open" aria-hidden="true"></i>
        <span>Solicitudes</span>
    </a>
    <a href="/admin/solicitudes/nueva" class="admin-bottom-item admin-bottom-item--cta <?= $esNuevaSol ? 'active' : '' ?>" <?= $esNuevaSol ? 'aria-current="page"' : '' ?>>
        <i class="bi bi-plus-lg" aria-hidden="true"></i>
        <span>Nueva</span>
    </a>
    <?php endif; ?>
    <?php if ($esAdministrador): ?>
    <a href="/admin/desglose" class="admin-bottom-item <?= $activarDesglose ? 'active' : '' ?>" <?= $activarDesglose ? 'aria-current="page"' : '' ?>>
        <i class="bi bi-graph-up-arrow" aria-hidden="true"></i>
        <span>Desglose</span>
    </a>
    <?php endif; ?>
    <button type="button" class="admin-bottom-item" id="btnBottomMenu" aria-controls="sidebarAdmin" aria-expanded="false" aria-label="Abrir menú completo">
        <i class="bi bi-list" aria-hidden="true"></i>
        <span>Menú</span>
    </button>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/formularios.js"></script>
<!-- UI base: tooltips globales + helper de copiar al portapapeles -->
<script>
(function () {
    function initTooltips(root) {
        if (!window.bootstrap || !bootstrap.Tooltip) return;
        var scope = root || document;
        scope.querySelectorAll('[data-bs-toggle="tooltip"], [data-bs-toggle="popover"]').forEach(function (el) {
            if (el.dataset.tooltipInitialized === '1') return;
            new bootstrap.Tooltip(el, { container: 'body', trigger: 'hover focus' });
            el.dataset.tooltipInitialized = '1';
        });
    }
    window.iniciarTooltips = initTooltips;

    window.copiarTexto = function (texto, btn) {
        var finalizar = function (ok) {
            if (!btn) return;
            var original = btn.innerHTML;
            btn.classList.add('copied');
            btn.innerHTML = ok
                ? '<i class="bi bi-check2" aria-hidden="true"></i> Copiado'
                : '<i class="bi bi-x-lg" aria-hidden="true"></i> Error';
            setTimeout(function () {
                btn.classList.remove('copied');
                btn.innerHTML = original;
            }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto).then(function () { finalizar(true); }, function () { finalizar(false); });
        } else {
            var ta = document.createElement('textarea');
            ta.value = texto; document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); finalizar(true); } catch (e) { finalizar(false); }
            document.body.removeChild(ta);
        }
    };

    document.addEventListener('DOMContentLoaded', function () { initTooltips(document); });
    // Re-inicializar en contenido inyectado dinámicamente
    var MO = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            m.addedNodes && m.addedNodes.forEach && m.addedNodes.forEach(function (n) {
                if (n && n.nodeType === 1) initTooltips(n);
            });
        });
    });
    document.addEventListener('DOMContentLoaded', function () { MO.observe(document.body, { childList: true, subtree: true }); });
})();
</script>
<!-- Feedback de carga: spinner en botones de submit -->
<script>
document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.noLoading) return;
    var btn = form.querySelector('button[type="submit"]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    btn.dataset.originalHtml = btn.innerHTML;
    var label = btn.dataset.loadingText || 'Procesando...';
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' + label;
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarAdmin');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('btnSidebarToggle');
    const closeBtn = document.getElementById('btnCloseSidebar');
    // El botón "Menú" de la barra inferior abre el mismo cajón que el topbar.
    const bottomMenuBtn = document.getElementById('btnBottomMenu');
    const body = document.body;

    var MOBILE_BREAKPOINT = 991.98;
    var MD_BREAKPOINT = 767.98;
    var STORAGE_KEY_COLLAPSED = 'admin-sidebar-collapsed';

    function isMobile() {
        return window.matchMedia('(max-width: ' + MOBILE_BREAKPOINT + 'px)').matches;
    }

    function isTabletOnly() {
        return window.matchMedia('(min-width: ' + (MD_BREAKPOINT + 0.02) + 'px) and (max-width: ' + MOBILE_BREAKPOINT + 'px)').matches;
    }

    function getStoredCollapsed() {
        try { return localStorage.getItem(STORAGE_KEY_COLLAPSED) === 'true'; }
        catch (e) { return false; }
    }

    function setStoredCollapsed(value) {
        try { localStorage.setItem(STORAGE_KEY_COLLAPSED, String(value)); }
        catch (e) {}
    }

    function setAriaExpanded(value) {
        if (toggleBtn) {
            toggleBtn.setAttribute('aria-expanded', String(value));
            toggleBtn.setAttribute('aria-label', value ? 'Cerrar menú' : 'Abrir menú');
        }
        // Las dos puertas del mismo cajón deben decir lo mismo para quien navega
        // con lector de pantalla o sin vista.
        if (bottomMenuBtn) {
            bottomMenuBtn.setAttribute('aria-expanded', String(value));
            bottomMenuBtn.setAttribute('aria-label', value ? 'Cerrar menú completo' : 'Abrir menú completo');
        }
    }

    function openSidebarMobile() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) {
            backdrop.classList.add('show');
            backdrop.setAttribute('aria-hidden', 'false');
        }
        body.classList.add('sidebar-open-no-scroll');
        setAriaExpanded(true);
    }

    function closeSidebarMobile() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.setAttribute('aria-hidden', 'true');
        }
        body.classList.remove('sidebar-open-no-scroll');
        setAriaExpanded(false);
    }

    function toggleCollapseDesktop() {
        if (!sidebar) return;
        var isCollapsed = sidebar.classList.toggle('collapsed');
        setStoredCollapsed(isCollapsed);
        setAriaExpanded(!isCollapsed);
    }

    function handleToggleBtnClick() {
        if (window.matchMedia('(max-width: ' + MD_BREAKPOINT + 'px)').matches) {
            var isOpen = sidebar ? sidebar.classList.contains('show') : false;
            if (isOpen) {
                closeSidebarMobile();
            } else {
                openSidebarMobile();
            }
        } else {
            toggleCollapseDesktop();
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', handleToggleBtnClick);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebarMobile);
    if (backdrop) backdrop.addEventListener('click', closeSidebarMobile);

    // El botón "Menú" de la barra inferior usa el mismo cajón que el topbar.
    if (bottomMenuBtn) {
        bottomMenuBtn.addEventListener('click', function () {
            var abierto = sidebar ? sidebar.classList.contains('show') : false;
            if (abierto) {
                closeSidebarMobile();
            } else {
                openSidebarMobile();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.matchMedia('(max-width: ' + MD_BREAKPOINT + 'px)').matches) {
            closeSidebarMobile();
        }
    });

    function applyInitialState() {
        var storedCollapsed = getStoredCollapsed();

        if (window.matchMedia('(max-width: ' + MD_BREAKPOINT + 'px)').matches) {
            if (sidebar) sidebar.classList.remove('collapsed');
            closeSidebarMobile();
        } else {
            if (sidebar) {
                if (storedCollapsed && !isTabletOnly()) {
                    sidebar.classList.add('collapsed');
                } else {
                    sidebar.classList.remove('collapsed');
                }
            }
            setAriaExpanded(!storedCollapsed || isTabletOnly());
        }
    }

    applyInitialState();

    var resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            if (!window.matchMedia('(max-width: ' + MD_BREAKPOINT + 'px)').matches) {
                closeSidebarMobile();
            } else {
                if (sidebar) sidebar.classList.remove('collapsed');
            }
        }, 150);
    });
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>


