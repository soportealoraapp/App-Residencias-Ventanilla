<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\AdminController::auditoria() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, string> $filtros */
$filtros = $__view['filtros'] ?? ['entidad' => '', 'accion' => '', 'entidad_id' => '', 'usuario_id' => '', 'fecha_desde' => '', 'fecha_hasta' => ''];
/** @var array<int, object> $registros */
$registros = $__view['registros'] ?? [];
/** @var object $pager */
$pager = $__view['pager'] ?? null;
/** @var array<string, mixed> $resumen */
$resumen = $__view['resumen'] ?? [];

$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');

/**
 * URL del registro con los filtros actuales cambiados solo en lo indicado.
 */
$urlAuditoria = static function (array $cambios) use ($filtros): string {
    $base = [
        'entidad'     => (string) ($filtros['entidad'] ?? ''),
        'accion'      => (string) ($filtros['accion'] ?? ''),
        'entidad_id'  => (string) ($filtros['entidad_id'] ?? ''),
        'usuario_id'  => (string) ($filtros['usuario_id'] ?? ''),
        'fecha_desde' => (string) ($filtros['fecha_desde'] ?? ''),
        'fecha_hasta' => (string) ($filtros['fecha_hasta'] ?? ''),
    ];
    $limpia = array_filter(array_merge($base, $cambios), static fn ($v): bool => $v !== '' && $v !== null);

    return site_url('admin/auditoria' . ($limpia === [] ? '' : '?' . http_build_query($limpia)));
};

$etiquetasEntidad = [
    'users'                     => 'Usuarios',
    'user_roles'                => 'Roles de usuario',
    'solicitudes'               => 'Solicitudes',
    'documentos'                => 'Documentos',
    'tarifas'                   => 'Tarifas',
    'concesiones'               => 'Concesiones',
    'formatos_tramite'          => 'Formatos',
    'parametros_app'            => 'Parámetros de la app',
    'agentes_transito'          => 'Agentes de tránsito',
    'boletas_infracciones'      => 'Boletas de infracción',
    'catalogo_infracciones_app' => 'Catálogo de faltas',
];
$hayFiltros = array_filter(
    ['entidad', 'accion', 'entidad_id', 'usuario_id', 'fecha_desde', 'fecha_hasta'],
    static fn (string $clave): bool => ($filtros[$clave] ?? '') !== ''
) !== [];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Auditoría del sistema<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Trazabilidad del sistema',
    'texto'  => 'Registro histórico de acciones sobre usuarios, trámites y catálogos. Toca cualquier entidad, acción u operador para filtrar el registro por ese valor; los intentos de acceso incluyen la IP de origen.',
]) ?>

<div class="dgs-seccion">
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="<?= $urlAuditoria([]) ?>" class="dgs-kpi">
                <div class="card border-0 shadow-sm h-100 dgs-tarjeta">
                    <div class="card-body p-3">
                        <div class="small text-muted fw-semibold">Eventos totales</div>
                        <div class="dgs-kpi-valor"><?= $num((int) ($resumen['total'] ?? 0)) ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= $urlAuditoria(['fecha_desde' => date('Y-m-01'), 'fecha_hasta' => date('Y-m-d')]) ?>" class="dgs-kpi">
                <div class="card border-0 shadow-sm h-100 dgs-tarjeta">
                    <div class="card-body p-3">
                        <div class="small text-muted fw-semibold">Del mes en curso</div>
                        <div class="dgs-kpi-valor"><?= $num((int) ($resumen['del_mes'] ?? 0)) ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="<?= $urlAuditoria(['accion' => 'login_fallido']) ?>" class="dgs-kpi">
                <div class="card border-0 shadow-sm h-100 dgs-tarjeta">
                    <div class="card-body p-3">
                        <div class="small text-muted fw-semibold">Intentos de acceso fallidos</div>
                        <div class="dgs-kpi-valor <?= ((int) ($resumen['fallidos'] ?? 0) > 0) ? 'text-danger' : '' ?>"><?= $num((int) ($resumen['fallidos'] ?? 0)) ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="/admin/desglose#auditoria" class="dgs-kpi">
                <div class="card border-0 shadow-sm h-100 dgs-tarjeta">
                    <div class="card-body p-3">
                        <div class="small text-muted fw-semibold">Concentración por entidad</div>
                        <div class="dgs-kpi-valor"><i class="bi bi-graph-up-arrow text-primary"></i></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="card mb-3 mb-md-4 border-0 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0 small fw-bold"><i class="bi bi-funnel me-1 text-primary"></i>Filtros rápidos</h6>
            <?php if (($filtros['entidad_id'] ?? '') !== ''): ?>
                <div class="dgs-nav mt-2 mb-1">
                    <span class="dgs-nav-item border-0 bg-transparent text-muted px-0">Filtrado por registro:</span>
                    <a href="<?= $urlAuditoria(['entidad_id' => '']) ?>" class="dgs-nav-item fw-bold bg-danger-subtle text-danger border-0">
                        ID <?= esc($filtros['entidad_id']) ?> <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                    <a href="<?= $urlAuditoria(['entidad_id' => '', 'entidad' => '']) ?>" class="dgs-nav-item">Quitar también la entidad</a>
                </div>
            <?php endif; ?>
            <div class="dgs-nav <?= ($filtros['entidad_id'] ?? '') !== '' ? 'mt-0' : 'mt-2' ?> mb-1">
                <a href="<?= $urlAuditoria(['entidad' => '']) ?>" class="dgs-nav-item <?= ($filtros['entidad'] ?? '') === '' ? 'fw-bold' : '' ?>">
                    Todas las entidades
                </a>
                <?php foreach (($resumen['entidades'] ?? []) as $fila): ?>
                    <?php $ent = (string) ($fila['entidad'] ?? ''); ?>
                    <a href="<?= $urlAuditoria(['entidad' => $ent]) ?>" class="dgs-nav-item <?= ($filtros['entidad'] ?? '') === $ent ? 'fw-bold' : '' ?>">
                        <?= esc($etiquetasEntidad[$ent] ?? $ent) ?>
                        <span class="text-muted"><?= $num((int) ($fila['total'] ?? 0)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="dgs-nav mt-0 mb-1">
                <a href="<?= $urlAuditoria(['accion' => '']) ?>" class="dgs-nav-item <?= ($filtros['accion'] ?? '') === '' ? 'fw-bold' : '' ?>">
                    Todas las acciones
                </a>
                <?php foreach (($resumen['acciones'] ?? []) as $fila): ?>
                    <?php $acc = (string) ($fila['accion'] ?? ''); ?>
                    <a href="<?= $urlAuditoria(['accion' => $acc]) ?>" class="dgs-nav-item <?= ($filtros['accion'] ?? '') === $acc ? 'fw-bold' : '' ?>">
                        <?= esc($acc) ?>
                        <span class="text-muted"><?= $num((int) ($fila['total'] ?? 0)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if (($resumen['operadores'] ?? []) !== []): ?>
                <div class="dgs-nav mt-0 mb-1">
                    <span class="dgs-nav-item border-0 bg-transparent text-muted px-0">Operadores con más actividad:</span>
                    <?php foreach ((array) $resumen['operadores'] as $op): ?>
                        <a href="<?= $urlAuditoria(['usuario_id' => (string) (int) $op['usuario_id']]) ?>" class="dgs-nav-item">
                            <?= esc((string) $op['nombre']) ?>
                            <span class="text-muted"><?= $num((int) $op['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/auditoria') ?>" class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-md-2">
                    <label for="entidad" class="form-label small fw-semibold">Entidad</label>
                    <select name="entidad" id="entidad" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($etiquetasEntidad as $value => $label): ?>
                            <option value="<?= esc($value) ?>" <?= ($filtros['entidad'] ?? '') === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label for="accion" class="form-label small fw-semibold">Acción</label>
                    <input type="text" name="accion" id="accion" class="form-control form-control-sm" value="<?= esc($filtros['accion'] ?? '') ?>" placeholder="crear, editar, login...">
                </div>
                <div class="col-12 col-md-2">
                    <label for="entidad_id" class="form-label small fw-semibold">ID registro</label>
                    <input inputmode="numeric" type="number" min="1" name="entidad_id" id="entidad_id" class="form-control form-control-sm" value="<?= esc($filtros['entidad_id'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-2">
                    <label for="usuario_id" class="form-label small fw-semibold">ID usuario</label>
                    <input inputmode="numeric" type="number" min="1" name="usuario_id" id="usuario_id" class="form-control form-control-sm" value="<?= esc($filtros['usuario_id'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label for="fecha_desde" class="form-label small fw-semibold">Desde</label>
                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control form-control-sm" value="<?= esc($filtros['fecha_desde'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label for="fecha_hasta" class="form-label small fw-semibold">Hasta</label>
                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control form-control-sm" value="<?= esc($filtros['fecha_hasta'] ?? '') ?>">
                </div>
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <?php if ($hayFiltros): ?>
                        <a href="<?= site_url('admin/auditoria') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Limpiar</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filtrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 gap-2 flex-wrap">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Eventos registrados</h2>
        <span class="small text-muted"><?= $num($pager ? (int) $pager->getTotal() : count($registros)) ?> registros</span>
    </div>

    <!-- Teléfono: tarjetas apiladas, la tabla de seis columnas no cabe -->
    <div class="d-block d-md-none">
        <?php if (empty($registros)): ?>
            <div class="text-center text-muted py-5">No se encontraron eventos de auditoría.</div>
        <?php else: ?>
            <?php foreach ($registros as $registro): ?>
                <?php $detalle = json_decode((string) ($registro->detalle ?? ''), true); ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <?php if (!empty($registro->usuario_id)): ?>
                            <a href="<?= $urlAuditoria(['usuario_id' => (string) (int) $registro->usuario_id]) ?>" class="fw-semibold small text-decoration-none">
                                <?= esc($registro->nombre_completo ?: $registro->username ?: 'Sistema') ?>
                            </a>
                        <?php else: ?>
                            <span class="fw-semibold small text-muted"><?= esc($registro->username ?: 'Sistema') ?></span>
                        <?php endif; ?>
                        <span class="small text-muted text-nowrap"><?= formatear_fecha($registro->fecha) ?></span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <a href="<?= $urlAuditoria(['entidad' => (string) $registro->entidad]) ?>" class="badge bg-light text-dark border text-decoration-none">
                            <?= esc($etiquetasEntidad[(string) $registro->entidad] ?? (string) $registro->entidad) ?>
                        </a>
                        <a href="<?= $urlAuditoria(['accion' => (string) $registro->accion]) ?>" class="badge bg-primary-subtle text-primary text-decoration-none">
                            <?= esc((string) $registro->accion) ?>
                        </a>
                        <?php if (!empty($registro->entidad_id)): ?>
                            <a href="<?= $urlAuditoria(['entidad' => (string) $registro->entidad, 'entidad_id' => (string) (int) $registro->entidad_id]) ?>" class="badge bg-light text-muted border font-monospace text-decoration-none" title="Ver solo los eventos de este registro">ID: <?= esc((string) $registro->entidad_id) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($registro->detalle)): ?>
                        <details>
                            <summary class="small text-primary">Ver detalle del evento</summary>
                            <pre class="bg-light border rounded p-2 mt-2 mb-0 small" style="white-space: pre-wrap;"><?= esc($detalle !== null ? (string) json_encode($detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $registro->detalle) ?></pre>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="table-responsive d-none d-md-block">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light"><tr><th>Fecha</th><th>Usuario</th><th>Entidad</th><th>ID</th><th>Acción</th><th>Detalle</th></tr></thead>
            <tbody>
            <?php if (empty($registros)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No se encontraron eventos de auditoría.</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $registro): ?>
                    <?php $detalle = json_decode((string) ($registro->detalle ?? ''), true); ?>
                    <tr>
                        <td class="text-nowrap small"><?= formatear_fecha($registro->fecha) ?></td>
                        <td>
                            <?php if (!empty($registro->usuario_id)): ?>
                                <a href="<?= $urlAuditoria(['usuario_id' => (string) (int) $registro->usuario_id]) ?>" class="fw-semibold small text-decoration-none">
                                    <?= esc($registro->nombre_completo ?: $registro->username ?: 'Sistema') ?>
                                </a>
                            <?php else: ?>
                                <span class="fw-semibold small text-muted"><?= esc($registro->username ?: 'Sistema') ?></span>
                            <?php endif; ?>
                            <div class="small text-muted">ID: <?= esc((string) ($registro->usuario_id ?? '-')) ?></div>
                        </td>
                        <td>
                            <a href="<?= $urlAuditoria(['entidad' => (string) $registro->entidad]) ?>" class="badge bg-light text-dark border text-decoration-none">
                                <?= esc($etiquetasEntidad[(string) $registro->entidad] ?? (string) $registro->entidad) ?>
                            </a>
                        </td>
                        <td class="font-monospace">
                            <?php if (!empty($registro->entidad_id)): ?>
                                <a href="<?= $urlAuditoria(['entidad' => (string) $registro->entidad, 'entidad_id' => (string) (int) $registro->entidad_id]) ?>" class="text-decoration-none" title="Ver solo los eventos de este registro"><?= esc((string) $registro->entidad_id) ?></a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= $urlAuditoria(['accion' => (string) $registro->accion]) ?>" class="badge bg-primary-subtle text-primary text-decoration-none">
                                <?= esc((string) $registro->accion) ?>
                            </a>
                        </td>
                        <td class="small"><details><summary class="text-primary">Ver detalle</summary><pre class="bg-light border rounded p-2 mt-2 mb-0 small" style="max-width: 420px; white-space: pre-wrap;"><?= esc($detalle !== null ? (string) json_encode($detalle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) ($registro->detalle ?? '-')) ?></pre></details></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pager && $pager->getPageCount() > 1): ?><div class="card-footer bg-white d-flex justify-content-center pt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
<?= $this->endSection() ?>
