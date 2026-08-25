<?php declare(strict_types=1);
?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('content') ?>

<div class="card shadow">
    <div class="card-header bg-info text-white text-center py-3">
        <h4 class="mb-0">Restablecer Contraseña</h4>
        <p class="mb-0 mt-1 small">Ingresa tu nueva contraseña</p>
    </div>
    <div class="card-body p-4">
        <?php if ($user === null): ?>
            <div class="alert alert-danger">
                <p class="mb-0">Token inválido o expirado. Por favor solicita un nuevo enlace de recuperación.</p>
            </div>
            <div class="text-center mt-3">
                <a href="/auth/forgot" class="btn btn-primary">Solicitar nuevo link</a>
            </div>
        <?php else: ?>
            <?php if (session()->has('errors')): ?>
                <div class="alert alert-danger">
                    <?php foreach (session('errors') as $error): ?>
                        <p class="mb-1"><?= esc($error) ?></p>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <?= form_open('/auth/reset/' . esc($token)) ?>
                <input type="hidden" name="token" value="<?= esc($token) ?>">
                <div class="mb-3">
                    <label for="password" class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite tu nueva contraseña" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-info btn-lg text-white">Actualizar contraseña</button>
                </div>
            <?= form_close() ?>
        <?php endif ?>
        <div class="mt-4 text-center">
            <p class="mb-0">
                <a href="/auth/login" class="text-decoration-none">Volver al inicio de sesión</a>
            </p>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
