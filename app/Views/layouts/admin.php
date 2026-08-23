<!DOCTYPE html><html lang="es"><head>
<title>Panel Administrativo - Ventanilla Uriangato</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body{background:#f0f4f8;font-size:.9rem;}
.sidebar-admin{background:#1a2332;color:#ecf0f1;min-height:100vh;position:fixed;top:0;left:0;width:250px;z-index:100;}
.sidebar-admin .sidebar-brand{padding:1.2rem 1rem;font-weight:bold;border-bottom:1px solid #34495e;font-size:1rem;}
.sidebar-admin .sidebar-link{color:#bdc3c7;display:block;padding:.75rem 1.1rem;text-decoration:none;border-left:3px solid transparent;}
.sidebar-admin .sidebar-link:hover{background:#2c3e50;color:white;border-left-color:#3498db;}
.sidebar-admin .sidebar-link.active{background:#2c3e50;color:white;border-left-color:#2ecc71;}
.sidebar-admin .sidebar-section-title{padding:1rem 1rem .3rem;font-size:.7rem;color:#95a5a6;text-transform:uppercase;letter-spacing:.05em;}
.main-admin{margin-left:250px;padding:1rem 1.5rem;}
.topbar-admin{background:white;padding:.7rem 1rem;border-bottom:1px solid #dee2e6;margin-bottom:1rem;border-radius:.375rem;display:flex;justify-content:space-between;align-items:center;}
.badge-estatus{font-size:.8rem;}
.table-sm th{background:#eaf2fb;}
</style>
</head><body>
<aside class="sidebar-admin">
<div class="sidebar-brand text-white"><i class="bi bi-shield-check me-2"></i>Panel Admin</div>
<div class="sidebar-section-title">Operaciones</div>
<a href="/admin/dashboard" class="sidebar-link <?= active('admin/dashboard') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
<a href="/admin/solicitudes" class="sidebar-link"><i class="bi bi-folder2-open me-2"></i> Solicitudes</a>
<a href="/admin/solicitudes?tramite=UR-TT-T-07" class="sidebar-link ms-2 small"><i class="bi bi-truck me-2"></i> T-07: Carga/Descarga</a>
<?= (getenv('APP_ENABLE_UR_TT_T_06') === 'true') ? '<a href="/admin/solicitudes?tramite=UR-TT-T-06" class="sidebar-link ms-2 small"><i class="bi bi-arrow-left-right me-2"></i> T-06: Cesión</a>' : '' ?>
<div class="sidebar-section-title mt-3">Catálogos</div>
<a href="/admin/tarifas" class="sidebar-link"><i class="bi bi-cash-coin me-2"></i> Tarifario</a>
<a href="/admin/concesiones" class="sidebar-link"><i class="bi bi-card-list me-2"></i> Concesiones (stub)</a>
<div class="sidebar-section-title mt-3">Sesión</div>
<div class="sidebar-link disabled"><?= esc(session('nombre_completo') ?? 'Usuario') ?></div>
<a href="/auth/logout" class="sidebar-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión</a>
</aside>
<div class="main-admin">
<div class="topbar-admin"><div><h5 class="mb-0"><?= $this->renderSection('pageTitle') ?></h5></div><div><small><?= implode(', ', session('roles')??[]) ?> · <span class="text-muted"><?= date('d/m/Y H:i') ?></span></small></div></div>
<?php if(session('message')): ?><div class="alert alert-info mb-3"><i class="bi bi-info-circle me-2"></i><?= esc(session('message')) ?></div><?php endif; ?>
<?php if(session('errors')): ?><div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i><ul class="mb-0"><?php foreach(session('errors') as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?= $this->renderSection('content') ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body></html>

