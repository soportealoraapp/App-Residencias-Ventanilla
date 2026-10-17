<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?><?= $modo === 'nuevo' ? 'Nueva cuenta' : 'Editar cuenta #' . (int) $usuario->id ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php $rolSeleccionado = old('rol', $rolActual); ?>

<div class="mb-3">
    <a href="/admin/usuarios" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Volver al listado</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <?= form_open_multipart($modo === 'nuevo' ? '/admin/usuarios/guardar' : '/admin/usuarios/actualizar/' . (int) $usuario->id) ?>

        <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Datos personales</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="nombre" class="form-label fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="90" value="<?= esc(old('nombre', $usuario->nombre ?? '')) ?>" placeholder="Ej. María Fernanda">
            </div>
            <div class="col-md-4">
                <label for="apellido" class="form-label fw-semibold">Apellido(s) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="apellido" name="apellido" required maxlength="120" value="<?= esc(old('apellido', $usuario->apellido ?? '')) ?>" placeholder="Ej. López García">
            </div>
            <div class="col-md-4">
                <label for="curp" class="form-label fw-semibold">CURP</label>
                <input type="text" class="form-control text-uppercase font-monospace" id="curp" name="curp" maxlength="18" minlength="18" value="<?= esc(old('curp', $usuario->curp ?? '')) ?>" placeholder="Ej. LOGM900101MGTPRR05">
            </div>
            <div class="col-md-4">
                <label for="rfc" class="form-label fw-semibold">RFC</label>
                <input type="text" class="form-control text-uppercase font-monospace" id="rfc" name="rfc" minlength="12" maxlength="13" value="<?= esc(old('rfc', $usuario->rfc ?? '')) ?>" placeholder="Ej. LOGM900101AB1">
            </div>
            <div class="col-md-4">
                <label for="telefono" class="form-label fw-semibold">Teléfono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="20" value="<?= esc(old('telefono', $usuario->telefono ?? '')) ?>" placeholder="Ej. 445 123 4567">
            </div>
            <div class="col-md-4">
                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                <input type="email" class="form-control" id="email" name="email" maxlength="255" value="<?= esc(old('email', $usuario->email ?? '')) ?>" placeholder="Ej. maria@correo.com">
            </div>
            <div class="col-md-6">
                <label for="domicilio" class="form-label fw-semibold">Domicilio</label>
                <input type="text" class="form-control" id="domicilio" name="domicilio" maxlength="250" value="<?= esc(old('domicilio', $usuario->domicilio ?? '')) ?>" placeholder="Calle, número, colonia, CP">
            </div>
            <div class="col-md-3">
                <label for="ciudad" class="form-label fw-semibold">Ciudad / Municipio</label>
                <input type="text" class="form-control" id="ciudad" name="ciudad" maxlength="150" value="<?= esc(old('ciudad', $usuario->ciudad ?? '')) ?>">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label fw-semibold">Estado</label>
                <input type="text" class="form-control" id="estado" name="estado" maxlength="100" value="<?= esc(old('estado', $usuario->estado ?? '')) ?>">
            </div>
        </div>

        <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Acceso al portal</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="username" class="form-label fw-semibold">Nombre de usuario <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" required minlength="5" maxlength="100" pattern="[A-Za-z0-9]+" title="Solo letras y números, mínimo 5" value="<?= esc(old('username', $usuario->username ?? '')) ?>" placeholder="Ej. mlopez90">
            </div>
            <div class="col-md-4">
                <label for="password" class="form-label fw-semibold">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" minlength="6" autocomplete="new-password" placeholder="<?= $modo === 'nuevo' ? 'Vacío = generar una temporal' : 'Vacío = conservar la actual' ?>">
            </div>
            <div class="col-md-2">
                <label for="rol" class="form-label fw-semibold">Rol <span class="text-danger">*</span></label>
                <select class="form-select" id="rol" name="rol" required>
                    <?php foreach ($roles as $valor => $etiqueta): ?>
                        <option value="<?= esc($valor) ?>" <?= $rolSeleccionado === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="activo" class="form-label fw-semibold">Estado</label>
                <?php $activo = (string) old('activo', (string) (int) ($usuario->activo ?? 1)); ?>
                <select class="form-select" id="activo" name="activo">
                    <option value="1" <?= $activo === '1' ? 'selected' : '' ?>>Activa</option>
                    <option value="0" <?= $activo === '0' ? 'selected' : '' ?>>Inactiva</option>
                </select>
            </div>
        </div>

        <h2 class="h6 text-uppercase text-muted fw-bold mb-3">Identificación oficial (opcional)</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="ine_frente" class="form-label fw-semibold">INE (frente)</label>
                <input type="file" class="form-control" id="ine_frente" name="ine_frente" accept="image/jpeg,image/png">
                <?php if (!empty($usuario->ine_frente)): ?><div class="form-text">Ya hay una imagen cargada; selecciona otra solo para reemplazarla.</div><?php endif; ?>
            </div>
            <div class="col-md-6">
                <label for="ine_reverso" class="form-label fw-semibold">INE (reverso)</label>
                <input type="file" class="form-control" id="ine_reverso" name="ine_reverso" accept="image/jpeg,image/png">
                <?php if (!empty($usuario->ine_reverso)): ?><div class="form-text">Ya hay una imagen cargada; selecciona otra solo para reemplazarla.</div><?php endif; ?>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="/admin/usuarios" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary" data-loading-text="Guardando...">
                <i class="bi bi-save me-1"></i><?= $modo === 'nuevo' ? 'Crear cuenta' : 'Guardar cambios' ?>
            </button>
        </div>

        <?= form_close() ?>
    </div>
</div>

<?= $this->endSection() ?>
