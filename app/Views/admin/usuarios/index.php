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
/** @var array{total?: int, activos?: int, inactivos?: int, roles?: array<string, int>} $conteos */
$conteos = $__view['conteos'] ?? [];
/** @var bool $esAdmin */
$esAdmin = (bool) ($__view['esAdmin'] ?? false);
/** @var object|null $pager */
$pager = $__view['pager'] ?? null;

/**
 * Construye una URL de listado conservando los filtros activos, de modo que
 * tocar un chip o una etiqueta no haga perder la búsqueda en curso.
 */
$urlUsuarios = static function (array $cambios) use ($filtros): string {
    $base = ['q' => $filtros['q'], 'rol' => $filtros['rol'], 'estado' => $filtros['estado']];
    $mezcla = array_merge($base, $cambios);
    $limpia = array_filter($mezcla, static fn ($v): bool => $v !== '' && $v !== null);

    return '/admin/usuarios' . ($limpia === [] ? '' : '?' . http_build_query($limpia));
};

$conteoRoles   = (array) ($conteos['roles'] ?? []);
$totalCuentas  = (int) ($conteos['total'] ?? 0);
$chipsUsuarios = [
    ['etiqueta' => 'Todas las cuentas', 'valor' => $totalCuentas, 'url' => $urlUsuarios(['rol' => '', 'estado' => '']), 'activo' => $filtros['rol'] === '' && $filtros['estado'] === '', 'clase' => 'bg-primary text-white'],
    ['etiqueta' => 'Activas',           'valor' => (int) ($conteos['activos'] ?? 0),   'url' => $urlUsuarios(['estado' => 'activo']),   'activo' => $filtros['estado'] === 'activo',   'clase' => 'bg-success text-white'],
    ['etiqueta' => 'Inactivas',         'valor' => (int) ($conteos['inactivos'] ?? 0), 'url' => $urlUsuarios(['estado' => 'inactivo']), 'activo' => $filtros['estado'] === 'inactivo', 'clase' => 'bg-danger text-white'],
];
foreach ($roles as $clave => $nombre) {
    if ($clave === 'ciudadano') {
        continue;
    }
    $chipsUsuarios[] = [
        'etiqueta' => $nombre,
        'valor'    => (int) ($conteoRoles[$clave] ?? 0),
        'url'      => $urlUsuarios(['rol' => $clave]),
        'activo'   => $filtros['rol'] === $clave,
        'clase'    => 'bg-dark text-white',
    ];
}
$ciudadanos = (int) ($conteoRoles['ciudadano'] ?? 0);
if ($ciudadanos > 0) {
    $chipsUsuarios[] = [
        'etiqueta' => 'Ciudadanos',
        'valor'    => $ciudadanos,
        'url'      => $urlUsuarios(['rol' => 'ciudadano']),
        'activo'   => $filtros['rol'] === 'ciudadano',
        'clase'    => 'bg-secondary text-white',
    ];
}
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Usuarios y ciudadanos<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cuentas de ciudadano y personal',
    'texto'  => 'El ciudadano puede <strong>autorregistrarse</strong> en el portal; aquí la ventanilla también crea o edita cuentas para la atención presencial con identificación oficial. Las <strong>cuentas de agentes de tránsito</strong> y las <strong>bajas definitivas</strong> son exclusivas del administrador.',
]) ?>

<div class="row mb-3 g-2">
    <div class="col-lg-9">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1" for="q">Buscar</label>
                <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" id="q" name="q" class="form-control form-control-sm" value="<?= esc($filtros['q']) ?>" placeholder="Nombre, usuario, correo o CURP...">
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
        <a href="/admin/usuarios/nuevo" class="btn btn-success btn-sm w-100 w-lg-auto"><i class="bi bi-person-plus me-1"></i> Nueva cuenta</a>
    </div>
</div>

<div class="dgs-nav" role="navigation" aria-label="Filtros rápidos de cuentas">
    <?php foreach ($chipsUsuarios as $c): ?>
        <a class="dgs-nav-item <?= $c['activo'] ? $c['clase'] . ' border-0' : '' ?>" href="<?= esc($c['url']) ?>" <?= $c['activo'] ? 'aria-current="page"' : '' ?>>
            <?= esc($c['etiqueta']) ?>
            <span class="badge <?= $c['activo'] ? 'bg-white text-dark' : 'bg-light text-dark border' ?>"><?= (int) $c['valor'] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive d-none d-md-block">
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
                                <?= !empty($u->email) ? '<a class="text-decoration-none" href="mailto:' . esc($u->email) . '">' . esc($u->email) . '</a><br>' : '' ?>
                                <span class="text-muted"><?= esc($u->telefono ?? '') ?></span>
                            </td>
                            <td>
                                <?php foreach ($rolesUsuario ?: ['sin rol'] as $r): ?>
                                    <?php if (isset($roles[$r])): ?>
                                        <a class="badge text-decoration-none <?= $r === 'ciudadano' ? 'bg-secondary' : 'bg-primary' ?>" href="<?= esc($urlUsuarios(['rol' => $r])) ?>" title="Ver todas las cuentas con este rol"><?= esc($roles[$r]) ?></a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= esc($r) ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <a class="badge text-decoration-none <?= $activo ? 'bg-success' : 'bg-danger' ?>" href="<?= esc($urlUsuarios(['estado' => $activo ? 'activo' : 'inactivo'])) ?>"><?= $activo ? 'Activa' : 'Inactiva' ?></a>
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

        <!-- Fichas para el teléfono: la tabla de 8 columnas no cabe en pantalla -->
        <div class="d-block d-md-none">
            <?php if (empty($usuarios)): ?>
                <div class="empty-state">
                    <i class="bi bi-person-search" aria-hidden="true"></i>
                    <div class="empty-titulo">Sin resultados</div>
                    <div class="empty-texto">No hay cuentas que coincidan con la búsqueda. Ajusta los filtros o crea una nueva cuenta.</div>
                </div>
            <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                    <?php
                    $rolesUsuario = $rolesPorId[(int) $u->id] ?? [];
                    $esInterna = array_diff($rolesUsuario, ['ciudadano']) !== [];
                    $editable = $esAdmin || ! $esInterna;
                    $activo = (int) $u->activo === 1;
                    ?>
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="overflow-hidden">
                                <div class="fw-semibold text-dark"><?= esc($u->nombre_completo ?? '—') ?></div>
                                <div class="small text-muted"><code class="text-dark"><?= esc($u->username ?? '—') ?></code> · #<?= (int) $u->id ?></div>
                            </div>
                            <a class="badge text-decoration-none <?= $activo ? 'bg-success' : 'bg-danger' ?> flex-shrink-0" href="<?= esc($urlUsuarios(['estado' => $activo ? 'activo' : 'inactivo'])) ?>"><?= $activo ? 'Activa' : 'Inactiva' ?></a>
                        </div>
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <?php foreach ($rolesUsuario ?: ['sin rol'] as $r): ?>
                                <?php if (isset($roles[$r])): ?>
                                    <a class="badge text-decoration-none <?= $r === 'ciudadano' ? 'bg-secondary' : 'bg-primary' ?>" href="<?= esc($urlUsuarios(['rol' => $r])) ?>"><?= esc($roles[$r]) ?></a>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border"><?= esc($r) ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php if (! empty($u->curp) || ! empty($u->email) || ! empty($u->telefono)): ?>
                            <div class="small text-muted mt-2">
                                <?php if (! empty($u->curp)): ?><div><i class="bi bi-fingerprint me-1"></i><?= esc($u->curp) ?></div><?php endif; ?>
                                <?php if (! empty($u->email)): ?><div><i class="bi bi-envelope me-1"></i><a class="text-decoration-none" href="mailto:<?= esc($u->email) ?>"><?= esc($u->email) ?></a></div><?php endif; ?>
                                <?php if (! empty($u->telefono)): ?><div><i class="bi bi-telephone me-1"></i><?= esc($u->telefono) ?></div><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 mt-3">
                            <?php if ($editable): ?>
                                <a href="/admin/usuarios/editar/<?= (int) $u->id ?>" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-pencil me-1"></i>Editar</a>
                            <?php else: ?>
                                <span class="btn btn-outline-secondary btn-sm disabled flex-fill">Solo administrador</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($pager)): ?>
    <div class="mt-4 d-flex justify-content-center">
        <?= $pager->links('default', 'bootstrap') ?>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>
