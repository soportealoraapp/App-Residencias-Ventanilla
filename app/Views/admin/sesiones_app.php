<?php declare(strict_types=1);

/**
 * Sesiones de la app de agentes de tránsito.
 *
 * Variables inyectadas por Admin\SesionesAppController::index() vía view(..., $data).
 * Se leen desde get_defined_vars() para que el análisis estático no las marque
 * como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var list<object> $sesiones */
$sesiones       = $__view['sesiones'] ?? [];
/** @var object|null $pager */
$pager          = $__view['pager'] ?? null;
/** @var array<string, string> $filtros */
$filtros        = (array) ($__view['filtros'] ?? []);
/** @var int $totalFiltrado */
$totalFiltrado  = (int) ($__view['totalFiltrado'] ?? 0);
/** @var array<string, int|string> $global */
$global         = (array) ($__view['global'] ?? []);
/** @var list<array{placa: string, nombre: string, total: int}> $porAgente */
$porAgente      = (array) ($__view['porAgente'] ?? []);
/** @var array<string, string> $estados */
$estados        = (array) ($__view['estados'] ?? []);
/** @var array<string, string> $camposOrden */
$camposOrden    = (array) ($__view['camposOrden'] ?? ['created_at' => 'Fecha de alta']);

$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');

/**
 * URL del listado con los filtros actuales, cambiando solo lo indicado.
 */
$urlSesiones = static function (array $cambios) use ($filtros): string {
    $base = [
        'estado' => (string) ($filtros['estado'] ?? ''),
        'placa'  => (string) ($filtros['placa'] ?? ''),
        'q'      => (string) ($filtros['q'] ?? ''),
        'orden'  => (string) ($filtros['orden'] ?? 'created_at'),
        'dir'    => strtolower((string) ($filtros['direccion'] ?? 'DESC')) === 'asc' ? 'asc' : 'desc',
    ];
    $limpia = array_filter(array_merge($base, $cambios), static fn ($v): bool => $v !== '' && $v !== null);

    return site_url('admin/sesiones-app' . ($limpia === [] ? '' : '?' . http_build_query($limpia)));
};

$hayFiltros = array_filter(
    ['estado', 'placa', 'q'],
    static fn (string $clave): bool => ($filtros[$clave] ?? '') !== ''
) !== [];

/**
 * Estado de una sesión calculado con la misma regla que usa el desglose:
 * revocada manda; si no, manda el vencimiento.
 */
$estadoSesion = static function (object $s): array {
    if (($s->revocado_en ?? null) !== null && (string) $s->revocado_en !== '') {
        return ['clave' => 'revocadas', 'rotulo' => 'Revocada', 'tono' => 'bg-secondary-subtle text-secondary-emphasis'];
    }

    $expira = (string) ($s->expira_en ?? '');
    if ($expira !== '' && strtotime($expira) >= time()) {
        return ['clave' => 'vigentes', 'rotulo' => 'Vigente', 'tono' => 'bg-success-subtle text-success'];
    }

    return ['clave' => 'vencidas', 'rotulo' => 'Vencida', 'tono' => 'bg-warning-subtle text-warning-emphasis'];
};
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Sesiones de la app<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="panel-titulo">
    <div>
        <h2><i class="bi bi-key" aria-hidden="true"></i> Sesiones de la app de agentes</h2>
        <p class="panel-subtitulo">Cada acceso emitido a la app móvil, su agente, su vencimiento y su último uso.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Inicio</a>
        <a href="/admin/app-transito" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-badge me-1"></i>Agentes y parámetros</a>
        <a href="/admin/desglose#transito" class="btn btn-outline-primary btn-sm"><i class="bi bi-graph-up-arrow me-1"></i>Ver en el desglose</a>
    </div>
</div>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cómo revisar los accesos móviles',
    'texto'  => 'Una sesión vigente es un acceso que todavía no caduca ni se revocó. Toca el estado para ver solo esas sesiones o la placa del agente para ver las de esa persona. El secreto del token nunca se muestra.',
]) ?>

<div class="dgs-seccion">
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Tokens en el registro',  $num((int) ($global['total'] ?? 0)),      $urlSesiones([]), 'bi-collection'],
            ['Vigentes ahora',         $num((int) ($global['vigentes'] ?? 0)),   $urlSesiones(['estado' => 'vigentes']), 'bi-shield-check'],
            ['Vencidas sin cerrar',    $num((int) ($global['vencidas'] ?? 0)),   $urlSesiones(['estado' => 'vencidas']), 'bi-hourglass-bottom'],
            ['Revocadas',              $num((int) ($global['revocadas'] ?? 0)),  $urlSesiones(['estado' => 'revocadas']), 'bi-slash-circle'],
        ] as $k): ?>
            <div class="col-6 col-lg-3">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($k[2]) ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted fw-semibold"><?= esc($k[0]) ?></small>
                        <i class="bi <?= $k[3] ?> text-primary" aria-hidden="true"></i>
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
                <a href="<?= $urlSesiones(['estado' => '']) ?>" class="dgs-nav-item <?= ($filtros['estado'] ?? '') === '' ? 'fw-bold' : '' ?>">
                    Todas <span class="text-muted"><?= $num((int) ($global['total'] ?? 0)) ?></span>
                </a>
                <?php foreach ($estados as $clave => $rotulo): ?>
                    <a href="<?= $urlSesiones(['estado' => $clave]) ?>" class="dgs-nav-item <?= ($filtros['estado'] ?? '') === $clave ? 'fw-bold' : '' ?>">
                        <?= esc($rotulo) ?>
                        <span class="text-muted"><?= $num((int) ($global[$clave] ?? 0)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($porAgente !== []): ?>
                <div class="small text-muted mt-2 mb-1">Agentes con acceso vigente ahora. Toca la placa para ver solo sus sesiones:</div>
                <div class="dgs-nav mt-0 mb-1">
                    <?php foreach ($porAgente as $a): ?>
                        <a href="<?= $urlSesiones(['placa' => (string) $a['placa']]) ?>" class="dgs-nav-item <?= ($filtros['placa'] ?? '') === (string) $a['placa'] ? 'fw-bold' : '' ?>">
                            <i class="bi bi-person-badge me-1" aria-hidden="true"></i><?= esc((string) $a['nombre']) ?>
                            <span class="font-monospace small text-muted ms-1"><?= esc((string) $a['placa']) ?></span>
                            <span class="text-muted"><?= $num((int) $a['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/sesiones-app') ?>" class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label for="q" class="form-label small fw-semibold">Placa, nombre o sector</label>
                    <input type="search" name="q" id="q" class="form-control form-control-sm" maxlength="80"
                           value="<?= esc($filtros['q'] ?? '') ?>" placeholder="AGT-305 u Oficial Pérez"
                           autocomplete="off" spellcheck="false" enterkeyhint="search">
                </div>
                <div class="col-6 col-md-3">
                    <label for="estado" class="form-label small fw-semibold">Estado</label>
                    <select name="estado" id="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($estados as $clave => $rotulo): ?>
                            <option value="<?= esc($clave) ?>" <?= ($filtros['estado'] ?? '') === $clave ? 'selected' : '' ?>><?= esc($rotulo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="placa" class="form-label small fw-semibold">Placa exacta</label>
                    <input type="text" name="placa" id="placa" class="form-control form-control-sm text-uppercase font-monospace" maxlength="20"
                           value="<?= esc($filtros['placa'] ?? '') ?>" placeholder="AGT-305"
                           autocomplete="off" autocapitalize="characters" spellcheck="false">
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search" aria-hidden="true"></i><span class="ms-1">Filtrar</span></button>
                </div>
                <?php if ($hayFiltros): ?>
                    <div class="col-12">
                        <a href="<?= site_url('admin/sesiones-app') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Quitar todos los filtros</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 gap-2 flex-wrap">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Sesiones</h2>
        <span class="small text-muted">
            <?= $num($totalFiltrado) ?> de <?= $num((int) ($global['total'] ?? 0)) ?>
            <?php if (($global['ultima_actividad'] ?? '') !== ''): ?>
                · último uso: <?= formatear_fecha((string) $global['ultima_actividad'], 'd/m/Y H:i') ?>
            <?php endif; ?>
        </span>
    </div>

    <?php if ($sesiones === []): ?>
        <div class="empty-state">
            <i class="bi bi-key" aria-hidden="true"></i>
            <div class="empty-titulo">Sin resultados</div>
            <div class="empty-texto"><?= $hayFiltros ? 'Ninguna sesión coincide con el filtro seleccionado.' : 'Ningún agente ha recibido acceso a la app todavía.' ?></div>
            <?php if ($hayFiltros): ?><a href="<?= site_url('admin/sesiones-app') ?>" class="btn btn-outline-primary btn-sm mt-2">Ver todas las sesiones</a><?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Teléfono: tarjetas apiladas; la tabla de cinco columnas no cabe -->
        <div class="d-block d-md-none">
            <?php foreach ($sesiones as $s): ?>
                <?php $est = $estadoSesion($s); ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <a class="fw-semibold small" href="/admin/app-transito/boletas?placa=<?= rawurlencode((string) ($s->placa ?? '')) ?>" title="Ver las boletas que capturó este agente">
                            <?= esc((string) ($s->nombre ?: $s->placa ?: 'Agente eliminado')) ?>
                        </a>
                        <a href="<?= $urlSesiones(['estado' => $est['clave']]) ?>" class="badge <?= $est['tono'] ?> text-decoration-none text-nowrap"><?= esc($est['rotulo']) ?></a>
                    </div>
                    <div class="small text-muted mt-1">
                        <span class="font-monospace"><?= esc((string) ($s->placa ?? '—')) ?></span>
                        <?php if (! empty($s->sector)): ?> · <?= esc((string) $s->sector) ?><?php endif; ?>
                    </div>
                    <div class="small mt-2">
                        <i class="bi bi-calendar-event me-1 text-muted" aria-hidden="true"></i>Vence: <?= formatear_fecha((string) ($s->expira_en ?? ''), 'd/m/Y H:i') ?>
                    </div>
                    <div class="small text-muted">
                        <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Último uso: <?= $s->ultimo_uso_en ? formatear_fecha((string) $s->ultimo_uso_en, 'd/m/Y H:i') : 'nunca registró actividad' ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Agente</th>
                        <th>Estado</th>
                        <th>
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? 'created_at') === 'expira_en' ? 'fw-bold' : '' ?>" href="<?= $urlSesiones(['orden' => 'expira_en', 'dir' => ($filtros['orden'] ?? '') === 'expira_en' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Vence <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                        <th>
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? '') === 'ultimo_uso_en' ? 'fw-bold' : '' ?>" href="<?= $urlSesiones(['orden' => 'ultimo_uso_en', 'dir' => ($filtros['orden'] ?? '') === 'ultimo_uso_en' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Último uso <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                        <th>
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? 'created_at') === 'created_at' ? 'fw-bold' : '' ?>" href="<?= $urlSesiones(['orden' => 'created_at', 'dir' => ($filtros['orden'] ?? 'created_at') === 'created_at' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Alta <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sesiones as $s): ?>
                        <?php $est = $estadoSesion($s); ?>
                        <tr>
                            <td class="mw-260">
                                <div class="small fw-semibold">
                                    <a class="text-decoration-none" href="/admin/app-transito/boletas?placa=<?= rawurlencode((string) ($s->placa ?? '')) ?>" title="Ver las boletas que capturó este agente"><?= esc((string) ($s->nombre ?: 'Agente eliminado')) ?></a>
                                </div>
                                <a href="<?= $urlSesiones(['placa' => (string) ($s->placa ?? '')]) ?>" class="font-monospace small text-decoration-none"><?= esc((string) ($s->placa ?? '—')) ?></a>
                                <?php if ((int) ($s->agente_activo ?? 1) === 0): ?>
                                    <span class="badge bg-light text-danger border ms-1">Agente dado de baja</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $urlSesiones(['estado' => $est['clave']]) ?>" class="badge <?= $est['tono'] ?> text-decoration-none"><?= esc($est['rotulo']) ?></a>
                                <?php if ((string) ($s->revocado_en ?? '') !== ''): ?>
                                    <div class="small text-muted"><?= formatear_fecha((string) $s->revocado_en, 'd/m/Y H:i') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-nowrap"><?= formatear_fecha((string) ($s->expira_en ?? ''), 'd/m/Y H:i') ?></td>
                            <td class="small text-nowrap text-muted"><?= $s->ultimo_uso_en ? formatear_fecha((string) $s->ultimo_uso_en, 'd/m/Y H:i') : '—' ?></td>
                            <td class="small text-nowrap text-muted"><?= formatear_fecha((string) ($s->created_at ?? ''), 'd/m/Y H:i') ?></td>
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
