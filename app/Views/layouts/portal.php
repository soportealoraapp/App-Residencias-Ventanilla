<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0d47a1">
    <title><?= $this->renderSection('title') ?: 'Ventanilla Digital de Movilidad - Uriangato' ?></title>
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Mobile-First Stylesheet -->
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body class="<?= session('user_id') ? 'has-mobile-bottom-nav' : '' ?>">

<nav class="navbar navbar-expand-md navbar-uriangato">
    <div class="container">
        <a class="navbar-brand" href="/portal">
            <img src="/logo-uri.webp" alt="Logo Uriangato" class="d-inline-block align-text-top me-2" style="height: 38px;">
            <span>Ventanilla Digital</span>
        </a>
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navPortal" aria-controls="navPortal" aria-expanded="false" aria-label="Abrir menú">
            <i class="bi bi-list fs-2"></i>
        </button>
        <div id="navPortal" class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-md-center">
                <li class="nav-item">
                    <a class="nav-link" href="/portal/dashboard">
                        <i class="bi bi-speedometer2 me-1"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/portal/tramites">
                        <i class="bi bi-clipboard-check me-1"></i> Trámites
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/portal/mis-solicitudes">
                        <i class="bi bi-journal-text me-1"></i> Mis solicitudes
                    </a>
                </li>
                <?php if (session('user_id')): ?>
                    <li class="nav-item dropdown ms-md-2 mt-2 mt-md-0">
                        <a class="nav-link dropdown-toggle btn btn-sm btn-outline-light text-white text-start d-flex align-items-center" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                            <i class="bi bi-person-circle fs-5 me-2"></i>
                            <span class="text-truncate" style="max-width: 160px;"><?= esc(session('nombre_completo') ?? session('username')) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header"><i class="bi bi-person-badge me-1"></i> <?= esc(session('username')) ?></h6></li>
                            <li><span class="dropdown-item-text small text-muted">Rol: <?= implode(', ', session('roles') ?? []) ?></span></li>
                            <?php 
                                $esAdmin = false;
                                foreach ((array)session('roles') as $r) {
                                    if (str_contains($r, 'admin') || str_contains($r, 'operador')) { $esAdmin = true; break; }
                                }
                                if ($esAdmin):
                            ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-primary fw-semibold" href="/admin/dashboard"><i class="bi bi-shield-check me-2"></i>Panel Administrador</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/auth/logout"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item mt-2 mt-md-0 ms-md-2">
                        <a class="btn btn-outline-light btn-sm w-100 mb-2 mb-md-0" href="/auth/login">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                        </a>
                    </li>
                    <li class="nav-item ms-md-2">
                        <a class="btn btn-light btn-sm w-100 text-primary fw-semibold" href="/auth/register">
                            <i class="bi bi-person-plus me-1"></i> Registrarse
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container my-3 my-md-4 flex-grow-1">
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
</main>

<?php if (session('user_id')): ?>
<!-- Mobile Bottom Navigation Bar (< 768px) -->
<nav class="mobile-bottom-bar" aria-label="Navegación móvil">
    <a href="/portal/dashboard" class="mobile-bottom-item <?= uri_string() === 'portal/dashboard' || uri_string() === 'portal' ? 'active' : '' ?>">
        <i class="bi bi-house-door"></i>
        <span>Inicio</span>
    </a>
    <a href="/portal/tramites" class="mobile-bottom-item <?= str_starts_with(uri_string(), 'portal/tramites') ? 'active' : '' ?>">
        <i class="bi bi-clipboard-check"></i>
        <span>Trámites</span>
    </a>
    <a href="/portal/mis-solicitudes" class="mobile-bottom-item <?= str_starts_with(uri_string(), 'portal/mis-solicitudes') || str_starts_with(uri_string(), 'portal/solicitud') ? 'active' : '' ?>">
        <i class="bi bi-journal-text"></i>
        <span>Solicitudes</span>
    </a>
    <a href="/auth/logout" class="mobile-bottom-item text-danger">
        <i class="bi bi-box-arrow-right"></i>
        <span>Salir</span>
    </a>
</nav>
<?php endif; ?>

<footer class="footer-ciudadano">
    <div class="container text-center">
        <div class="row align-items-center gy-3">
            <div class="col-md-6 text-md-start">
                <div class="d-flex align-items-center">
                    <img src="/logo-uri.webp" alt="Logo Uriangato" class="me-3" style="height: 48px;">
                    <div>
                        <h6 class="text-white mb-1 fw-bold">H. Ayuntamiento de Uriangato, Gto.</h6>
                        <p class="mb-0 small text-white-50">Dirección de Movilidad y Transporte · Ventanilla Digital</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0 small text-white-50">&copy; <?= date('Y') ?> Uriangato Digital · Todos los derechos reservados</p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>

