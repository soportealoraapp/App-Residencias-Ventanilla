<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\UsuariosController::index() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, string> $filtros */
$filtros = $__view['filtros'] ?? ['q' => '', 'rol' => '', 'estado' => ''];
/** @var array<string, string> $roles */
$roles = $__view['roles'] ?? [];
/** @var array<int, object> $usuarios */
$usuarios = $__view['usuarios'] ?? [];
/** @var array<int, array<int, string>> $rolesPorId */
$rolesPorId = $__view['rolesPorId'] ?? [];
/** @var bool $esAdmin */
$esAdmin = (bool) ($__view['esAdmin'] ?? false);
/** @var object|null $pager */
$pager = $__view['pager'] ?? null;
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Usuarios y ciudadanos<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cuentas de ciudadano y personal',
    'texto'  => 'El registro en línea está deshabilitado. Las cuentas de ciudadano se crean aquí cuando la persona acude a ventanilla con su identificación oficial. Las <strong>bajas definitivas</strong> son exclusivas del administrador.',
]) ?>

<div class="row mb-3 g-2">
    <div class="col-lg-9">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1" for="q">Buscar</label>
                <input type="text" id="q" name="q" class="form-control form-control-sm" value="<?= esc($filtros['q']) ?>" placeholder="Nombre, usuario, correo o CURP...">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1" for="rol">Rol</label>
                <select id="rol" name="rol" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($roles as $valor => $etiqueta): ?>
                        <option value="<?= esc($valor) ?>" <?= $filtros['rol'] === $valor ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1" for="estado">Estado</label>
                <select id="estado" name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activos</option>
                    <option value="inactivo" <?= $filtros['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>
            <div class="col-md-2 text-nowrap">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i> Buscar</button>
                <?php if ($filtros['q'] !== '' || $filtros['rol'] !== '' || $filtros['estado'] !== ''): ?>
                    <a href="/admin/usuarios" class="btn btn-outline-secondary btn-sm" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="col-lg-3 text-lg-end align-self-end">
        <a href="/admin/usuarios/nuevo" class="btn btn-success btn-sm"><i class="bi bi-person-plus me-1"></i> Nueva cuenta</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>CURP</th>
                        <th>Contacto</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-person-search" aria-hidden="true"></i>
                                <div class="empty-titulo">Sin resultados</div>
                                <div class="empty-texto">No hay cuentas que coincidan con la búsqueda. Ajusta los filtros o crea una nueva cuenta.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $u): ?>
                        <?php
                        $rolesUsuario = $rolesPorId[(int) $u->id] ?? [];
                        $esInterna = array_diff($rolesUsuario, ['ciudadano']) !== [];
                        $editable = $esAdmin || ! $esInterna;
                        $activo = (int) $u->activo === 1;
                        ?>
                        <tr>
                            <td class="fw-semibold">#<?= (int) $u->id ?></td>
                            <td><?= esc($u->nombre_completo ?? '—') ?></td>
                            <td><code class="text-dark"><?= esc($u->username ?? '—') ?></code></td>
                            <td class="small font-monospace"><?= esc($u->curp ?? '—') ?></td>
                            <td class="small">
                                <?= !empty($u->email) ? esc($u->email) . '<br>' : '' ?>
                                <span class="text-muted"><?= esc($u->telefono ?? '') ?></span>
                            </td>
                            <td>
                                <?php foreach ($rolesUsuario ?: ['sin rol'] as $r): ?>
                                    <span class="badge <?= $r === 'ciudadano' ? 'bg-secondary' : 'bg-primary' ?>"><?= esc($roles[$r] ?? $r) ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <span class="badge <?= $activo ? 'bg-success' : 'bg-danger' ?>"><?= $activo ? 'Activa' : 'Inactiva' ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($editable): ?>
                                    <a href="/admin/usuarios/editar/<?= (int) $u->id ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Editar cuenta" aria-label="Editar cuenta"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <?php if ((int) $u->id !== (int) session('user_id')): ?>
                                        <?= form_open('/admin/usuarios/toggle/' . (int) $u->id, ['class' => 'd-inline', 'data-no-loading' => '1']) ?>
                                            <button type="submit" class="btn btn-outline-<?= $activo ? 'warning' : 'success' ?> btn-sm" data-bs-toggle="tooltip" data-bs-title="<?= $activo ? 'Desactivar' : 'Activar' ?> cuenta" aria-label="<?= $activo ? 'Desactivar' : 'Activar' ?> cuenta">
                                                <i class="bi bi-<?= $activo ? 'pause-circle' : 'play-circle' ?>" aria-hidden="true"></i>
                                            </button>
                                        <?= form_close() ?>
                                        <?php if ($esAdmin): ?>
                                            <?= form_open('/admin/usuarios/eliminar/' . (int) $u->id, ['class' => 'd-inline', 'data-no-loading' => '1', 'onsubmit' => "return confirm('¿Eliminar definitivamente la cuenta " . esc($u->username, 'js') . "? Si tiene solicitudes registradas no se podrá eliminar.');"]) ?>
                                                <button type="submit" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" data-bs-title="Eliminar cuenta" aria-label="Eliminar cuenta"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                            <?= form_close() ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Solo administrador</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($pager)): ?>
    <div class="mt-4 d-flex justify-content-center">
        <?= $pager->links('default', 'bootstrap') ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
