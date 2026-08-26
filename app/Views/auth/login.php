<?php declare(strict_types=1); ?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('title') ?>Iniciar Sesión - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="auth-header">
    <div class="auth-brand-icon">
        <i class="bi bi-bank2"></i>
    </div>
    <h2 class="h4 mb-1 fw-bold">Iniciar Sesión</h2>
    <p class="text-muted small mb-0">Ventanilla Digital · Uriangato, Gto.</p>
</div>

<div class="auth-body">
    <?php if (session()->has('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show p-2 mb-3 small" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-circle-fill me-2 text-danger fs-5"></i>
                <div>
                    <?php foreach (session('errors') as $error): ?>
                        <div><?= esc($error) ?></div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if (session()->has('message')): ?>
        <div class="alert alert-success alert-dismissible fade show p-2 mb-3 small" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 text-success fs-5"></i>
                <div><?= esc(session('message')) ?></div>
            </div>
        </div>
    <?php endif ?>

    <?= form_open('/auth/attempt-login') ?>
        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold">Usuario o Correo</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" placeholder="admin o tu correo" required autofocus>
            </div>
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <label for="password" class="form-label small fw-semibold mb-0">Contraseña</label>
                <a href="/auth/forgot" class="small text-decoration-none text-muted">¿La olvidaste?</a>
            </div>
            <div class="input-group mt-1">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="Tu contraseña" required>
            </div>
        </div>
        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2"></i>Entrar al portal
            </button>
        </div>
    <?= form_close() ?>

    <div class="mt-4 pt-3 border-top text-center small">
        <p class="mb-2 text-muted">
            ¿No tienes cuenta? <a href="/auth/register" class="fw-semibold text-primary text-decoration-none">Regístrate aquí</a>
        </p>
        <p class="mb-0">
            <a href="/" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a>
        </p>
    </div>
</div>

<?= $this->endSection() ?>

