<?php declare(strict_types=1); ?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('title') ?>Restablecer Contraseña - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="auth-header">
    <div class="auth-brand-icon bg-info" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
        <i class="bi bi-shield-lock"></i>
    </div>
    <h2 class="h4 mb-1 fw-bold">Restablecer Contraseña</h2>
    <p class="text-muted small mb-0">Ingresa tu nueva contraseña</p>
</div>

<div class="auth-body">
    <?php if ($user === null): ?>
        <div class="alert alert-danger alert-dismissible fade show p-3 small" role="alert">
            <div class="d-flex align-items-center mb-2">
                <i class="bi bi-x-circle-fill me-2 fs-5 text-danger"></i>
                <strong>Enlace no válido</strong>
            </div>
            <p class="mb-0">El token es inválido o ha expirado. Por favor solicita un nuevo enlace de recuperación.</p>
        </div>
        <div class="d-grid mt-3">
            <a href="/auth/forgot" class="btn btn-primary">Solicitar nuevo enlace</a>
        </div>
    <?php else: ?>
        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show p-2 mb-3 small" role="alert">
                <?php foreach (session('errors') as $error): ?>
                    <div><?= esc($error) ?></div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?= form_open('/auth/reset/' . esc($token)) ?>
            <input type="hidden" name="token" value="<?= esc($token) ?>">
            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Nueva contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 8 caracteres" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password_confirm" class="form-label small fw-semibold">Confirmar nueva contraseña</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite tu contraseña" required>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-info btn-lg shadow-sm text-white fw-bold">
                    <i class="bi bi-check-lg me-2"></i>Actualizar contraseña
                </button>
            </div>
        <?= form_close() ?>
    <?php endif ?>

    <div class="mt-4 pt-3 border-top text-center small">
        <a href="/auth/login" class="text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Volver al inicio de sesión
        </a>
    </div>
</div>

<?= $this->endSection() ?>

