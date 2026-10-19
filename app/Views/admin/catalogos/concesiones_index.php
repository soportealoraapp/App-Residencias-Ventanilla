<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\CatalogoConcesionesController::index() vía
 * view(..., $data). Se leen desde get_defined_vars() para que Intelephense no
 * las marque como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<int, object> $concesiones */
$concesiones = $__view['concesiones'] ?? [];
/** @var array{q?: string, estatus?: string} $filtros */
$filtros = $__view['filtros'] ?? [];
$filtros += ['q' => '', 'estatus' => ''];
/** @var array<string, string> $estatusOpciones */
$estatusOpciones = $__view['estatusOpciones'] ?? [
    ''               => 'Todos',
    'vigente'        => 'Vigente',
    'vencida'        => 'Vencida',
    'en_transmision' => 'En trámite de transmisión',
];
/** @var array{total?: int, por_estatus?: array<string, int>} $conteos */
$conteos = $__view['conteos'] ?? [];
/** @var object|null $pager */
$pager = $__view['pager'] ?? null;

/**
 * URL del padrón que conserva la búsqueda activa: tocar un chip o una etiqueta
 * nunca hace perder el filtro en curso.
 */
$urlConcesiones = static function (array $cambios) use ($filtros): string {
    $mezcla = array_merge(['q' => $filtros['q'], 'estatus' => $filtros['estatus']], $cambios);
    $limpia = array_filter($mezcla, static fn ($v): bool => $v !== '' && $v !== null);

    return '/admin/concesiones' . ($limpia === [] ? '' : '?' . http_build_query($limpia));
};

$porEstatus = (array) ($conteos['por_estatus'] ?? []);
$chipsConcesiones = [
    ['etiqueta' => 'Todo el padrón', 'valor' => (int) ($conteos['total'] ?? 0), 'url' => $urlConcesiones(['estatus' => '']), 'activo' => $filtros['estatus'] === '', 'clase' => 'bg-primary text-white'],
];
foreach ($estatusOpciones as $valor => $etiqueta) {
    if ($valor === '') {
        continue;
    }
    $chipsConcesiones[] = [
        'etiqueta' => $etiqueta,
        'valor'    => (int) ($porEstatus[$valor] ?? 0),
        'url'      => $urlConcesiones(['estatus' => $valor]),
        'activo'   => $filtros['estatus'] === $valor,
        'clase'    => match ($valor) {
            'vigente'        => 'bg-success text-white',
            'vencida'        => 'bg-secondary text-white',
            default          => 'bg-warning text-dark',
        },
    ];
}
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Catálogo de Concesiones<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Concesiones de transporte',
    'texto'  => 'Consulta el padrón de concesiones: ruta, vigencia y estado. Desde aquí se da seguimiento a las concesiones vigentes, vencidas y en transmisión.',
]) ?>

<div class="row mb-3 g-2">
    <div class="col-lg-8">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Buscar</label>
                <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" name="q" class="form-control form-control-sm"
                    value="<?= esc($filtros['q']) ?>"
                    placeholder="Núm. título, titular, placas...">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Estatus</label>
                <select name="estatus" class="form-select form-select-sm">
                    <?php foreach ($estatusOpciones as $valor => $etiqueta): ?>
                        <option value="<?= esc($valor) ?>" <?= $filtros['estatus'] === $valor ? 'selected' : '' ?>>
                            <?= esc($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-nowrap">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search me-1"></i> Buscar</button>
                <?php if ($filtros['q'] !== '' || $filtros['estatus'] !== ''): ?>
                    <a href="/admin/concesiones" class="btn btn-outline-secondary btn-sm" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="col-lg-4 text-lg-end align-self-end">
        <a href="/admin/concesiones/nuevo" class="btn btn-success btn-sm w-100 w-lg-auto"><i class="bi bi-plus-lg me-1"></i> Nueva concesión</a>
    </div>
</div>

<div class="dgs-nav" role="navigation" aria-label="Filtros rápidos del padrón de concesiones">
    <?php foreach ($chipsConcesiones as $c): ?>
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
                        <th>Número título</th>
                        <th>Titular actual</th>
                        <th>Tipo persona</th>
                        <th>Placas</th>
                        <th>Núm. Serie</th>
                        <th>Vigencia inicio</th>
                        <th>Vigencia fin</th>
                        <th>Estatus</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($concesiones)): ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="bi bi-card-list" aria-hidden="true"></i>
                                    <div class="empty-titulo">Sin concesiones en este filtro</div>
                                    <div class="empty-texto">Revisa la búsqueda o <a href="/admin/concesiones/nuevo">captura una nueva concesión</a>.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($concesiones as $c): ?>
                            <tr>
                                <td class="fw-semibold"><a class="text-decoration-none" href="/admin/concesiones/editar/<?= (int) $c->id ?>">#<?= (int) $c->id ?></a></td>
                                <td><a class="text-decoration-none" href="/admin/concesiones/editar/<?= (int) $c->id ?>"><code class="text-dark"><?= esc($c->numero_titulo) ?></code></a></td>
                                <td><?= esc($c->titular_actual) ?></td>
                                <td>
                                    <?php if (!empty($c->tipo_persona)): ?>
                                        <span class="badge <?= $c->tipo_persona === 'fisica' ? 'bg-secondary' : 'bg-dark' ?>">
                                            <?= esc($c->tipo_persona === 'fisica' ? 'Física' : 'Moral') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($c->vehiculo_placas)): ?>
                                        <a class="badge text-decoration-none bg-info-subtle text-info" href="<?= esc($urlConcesiones(['q' => (string) $c->vehiculo_placas])) ?>" title="Buscar esta concesión por placas"><?= esc($c->vehiculo_placas) ?></a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= !empty($c->vehiculo_num_serie) ? esc($c->vehiculo_num_serie) : '-' ?>
                                </td>
                                <td class="text-nowrap"><?= formatear_fecha($c->vigencia_inicio, 'd/m/Y') ?></td>
                                <td class="text-nowrap"><?= formatear_fecha($c->vigencia_fin, 'd/m/Y') ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match($c->estatus) {
                                        'vigente'        => 'bg-success',
                                        'vencida'        => 'bg-secondary',
                                        'en_transmision' => 'bg-warning text-dark',
                                        default          => 'bg-light text-dark',
                                    };
                                    $estatusLabel = match($c->estatus) {
                                        'vigente'        => 'Vigente',
                                        'vencida'        => 'Vencida',
                                        'en_transmision' => 'En transmisión',
                                        default          => $c->estatus,
                                    };
                                    ?>
                                    <a class="badge text-decoration-none <?= $badgeClass ?>" href="<?= esc($urlConcesiones(['estatus' => (string) $c->estatus])) ?>" title="Ver todas las concesiones <?= esc($estatusLabel, 'attr') ?>"><?= esc($estatusLabel) ?></a>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="/admin/concesiones/editar/<?= (int) $c->id ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Editar concesión" aria-label="Editar concesión"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" data-bs-title="Eliminar concesión" aria-label="Eliminar concesión" onclick="confirmarEliminar(<?= (int) $c->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    <form id="form-eliminar-<?= (int) $c->id ?>" action="/admin/concesiones/eliminar/<?= (int) $c->id ?>" method="post" class="d-none"></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Fichas para el teléfono: la tabla de 10 columnas no cabe en pantalla -->
        <div class="d-block d-md-none">
            <?php if (empty($concesiones)): ?>
                <div class="empty-state">
                    <i class="bi bi-card-list" aria-hidden="true"></i>
                    <div class="empty-titulo">Sin concesiones en este filtro</div>
                    <div class="empty-texto">Revisa la búsqueda o <a href="/admin/concesiones/nuevo">captura una nueva concesión</a>.</div>
                </div>
            <?php else: ?>
                <?php foreach ($concesiones as $c): ?>
                    <?php
                    $badgeClass = match ($c->estatus) {
                        'vigente'        => 'bg-success',
                        'vencida'        => 'bg-secondary',
                        'en_transmision' => 'bg-warning text-dark',
                        default          => 'bg-light text-dark',
                    };
                    $estatusLabel = match ($c->estatus) {
                        'vigente'        => 'Vigente',
                        'vencida'        => 'Vencida',
                        'en_transmision' => 'En transmisión',
                        default          => $c->estatus,
                    };
                    ?>
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="overflow-hidden">
                                <a class="fw-semibold text-decoration-none" href="/admin/concesiones/editar/<?= (int) $c->id ?>"><?= esc($c->numero_titulo) ?></a>
                                <div class="small text-muted"><?= esc($c->titular_actual) ?> · #<?= (int) $c->id ?></div>
                            </div>
                            <a class="badge text-decoration-none <?= $badgeClass ?> flex-shrink-0" href="<?= esc($urlConcesiones(['estatus' => (string) $c->estatus])) ?>"><?= esc($estatusLabel) ?></a>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 small mt-2">
                            <?php if (!empty($c->vehiculo_placas)): ?>
                                <a class="badge text-decoration-none bg-info-subtle text-info" href="<?= esc($urlConcesiones(['q' => (string) $c->vehiculo_placas])) ?>"><?= esc($c->vehiculo_placas) ?></a>
                            <?php endif; ?>
                            <?php if (!empty($c->tipo_persona)): ?>
                                <span class="badge bg-<?= $c->tipo_persona === 'fisica' ? 'secondary' : 'dark' ?>"><?= $c->tipo_persona === 'fisica' ? 'Física' : 'Moral' ?></span>
                            <?php endif; ?>
                            <span class="text-muted"><?= formatear_fecha($c->vigencia_inicio, 'd/m/Y') ?> → <?= formatear_fecha($c->vigencia_fin, 'd/m/Y') ?></span>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="/admin/concesiones/editar/<?= (int) $c->id ?>" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-pencil me-1"></i>Editar</a>
                            <button type="button" class="btn btn-outline-danger btn-sm" aria-label="Eliminar concesión" onclick="confirmarEliminar(<?= (int) $c->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
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

<script>
function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de eliminar esta concesión? Los cambios se registrarán en auditoría y no afectarán solicitudes ya creadas.')) {
        document.getElementById('form-eliminar-' + id).submit();
    }
}
</script>

<?= $this->endSection() ?>
