<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\ConvocatoriasController::index() vía
 * view(..., $data). Se leen desde get_defined_vars() para que Intelephense no
 * las marque como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<int, object> $convocatorias */
$convocatorias = $__view['convocatorias'] ?? [];
/** @var int $total */
$total = (int) ($__view['total'] ?? 0);
/** @var array<int, int> $postulaciones id de convocatoria => postulaciones */
$postulaciones = $__view['postulaciones'] ?? [];
/** @var array{filtro?: string, q?: string} $filtros */
$filtros = $__view['filtros'] ?? [];
$filtros += ['filtro' => '', 'q' => ''];
/** @var array<string, int> $conteos */
$conteos = $__view['conteos'] ?? [];
/** @var object|null $paginador */
$paginador = $__view['paginador'] ?? null;

$opcionesFiltro = \App\Models\ConvocatoriaModel::FILTROS;

/**
 * URL del listado que conserva la búsqueda activa: tocar un chip o una etiqueta
 * nunca hace perder el filtro en curso.
 */
$urlConvocatorias = static function (array $cambios) use ($filtros): string {
    $mezcla = array_merge(['filtro' => $filtros['filtro'], 'q' => $filtros['q']], $cambios);
    $limpia = array_filter($mezcla, static fn ($v): bool => $v !== '' && $v !== null);

    return '/admin/convocatorias' . ($limpia === [] ? '' : '?' . http_build_query($limpia));
};

$chips = [
    ['valor' => 'total', 'etiqueta' => 'Todas las convocatorias', 'clase' => 'bg-primary text-white'],
    ['valor' => 'abiertas',  'etiqueta' => 'Abiertas al registro', 'clase' => 'bg-success text-white'],
    ['valor' => 'por_abrir', 'etiqueta' => 'Por abrir',            'clase' => 'bg-info text-white'],
    ['valor' => 'cerradas',  'etiqueta' => 'Cerradas',             'clase' => 'bg-secondary text-white'],
    ['valor' => 'anuladas',  'etiqueta' => 'Anuladas',             'clase' => 'bg-danger text-white'],
];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Convocatorias UR-01<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Convocatorias de otorgamiento de concesiones',
    'texto'  => 'Una convocatoria abre una ventanilla para que varios ciudadanos postulen su expediente de concesión (UR-01) y el Ayuntamiento designe un ganador. Cree la convocatoria con su periodo de registro y sus bases oficiales; cuando esté Vigente y en fecha, aparecerá en el portal ciudadano.',
]) ?>

<div class="row mb-3 g-2">
    <div class="col-lg-8">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1">Buscar</label>
                <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" name="q" class="form-control form-control-sm"
                    value="<?= esc($filtros['q']) ?>"
                    placeholder="Título o texto de las bases...">
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Ventana</label>
                <select name="filtro" class="form-select form-select-sm">
                    <?php foreach ($opcionesFiltro as $valor => $etiqueta): ?>
                        <option value="<?= esc($valor) ?>" <?= $filtros['filtro'] === $valor ? 'selected' : '' ?>>
                            <?= esc($etiqueta) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 text-nowrap">
                <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search me-1"></i> Buscar</button>
                <?php if ($filtros['q'] !== '' || $filtros['filtro'] !== ''): ?>
                    <a href="/admin/convocatorias" class="btn btn-outline-secondary btn-sm" aria-label="Limpiar filtros"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="col-lg-4 text-lg-end align-self-end">
        <a href="/admin/convocatorias/nueva" class="btn btn-success btn-sm w-100 w-lg-auto"><i class="bi bi-plus-lg me-1"></i> Nueva convocatoria</a>
    </div>
</div>

<div class="dgs-nav" role="navigation" aria-label="Filtros rápidos de convocatorias">
    <?php foreach ($chips as $c): ?>
        <?php $activo = $filtros['filtro'] === ($c['valor'] === 'total' ? '' : $c['valor']); ?>
        <a class="dgs-nav-item <?= $activo ? $c['clase'] . ' border-0' : '' ?>"
            href="<?= esc($urlConvocatorias(['filtro' => $c['valor'] === 'total' ? '' : $c['valor']])) ?>"
            <?= $activo ? 'aria-current="page"' : '' ?>>
            <?= esc($c['etiqueta']) ?>
            <span class="badge <?= $activo ? 'bg-white text-dark' : 'bg-light text-dark border' ?>"><?= (int) ($conteos[$c['valor']] ?? 0) ?></span>
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
                        <th>Título</th>
                        <th>Publicación</th>
                        <th>Periodo de registro</th>
                        <th>Situación</th>
                        <th class="text-end">Postulaciones</th>
                        <th>Bases</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($convocatorias)): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-award" aria-hidden="true"></i>
                                    <div class="empty-titulo">No hay convocatorias en este filtro</div>
                                    <div class="empty-texto">
                                        <?php if ($total === 0): ?>
                                            Todavía no se ha publicado ninguna convocatoria de otorgamiento.
                                        <?php else: ?>
                                            Hay <?= (int) $total ?> convocatoria(s) en otras ventanas; cambie el filtro o <a href="/admin/convocatorias">ver todas</a>.
                                        <?php endif; ?>
                                        <a href="/admin/convocatorias/nueva">Cree la primera convocatoria</a> con su periodo de registro y sus bases oficiales.
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($convocatorias as $c): ?>
                            <?php
                            $ventana = \App\Models\ConvocatoriaModel::etiquetaVentana($c);
                            $postulacionesNumero = (int) ($postulaciones[(int) $c->id] ?? 0);
                            $evaluacionUrl = '/admin/convocatorias/' . (int) $c->id . '/evaluacion';
                            ?>
                            <tr>
                                <td class="fw-semibold"><a class="text-decoration-none" href="<?= esc($evaluacionUrl) ?>">#<?= (int) $c->id ?></a></td>
                                <td>
                                    <a class="text-decoration-none fw-semibold" href="<?= esc($evaluacionUrl) ?>"><?= esc($c->titulo ?: 'Convocatoria sin título') ?></a>
                                </td>
                                <td class="text-nowrap"><?= formatear_fecha($c->fecha_publicacion, 'd/m/Y') ?></td>
                                <td class="text-nowrap small">
                                    <?= formatear_fecha($c->periodo_registro_inicio, 'd/m/Y') ?>
                                    <span class="text-muted">→</span>
                                    <?= formatear_fecha($c->periodo_registro_fin, 'd/m/Y') ?>
                                </td>
                                <td>
                                    <a class="badge text-decoration-none bg-<?= esc($ventana['tono']) ?> text-white"
                                        href="<?= esc($urlConvocatorias(['filtro' => match ($ventana['etiqueta']) {
                                            'Abierta al registro' => 'abiertas',
                                            'Por abrir'           => 'por_abrir',
                                            'Registro cerrado'    => 'cerradas',
                                            'Anulada'             => 'anuladas',
                                            default               => '',
                                        }])) ?>"
                                        title="Estatus registrado: <?= esc($c->estatus) ?>"><?= esc($ventana['etiqueta']) ?></a>
                                </td>
                                <td class="text-end">
                                    <a class="badge text-decoration-none <?= $postulacionesNumero > 0 ? 'bg-primary text-white' : 'bg-light text-dark border' ?>"
                                        href="<?= esc($evaluacionUrl) ?>" title="Ver los expedientes postulados"><?= $postulacionesNumero ?></a>
                                </td>
                                <td>
                                    <?php if (! empty($c->bases_archivo)): ?>
                                        <a class="badge text-decoration-none bg-danger-subtle text-danger" href="/admin/convocatorias/<?= (int) $c->id ?>/bases" target="_blank" rel="noopener" title="<?= esc($c->bases_nombre) ?>">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Solo texto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= esc($evaluacionUrl) ?>" class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Evaluar y seleccionar ganador" aria-label="Evaluar convocatoria"><i class="bi bi-people" aria-hidden="true"></i></a>
                                    <a href="/admin/convocatorias/editar/<?= (int) $c->id ?>" class="btn btn-outline-secondary btn-sm" data-bs-toggle="tooltip" data-bs-title="Editar convocatoria" aria-label="Editar convocatoria"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="tooltip" data-bs-title="Eliminar convocatoria" aria-label="Eliminar convocatoria" onclick="confirmarEliminar(<?= (int) $c->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    <form id="form-eliminar-<?= (int) $c->id ?>" action="/admin/convocatorias/eliminar/<?= (int) $c->id ?>" method="post" class="d-none"><?= csrf_field() ?></form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Fichas para el teléfono: la tabla de 8 columnas no cabe en pantalla -->
        <div class="d-block d-md-none">
            <?php if (empty($convocatorias)): ?>
                <div class="empty-state">
                    <i class="bi bi-award" aria-hidden="true"></i>
                    <div class="empty-titulo">No hay convocatorias en este filtro</div>
                    <div class="empty-texto"><a href="/admin/convocatorias/nueva">Cree una convocatoria</a> con su periodo de registro y sus bases.</div>
                </div>
            <?php else: ?>
                <?php foreach ($convocatorias as $c): ?>
                    <?php
                    $ventana = \App\Models\ConvocatoriaModel::etiquetaVentana($c);
                    $postulacionesNumero = (int) ($postulaciones[(int) $c->id] ?? 0);
                    $evaluacionUrl = '/admin/convocatorias/' . (int) $c->id . '/evaluacion';
                    ?>
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="overflow-hidden">
                                <a class="fw-semibold text-decoration-none" href="<?= esc($evaluacionUrl) ?>"><?= esc($c->titulo ?: 'Convocatoria #' . (int) $c->id) ?></a>
                                <div class="small text-muted">#<?= (int) $c->id ?> · Publicada <?= formatear_fecha($c->fecha_publicacion, 'd/m/Y') ?></div>
                            </div>
                            <span class="badge bg-<?= esc($ventana['tono']) ?> text-white flex-shrink-0"><?= esc($ventana['etiqueta']) ?></span>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 small mt-2">
                            <span class="text-muted"><?= formatear_fecha($c->periodo_registro_inicio, 'd/m/Y') ?> → <?= formatear_fecha($c->periodo_registro_fin, 'd/m/Y') ?></span>
                            <span class="badge bg-light text-dark border"><?= $postulacionesNumero ?> postulación(es)</span>
                            <?php if (! empty($c->bases_archivo)): ?>
                                <a class="badge text-decoration-none bg-danger-subtle text-danger" href="/admin/convocatorias/<?= (int) $c->id ?>/bases" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i>Bases</a>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="<?= esc($evaluacionUrl) ?>" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-people me-1"></i>Evaluar</a>
                            <a href="/admin/convocatorias/editar/<?= (int) $c->id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i></a>
                            <button type="button" class="btn btn-outline-danger btn-sm" aria-label="Eliminar convocatoria" onclick="confirmarEliminar(<?= (int) $c->id ?>)"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (! empty($paginador) && $paginador->getPageCount() > 1): ?>
    <div class="mt-4 d-flex justify-content-center">
        <?= $paginador->links('default', 'bootstrap') ?>
    </div>
<?php endif; ?>

<script>
function confirmarEliminar(id) {
    if (confirm('¿Eliminar definitivamente esta convocatoria? Solo es posible si no tiene postulaciones. El cambio queda registrado en auditoría.')) {
        document.getElementById('form-eliminar-' + id).submit();
    }
}
</script>

<?= $this->endSection() ?>
