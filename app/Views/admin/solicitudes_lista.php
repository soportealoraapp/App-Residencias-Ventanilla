<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\AdminController::listaSolicitudes() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, string> $filtros */
$filtros = $__view['filtros'] ?? ['tramite' => '', 'estatus' => '', 'q' => ''];
/** @var array<int, object> $solicitudes */
$solicitudes = $__view['solicitudes'] ?? [];
/** @var object $pager */
$pager = $__view['pager'] ?? null;
/** @var array<string, float|int> $montos */
$montos = $__view['montos'] ?? ['filtrado' => 0.0, 'total' => 0];
/** @var array<string, string> $camposOrden */
$camposOrden = $__view['camposOrden'] ?? ['fecha_solicitud' => 'Fecha'];

$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');

/**
 * URL del listado conservando los filtros activos y cambiando solo lo que se
 * indique. Así, tocar una etiqueta de estatus no borra el trámite ni la fecha.
 */
$urlFiltros = static function (array $cambios) use ($filtros): string {
    $base = [
        'tramite'     => (string) ($filtros['tramite'] ?? ''),
        'estatus'     => (string) ($filtros['estatus'] ?? ''),
        'q'           => (string) ($filtros['q'] ?? ''),
        'origen'      => (string) ($filtros['origen'] ?? ''),
        'pago'        => (string) ($filtros['pago'] ?? ''),
        'resuelto'    => (string) ($filtros['resuelto'] ?? ''),
        'mes'         => (string) ($filtros['mes'] ?? ''),
        'desde'       => (string) ($filtros['desde'] ?? ''),
        'hasta'       => (string) ($filtros['hasta'] ?? ''),
        'fecha_campo' => (string) ($filtros['fecha_campo'] ?? 'solicitud'),
        'orden'       => (string) ($filtros['orden'] ?? 'fecha_solicitud'),
        'dir'         => strtolower((string) ($filtros['direccion'] ?? 'desc')),
    ];
    $mezcla = array_merge($base, $cambios);
    // Los valores por defecto no viajan en la URL: se leen igual en el controlador.
    if (($mezcla['fecha_campo'] ?? '') === 'solicitud') {
        unset($mezcla['fecha_campo']);
    }
    if (($mezcla['orden'] ?? '') === 'fecha_solicitud') {
        unset($mezcla['orden']);
    }
    if (($mezcla['dir'] ?? '') === 'desc') {
        unset($mezcla['dir']);
    }
    $limpia = array_filter($mezcla, static fn ($v): bool => $v !== '' && $v !== null);

    return site_url('admin/solicitudes' . ($limpia === [] ? '' : '?' . http_build_query($limpia)));
};

$hayFiltros = array_filter(
    ['tramite', 'estatus', 'q', 'origen', 'pago', 'resuelto', 'mes', 'desde', 'hasta'],
    static fn (string $clave): bool => ($filtros[$clave] ?? '') !== ''
) !== [];

$ordenActual = (string) ($filtros['orden'] ?? 'fecha_solicitud');
$dirActual   = strtolower((string) ($filtros['direccion'] ?? 'desc'));

/* Al pulsar una columna se invierte su propio sentido; al cambiar de columna se
   parte del orden natural (alfabético ascendente, cantidades descendente). */
$enlaceOrden = static function (string $columna) use ($urlFiltros, $ordenActual, $dirActual): string {
    if ($ordenActual === $columna) {
        $nueva = $dirActual === 'asc' ? 'desc' : 'asc';
    } else {
        $nueva = in_array($columna, ['folio', 'tramite', 'estatus'], true) ? 'asc' : 'desc';
    }

    return $urlFiltros(['orden' => $columna, 'dir' => $nueva]);
};
$marcadorOrden = static function (string $columna) use ($ordenActual, $dirActual): string {
    if ($ordenActual !== $columna) {
        return 'bi-arrow-down-up text-muted';
    }

    return $dirActual === 'asc' ? 'bi-sort-up text-primary' : 'bi-sort-down text-primary';
};
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Listado de Solicitudes<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
helper('url');
$solicitudDatoModel = new \App\Models\SolicitudDatoModel();
$userModel = new \App\Models\UserModel();

$tramitesDisponibles = [
    'UR-TT-T-01' => 'T-01: Concesión de Transporte',
    'UR-TT-T-02' => 'T-02: Constancia de Despintado',
    'UR-TT-T-03' => 'T-03: Orden de Plaqueo',
    'UR-TT-T-04' => 'T-04: Permiso Eventual de Transporte',
    'UR-TT-T-05' => 'T-05: Permiso para Cierre de Calle',
];
if (\App\Libraries\FeatureFlags::habilitarUrTtT06()) {
    $tramitesDisponibles['UR-TT-T-06'] = 'T-06: Cesión de Concesión';
}
$tramitesDisponibles['UR-TT-T-07'] = 'T-07: Permiso de Carga y Descarga';

$estatusLista = \App\Libraries\EstadoSolicitudService::ESTATUS_MAESTRO;
?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Expedientes de trámites',
    'texto'  => 'Filtra por trámite, estatus, canal de captura, pago o periodo. Las etiquetas de color conservan los demás filtros al tocarlas, y en escritorio puedes ordenar por cualquier columna. Haz clic en <strong>Ver</strong> para abrir el expediente completo.',
]) ?>

<div class="card mb-3 mb-md-4 border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0 small fw-bold"><i class="bi bi-funnel me-1 text-primary"></i>Filtros de búsqueda</h6>
            <a href="/admin/desglose" class="small text-decoration-none d-none d-sm-inline">
                <i class="bi bi-graph-up-arrow me-1"></i>Ver desglose del sistema
            </a>
        </div>
        <div class="dgs-nav mt-2 mb-1">
            <?php
            // [etiqueta, valor de estatus, color al estar activa]. La primera abre
            // el listado sin filtro de estatus y conserva el resto.
            $etiquetas = [
                ['Todos',          '',               'bg-primary'],
                ['Recibidos',      'Recibido',       'bg-secondary'],
                ['En revisión',    'En revisión',    'bg-info'],
                ['Prevención',     'Prevención',     'bg-danger'],
                ['Pago pendiente', 'Pago pendiente', 'bg-warning'],
                ['Pagados',        'Pagado',         'bg-success'],
                ['Vigentes',       'Vigente',        'bg-success'],
                ['Vencidos',       'Vencido',        'bg-danger'],
                ['Rechazados',     'Rechazado',      'bg-danger'],
                ['Concluidos',     'Concluido',      'bg-dark'],
            ];
            foreach ($etiquetas as [$rotulo, $estatus, $color]):
                $activo = ($filtros['estatus'] ?? '') === $estatus;
                $textoActivo = in_array($color, ['bg-warning', 'bg-info'], true) ? 'text-dark' : 'text-white';
            ?>
                <a href="<?= $urlFiltros(['estatus' => $estatus]) ?>"
                   class="dgs-nav-item <?= $activo ? $color . ' border-0 ' . $textoActivo : '' ?>"
                   <?= $activo ? 'aria-current="true"' : '' ?>>
                    <?php if ($activo): ?><i class="bi bi-check2" aria-hidden="true"></i><?php endif; ?>
                    <?= esc($rotulo) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('admin/solicitudes') ?>" class="row g-2 g-md-3 align-items-end">
            <div class="col-12 col-md-3">
                <label for="tramite" class="form-label small fw-semibold">Trámite</label>
                <select name="tramite" id="tramite" class="form-select form-select-sm">
                    <option value="">— Todos los trámites —</option>
                    <?php foreach ($tramitesDisponibles as $clave => $nombre): ?>
                        <option value="<?= esc($clave) ?>" <?= ($filtros['tramite'] === $clave) ? 'selected' : '' ?>>
                            <?= esc($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="estatus" class="form-label small fw-semibold">Estatus</label>
                <select name="estatus" id="estatus" class="form-select form-select-sm">
                    <option value="">— Todos los estatus —</option>
                    <?php foreach ($estatusLista as $e): ?>
                        <option value="<?= esc($e) ?>" <?= ($filtros['estatus'] === $e) ? 'selected' : '' ?>>
                            <?= esc($e) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label for="q" class="form-label small fw-semibold">Búsqueda rápida</label>
                <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" name="q" id="q" class="form-control form-control-sm"
                    placeholder="folio, RFC, razón social o nombre..."
                    value="<?= esc($filtros['q']) ?>">
            </div>
            <div class="col-12 col-md-2">
                <label for="origen" class="form-label small fw-semibold">Canal</label>
                <select name="origen" id="origen" class="form-select form-select-sm">
                    <option value="">— Portal y ventanilla —</option>
                    <option value="digital" <?= ($filtros['origen'] ?? '') === 'digital' ? 'selected' : '' ?>>Portal digital</option>
                    <option value="presencial" <?= ($filtros['origen'] ?? '') === 'presencial' ? 'selected' : '' ?>>Capturado en ventanilla</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="pago" class="form-label small fw-semibold">Cobro</label>
                <select name="pago" id="pago" class="form-select form-select-sm">
                    <option value="">— Sin especificar —</option>
                    <option value="cobrado" <?= ($filtros['pago'] ?? '') === 'cobrado' ? 'selected' : '' ?>>Con pago registrado</option>
                    <option value="sin_cobrar" <?= ($filtros['pago'] ?? '') === 'sin_cobrar' ? 'selected' : '' ?>>Sin cobrar</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label for="resuelto" class="form-label small fw-semibold">Resolución</label>
                <select name="resuelto" id="resuelto" class="form-select form-select-sm">
                    <option value="">— Todas —</option>
                    <option value="si" <?= ($filtros['resuelto'] ?? '') === 'si' ? 'selected' : '' ?>>Ya resueltas</option>
                    <option value="no" <?= ($filtros['resuelto'] ?? '') === 'no' ? 'selected' : '' ?>>Sin resolver</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label for="mes" class="form-label small fw-semibold">Mes</label>
                <input type="month" name="mes" id="mes" class="form-control form-control-sm" value="<?= esc($filtros['mes'] ?? '') ?>">
            </div>
            <div class="col-12 col-md-3">
                <label for="fecha_campo" class="form-label small fw-semibold">Periodo aplicado a</label>
                <select name="fecha_campo" id="fecha_campo" class="form-select form-select-sm">
                    <option value="solicitud" <?= ($filtros['fecha_campo'] ?? 'solicitud') === 'solicitud' ? 'selected' : '' ?>>Fecha de solicitud</option>
                    <option value="pago" <?= ($filtros['fecha_campo'] ?? '') === 'pago' ? 'selected' : '' ?>>Fecha de pago</option>
                    <option value="resolucion" <?= ($filtros['fecha_campo'] ?? '') === 'resolucion' ? 'selected' : '' ?>>Fecha de resolución</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label for="desde" class="form-label small fw-semibold">Desde</label>
                <input type="date" name="desde" id="desde" class="form-control form-control-sm" value="<?= esc($filtros['desde'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label for="hasta" class="form-label small fw-semibold">Hasta</label>
                <input type="date" name="hasta" id="hasta" class="form-control form-control-sm" value="<?= esc($filtros['hasta'] ?? '') ?>">
            </div>
            <?php if (($filtros['orden'] ?? '') !== 'fecha_solicitud'): ?>
                <input type="hidden" name="orden" value="<?= esc($filtros['orden']) ?>">
            <?php endif; ?>
            <?php if (strtolower($filtros['direccion'] ?? 'desc') !== 'desc'): ?>
                <input type="hidden" name="dir" value="<?= esc(strtolower($filtros['direccion'])) ?>">
            <?php endif; ?>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 flex-wrap gap-2">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-list-columns-reverse me-2 text-primary"></i>Solicitudes (<?= number_format($pager ? $pager->getTotal() : count($solicitudes)) ?> registros)
            <span class="d-block small fw-normal text-muted mt-1">
                Dinero del filtro: <strong class="text-success"><?= formatear_dinero((float) ($montos['filtrado'] ?? 0)) ?></strong>
                <?php if ((int) ($montos['total'] ?? 0) > 0): ?>
                    · <?= $num((int) $montos['total']) ?> solicitudes en total
                <?php endif; ?>
            </span>
        </h6>
        <div class="d-flex gap-2">
            <a href="/admin/solicitudes/nueva" class="btn btn-success btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nueva Presencial
            </a>
            <?php if ($hayFiltros): ?>
                <a href="<?= site_url('admin/solicitudes') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-lg me-1"></i>Limpiar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Datos complementarios resueltos una sola vez por solicitud. La tabla y el
    // listado de teléfono comparten el resultado, así el N+1 no se duplica al
    // renderizar las dos vistas sobre la misma página.
    $filas = [];
    foreach ($solicitudes as $s) {
        $datos    = $solicitudDatoModel->porSolicitudAgrupado($s->id);
        $ciudadano = !empty($s->ciudadano_id) ? $userModel->find($s->ciudadano_id) : null;
        $filas[] = [
            's'      => $s,
            'rfc'    => (string) ($datos['rfc'] ?? $datos['RFC'] ?? ($ciudadano->rfc ?? '')),
            'nombre' => (string) ($datos['razon_social_o_nombre'] ?? $datos['solicitante_nombre'] ?? ($ciudadano->nombre_completo ?? ($ciudadano->username ?? '—'))),
        ];
    }
    ?>

    <!-- Teléfono: tarjetas apiladas (menos de 768 px) -->
    <div class="d-block d-md-none">
        <?php if (empty($solicitudes)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <div class="empty-titulo">Sin solicitudes</div>
                <div class="empty-texto">No se encontraron solicitudes con los filtros indicados.</div>
                <a href="<?= site_url('admin/solicitudes') ?>" class="btn btn-outline-primary btn-sm mt-3">Quitar filtros</a>
            </div>
        <?php else: ?>
            <?php foreach ($filas as $fila): ?>
                <?php $s = $fila['s']; $rfc = $fila['rfc']; $nombre = $fila['nombre']; ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="fw-bold font-monospace text-primary text-decoration-none">
                                <?= esc($s->folio) ?>
                            </a>
                            <div class="small fw-semibold"><?= esc($nombre) ?></div>
                            <?php if (!empty($rfc)): ?>
                                <div class="small text-muted"><span class="badge bg-light text-dark border">RFC: <?= esc($rfc) ?></span></div>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $urlFiltros(['estatus' => (string) $s->estatus]) ?>" class="text-decoration-none" title="Ver todas las solicitudes en este estatus">
                            <span class="badge estatus-badge <?= estatus_badge($s->estatus) ?>">
                                <?= esc($s->estatus) ?>
                            </span>
                        </a>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                        <div>
                            <a href="<?= $urlFiltros(['tramite' => (string) $s->tramite]) ?>" class="small text-decoration-none"><?= tramite_nombre($s->tramite) ?></a>
                            <div><i class="bi bi-calendar3 me-1"></i><?= formatear_fecha($s->fecha_solicitud) ?></div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-dark fs-6"><?= formatear_dinero((float)$s->monto) ?></div>
                            <?php if (empty($s->fecha_pago)): ?>
                                <a href="<?= $urlFiltros(['pago' => 'sin_cobrar']) ?>" class="text-decoration-none" title="Ver lo que sigue sin cobrar"><span class="badge bg-warning text-dark">Sin cobrar</span></a>
                            <?php else: ?>
                                <a href="<?= $urlFiltros(['pago' => 'cobrado']) ?>" class="text-decoration-none" title="Ver los pagos registrados"><span class="badge bg-success-subtle text-success border">Pagado</span></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-eye me-1"></i>Ver expediente
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Escritorio: tabla completa (768 px o más) -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <?php
                    // Cabecera enlazada: un toque ordena por ese campo sin perder
                    // los filtros. Las columnas con null son solo informativas.
                    $columnas = [
                        ['folio',           'Folio'],
                        ['tramite',         'Trámite'],
                        [null,              'Ciudadano'],
                        [null,              'RFC'],
                        ['estatus',         'Estatus'],
                        ['fecha_solicitud', 'Fecha solicitud'],
                        ['monto',           'Monto'],
                    ];
                    foreach ($columnas as [$columna, $titulo]):
                        $esOrdenable = is_string($columna) && array_key_exists((string) $columna, $camposOrden);
                    ?>
                        <th scope="col" class="text-nowrap">
                            <?php if ($esOrdenable): ?>
                                <a href="<?= $enlaceOrden($columna) ?>" class="text-decoration-none fw-semibold small" title="Ordenar por <?= esc($titulo) ?>">
                                    <?= esc($titulo) ?> <i class="bi <?= $marcadorOrden($columna) ?>" aria-hidden="true"></i>
                                </a>
                            <?php else: ?>
                                <span class="fw-semibold small"><?= esc($titulo) ?></span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <div class="empty-titulo">Sin solicitudes</div>
                                <div class="empty-texto">No se encontraron solicitudes con los filtros indicados.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filas as $fila): ?>
                        <?php $s = $fila['s']; $rfc = $fila['rfc']; $nombre = $fila['nombre']; ?>
                        <tr>
                            <td>
                                <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>" class="fw-bold text-decoration-none font-monospace">
                                    <?= esc($s->folio) ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?= $urlFiltros(['tramite' => (string) $s->tramite]) ?>" class="text-decoration-none" title="Ver todas las solicitudes de este trámite">
                                    <?= tramite_nombre($s->tramite) ?>
                                </a>
                                <small class="text-muted"><?= esc($s->tramite) ?></small>
                            </td>
                            <td>
                                <a href="<?= $urlFiltros(['q' => $nombre]) ?>" class="text-decoration-none" title="Buscar a este solicitante en el listado"><?= esc($nombre) ?></a>
                            </td>
                            <td>
                                <?php if ($rfc !== ''): ?>
                                    <a href="<?= $urlFiltros(['q' => $rfc]) ?>" class="text-decoration-none font-monospace small" title="Ver las solicitudes con este RFC"><?= esc($rfc) ?></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= $urlFiltros(['estatus' => (string) $s->estatus]) ?>" class="text-decoration-none" title="Ver todas las solicitudes en este estatus">
                                    <span class="badge estatus-badge <?= estatus_badge($s->estatus) ?>">
                                        <?= esc($s->estatus) ?>
                                    </span>
                                </a>
                            </td>
                            <td class="text-nowrap small"><?= formatear_fecha($s->fecha_solicitud) ?></td>
                            <td class="text-nowrap fw-semibold"><?= formatear_dinero((float)$s->monto) ?></td>
                            <td class="text-end text-nowrap">
                                <a href="<?= site_url('admin/solicitudes/' . $s->folio) ?>"
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-eye me-1"></i>Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (!empty($solicitudes) && $pager && $pager->getPageCount() > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-center pt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

