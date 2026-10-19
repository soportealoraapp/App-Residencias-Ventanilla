<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\AppTransitoController::catalogo() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var list<object> $items */
$items = $__view['items'] ?? [];
/** @var float $valorUma */
$valorUma = (float) ($__view['valorUma'] ?? 0);
/** @var array{q?: string, categoria?: string, estado?: string} $filtros */
$filtros = $__view['filtros'] ?? [];
$filtros += ['q' => '', 'categoria' => '', 'estado' => ''];
/** @var array<string, int> $categorias */
$categorias = $__view['categorias'] ?? [];
/** @var array{total?: int, activas?: int, inactivas?: int} $conteos */
$conteos = $__view['conteos'] ?? [];
/** @var array<string, int> $boletasPorFalta */
$boletasPorFalta = $__view['boletasPorFalta'] ?? [];

/**
 * URL del catálogo que conserva los filtros activos.
 */
$urlCatalogo = static function (array $cambios) use ($filtros): string {
    $base = [
        'q'         => (string) $filtros['q'],
        'categoria' => (string) $filtros['categoria'],
        'estado'    => (string) $filtros['estado'],
    ];
    $limpia = array_filter(array_merge($base, $cambios), static fn ($v): bool => $v !== '' && $v !== null);

    return '/admin/app-transito/catalogo' . ($limpia === [] ? '' : '?' . http_build_query($limpia));
};

/**
 * Boletas emitidas con un fundamento concreto: el catálogo permite bajar al
 * detalle de cómo se está usando cada falta en el campo.
 */
$urlBoletasFalta = static function (string $fundamento) use ($filtros): string {
    $query = ['falta' => $fundamento];
    if ((string) $filtros['estado'] !== '') {
        $query['estado'] = (string) $filtros['estado'];
    }

    return '/admin/app-transito/boletas?' . http_build_query($query);
};

$chipsCatalogo = [
    ['etiqueta' => 'Todas las faltas', 'valor' => (int) ($conteos['total'] ?? 0),     'url' => $urlCatalogo(['estado' => '']),              'activo' => $filtros['estado'] === '',        'clase' => 'bg-primary text-white'],
    ['etiqueta' => 'Visibles en la app', 'valor' => (int) ($conteos['activas'] ?? 0), 'url' => $urlCatalogo(['estado' => 'activa']),        'activo' => $filtros['estado'] === 'activa',   'clase' => 'bg-success text-white'],
    ['etiqueta' => 'Dadas de baja',    'valor' => (int) ($conteos['inactivas'] ?? 0), 'url' => $urlCatalogo(['estado' => 'inactiva']),      'activo' => $filtros['estado'] === 'inactiva', 'clase' => 'bg-danger text-white'],
];
foreach ($categorias as $categoria => $total) {
    $chipsCatalogo[] = [
        'etiqueta' => $categoria,
        'valor'    => (int) $total,
        'url'      => $urlCatalogo(['categoria' => (string) $categoria]),
        'activo'   => $filtros['categoria'] === (string) $categoria,
        'clase'    => 'bg-dark text-white',
    ];
}
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Catálogo de Infracciones - App Agentes<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/app-transito" class="text-decoration-none text-muted small">&larr; Volver a Configuración</a>
                <span class="text-muted small">/</span>
                <span class="badge bg-warning-subtle text-warning">Reglamento Uriangato</span>
            </div>
            <h2 class="h3 fw-bold mt-1 mb-0 text-dark">Catálogo Oficial de Infracciones</h2>
            <p class="text-muted small mb-0">Faltas viales disponibles en el catálogo de la app móvil para emisión de boletas en campo. Cada monto enlaza las boletas reales que se cobraron con esa falta.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border p-2">
                <i class="bi bi-currency-dollar me-1 text-primary"></i> 1 UMA = <strong>$<?= number_format($valorUma, 2) ?> MXN</strong>
            </span>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center shadow-sm"
                data-bs-toggle="modal" data-bs-target="#modalNuevaFalta">
                <i class="bi bi-plus-circle me-2"></i> + Nueva Falta
            </button>
        </div>
    </div>

    <div class="dgs-nav" role="navigation" aria-label="Filtros rápidos del catálogo de faltas">
        <?php foreach ($chipsCatalogo as $c): ?>
            <a class="dgs-nav-item <?= $c['activo'] ? $c['clase'] . ' border-0' : '' ?>" href="<?= esc($c['url']) ?>" <?= $c['activo'] ? 'aria-current="page"' : '' ?>>
                <?= esc($c['etiqueta']) ?>
                <span class="badge <?= $c['activo'] ? 'bg-white text-dark' : 'bg-light text-dark border' ?>"><?= (int) $c['valor'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <form method="get" action="/admin/app-transito/catalogo" class="row g-2 align-items-center mb-3">
        <input type="hidden" name="estado" value="<?= esc($filtros['estado']) ?>">
        <input type="hidden" name="categoria" value="<?= esc($filtros['categoria']) ?>">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" class="form-control border-start-0" name="q" value="<?= esc($filtros['q']) ?>" placeholder="Buscar artículo o descripción..." aria-label="Buscar en el catálogo">
            </div>
        </div>
        <div class="col-6 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary btn-sm">Filtrar</button>
            <?php if ($filtros['q'] !== '' || $filtros['categoria'] !== '' || $filtros['estado'] !== ''): ?>
                <a href="/admin/app-transito/catalogo" class="btn btn-outline-secondary btn-sm" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>

    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th class="ps-4">Fundamento Legal</th>
                            <th>Descripción Oficial</th>
                            <th>Categoría</th>
                            <th>Monto en UMA</th>
                            <th>Equivalente en Pesos</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-4">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="bi bi-journal-x" aria-hidden="true"></i>
                                        <div class="empty-titulo">Sin faltas en este filtro</div>
                                        <div class="empty-texto">Ajusta la búsqueda o <a href="/admin/app-transito/catalogo">ver el catálogo completo</a>.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $minPesos = (float)$item->monto_min_uma * $valorUma;
                                    $maxPesos = (float)$item->monto_max_uma * $valorUma;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <a class="fw-bold font-monospace text-primary text-decoration-none" href="<?= esc($urlBoletasFalta((string) $item->fundamento_legal)) ?>" title="Ver las boletas emitidas con este fundamento"><?= esc($item->fundamento_legal) ?></a>
                                        <?php $usadas = (int) ($boletasPorFalta[(string) $item->fundamento_legal] ?? 0); ?>
                                        <?php if ($usadas > 0): ?>
                                            <div class="small text-muted"><?= $usadas ?> boleta<?= $usadas === 1 ? '' : 's' ?></div>
                                        <?php else: ?>
                                            <div class="small text-muted">sin uso en campo</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small" style="max-width: 380px;">
                                            <?= esc($item->descripcion) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a class="badge bg-light text-secondary border small text-decoration-none" href="<?= esc($urlCatalogo(['categoria' => (string) $item->categoria])) ?>" title="Ver todas las faltas de esta categoría">
                                            <?= esc($item->categoria) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small">
                                            <?= number_format((float)$item->monto_min_uma, 1) ?> - <?= number_format((float)$item->monto_max_uma, 1) ?>
                                        </div>
                                        <small class="text-muted">UMAs</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success small">
                                            $<?= number_format($minPesos, 2) ?> - $<?= number_format($maxPesos, 2) ?>
                                        </div>
                                        <small class="text-muted">MXN</small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($item->activo == 1): ?>
                                            <a class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 text-decoration-none" href="<?= esc($urlCatalogo(['estado' => 'activa'])) ?>">Activo</a>
                                        <?php else: ?>
                                            <a class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 text-decoration-none" href="<?= esc($urlCatalogo(['estado' => 'inactiva'])) ?>">Inactivo</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4 text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                            onclick="editarFalta(<?= esc(json_encode($item), 'attr') ?>)">
                                            Editar
                                        </button>
                                        <?php if (in_array('administrador', (array) (session('roles') ?? []), true)): ?>
                                            <form action="/admin/app-transito/catalogo/eliminar/<?= (int) $item->id ?>" method="POST" class="d-inline" data-no-loading="true"
                                                onsubmit="return confirm('¿Eliminar la falta <?= esc($item->fundamento_legal, 'js') ?> del catálogo? Las boletas ya emitidas no se modifican. Si solo quieres ocultarla en la app, edítala y desactívala.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Fichas para el teléfono: la tabla de siete columnas no cabe en pantalla -->
            <div class="d-block d-md-none">
                <?php if (empty($items)): ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x" aria-hidden="true"></i>
                        <div class="empty-titulo">Sin faltas en este filtro</div>
                        <div class="empty-texto">Ajusta la búsqueda o <a href="/admin/app-transito/catalogo">ver el catálogo completo</a>.</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php
                            $minPesos = (float) $item->monto_min_uma * $valorUma;
                            $maxPesos = (float) $item->monto_max_uma * $valorUma;
                            $usadas   = (int) ($boletasPorFalta[(string) $item->fundamento_legal] ?? 0);
                        ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <a class="fw-bold font-monospace text-primary text-decoration-none" href="<?= esc($urlBoletasFalta((string) $item->fundamento_legal)) ?>"><?= esc($item->fundamento_legal) ?></a>
                                <?php if ($item->activo == 1): ?>
                                    <a class="badge text-decoration-none bg-success-subtle text-success border border-success-subtle rounded-pill flex-shrink-0" href="<?= esc($urlCatalogo(['estado' => 'activa'])) ?>">Visible</a>
                                <?php else: ?>
                                    <a class="badge text-decoration-none bg-danger-subtle text-danger border border-danger-subtle rounded-pill flex-shrink-0" href="<?= esc($urlCatalogo(['estado' => 'inactiva'])) ?>">Dada de baja</a>
                                <?php endif; ?>
                            </div>
                            <p class="small text-dark mb-2"><?= esc($item->descripcion) ?></p>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <a class="badge bg-light text-secondary border small text-decoration-none" href="<?= esc($urlCatalogo(['categoria' => (string) $item->categoria])) ?>"><?= esc($item->categoria) ?></a>
                                <span class="small text-muted"><?= number_format((float) $item->monto_min_uma, 1) ?>–<?= number_format((float) $item->monto_max_uma, 1) ?> UMA</span>
                                <span class="fw-bold text-success small">$<?= number_format($minPesos, 2) ?> – $<?= number_format($maxPesos, 2) ?></span>
                            </div>
                            <div class="small text-muted mt-1">
                                <i class="bi bi-receipt me-1"></i><?= $usadas > 0 ? $usadas . ' boleta' . ($usadas === 1 ? '' : 's') . ' emitida' . ($usadas === 1 ? '' : 's') : 'sin uso en campo' ?>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill flex-fill"
                                    onclick="editarFalta(<?= esc(json_encode($item), 'attr') ?>)">
                                    Editar falta
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVA/EDITAR FALTA -->
<div class="modal fade" id="modalNuevaFalta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalFaltaTitulo">Registrar Falta en Catálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/app-transito/catalogo/guardar" method="POST" id="formFalta">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="falta_id" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="fundamento_legal" class="form-label small fw-semibold">Fundamento Legal (Artículo) <span class="text-danger">*</span></label>
                        <input autocomplete="off" autocapitalize="words" spellcheck="false" type="text" class="form-control" id="fundamento_legal" name="fundamento_legal" placeholder="Ej. Art. 39 Frac. IX" required>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label small fw-semibold">Descripción Oficial <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Texto descriptivo de la infracción" required></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="categoria" class="form-label small fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <input autocomplete="off" autocapitalize="words" spellcheck="false" type="text" class="form-control text-uppercase" id="categoria" name="categoria" placeholder="Ej. MANEJO Y VIALIDAD" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="orden" class="form-label small fw-semibold">Orden de Despliegue</label>
                            <input inputmode="numeric" type="number" class="form-control" id="orden" name="orden" value="0">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="monto_min_uma" class="form-label small fw-semibold">Monto Mínimo (UMAs) <span class="text-danger">*</span></label>
                            <input inputmode="decimal" type="number" step="0.1" min="0" class="form-control" id="monto_min_uma" name="monto_min_uma" required>
                        </div>
                        <div class="col-sm-6">
                            <label for="monto_max_uma" class="form-label small fw-semibold">Monto Máximo (UMAs) <span class="text-danger">*</span></label>
                            <input inputmode="decimal" type="number" step="0.1" min="0" class="form-control" id="monto_max_uma" name="monto_max_uma" required>
                        </div>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" role="switch" id="activo" name="activo" value="1" checked>
                        <label class="form-check-label small fw-semibold" for="activo">Falta Activa en la App Móvil</label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Guardar Infracción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarFalta(item) {
    document.getElementById('modalFaltaTitulo').innerText = 'Editar Infracción';
    document.getElementById('falta_id').value = item.id;
    document.getElementById('fundamento_legal').value = item.fundamento_legal;
    document.getElementById('descripcion').value = item.descripcion;
    document.getElementById('categoria').value = item.categoria;
    document.getElementById('monto_min_uma').value = item.monto_min_uma;
    document.getElementById('monto_max_uma').value = item.monto_max_uma;
    document.getElementById('orden').value = item.orden ?? 0;
    document.getElementById('activo').checked = item.activo == 1;

    const modal = new bootstrap.Modal(document.getElementById('modalNuevaFalta'));
    modal.show();
}

document.getElementById('modalNuevaFalta')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalFaltaTitulo').innerText = 'Registrar Falta en Catálogo';
    document.getElementById('formFalta').reset();
    document.getElementById('falta_id').value = '';
    document.getElementById('activo').checked = true;
});
</script>

<?= $this->endSection() ?>
