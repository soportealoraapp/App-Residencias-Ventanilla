<?php declare(strict_types=1);
?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('content') ?>

<div class="card shadow">
    <div class="card-header bg-success text-white text-center py-3">
        <h4 class="mb-0">Crear Cuenta</h4>
        <p class="mb-0 mt-1 small">Registro de Ciudadano</p>
    </div>
    <div class="card-body p-4">
        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session('errors') as $error): ?>
                    <p class="mb-1"><?= esc($error) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?= form_open('/auth/attempt-register') ?>
            <div class="mb-3">
                <label for="nombre_completo" class="form-label">Nombre completo</label>
                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?= old('nombre_completo') ?>" placeholder="Nombre(s) y apellidos" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Nombre de usuario</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" placeholder="Solo letras y números, mínimo 5 caracteres" required>
            </div>
            <div class="mb-3">
                <label for="rfc" class="form-label">RFC (opcional)</label>
                <input type="text" class="form-control" id="rfc" name="rfc" value="<?= old('rfc') ?>" placeholder="XAXX010101000" maxlength="13">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
            </div>
            <div class="mb-4">
                <label for="password_confirm" class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite tu contraseña" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg">Crear cuenta</button>
            </div>
        <?= form_close() ?>
        <div class="mt-4 text-center">
            <p class="mb-0">
                <a href="/auth/login" class="text-decoration-none">Ya tienes cuenta? Inicia sesión</a>
            </p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
