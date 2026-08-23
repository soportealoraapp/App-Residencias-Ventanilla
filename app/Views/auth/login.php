<?php declare(strict_types=1);
?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('content') ?>

<div class="card shadow">
    <div class="card-header bg-primary text-white text-center py-3">
        <h4 class="mb-0">Iniciar Sesión</h4>
        <p class="mb-0 mt-1 small">Ventanilla Uriangato</p>
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
            <div class="alert alert-success">
                <?= esc(session('message')) ?>
            </div>
        <?php endif ?>

        <form method="POST" action="/auth/attempt-login">
            <div class="mb-3">
                <label for="username" class="form-label">Usuario o Correo electrónico</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= old('username') ?>" placeholder="Ingresa tu usuario o email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Ingresa tu contraseña" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Iniciar sesión</button>
            </div>
        </form>
        <div class="mt-4 text-center">
            <p class="mb-1">
                <a href="/auth/register" class="text-decoration-none">No tienes cuenta? Regístrate</a>
            </p>
            <p class="mb-0">
                <a href="/auth/forgot" class="text-decoration-none">Olvidé mi contraseña</a>
            </p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
