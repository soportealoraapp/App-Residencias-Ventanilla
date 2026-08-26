<?php declare(strict_types=1); ?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('title') ?>Crear Cuenta - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="auth-header">
    <div class="auth-brand-icon bg-success" style="background: linear-gradient(135deg, #0e9f6e 0%, #059669 100%);">
        <i class="bi bi-person-plus"></i>
    </div>
    <h2 class="h4 mb-1 fw-bold">Crear Cuenta</h2>
    <p class="text-muted small mb-0">Registro de Ciudadano · Uriangato, Gto.</p>
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

    <?= form_open('/auth/attempt-register') ?>
        <div class="mb-3">
            <label for="nombre_completo" class="form-label small fw-semibold">Nombre completo <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-person-vcard"></i></span>
                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?= old('nombre_completo') ?>" placeholder="Nombre(s) y apellidos" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="correo@ejemplo.com" required>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-sm-7">
                <label for="username" class="form-label small fw-semibold">Usuario <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" placeholder="Mínimo 5 letras/números" required>
            </div>
            <div class="col-sm-5">
                <label for="rfc" class="form-label small fw-semibold">RFC <span class="text-muted">(opcional)</span></label>
                <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" value="<?= old('rfc') ?>" placeholder="XAXX010101000" maxlength="13">
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-sm-6">
                <label for="password" class="form-label small fw-semibold">Contraseña <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
            </div>
            <div class="col-sm-6">
                <label for="password_confirm" class="form-label small fw-semibold">Confirmar <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite contraseña" required>
            </div>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button type="submit" class="btn btn-success btn-lg shadow-sm" style="background-color: #0e9f6e; border-color: #0e9f6e;">
                <i class="bi bi-person-check me-2"></i>Registrarme
            </button>
        </div>
    <?= form_close() ?>

    <div class="mt-4 pt-3 border-top text-center small">
        <p class="mb-2 text-muted">
            ¿Ya tienes cuenta? <a href="/auth/login" class="fw-semibold text-success text-decoration-none">Inicia sesión</a>
        </p>
        <p class="mb-0">
            <a href="/" class="text-muted text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Volver al inicio</a>
        </p>
    </div>
</div>

<?= $this->endSection() ?>

