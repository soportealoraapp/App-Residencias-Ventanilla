<?php declare(strict_types=1);

/**
 * Almacén global de documentos.
 *
 * Variables inyectadas por Admin\DocumentosController::index() vía view(..., $data).
 * Se leen desde get_defined_vars() para que el análisis estático no las marque
 * como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var list<object> $documentos */
$documentos     = $__view['documentos'] ?? [];
/** @var object|null $pager */
$pager          = $__view['pager'] ?? null;
/** @var array<string, string> $filtros */
$filtros        = (array) ($__view['filtros'] ?? []);
/** @var int $totalFiltrado */
$totalFiltrado  = (int) ($__view['totalFiltrado'] ?? 0);
/** @var array<string, int|float> $global */
$global         = (array) ($__view['global'] ?? []);
/** @var list<array<string, int|string>> $porTipo */
$porTipo        = (array) ($__view['porTipo'] ?? []);
/** @var string $mesActual */
$mesActual      = (string) ($__view['mesActual'] ?? date('Y-m'));
/** @var int $delMes */
$delMes         = (int) ($__view['delMes'] ?? 0);
/** @var array<string, string> $camposOrden */
$camposOrden    = (array) ($__view['camposOrden'] ?? ['fecha_carga' => 'Fecha de carga']);

$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');

/**
 * URL del listado con los filtros actuales, cambiando solo lo indicado.
 * Sacar esto de una función evita que dos tarjetas enlacen a criterios distintos
 * para la misma cifra.
 */
$urlDocs = static function (array $cambios) use ($filtros): string {
    $base = [
        'tipo'     => (string) ($filtros['tipo'] ?? ''),
        'validado' => (string) ($filtros['validado'] ?? ''),
        'mes'      => (string) ($filtros['mes'] ?? ''),
        'tramite'  => (string) ($filtros['tramite'] ?? ''),
        'q'        => (string) ($filtros['q'] ?? ''),
        'orden'    => (string) ($filtros['orden'] ?? 'fecha_carga'),
        'dir'      => strtolower((string) ($filtros['direccion'] ?? 'DESC')) === 'asc' ? 'asc' : 'desc',
    ];
    $limpia = array_filter(array_merge($base, $cambios), static fn ($v): bool => $v !== '' && $v !== null);

    return site_url('admin/documentos' . ($limpia === [] ? '' : '?' . http_build_query($limpia)));
};

$hayFiltros = array_filter(
    ['tipo', 'validado', 'mes', 'tramite', 'q'],
    static fn (string $clave): bool => ($filtros[$clave] ?? '') !== ''
) !== [];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Documentos recibidos<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="panel-titulo">
    <div>
        <h2><i class="bi bi-file-earmark" aria-hidden="true"></i> Documentos recibidos</h2>
        <p class="panel-subtitulo">Cada archivo que entró al sistema, con su expediente y quien lo subió.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Inicio</a>
        <a href="/admin/desglose#documentos" class="btn btn-outline-primary btn-sm"><i class="bi bi-graph-up-arrow me-1"></i>Ver en el desglose</a>
    </div>
</div>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cómo revisar los archivos',
    'texto'  => 'Toca un tipo, un mes o el estado de revisión para filtrar el almacén por ese valor. El nombre del archivo abre la descarga y el folio abre el expediente completo.',
]) ?>

<div class="dgs-seccion">
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Archivos en el sistema', $num((int) ($global['total'] ?? 0)), $urlDocs([]), 'bi-archive'],
            ['Cargados en ' . nombre_mes(), $num($delMes), $urlDocs(['mes' => $mesActual]), 'bi-upload'],
            ['Sin revisar',            $num((int) ($global['por_validar'] ?? 0)), $urlDocs(['validado' => 'no']), 'bi-exclamation-circle'],
            ['Espacio ocupado',        formato_bytes((float) ($global['bytes'] ?? 0)), $urlDocs([]), 'bi-hdd'],
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
                <a href="<?= $urlDocs(['tipo' => '']) ?>" class="dgs-nav-item <?= ($filtros['tipo'] ?? '') === '' ? 'fw-bold' : '' ?>">
                    Todos los tipos <span class="text-muted"><?= $num((int) ($global['total'] ?? 0)) ?></span>
                </a>
                <?php foreach ($porTipo as $t): ?>
                    <?php $tipo = (string) $t['tipo']; ?>
                    <a href="<?= $urlDocs(['tipo' => $tipo]) ?>" class="dgs-nav-item <?= ($filtros['tipo'] ?? '') === $tipo ? 'fw-bold' : '' ?>"
                       title="<?= esc(formato_bytes((float) $t['bytes'])) ?> · <?= (int) $t['validados'] ?> de <?= (int) $t['total'] ?> revisados">
                        <?= esc(tipo_documento_nombre($tipo)) ?>
                        <span class="text-muted"><?= $num((int) $t['total']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="dgs-nav mt-0 mb-1">
                <a href="<?= $urlDocs(['validado' => '']) ?>" class="dgs-nav-item <?= ($filtros['validado'] ?? '') === '' ? 'fw-bold' : '' ?>">Revisión: todas</a>
                <a href="<?= $urlDocs(['validado' => 'si']) ?>" class="dgs-nav-item <?= ($filtros['validado'] ?? '') === 'si' ? 'fw-bold' : '' ?>">
                    Validadas <span class="text-muted"><?= $num((int) ($global['validados'] ?? 0)) ?></span>
                </a>
                <a href="<?= $urlDocs(['validado' => 'no']) ?>" class="dgs-nav-item <?= ($filtros['validado'] ?? '') === 'no' ? 'fw-bold' : '' ?>">
                    Por validar <span class="text-muted"><?= $num((int) ($global['por_validar'] ?? 0)) ?></span>
                </a>
                <a href="<?= $urlDocs(['mes' => date('Y-m')]) ?>" class="dgs-nav-item <?= ($filtros['mes'] ?? '') === date('Y-m') ? 'fw-bold' : '' ?>">Este mes</a>
                <a href="<?= $urlDocs(['mes' => date('Y-m', strtotime('first day of last month'))]) ?>" class="dgs-nav-item">Mes pasado</a>
            </div>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('admin/documentos') ?>" class="row g-2 g-md-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="q" class="form-label small fw-semibold">Folio o nombre del archivo</label>
                    <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="search" name="q" id="q" class="form-control form-control-sm" maxlength="80"
                           value="<?= esc($filtros['q'] ?? '') ?>" placeholder="SOL-2026-0001 o invoice.pdf">
                </div>
                <div class="col-12 col-md-3">
                    <label for="tipo" class="form-label small fw-semibold">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($porTipo as $t): ?>
                            <?php $tipo = (string) $t['tipo']; ?>
                            <option value="<?= esc($tipo) ?>" <?= ($filtros['tipo'] ?? '') === $tipo ? 'selected' : '' ?>>
                                <?= esc(tipo_documento_nombre($tipo)) ?> (<?= (int) $t['total'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="validado" class="form-label small fw-semibold">Revisión</label>
                    <select name="validado" id="validado" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="si" <?= ($filtros['validado'] ?? '') === 'si' ? 'selected' : '' ?>>Validadas</option>
                        <option value="no" <?= ($filtros['validado'] ?? '') === 'no' ? 'selected' : '' ?>>Por validar</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label for="mes" class="form-label small fw-semibold">Mes</label>
                    <input type="month" name="mes" id="mes" class="form-control form-control-sm" value="<?= esc($filtros['mes'] ?? '') ?>">
                </div>
                <div class="col-12 col-md-1 d-flex gap-2 justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search" aria-hidden="true"></i><span class="ms-1">Filtrar</span></button>
                </div>
                <?php if ($hayFiltros): ?>
                    <div class="col-12">
                        <a href="<?= site_url('admin/documentos') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg me-1"></i>Quitar todos los filtros</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 gap-2 flex-wrap">
        <h2 class="h6 mb-0 fw-bold"><i class="bi bi-list-columns-reverse me-2 text-primary"></i>Archivos</h2>
        <span class="small text-muted">
            <?= $num($totalFiltrado) ?> de <?= $num((int) ($global['total'] ?? 0)) ?>
            <?php if (($filtros['tramite'] ?? '') !== ''): ?> · trámite <?= esc(tramite_nombre((string) $filtros['tramite'])) ?><?php endif; ?>
        </span>
    </div>

    <?php if ($documentos === []): ?>
        <div class="empty-state">
            <i class="bi bi-file-earmark-x" aria-hidden="true"></i>
            <div class="empty-titulo">Sin resultados</div>
            <div class="empty-texto"><?= $hayFiltros ? 'Ningún archivo coincide con el filtro seleccionado.' : 'Todavía no se ha cargado ningún documento al sistema.' ?></div>
            <?php if ($hayFiltros): ?><a href="<?= site_url('admin/documentos') ?>" class="btn btn-outline-primary btn-sm mt-2">Ver todos los archivos</a><?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Teléfono: tarjetas apiladas; una tabla de seis columnas no cabe -->
        <div class="d-block d-md-none">
            <?php foreach ($documentos as $doc): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <a class="fw-semibold small text-break" href="<?= esc(site_url('admin/solicitudes/descargar-documento/' . (int) $doc->id)) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-download me-1" aria-hidden="true"></i><?= esc((string) $doc->nombre_original) ?>
                        </a>
                        <span class="small text-muted text-nowrap"><?= formato_bytes((int) $doc->tamano_bytes) ?></span>
                    </div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-2">
                        <?php $tipoDoc = (string) ($doc->tipo_documento ?: 'sin_clasificar'); ?>
                        <a href="<?= $urlDocs(['tipo' => $tipoDoc]) ?>" class="badge bg-light text-dark border text-decoration-none">
                            <?= esc(tipo_documento_nombre($doc->tipo_documento)) ?>
                        </a>
                        <?php if ((int) $doc->validado === 1): ?>
                            <a href="<?= $urlDocs(['validado' => 'si']) ?>" class="badge bg-success-subtle text-success text-decoration-none"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Validado</a>
                        <?php else: ?>
                            <a href="<?= $urlDocs(['validado' => 'no']) ?>" class="badge bg-warning-subtle text-warning-emphasis text-decoration-none"><i class="bi bi-clock me-1" aria-hidden="true"></i>Por validar</a>
                        <?php endif; ?>
                        <?php if (! empty($doc->folio)): ?>
                            <a class="badge bg-primary-subtle text-primary text-decoration-none font-monospace"
                               href="<?= esc(site_url('admin/solicitudes/' . rawurlencode((string) $doc->folio))) ?>"
                               title="<?= esc(tramite_nombre((string) ($doc->tramite ?? ''))) ?> · <?= esc((string) ($doc->estatus ?? '')) ?>">
                                <?= esc((string) $doc->folio) ?> <i class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mt-1">
                        <?= formatear_fecha($doc->fecha_carga, 'd/m/Y H:i') ?> · <?= esc((string) ($doc->usuario_nombre ?: $doc->usuario_username ?: 'Sistema')) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Archivo</th>
                        <th>Tipo</th>
                        <th>Expediente</th>
                        <th class="text-end">
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? '') === 'tamano_bytes' ? 'fw-bold' : '' ?>" href="<?= $urlDocs(['orden' => 'tamano_bytes', 'dir' => ($filtros['orden'] ?? '') === 'tamano_bytes' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Tamaño <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                        <th>
                            <a class="text-decoration-none <?= ($filtros['orden'] ?? 'fecha_carga') === 'fecha_carga' ? 'fw-bold' : '' ?>" href="<?= $urlDocs(['orden' => 'fecha_carga', 'dir' => ($filtros['orden'] ?? 'fecha_carga') === 'fecha_carga' && strtolower((string) ($filtros['direccion'] ?? '')) === 'asc' ? 'desc' : 'asc']) ?>">Carga <i class="bi bi-arrow-down-up" aria-hidden="true"></i></a>
                        </th>
                        <th>Revisión</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td class="mw-260">
                                <a class="text-decoration-none fw-semibold small text-break" href="<?= esc(site_url('admin/solicitudes/descargar-documento/' . (int) $doc->id)) ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-download me-1" aria-hidden="true"></i><?= esc((string) $doc->nombre_original) ?>
                                </a>
                                <div class="small text-muted"><?= esc((string) ($doc->mime_type ?: '')) ?></div>
                            </td>
                            <td>
                                <?php $tipoDoc = (string) ($doc->tipo_documento ?: 'sin_clasificar'); ?>
                                <a href="<?= $urlDocs(['tipo' => $tipoDoc]) ?>" class="badge bg-light text-dark border text-decoration-none">
                                    <?= esc(tipo_documento_nombre($doc->tipo_documento)) ?>
                                </a>
                            </td>
                            <td>
                                <?php if (! empty($doc->folio)): ?>
                                    <a class="font-monospace small text-decoration-none" href="<?= esc(site_url('admin/solicitudes/' . rawurlencode((string) $doc->folio))) ?>"><?= esc((string) $doc->folio) ?></a>
                                    <div class="small text-muted"><?= esc(tramite_nombre((string) ($doc->tramite ?? ''))) ?></div>
                                <?php else: ?>
                                    <span class="text-muted small">Expediente eliminado</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end small text-nowrap"><?= formato_bytes((int) $doc->tamano_bytes) ?></td>
                            <td class="small text-nowrap">
                                <a href="<?= $urlDocs(['mes' => substr((string) $doc->fecha_carga, 0, 7), 'tipo' => (string) ($filtros['tipo'] ?? ''), 'orden' => 'fecha_carga']) ?>" class="text-decoration-none">
                                    <?= formatear_fecha($doc->fecha_carga, 'd/m/Y H:i') ?>
                                </a>
                                <div class="text-muted"><?= esc((string) ($doc->usuario_nombre ?: $doc->usuario_username ?: 'Sistema')) ?></div>
                            </td>
                            <td>
                                <?php if ((int) $doc->validado === 1): ?>
                                    <a href="<?= $urlDocs(['validado' => 'si']) ?>" class="badge bg-success-subtle text-success text-decoration-none">Validado</a>
                                <?php else: ?>
                                    <a href="<?= $urlDocs(['validado' => 'no']) ?>" class="badge bg-warning-subtle text-warning-emphasis text-decoration-none">Por validar</a>
                                <?php endif; ?>
                            </td>
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
