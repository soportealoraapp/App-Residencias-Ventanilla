<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\Catalogos\TarifasController::index() vía
 * view(..., $data). Se leen desde get_defined_vars() para que Intelephense
 * no las marque como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<int, string> $tramites */
$tramites = $__view['tramites'] ?? [];

/** @var array{tramite?: string, referencia?: string} $filtros */
$filtros = $__view['filtros'] ?? ['tramite' => '', 'referencia' => ''];
$filtros += ['tramite' => '', 'referencia' => ''];

/** @var array<int, object> $tarifas */
$tarifas = $__view['tarifas'] ?? [];

/** @var array{total?: int, por_tramite?: array<string, int>, no_oficiales?: int} $conteos */
$conteos = $__view['conteos'] ?? [];

/**
 * URL de listado que conserva los filtros activos: tocar un chip o una etiqueta
 * del catálogo nunca hace perder el filtro en curso.
 */
$urlTarifas = static function (array $cambios) use ($filtros): string {
    $mezcla = array_merge(['tramite' => $filtros['tramite'], 'referencia' => $filtros['referencia']], $cambios);
    $limpia = array_filter($mezcla, static fn ($v): bool => $v !== '' && $v !== null);

    return '/admin/tarifas' . ($limpia === [] ? '' : '?' . http_build_query($limpia));
};

$porTramite   = (array) ($conteos['por_tramite'] ?? []);
$totalTarifas = (int) ($conteos['total'] ?? count($tarifas));
$noOficiales  = (int) ($conteos['no_oficiales'] ?? 0);

$chipsTarifas = [
    [
        'etiqueta' => 'Todas las tarifas',
        'valor'    => $totalTarifas,
        'url'      => $urlTarifas(['tramite' => '', 'referencia' => '']),
        'activo'   => $filtros['tramite'] === '' && $filtros['referencia'] === '',
        'clase'    => 'bg-primary text-white',
    ],
];
foreach ($tramites as $t) {
    $chipsTarifas[] = [
        'etiqueta' => $t,
        'valor'    => (int) ($porTramite[$t] ?? 0),
        'url'      => $urlTarifas(['tramite' => $t]),
        'activo'   => $filtros['tramite'] === $t,
        'clase'    => 'bg-dark text-white',
    ];
}
$chipsTarifas[] = [
    'etiqueta' => 'Montos de referencia',
    'valor'    => $noOficiales,
    'url'      => $urlTarifas(['referencia' => 'no_oficial']),
    'activo'   => $filtros['referencia'] === 'no_oficial',
    'clase'    => 'bg-warning text-dark',
];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Catálogo de Tarifas<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Tarifario de trámites',
    'texto'  => 'Define el costo oficial vigente por trámite. Los montos se aplican automáticamente a las nuevas solicitudes según el tarifario activo.',
]) ?>

<div class="row mb-3 g-2">
    <div class="col-lg-8">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="referencia" value="<?= esc($filtros['referencia']) ?>">
            <div class="col-md-6">
                <label class="form-label small text-muted mb-1" for="tramite">Filtrar por trámite</label>
                <select id="tramite" name="tramite" class="form-select form-select-sm">
                    <option value="">Todos los trámites</option>
                    <?php foreach ($tramites as $t): ?>
                        <option value="<?= esc($t) ?>" <?= $filtros['tramite'] === $t ? 'selected' : '' ?>>
                            <?= esc($t) ?> - <?= esc(tramite_nombre($t)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-nowrap">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-funnel me-1"></i> Filtrar</button>
                <?php if ($filtros['tramite'] !== '' || $filtros['referencia'] !== ''): ?>
                    <a href="/admin/tarifas" class="btn btn-outline-secondary btn-sm" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="col-lg-4 text-lg-end align-self-end">
        <a href="/admin/tarifas/nuevo" class="btn btn-success btn-sm w-100 w-lg-auto"><i class="bi bi-plus-lg me-1"></i> Nueva tarifa</a>
    </div>
</div>

<div class="dgs-nav" role="navigation" aria-label="Filtros rápidos del tarifario">
    <?php foreach ($chipsTarifas as $c): ?>
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
                        <th>Id</th>
                        <th>Trámite</th>
                        <th>Criterio</th>
                        <th>Monto</th>
                        <th>Vigente desde</th>
                        <th>Vigente hasta</th>
                        <th>Oficialidad</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tarifas)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-cash-stack" aria-hidden="true"></i>
                                    <div class="empty-titulo">Sin tarifas en este filtro</div>
                                    <div class="empty-texto">Ajusta los filtros o <a href="/admin/tarifas/nuevo">captura una nueva tarifa</a>.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tarifas as $tarifa): ?>
                            <tr>
                                <td class="fw-semibold"><a class="text-decoration-none" href="/admin/tarifas/editar/<?= (int) $tarifa->id ?>">#<?= (int) $tarifa->id ?></a></td>
                                <td>
                                    <a class="badge text-decoration-none bg-primary-subtle text-primary" href="<?= esc($urlTarifas(['tramite' => (string) $tarifa->tramite])) ?>" title="Ver todas las tarifas de este trámite"><?= esc($tarifa->tramite) ?></a>
                                    <div class="small text-muted"><?= esc(tramite_nombre($tarifa->tramite)) ?></div>
                                </td>
                                <td>
                                    <code class="text-dark"><?= esc($tarifa->criterio) ?></code>
                                    <?php if (!empty($tarifa->descripcion)): ?>
                                        <div class="small text-muted"><?= esc($tarifa->descripcion) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap fw-bold text-success"><?= formatear_dinero((float)$tarifa->monto) ?></td>
                                <td class="text-nowrap"><?= formatear_fecha($tarifa->vigente_desde, 'd/m/Y') ?></td>
                                <td class="text-nowrap">
                                    <?php if ($tarifa->vigente_hasta === null || $tarifa->vigente_hasta === ''): ?>
                                        <span class="badge bg-success">Vigente</span>
                                    <?php else: ?>
                                        <?= formatear_fecha($tarifa->vigente_hasta, 'd/m/Y') ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $tarifa->placeholder_oficial === 1): ?>
                                        <a class="badge text-decoration-none bg-warning text-dark" href="<?= esc($urlTarifas(['referencia' => 'no_oficial'])) ?>" title="Ver todos los montos de referencia">NO OFICIAL</a>
                                    <?php else: ?>
                                        <a class="badge text-decoration-none bg-success" href="<?= esc($urlTarifas(['referencia' => 'oficial'])) ?>" title="Ver todos los montos verificados">Verificado</a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="/admin/tarifas/editar/<?= (int) $tarifa->id ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Editar tarifa" aria-label="Editar tarifa"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" data-bs-title="Eliminar tarifa" aria-label="Eliminar tarifa" onclick="confirmarEliminar(<?= (int) $tarifa->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    <form id="form-eliminar-<?= (int) $tarifa->id ?>" action="/admin/tarifas/eliminar/<?= (int) $tarifa->id ?>" method="post" class="d-none"></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Fichas para el teléfono: la tabla de 8 columnas no cabe en pantalla -->
        <div class="d-block d-md-none">
            <?php if (empty($tarifas)): ?>
                <div class="empty-state">
                    <i class="bi bi-cash-stack" aria-hidden="true"></i>
                    <div class="empty-titulo">Sin tarifas en este filtro</div>
                    <div class="empty-texto">Ajusta los filtros o <a href="/admin/tarifas/nuevo">captura una nueva tarifa</a>.</div>
                </div>
            <?php else: ?>
                <?php foreach ($tarifas as $tarifa): ?>
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="overflow-hidden">
                                <a class="badge text-decoration-none bg-primary-subtle text-primary" href="<?= esc($urlTarifas(['tramite' => (string) $tarifa->tramite])) ?>"><?= esc($tarifa->tramite) ?></a>
                                <div class="small text-muted"><?= esc(tramite_nombre($tarifa->tramite)) ?></div>
                            </div>
                            <span class="fs-5 fw-bold text-success flex-shrink-0"><?= formatear_dinero((float) $tarifa->monto) ?></span>
                        </div>
                        <div class="mt-2">
                            <code class="text-dark"><?= esc($tarifa->criterio) ?></code>
                            <?php if (!empty($tarifa->descripcion)): ?>
                                <div class="small text-muted"><?= esc($tarifa->descripcion) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted mt-2">
                            <span><i class="bi bi-calendar-event me-1"></i>Desde <?= formatear_fecha($tarifa->vigente_desde, 'd/m/Y') ?></span>
                            <?php if ($tarifa->vigente_hasta === null || $tarifa->vigente_hasta === ''): ?>
                                <span class="badge bg-success">Vigente</span>
                            <?php else: ?>
                                <span>Hasta <?= formatear_fecha($tarifa->vigente_hasta, 'd/m/Y') ?></span>
                            <?php endif; ?>
                            <?php if ((int) $tarifa->placeholder_oficial === 1): ?>
                                <a class="badge text-decoration-none bg-warning text-dark" href="<?= esc($urlTarifas(['referencia' => 'no_oficial'])) ?>">NO OFICIAL</a>
                            <?php else: ?>
                                <a class="badge text-decoration-none bg-success" href="<?= esc($urlTarifas(['referencia' => 'oficial'])) ?>">Verificado</a>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="/admin/tarifas/editar/<?= (int) $tarifa->id ?>" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-pencil me-1"></i>Editar tarifa #<?= (int) $tarifa->id ?></a>
                            <button type="button" class="btn btn-outline-danger btn-sm" aria-label="Eliminar tarifa" onclick="confirmarEliminar(<?= (int) $tarifa->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de eliminar esta tarifa? No afectará solicitudes ya procesadas.')) {
        document.getElementById('form-eliminar-' + id).submit();
    }
}
</script>

<?= $this->endSection() ?>
