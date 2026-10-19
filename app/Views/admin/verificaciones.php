<?php declare(strict_types=1);

/**
 * Agenda de verificaciones físicas.
 *
 * Variables inyectadas por Admin\VerificacionesController::index() vía view(..., $data).
 * Se leen desde get_defined_vars() para que el análisis estático no las marque
 * como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var list<object> $citas */
$citas          = $__view['citas'] ?? [];
/** @var object|null $pager */
$pager          = $__view['pager'] ?? null;
/** @var array<string, string> $filtros */
$filtros        = (array) ($__view['filtros'] ?? []);
/** @var int $totalFiltrado */
$totalFiltrado  = (int) ($__view['totalFiltrado'] ?? 0);
/** @var array<string, int> $global */
$global         = (array) ($__view['global'] ?? []);
/** @var list<array{resultado: string, total: int}> $porResultado */
$porResultado   = (array) ($__view['porResultado'] ?? []);
/** @var array<string, string> $estados */
$estados        = (array) ($__view['estados'] ?? []);
/** @var array<string, string> $camposOrden */
$camposOrden    = (array) ($__view['camposOrden'] ?? ['fecha_cita' => 'Fecha de la cita']);

$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');

/**
 * URL del listado con los filtros actuales, cambiando solo lo indicado.
 * Centralizarla evita que dos tarjetas enlacen a criterios distintos para la misma cifra.
 */
$urlCitas = static function (array $cambios) use ($filtros): string {
    $base = [
        'estado'    => (string) ($filtros['estado'] ?? ''),
        'resultado' => (string) ($filtros['resultado'] ?? ''),
        'tramite'   => (string) ($filtros['tramite'] ?? ''),
        'q'         => (string) ($filtros['q'] ?? ''),
        'desde'     => (string) ($filtros['desde'] ?? ''),
        'hasta'     => (string) ($filtros['hasta'] ?? ''),
        'orden'     => (string) ($filtros['orden'] ?? 'fecha_cita'),
        'dir'       => strtolower((string) ($filtros['direccion'] ?? 'DESC')) === 'asc' ? 'asc' : 'desc',
    ];
    $limpia = array_filter(array_merge($base, $cambios), static fn ($v): bool => $v !== '' && $v !== null);

    return site_url('admin/verificaciones' . ($limpia === [] ? '' : '?' . http_build_query($limpia)));
};

$hayFiltros = array_filter(
    ['estado', 'resultado', 'tramite', 'q', 'desde', 'hasta'],
    static fn (string $clave): bool => ($filtros[$clave] ?? '') !== ''
) !== [];

/** Etiqueta legible del dictamen capturado. */
$resultadoNombre = static fn (string $valor): string => $valor === '' || $valor === 'sin_dictamen'
    ? 'Sin dictamen'
    : ucfirst(str_replace('_', ' ', $valor));

/** Cada chip de estado apunta a la misma cifra que muestra el encabezado. */
$claveGlobal = static fn (string $estado): string => match ($estado) {
    'proximas'     => 'proximas',
    'vencidas'     => 'vencidas',
    'con_dictamen' => 'con_dictamen',
    default        => 'sin_dictamen',
};
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Verificaciones físicas<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="panel-titulo">
    <div>
        <h2><i class="bi bi-clipboard2-check" aria-hidden="true"></i> Verificaciones físicas</h2>
        <p class="panel-subtitulo">Todas las citas de inspección, con su expediente y el dictamen capturado.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Inicio</a>
        <a href="/admin/desglose#verificaciones" class="btn btn-outline-primary btn-sm"><i class="bi bi-graph-up-arrow me-1"></i>Ver en el desglose</a>
    </div>
</div>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cómo seguir la agenda de inspecciones',
    'texto'  => 'Toca una cifra o un dictamen para filtrar la agenda por exactamente ese valor. El folio abre el expediente donde se captura el resultado de la revisión.',
]) ?>

<div class="dgs-seccion">
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Citas registradas',       $num((int) ($global['total'] ?? 0)),        $urlCitas([]), 'bi-calendar3'],
            ['Próximas citas',          $num((int) ($global['proximas'] ?? 0)),     $urlCitas(['estado' => 'proximas']), 'bi-clock'],
            ['Con dictamen capturado',  $num((int) ($global['con_dictamen'] ?? 0)), $urlCitas(['estado' => 'con_dictamen']), 'bi-check2-circle'],
            ['Sin dictamen',            $num((int) ($global['sin_dictamen'] ?? 0)), $urlCitas(['estado' => 'sin_dictamen']), 'bi-pencil'],
            ['Vencidas sin dictamen',   $num((int) ($global['vencidas'] ?? 0)),     $urlCitas(['estado' => 'vencidas']), 'bi-exclamation-triangle'],
        ] as $k): ?>
            <div class="col-6 col-md-4 col-lg">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($k[2]) ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted fw-semibold"><?= esc($k[0]) ?></small>
                        <i class="bi <?= $k[3] ?> <?= $k[0] === 'Vencidas sin dictamen' ? 'text-danger' : 'text-primary' ?>" aria-hidden="true"></i>
                    </div>
                    <div class="dgs-kpi-valor"><?= esc($k[1]) ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card border-0 shadow-sm mb-3 mb-md-4">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0 small fw-bold"><i class="bi bi-funnel me-1 text-primary"></i>Filtros rápidos</h6>
            <div class="dgs-nav mt-2 mb-1">
                <a href="<?= $urlCitas(['estado' => '']) ?>" class="dgs-nav-item <?= ($filtros['estado'] ?? '') === '' ? 'fw-bold' : '' ?>">
                    Todas las citas <span class="text-muted"><?= $num((int) ($global['total'] ?? 0)) ?></span>
                </a>
                <?php foreach ($estados as $clave => $rotulo): ?>
                    <a href="<?= $urlCitas(['estado' => $clave]) ?>" class="dgs-nav-item <?= ($filtros['estado'] ?? '') === $clave ? 'fw-bold' : '' ?>">
                        <?= esc($rotulo) ?>
                        <span class="text-muted"><?= $num((int) ($global[$claveGlobal($clave)] ?? 0)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($porResultado !== []): ?>
                <div class="dgs-nav mt-0 mb-1">
                    <a href="<?= $urlCitas(['resultado' => '']) ?>" class="dgs-nav-item <?= ($filtros['resultado'] ?? '') === '' ? 'fw-bold' : '' ?>">Dictamen: cualquiera</a>
                    <?php foreach ($porResultado as $r): ?>
                        <?php $valor = (string) $r['resultado']; ?>
                        <a href="<?= $urlCitas(['resultado' => $valor]) ?>" class="dgs-nav-item <?= ($filtros['resultado'] ?? '') === $valor ? 'fw-bold' : '' ?>"
                           title="Ver exactamente las citas con este resultado">
                            <?= esc($resultadoNombre($valor)) ?>
                            <span class="text-muted"><?= $num((int) $r['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/verificaciones') ?>" class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label small fw-semibold">Folio, nombre u observaciones</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm" maxlength="80"
                           value="<?= esc($filtros['q'] ?? '') ?>" placeholder="SOL-2026-0001"
                           autocomplete="off" spellcheck="false" enterkeyhint="search">
                </div>
                <div class="col-6 col-md-2">
                    <label for="estado" class="form-label small fw-semibold">Estado</label>
                    <select name="estado" id="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $clave => $rotulo): ?>
                            <option value="<?= esc($clave) ?>" <?= ($filtros['estado'] ?? '') === $clave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="resultado" class="form-label small fw-semibold">Dictamen</label>
                    <select name="resultado" id="resultado" class="form-select form-select-sm">
                        <option value="">Cualquiera</option>
                        <?php foreach ($porResultado as $r): ?>
                            <?php $valor = (string) $r['resultado']; ?>
                            <option value="<?= esc($valor) ?>" <?= ($filtros['resultado'] ?? '') === $valor ? 'selected' : '' ?>>
                                <?= esc($resultadoNombre($valor)) ?> (<?= (int) $r['total'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="desde" class="form-label small fw-semibold">Citas desde</label>
                    <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="<?= esc($filtros['desde'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label for="hasta" class="form-label small fw-semibold">Citas hasta</label>
                    <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="<?= esc($filtros['hasta'] ?? '') ?>">
                </div>
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search" aria-hidden="true"></i><span class="ms-1">Filtrar</span></button>
                    <?php if ($hayFiltros): ?>
                        <a href="<?= site_url('admin/verificaciones') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Quitar todos los filtros</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 gap-2 flex-wrap">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Citas de inspección</h2>
        <span class="small text-muted"><?= $num($totalFiltrado) ?> de <?= $num((int) ($global['total'] ?? 0)) ?></span>
    </div>

    <?php if ($citas === []): ?>
        <div class="empty-state">
            <i class="bi bi-calendar-x" aria-hidden="true"></i>
            <div class="empty-titulo">Sin resultados</div>
            <div class="empty-texto"><?= $hayFiltros ? 'Ninguna cita coincide con el filtro seleccionado.' : 'Todavía no se ha agendado ninguna verificación física.' ?></div>
            <?php if ($hayFiltros): ?><a href="<?= site_url('admin/verificaciones') ?>" class="btn btn-outline-primary btn-sm mt-2">Ver todas las citas</a><?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Teléfono: tarjetas apiladas; una tabla de cinco columnas no cabe -->
        <div class="d-block d-md-none">
            <?php foreach ($citas as $cita): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <?php if (! empty($cita->folio)): ?>
                            <a class="fw-semibold font-monospace small" href="<?= esc(site_url('admin/solicitudes/' . rawurlencode((string) $cita->folio))) ?>">
                                <?= esc((string) $cita->folio) ?> <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        <?php else: ?>
                            <span class="fw-semibold small text-muted">Expediente eliminado</span>
                        <?php endif; ?>
                        <span class="small text-muted text-nowrap"><?= formatear_fecha($cita->fecha_cita, 'd/m/Y H:i') ?></span>
                    </div>
                    <div class="small text-muted mt-1"><?= esc(tramite_nombre((string) ($cita->tramite ?? ''))) ?> · <?= esc((string) ($cita->estatus ?? '')) ?></div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-2">
                        <?php $dictamen = (string) ($cita->resultado ?? ''); ?>
                        <?php if ($dictamen === ''): ?>
                            <a href="<?= $urlCitas(['estado' => 'sin_dictamen']) ?>" class="badge bg-warning-subtle text-warning-emphasis text-decoration-none">
                                <i class="bi bi-pencil me-1" aria-hidden="true"></i>Sin dictamen
                            </a>
                        <?php else: ?>
                            <a href="<?= $urlCitas(['resultado' => $dictamen]) ?>" class="badge bg-success-subtle text-success text-decoration-none">
                                <?= esc($resultadoNombre($dictamen)) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (trim((string) ($cita->observaciones ?? '')) !== ''): ?>
                        <div class="small text-muted mt-1"><?= esc((string) $cita->observaciones) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Expediente</th>
                        <th>
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? 'fecha_cita') === 'fecha_cita' ? 'fw-bold' : '' ?>" href="<?= $urlCitas(['orden' => 'fecha_cita', 'dir' => ($filtros['orden'] ?? 'fecha_cita') === 'fecha_cita' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Cita <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                        <th>Dictamen</th>
                        <th>Observaciones</th>
                        <th>
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? '') === 'created_at' ? 'fw-bold' : '' ?>" href="<?= $urlCitas(['orden' => 'created_at', 'dir' => ($filtros['orden'] ?? '') === 'created_at' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Registrada <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas as $cita): ?>
                        <tr>
                            <td class="mw-260">
                                <?php if (! empty($cita->folio)): ?>
                                    <a class="font-monospace small text-decoration-none fw-semibold" href="<?= esc(site_url('admin/solicitudes/' . rawurlencode((string) $cita->folio))) ?>"><?= esc((string) $cita->folio) ?></a>
                                <?php else: ?>
                                    <span class="text-muted small">Expediente eliminado</span>
                                <?php endif; ?>
                                <div class="small text-muted"><?= esc(tramite_nombre((string) ($cita->tramite ?? ''))) ?></div>
                            </td>
                            <td class="small text-nowrap">
                                <?= formatear_fecha($cita->fecha_cita, 'd/m/Y H:i') ?>
                                <div class="text-muted"><?= esc((string) ($cita->estatus ?? '')) ?></div>
                            </td>
                            <td>
                                <?php $dictamen = (string) ($cita->resultado ?? ''); ?>
                                <?php if ($dictamen === ''): ?>
                                    <a href="<?= $urlCitas(['estado' => 'sin_dictamen']) ?>" class="badge bg-warning-subtle text-warning-emphasis text-decoration-none">Sin dictamen</a>
                                <?php else: ?>
                                    <a href="<?= $urlCitas(['resultado' => $dictamen]) ?>" class="badge bg-success-subtle text-success text-decoration-none"><?= esc($resultadoNombre($dictamen)) ?></a>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted mw-260"><?= esc(trim((string) ($cita->observaciones ?? '')) ?: '—') ?></td>
                            <td class="small text-nowrap text-muted"><?= formatear_fecha($cita->created_at ?? null, 'd/m/Y H:i') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pager && $pager->getPageCount() > 1): ?>
            <div class="card-footer bg-white d-flex justify-content-center pt-3"><?= $pager->links() ?></div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
