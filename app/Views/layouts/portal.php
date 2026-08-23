<!DOCTYPE html><html lang="es"><head>
<title>Ventanilla Digital de Movilidad - Uriangato</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{background-color:#f8f9fa;}.navbar-uriangato{background:#0d47a1;}.navbar-brand{font-weight:bold;color:white !important;}.footer-ciudadano{background:#1976d2;color:white;padding:2rem 0;margin-top:3rem;} .tramite-card{transition: transform .2s;} .tramite-card:hover{transform: translateY(-4px);box-shadow:0 8px 20px rgba(0,0,0,.1);} .badge-placeholder{background:#ff9800;}</style>
</head><body>
<nav class="navbar navbar-expand-md navbar-uriangato mb-4"><div class="container">
<a class="navbar-brand" href="/portal"><i class="bi bi-bank2 me-2"></i>Ventanilla Digital - Uriangato</a>
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navPortal"><span class="navbar-toggler-icon"></span></button>
<div id="navPortal" class="collapse navbar-collapse"><ul class="navbar-nav ms-auto">
<li class="nav-item"><a class="nav-link text-white" href="/portal/tramites"><i class="bi bi-clipboard-check me-1"></i> Trámites</a></li>
<li class="nav-item"><a class="nav-link text-white" href="/portal/mis-solicitudes"><i class="bi bi-journal-text me-1"></i> Mis solicitudes</a></li>
<?php if(session('user_id')): ?>
<li class="nav-item dropdown">
<a class="nav-link text-white dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="bi bi-person-circle me-1"></i> <?= esc(session('nombre_completo')) ?></a>
<ul class="dropdown-menu dropdown-menu-end">
<li><span class="dropdown-item-text small">Roles: <?= implode(', ', session('roles')??[]) ?></span></li>
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item" href="/auth/logout"><i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión</a></li>
</ul></li>
<?php else: ?>
<li class="nav-item"><a class="nav-link text-white" href="/auth/login"><i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión</a></li>
<li class="nav-item"><a class="nav-link text-white" href="/auth/register"><i class="bi bi-person-plus me-1"></i> Registro</a></li>
<?php endif; ?>
</ul></div></div></nav>
<div class="container mb-5">
<?php if(session('message')): ?><div class="alert alert-info mb-4"><i class="bi bi-info-circle me-2"></i><?= esc(session('message')) ?></div><?php endif; ?>
<?php if(session('errors')): ?><div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle me-2"></i><ul class="mb-0"><?php foreach(session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?= $this->renderSection('content') ?>
</div>
<footer class="footer-ciudadano"><div class="container text-center"><p class="mb-1"><strong>H. Ayuntamiento de Uriangato, Gto.</strong></p><p class="mb-1 small">Dirección de Movilidad y Transporte - Ventanilla Digital</p><p class="mb-0 small">&copy; <?= date('Y') ?> - Todos los derechos reservados</p></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
