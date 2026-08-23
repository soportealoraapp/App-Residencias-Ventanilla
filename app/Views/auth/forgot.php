<?php declare(strict_types=1);
?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('content') ?>

<div class="card shadow">
    <div class="card-header bg-warning text-dark text-center py-3">
        <h4 class="mb-0">Recuperar Contraseña</h4>
        <p class="mb-0 mt-1 small">Ingresa tu correo registrado</p>
    </div>
    <div class="card-body p-4">
        <?php if (session()->has('errors')): ?>
            <div class="alert alert-danger">
                <?php foreach (session('errors') as $error): ?>
                    <p class="mb-1"><?= esc($error) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <?php if (session()->has('message')): ?>
            <div class="alert alert-info">
                <?= esc(session('message')) ?>
            </div>
        <?php endif ?>

        <form method="POST" action="/auth/attempt-forgot">
            <div class="mb-4">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" placeholder="correo@ejemplo.com" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-warning btn-lg">Enviar link</button>
            </div>
        </form>
        <div class="mt-4 text-center">
            <p class="mb-0">
                <a href="/auth/login" class="text-decoration-none">Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
