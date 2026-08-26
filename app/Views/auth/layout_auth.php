<?php declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <title><?= $this->renderSection('title') ?: 'Acceso - Ventanilla Digital Uriangato' ?></title>
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom Mobile-First Stylesheet -->
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="text-center mb-4">
            <img src="/logo-uri.webp" alt="Logo Uriangato" style="height: 72px;">
        </div>
        <div class="auth-card">
            <?= $this->renderSection('content') ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

