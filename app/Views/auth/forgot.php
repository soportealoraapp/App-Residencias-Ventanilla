<?php declare(strict_types=1); ?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('title') ?>Recuperar Contraseña - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="auth-header">
    <div class="auth-brand-icon bg-warning" style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%);">
        <i class="bi bi-key"></i>
    </div>
    <h2 class="h4 mb-1 fw-bold">Recuperar Contraseña</h2>
    <p class="text-muted small mb-0">Ingresa tu correo registrado</p>
</div>

<div class="auth-body">
    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show p-2 mb-3 small" role="alert">
            <?php foreach (session('errors') as $error): ?>
                <div><?= esc($error) ?></div>
            <?php endforeach ?>
        </div>
    <?php endif ?>

    <?php if (session()->has('message')): ?>
        <div class="alert alert-info alert-dismissible fade show p-2 mb-3 small" role="alert">
            <i class="bi bi-info-circle-fill me-1"></i> <?= esc(session('message')) ?>
        </div>
    <?php endif ?>

    <?= form_open('/auth/attempt-forgot') ?>
        <div class="mb-4">
            <label for="email" class="form-label small fw-semibold">Correo electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="correo@ejemplo.com" required autofocus>
            </div>
            <div class="form-text small">Te enviaremos las instrucciones de recuperación.</div>
        </div>
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-warning btn-lg shadow-sm text-dark fw-bold">
                <i class="bi bi-send me-2"></i>Enviar enlace
            </button>
        </div>
    <?= form_close() ?>

    <div class="mt-4 pt-3 border-top text-center small">
        <a href="/auth/login" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
        </a>
    </div>
</div>

<?= $this->endSection() ?>

