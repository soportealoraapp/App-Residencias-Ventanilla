<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\DesgloseController::index() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, int|float> $kpis */
$kpis       = $__view['kpis'] ?? [];
$porEstatus = $__view['porEstatus'] ?? ['lista' => [], 'total' => 0];
$porTramite = $__view['porTramite'] ?? [];
$porCanal   = $__view['porCanal'] ?? [];
$serie      = $__view['serie'] ?? [];
$flujo      = $__view['flujo'] ?? [];
$tiempos    = $__view['tiempos'] ?? [];
$vigencias  = $__view['vigencias'] ?? [];
$documentos = $__view['documentos'] ?? [];
$cuentas    = $__view['cuentas'] ?? [];
$auditoria  = $__view['auditoria'] ?? [];
/** @var array<string, int|float|string|list<array<string, int|string>>> $transito */
$transito   = $__view['transito'] ?? [];
/** @var array<string, int|float|string|list<array<string, int|string>>> $verif */
$verif      = $__view['verif'] ?? [];
$catalogos  = $__view['catalogos'] ?? [];
$alertas    = $__view['alertas'] ?? [];
$generado   = $__view['generado'] ?? date('d/m/Y H:i');

/**
 * Arma la URL del listado de solicitudes conservando solo los filtros útiles.
 *
 * @param array<string, string|int> $parametros
 */
$sol = static function (array $parametros): string {
    $limpios = array_filter($parametros, static fn ($v): bool => $v !== '' && $v !== null);

    return '/admin/solicitudes' . ($limpios === [] ? '' : '?' . http_build_query($limpios));
};

$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');

$porcentaje = static function (int|float $valor, int|float $base): float {
    return $base > 0 ? round(((float) $valor * 100) / (float) $base, 1) : 0.0;
};

// Un solo criterio de peso en todo el panel: el mismo que usa el almacén de
// documentos, para que la misma carpeta no se lea distinto en dos pantallas.
$fmtBytes = static fn (float $bits): string => formato_bytes($bits);

$etiquetaRol = static function (string $rol): string {
    return match ($rol) {
        'administrador'       => 'Administrador',
        'operador_ventanilla' => 'Operador de ventanilla',
        'ciudadano'           => 'Ciudadano',
        default               => ucfirst(str_replace('_', ' ', $rol)),
    };
};
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Desglose del sistema<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="panel-titulo">
    <div>
        <h2><i class="bi bi-clipboard-data" aria-hidden="true"></i> Desglose del sistema</h2>
        <p class="panel-subtitulo">Todas las métricas de la ventanilla en una sola vista. Cada cifra es un enlace: ábrela para ver el detalle.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/dashboard" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Inicio</a>
        <a href="/admin/solicitudes/nueva" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Nueva solicitud</a>
    </div>
</div>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cómo leer este desglose',
    'texto'  => 'Los bloques se calculan sobre todos los registros del sistema. Toca cualquier número, barra o tarjeta para abrir el listado ya filtrado por ese criterio. La barra gris indica el peso relativo dentro del total.',
]) ?>

<!-- Navegación por secciones: deslizable horizontal en móvil, fija en escritorio -->
<nav class="dgs-nav" aria-label="Secciones del desglose">
    <?php foreach ([
        'alertas'     => ['Pendiente de acción', 'bi-exclamation-triangle'],
        'resumen'     => ['Resumen', 'bi-lightning'],
        'estatus'     => ['Estatus', 'bi-diagram-3'],
        'tramites'    => ['Trámites', 'bi-folder2-open'],
        'canales'     => ['Canales', 'bi-phone'],
        'ingresos'    => ['Ingresos', 'bi-cash-coin'],
        'flujo'       => ['Flujo', 'bi-arrow-repeat'],
        'tiempos'     => ['Tiempos', 'bi-stopwatch'],
        'vigencias'   => ['Vigencias', 'bi-calendar-x'],
        'documentos'  => ['Documentos', 'bi-file-earmark'],
        'cuentas'     => ['Cuentas', 'bi-people'],
        'auditoria'   => ['Auditoría', 'bi-shield-check'],
        'transito'    => ['Tránsito', 'bi-cone-striped'],
        'verificaciones' => ['Verificaciones', 'bi-clipboard2-check'],
        'catalogos'   => ['Catálogos', 'bi-journal-bookmark'],
    ] as $ancla => [$etiqueta, $icono]): ?>
        <a class="dgs-nav-item" href="#<?= $ancla ?>">
            <i class="bi <?= $icono ?>" aria-hidden="true"></i><span><?= $etiqueta ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<p class="small text-muted mb-3"><i class="bi bi-clock me-1"></i>Calculado al momento: <?= esc($generado) ?></p>

<!-- ===================== ALERTAS ===================== -->
<section id="alertas" class="dgs-seccion">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h2 class="h6 mb-0 fw-bold"><i class="bi bi-exclamation-triangle me-1 text-warning"></i>Pendiente de acción</h2>
            <span class="small text-muted"><?= $num(count($alertas)) ?> avisos</span>
        </div>
        <div class="card-body p-0">
            <?php if ($alertas === []): ?>
                <div class="empty-state">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <div class="empty-titulo">Nada urgente</div>
                    <div class="empty-texto">No hay solicitudes atascadas, vigencias por vencer ni documentos sin validar.</div>
                </div>
            <?php else: ?>
                <?php foreach ($alertas as $alerta): ?>
                    <a class="dgs-fila dgs-fila--<?= esc($alerta['nivel']) ?>" href="<?= esc($alerta['url']) ?>">
                        <span class="dgs-fila-etiqueta"><?= esc($alerta['rotulo']) ?></span>
                        <span class="dgs-fila-valor fw-bold"><?= $num((int) $alerta['total']) ?></span>
                        <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== RESUMEN ===================== -->
<section id="resumen" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-lightning me-1 text-primary"></i>Resumen general</h2>
    <div class="row g-2 g-md-3">
        <?php
        $kpiCards = [
            ['label' => 'Solicitudes',        'valor' => $num((int) ($kpis['solicitudes_total'] ?? 0)), 'nota' => 'histórico completo',            'url' => $sol([]),                                 'icono' => 'bi-folder2-open',  'tono' => 'primary'],
            ['label' => 'En trámite',         'valor' => $num((int) ($kpis['en_tramite'] ?? 0)),         'nota' => 'esperando al personal',          'url' => '/admin/desglose#flujo',                  'icono' => 'bi-hourglass-split', 'tono' => 'warning'],
            ['label' => 'Cobradas',           'valor' => $num((int) ($kpis['cobradas'] ?? 0)),           'nota' => 'con fecha de pago',              'url' => $sol(['pago' => 'cobrado']),                'icono' => 'bi-check-circle',    'tono' => 'success'],
            ['label' => 'Ingresos cobrados',  'valor' => formatear_dinero((float) ($kpis['ingresos_cobrados'] ?? 0)), 'nota' => 'acumulado histórico', 'url' => '/admin/desglose#ingresos',            'icono' => 'bi-cash-coin',       'tono' => 'success'],
            ['label' => 'Por cobrar',         'valor' => formatear_dinero((float) ($kpis['ingresos_por_cobrar'] ?? 0)), 'nota' => 'estatus Pago pendiente', 'url' => $sol(['estatus' => 'Pago pendiente']), 'icono' => 'bi-wallet2',       'tono' => 'warning'],
            ['label' => 'Ticket promedio',    'valor' => formatear_dinero((float) ($kpis['ticket_promedio'] ?? 0)), 'nota' => 'por trámite cobrado', 'url' => '/admin/desglose#ingresos',                 'icono' => 'bi-graph-up',        'tono' => 'info'],
            ['label' => 'Ciudadanos atendidos', 'valor' => $num((int) ($kpis['ciudadanos_activos'] ?? 0)), 'nota' => 'con al menos una solicitud', 'url' => '/admin/usuarios?rol=ciudadano',            'icono' => 'bi-people',            'tono' => 'primary'],
            ['label' => 'Documentos',         'valor' => $num((int) ($kpis['documentos'] ?? 0)),         'nota' => 'archivos recibidos',             'url' => '/admin/desglose#documentos',               'icono' => 'bi-file-earmark',      'tono' => 'secondary'],
            ['label' => 'Movimientos',        'valor' => $num((int) ($kpis['movimientos_estatus'] ?? 0)), 'nota' => 'cambios de estatus',            'url' => '/admin/desglose#flujo',                    'icono' => 'bi-arrow-repeat',      'tono' => 'info'],
            ['label' => 'Eventos de sistema', 'valor' => $num((int) ($kpis['eventos_auditoria'] ?? 0)),  'nota' => 'bitácora de auditoría',          'url' => '/admin/auditoria',                         'icono' => 'bi-shield-check',      'tono' => 'dark'],
            ['label' => 'Boletas de tránsito', 'valor' => $num((int) ($kpis['boletas_transito'] ?? 0)),  'nota' => 'sincronizadas desde el campo',   'url' => '/admin/app-transito/boletas',              'icono' => 'bi-receipt-cutoff',    'tono' => 'danger'],
            ['label' => 'Del mes en curso',   'valor' => $num((int) ($kpis['solicitudes_mes_actual'] ?? 0)), 'nota' => nombre_mes() . ' ' . date('Y'),                'url' => $sol(['mes' => date('Y-m')]),             'icono' => 'bi-calendar3',         'tono' => 'primary'],
        ];
        ?>
        <?php foreach ($kpiCards as $c): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($c['url']) ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold"><?= esc($c['label']) ?></span>
                        <span class="stat-icon-wrapper bg-<?= $c['tono'] ?>-subtle text-<?= $c['tono'] ?>"><i class="bi <?= $c['icono'] ?>"></i></span>
                    </div>
                    <div class="dgs-kpi-valor"><?= esc($c['valor']) ?></div>
                    <small class="text-muted"><?= esc($c['nota']) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===================== ESTATUS ===================== -->
<section id="estatus" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-diagram-3 me-1 text-primary"></i>Solicitudes por estatus</h2>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if (($porEstatus['total'] ?? 0) === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox" aria-hidden="true"></i>
                    <div class="empty-titulo">Sin solicitudes</div>
                    <div class="empty-texto">Aún no hay registros que distribuir por estatus.</div>
                </div>
            <?php else: ?>
                <?php foreach ($porEstatus['lista'] as $e): ?>
                    <a class="dgs-fila" href="<?= esc($sol(['estatus' => $e['estatus']])) ?>">
                        <span class="dgs-fila-etiqueta">
                            <span class="badge estatus-badge <?= estatus_badge((string) $e['estatus']) ?> me-2"><?= esc($e['estatus']) ?></span>
                            <?php if ((int) $e['cerrado'] === 1): ?><small class="text-muted">expediente cerrado</small><?php endif; ?>
                        </span>
                        <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $e['pct']) ?>%"></span></span>
                        <span class="dgs-fila-valor"><?= $num((int) $e['total']) ?></span>
                        <span class="dgs-fila-pct text-muted"><?= esc((string) $e['pct']) ?>%</span>
                        <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== TRÁMITES ===================== -->
<section id="tramites" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-folder2-open me-1 text-primary"></i>Trámites: volumen y dinero</h2>
    <div class="row g-2 g-md-3">
        <?php foreach ($porTramite as $t): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm dgs-tarjeta">
                    <div class="card-body p-3">
                        <a class="dgs-enlace-titulo" href="<?= esc($sol(['tramite' => $t['tramite']])) ?>">
                            <span class="fw-bold"><?= esc($t['nombre']) ?></span>
                            <span class="small text-muted ms-2 font-monospace"><?= esc($t['tramite']) ?></span>
                        </a>
                        <div class="d-flex align-items-end gap-3 my-2">
                            <div>
                                <div class="dgs-mini-valor"><?= $num((int) $t['total']) ?></div>
                                <div class="small text-muted">solicitudes · <?= esc((string) $t['pct']) ?>%</div>
                            </div>
                            <div class="ms-auto text-end">
                                <div class="dgs-mini-valor text-success"><?= formatear_dinero((float) $t['cobrado']) ?></div>
                                <div class="small text-muted">cobrado</div>
                            </div>
                        </div>
                        <div class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $t['pct']) ?>%"></span></div>
                        <div class="d-flex flex-wrap gap-1 mt-3">
                            <a class="badge bg-light text-dark border text-decoration-none" href="<?= esc($sol(['tramite' => $t['tramite'], 'pago' => 'cobrado'])) ?>">
                                <i class="bi bi-check2 me-1"></i><?= $num((int) $t['cobradas']) ?> cobrados
                            </a>
                            <a class="badge bg-light text-dark border text-decoration-none" href="<?= esc($sol(['tramite' => $t['tramite'], 'estatus' => 'Pago pendiente'])) ?>">
                                <i class="bi bi-wallet2 me-1"></i><?= formatear_dinero((float) $t['por_cobrar']) ?> por cobrar
                            </a>
                            <a class="badge bg-light text-dark border text-decoration-none" href="<?= esc($sol(['tramite' => $t['tramite'], 'origen' => 'presencial'])) ?>">
                                <i class="bi bi-person-badge me-1"></i><?= $num((int) $t['presencial']) ?> ventanilla
                            </a>
                            <a class="badge bg-light text-dark border text-decoration-none" href="<?= esc($sol(['tramite' => $t['tramite'], 'origen' => 'digital'])) ?>">
                                <i class="bi bi-phone me-1"></i><?= $num((int) $t['digital']) ?> portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($porTramite === []): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm"><div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-inbox" aria-hidden="true"></i>
                        <div class="empty-titulo">Sin trámites registrados</div>
                        <div class="empty-texto">Cuando existan solicitudes verás aquí el volumen y el dinero de cada trámite.</div>
                    </div>
                </div></div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ===================== CANALES ===================== -->
<section id="canales" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-phone me-1 text-primary"></i>Canal de captura</h2>
    <div class="row g-2 g-md-3">
        <?php foreach ($porCanal as $c): ?>
            <div class="col-12 col-md-6">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($sol(['origen' => $c['origen']])) ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-semibold"><?= esc($c['nombre']) ?></span>
                        <i class="bi <?= $c['canal'] === 'digital' ? 'bi-phone' : 'bi-person-badge' ?> text-primary fs-5"></i>
                    </div>
                    <div class="dgs-kpi-valor"><?= $num((int) $c['total']) ?></div>
                    <div class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $c['pct']) ?>%"></span></div>
                    <small class="text-muted"><?= esc((string) $c['pct']) ?>% del total · monto <?= formatear_dinero((float) $c['monto']) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ===================== INGRESOS ===================== -->
<section id="ingresos" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-cash-coin me-1 text-success"></i>Ingresos mes a mes</h2>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if ($serie === []): ?>
                <p class="text-muted mb-0">Sin datos suficientes para construir la serie.</p>
            <?php else: ?>
                <div class="dgs-serie" role="img" aria-label="Solicitudes recibidas por mes en los últimos doce meses">
                    <?php foreach ($serie as $s): ?>
                        <a class="dgs-col" href="<?= esc($sol(['mes' => $s['mes']])) ?>" title="<?= esc($s['etiqueta']) ?>: <?= $num((int) $s['solicitudes']) ?> solicitudes">
                            <span class="dgs-col-num"><?= $num((int) $s['solicitudes']) ?></span>
                            <span class="dgs-col-barra" style="height: <?= esc((string) max(2, (float) $s['pct_altura'])) ?>%"></span>
                            <span class="dgs-col-mes"><?= esc($s['etiqueta']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="table-responsive mt-3">
                    <table class="table table-sm table-hover align-middle mb-0 dgs-tabla">
                        <thead class="table-light">
                            <tr><th>Mes</th><th class="text-end">Recibidas</th><th class="text-end">Cobradas</th><th class="text-end">Ingresos</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($serie) as $s): ?>
                                <tr>
                                    <td><a href="<?= esc($sol(['mes' => $s['mes']])) ?>" class="text-decoration-none fw-semibold"><?= esc($s['etiqueta']) ?></a></td>
                                    <td class="text-end">
                                        <a href="<?= esc($sol(['mes' => $s['mes']])) ?>" class="text-decoration-none"><?= $num((int) $s['solicitudes']) ?></a>
                                    </td>
                                    <td class="text-end">
                                        <!-- cobros del mes: la ventana de tiempo se aplica a la fecha de pago -->
                                        <a href="<?= esc($sol(['mes' => $s['mes'], 'pago' => 'cobrado', 'fecha_campo' => 'pago'])) ?>" class="text-decoration-none"><?= $num((int) $s['cobradas']) ?></a>
                                    </td>
                                    <td class="text-end fw-semibold text-success">
                                        <a href="<?= esc($sol(['mes' => $s['mes'], 'pago' => 'cobrado', 'fecha_campo' => 'pago'])) ?>" class="text-decoration-none"><?= formatear_dinero((float) $s['ingresos']) ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== FLUJO ===================== -->
<section id="flujo" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-arrow-repeat me-1 text-info"></i>Flujo entre estatus</h2>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <?php if ($flujo === []): ?>
                <div class="empty-state">
                    <i class="bi bi-diagram-2" aria-hidden="true"></i>
                    <div class="empty-titulo">Sin movimientos registrados</div>
                    <div class="empty-texto">El flujo aparece conforme el personal cambia el estatus de las solicitudes.</div>
                </div>
            <?php else: ?>
                <?php $maxFlujo = max(array_column($flujo, 'total')); ?>
                <?php foreach ($flujo as $f): ?>
                    <a class="dgs-fila" href="<?= esc($sol(['estatus' => $f['nuevo']])) ?>">
                        <span class="dgs-fila-etiqueta">
                            <?php if ($f['anterior'] !== ''): ?>
                                <span class="badge estatus-badge bg-light text-dark border"><?= esc($f['anterior']) ?></span>
                                <i class="bi bi-arrow-right mx-1 text-muted small" aria-hidden="true"></i>
                            <?php endif; ?>
                            <span class="badge estatus-badge <?= estatus_badge($f['nuevo']) ?>"><?= esc($f['nuevo']) ?></span>
                        </span>
                        <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $f['total'], $maxFlujo)) ?>%"></span></span>
                        <span class="dgs-fila-valor"><?= $num((int) $f['total']) ?></span>
                        <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== TIEMPOS ===================== -->
<section id="tiempos" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-stopwatch me-1 text-warning"></i>Tiempos de atención</h2>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Promedio de resolución', $num((int) round((float) ($tiempos['promedio_dias'] ?? 0))) . ' días', 'desde el recibimiento', '/admin/desglose#flujo'],
            ['Caso más lento',         $num((int) ($tiempos['maximo_dias'] ?? 0)) . ' días', 'tiempo máximo registrado', '/admin/desglose#flujo'],
            ['Sin fecha de resolución', $num((int) ($tiempos['sin_resolver'] ?? 0)), 'expedientes abiertos', $sol(['resuelto' => 'no'])],
        ] as $k): ?>
            <div class="col-12 col-md-4">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($k[3]) ?>">
                    <span class="text-muted small fw-semibold"><?= esc($k[0]) ?></span>
                    <div class="dgs-kpi-valor"><?= esc($k[1]) ?></div>
                    <small class="text-muted"><?= esc($k[2]) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0 fw-bold"><i class="bi bi-exclamation-circle me-1 text-danger"></i>Con más de 7 días sin movimiento</h3>
            <span class="small text-muted"><?= $num((int) ($tiempos['total_demoradas'] ?? 0)) ?> en total</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($tiempos['demoradas'])): ?>
                <div class="empty-state">
                    <i class="bi bi-check2-circle" aria-hidden="true"></i>
                    <div class="empty-titulo">Ninguna atascada</div>
                    <div class="empty-texto">Todas las solicitudes abiertas tuvieron actividad en la última semana.</div>
                </div>
            <?php else: ?>
                <?php foreach ($tiempos['demoradas'] as $d): ?>
                    <a class="dgs-fila" href="<?= esc($d['url']) ?>">
                        <span class="dgs-fila-etiqueta">
                            <span class="fw-bold font-monospace"><?= esc($d['folio']) ?></span>
                            <span class="small text-muted d-block"><?= esc($d['tramite']) ?></span>
                        </span>
                        <span class="badge estatus-badge <?= estatus_badge((string) $d['estatus']) ?>"><?= esc($d['estatus']) ?></span>
                        <span class="dgs-fila-valor text-danger"><?= $num((int) $d['dias']) ?> d</span>
                        <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== VIGENCIAS ===================== -->
<section id="vigencias" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-calendar-x me-1 text-danger"></i>Vigencias</h2>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Por vencer en 30 días', (int) ($vigencias['por_vencer_30'] ?? 0), 'bg-warning text-dark', $sol(['estatus' => 'Vigente'])],
            ['Por vencer en 90 días', (int) ($vigencias['por_vencer_90'] ?? 0), 'bg-info text-dark', $sol(['estatus' => 'Vigente'])],
            ['Vencidas sin cerrar',   (int) ($vigencias['vencidas'] ?? 0),     'bg-danger text-white', $sol(['estatus' => 'Vencido'])],
        ] as $k): ?>
            <div class="col-4">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($k[3]) ?>">
                    <span class="badge <?= $k[2] ?> mb-1"><?= $num($k[1]) ?></span>
                    <div class="small fw-semibold text-dark"><?= esc($k[0]) ?></div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if (! empty($vigencias['detalle'])): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Próximas a vencer</h3></div>
            <div class="card-body p-0">
                <?php foreach ($vigencias['detalle'] as $v): ?>
                    <a class="dgs-fila" href="<?= esc($v['url']) ?>">
                        <span class="dgs-fila-etiqueta">
                            <span class="fw-bold font-monospace"><?= esc($v['folio']) ?></span>
                            <span class="small text-muted d-block"><?= esc($v['tramite']) ?></span>
                        </span>
                        <span class="small text-muted">vence <?= esc($v['vence']) ?></span>
                        <span class="dgs-fila-valor"><?= $num((int) $v['dias']) ?> d</span>
                        <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- ===================== DOCUMENTOS ===================== -->
<section id="documentos" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-file-earmark me-1 text-secondary"></i>Documentos recibidos</h2>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Total de archivos',   $num((int) ($documentos['total'] ?? 0)), 'del mes: ' . $num((int) ($documentos['del_mes'] ?? 0)), '/admin/documentos'],
            ['Espacio ocupado',     $fmtBytes((float) ($documentos['bytes'] ?? 0)), 'en el almacén de documentos', '/admin/documentos'],
            ['Sin validar',         $num((int) ($documentos['por_validar'] ?? 0)), 'validados: ' . $num((int) ($documentos['validados'] ?? 0)), '/admin/documentos?validado=no'],
        ] as $k): ?>
            <div class="col-12 col-md-4">
                <a class="stat-card-elevated dgs-kpi p-3 h-100" href="<?= esc($k[3]) ?>">
                    <span class="text-muted small fw-semibold"><?= esc($k[0]) ?></span>
                    <div class="dgs-kpi-valor"><?= esc($k[1]) ?></div>
                    <small class="text-muted"><?= esc($k[2]) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <h3 class="h6 mb-0 fw-bold">Archivos por tipo</h3>
            <a class="small" href="/admin/documentos">Abrir el almacén completo</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($documentos['tipos'])): ?>
                <div class="empty-state">
                    <i class="bi bi-file-earmark-x" aria-hidden="true"></i>
                    <div class="empty-titulo">Sin documentos</div>
                    <div class="empty-texto">Nadie ha subido archivos al sistema todavía.</div>
                </div>
            <?php else: ?>
                <?php $maxDoc = max(array_column($documentos['tipos'], 'total')); ?>
                <?php foreach ($documentos['tipos'] as $t): ?>
                    <a class="dgs-fila" href="/admin/documentos?tipo=<?= urlencode((string) $t['tipo']) ?>" title="Ver uno por uno los archivos de este tipo">
                        <span class="dgs-fila-etiqueta"><?= esc(tipo_documento_nombre((string) $t['tipo'])) ?>
                            <small class="text-muted d-block"><?= $fmtBytes((float) $t['bytes']) ?> · <?= (int) $t['validados'] ?> validados</small>
                        </span>
                        <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $t['total'], $maxDoc)) ?>%"></span></span>
                        <span class="dgs-fila-valor"><?= $num((int) $t['total']) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== CUENTAS ===================== -->
<section id="cuentas" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-people me-1 text-primary"></i>Cuentas y roles</h2>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Cuentas totales',      (int) ($cuentas['total'] ?? 0),        '/admin/usuarios'],
            ['Activas',              (int) ($cuentas['activos'] ?? 0),      '/admin/usuarios?estado=activo'],
            ['Inactivas',            (int) ($cuentas['inactivos'] ?? 0),    '/admin/usuarios?estado=inactivo'],
            ['Alta en el mes',       (int) ($cuentas['nuevos_mes'] ?? 0),   '/admin/usuarios'],
            ['Con CURP capturada',   (int) ($cuentas['con_curp'] ?? 0),     '/admin/usuarios'],
            ['Con INE cargado',      (int) ($cuentas['con_ine'] ?? 0),      '/admin/usuarios'],
        ] as $k): ?>
            <div class="col-6 col-md-4 col-xl-2">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($k[2]) ?>">
                    <div class="dgs-mini-valor"><?= $num($k[1]) ?></div>
                    <small class="text-muted"><?= esc($k[0]) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Distribución por rol</h3></div>
        <div class="card-body p-0">
            <?php $totalRoles = array_sum(array_column($cuentas['roles'] ?? [], 'total')); ?>
            <?php foreach ($cuentas['roles'] ?? [] as $r): ?>
                <a class="dgs-fila" href="/admin/usuarios?rol=<?= urlencode((string) $r['rol']) ?>">
                    <span class="dgs-fila-etiqueta">
                        <span class="rol-chip <?= $r['rol'] === 'administrador' ? 'rol-admin' : ($r['rol'] === 'operador_ventanilla' ? 'rol-ventanilla' : 'rol-ciudadano') ?>">
                            <?= esc($etiquetaRol((string) $r['rol'])) ?>
                        </span>
                    </span>
                    <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $r['total'], $totalRoles)) ?>%"></span></span>
                    <span class="dgs-fila-valor"><?= $num((int) $r['total']) ?></span>
                    <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
            <?php if (empty($cuentas['roles'])): ?>
                <div class="p-3 text-muted small">Sin cuentas registradas.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===================== AUDITORÍA ===================== -->
<section id="auditoria" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-shield-check me-1 text-dark"></i>Bitácora del sistema</h2>
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-4">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/auditoria">
                <div class="dgs-kpi-valor"><?= $num((int) ($auditoria['total'] ?? 0)) ?></div>
                <small class="text-muted">eventos acumulados</small>
            </a>
        </div>
        <div class="col-6 col-md-4">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/auditoria?fecha_desde=<?= date('Y-m-01') ?>">
                <div class="dgs-kpi-valor"><?= $num((int) ($auditoria['del_mes'] ?? 0)) ?></div>
                <small class="text-muted">eventos de <?= esc(nombre_mes()) ?></small>
            </a>
        </div>
        <div class="col-12 col-md-4">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/auditoria?accion=login_fallido">
                <div class="dgs-kpi-valor text-danger"><?= $num((int) ($auditoria['fallidos'] ?? 0)) ?></div>
                <small class="text-muted">intentos de acceso fallidos</small>
            </a>
        </div>
    </div>
    <div class="row g-2 g-md-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Por entidad</h3></div>
                <div class="card-body p-0">
                    <?php $maxEnt = max(1, ...array_map(static fn ($r) => (int) $r['total'], $auditoria['entidades'] ?: [['total' => 1]])); ?>
                    <?php foreach ($auditoria['entidades'] ?? [] as $e): ?>
                        <a class="dgs-fila" href="/admin/auditoria?entidad=<?= urlencode((string) $e['entidad']) ?>">
                            <span class="dgs-fila-etiqueta"><?= esc($e['entidad']) ?></span>
                            <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $e['total'], $maxEnt)) ?>%"></span></span>
                            <span class="dgs-fila-valor"><?= $num((int) $e['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($auditoria['entidades'])): ?><div class="p-3 text-muted small">Sin eventos.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Por acción</h3></div>
                <div class="card-body p-0">
                    <?php $maxAcc = max(1, ...array_map(static fn ($r) => (int) $r['total'], $auditoria['acciones'] ?: [['total' => 1]])); ?>
                    <?php foreach ($auditoria['acciones'] ?? [] as $a): ?>
                        <a class="dgs-fila" href="/admin/auditoria?accion=<?= urlencode((string) $a['accion']) ?>">
                            <span class="dgs-fila-etiqueta"><code><?= esc($a['accion']) ?></code></span>
                            <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $a['total'], $maxAcc)) ?>%"></span></span>
                            <span class="dgs-fila-valor"><?= $num((int) $a['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($auditoria['acciones'])): ?><div class="p-3 text-muted small">Sin eventos.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Quién opera más el sistema</h3></div>
                <div class="card-body p-0">
                    <?php $maxOp = max(1, ...array_map(static fn ($r) => (int) $r['total'], $auditoria['operadores'] ?: [['total' => 1]])); ?>
                    <?php foreach ($auditoria['operadores'] ?? [] as $o): ?>
                        <a class="dgs-fila" href="/admin/auditoria?usuario_id=<?= (int) $o['usuario_id'] ?>">
                            <span class="dgs-fila-etiqueta">
                                <?= esc($o['nombre']) ?>
                                <small class="text-muted d-block">ID <?= (int) $o['usuario_id'] ?></small>
                            </span>
                            <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $o['total'], $maxOp)) ?>%"></span></span>
                            <span class="dgs-fila-valor"><?= $num((int) $o['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($auditoria['operadores'])): ?><div class="p-3 text-muted small">Sin operaciones registradas.</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== TRÁNSITO ===================== -->
<section id="transito" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-cone-striped me-1 text-danger"></i>App de agentes de tránsito</h2>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Boletas sincronizadas', (int) ($transito['total'] ?? 0), '/admin/app-transito/boletas'],
            ['Boletas de ' . nombre_mes(), (int) ($transito['del_mes'] ?? 0), '/admin/app-transito/boletas'],
            ['Agentes activos',       (int) ($transito['agentes_activos'] ?? 0), '/admin/app-transito'],
            ['Faltas en catálogo',    (int) ($transito['faltas_catalogo'] ?? 0), '/admin/app-transito/catalogo'],
        ] as $k): ?>
            <div class="col-6 col-lg-3">
                <a class="stat-card-elevated dgs-kpi p-3" href="<?= esc($k[2]) ?>">
                    <div class="dgs-kpi-valor"><?= $num($k[1]) ?></div>
                    <small class="text-muted"><?= esc($k[0]) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="row g-2 g-md-3">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Estado de las boletas</h3></div>
                <div class="card-body p-0">
                    <?php $maxEst = max(1, ...array_map(static fn ($r) => (int) $r['total'], $transito['estados'] ?: [['total' => 1]])); ?>
                    <?php foreach ($transito['estados'] ?? [] as $e): ?>
                        <a class="dgs-fila" href="/admin/app-transito/boletas?estado=<?= urlencode((string) $e['estado']) ?>">
                            <span class="dgs-fila-etiqueta"><?= esc(ucfirst((string) $e['estado'])) ?></span>
                            <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $e['total'], $maxEst)) ?>%"></span></span>
                            <span class="dgs-fila-valor"><?= $num((int) $e['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($transito['estados'])): ?><div class="p-3 text-muted small">Sin boletas.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Agentes que más infraccionan</h3></div>
                <div class="card-body p-0">
                    <?php $maxAg = max(1, ...array_map(static fn ($r) => (int) $r['total'], $transito['agentes'] ?: [['total' => 1]])); ?>
                    <?php foreach ($transito['agentes'] ?? [] as $a): ?>
                        <a class="dgs-fila" href="/admin/app-transito/boletas?placa=<?= urlencode((string) $a['placa']) ?>">
                            <span class="dgs-fila-etiqueta">
                                <?= esc($a['nombre']) ?>
                                <small class="text-muted d-block font-monospace"><?= esc($a['placa']) ?></small>
                            </span>
                            <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $a['total'], $maxAg)) ?>%"></span></span>
                            <span class="dgs-fila-valor"><?= $num((int) $a['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($transito['agentes'])): ?><div class="p-3 text-muted small">Sin boletas por agente.</div><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Fundamentos más impuestos</h3></div>
                <div class="card-body p-0">
                    <?php foreach ($transito['faltas'] ?? [] as $f): ?>
                        <a class="dgs-fila" href="/admin/app-transito/boletas?falta=<?= urlencode((string) $f['fundamento']) ?>" title="Ver las boletas que citan exactamente este fundamento">
                            <span class="dgs-fila-etiqueta"><code class="small"><?= esc($f['fundamento']) ?></code></span>
                            <span class="dgs-fila-valor"><?= $num((int) $f['total']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (empty($transito['faltas'])): ?><div class="p-3 text-muted small">Sin faltas registradas.</div><?php endif; ?>
                    <div class="p-3 border-top small text-muted">
                        <i class="bi bi-camera me-1"></i>Con evidencia fotográfica: <?= $num((int) ($transito['con_foto'] ?? 0)) ?> ·
                        <i class="bi bi-slash-circle me-1"></i>Sin placas: <?= $num((int) ($transito['sin_placas'] ?? 0)) ?> ·
                        <i class="bi bi-person-x me-1"></i>Conductor ausente: <?= $num((int) ($transito['ausente'] ?? 0)) ?><br>
                        <i class="bi bi-calculator me-1"></i>Promedio de la falta: <?= $num((float) ($transito['uma_promedio'] ?? 0)) ?> UMA (estimado, no incluye reincidencias)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Sesiones de la app: quién puede estar en turno ahora mismo. Cada token
    // vigente es un inicio de sesión que todavía no caduca ni se cerró.
    $sesionesVigentes = (int) ($transito['sesiones_vigentes'] ?? 0);
    $enTurno          = (array) ($transito['agentes_en_turno'] ?? []);
    $ultimaApp        = (string) ($transito['ultima_actividad_app'] ?? '');
    ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="h6 mb-0 fw-bold">Sesiones de la app de agentes</h3>
            <div class="d-flex gap-3 flex-wrap">
                <a class="small" href="/admin/sesiones-app">Listado completo de sesiones</a>
                <a class="small" href="/admin/app-transito">Altas, bajas y parámetros</a>
            </div>
        </div>
        <div class="card-body py-3">
            <div class="row g-2 mb-3">
                <?php foreach ([
                    ['Vigentes ahora',          (int) ($transito['sesiones_vigentes'] ?? 0), 'text-success', 'vigentes'],
                    ['Vencidas sin cerrar',     (int) ($transito['sesiones_vencidas'] ?? 0), 'text-warning', 'vencidas'],
                    ['Revocadas',               (int) ($transito['sesiones_revocadas'] ?? 0), 'text-secondary', 'revocadas'],
                    ['Tokens en el registro',   (int) ($transito['sesiones_total'] ?? 0), 'text-muted', ''],
                ] as $s): ?>
                    <div class="col-6 col-md-3">
                        <a class="dgs-kpi border rounded p-2 h-100" href="/admin/sesiones-app<?= $s[3] !== '' ? '?estado=' . $s[3] : '' ?>"
                           title="Ver en el listado de sesiones exactamente esta cifra">
                            <div class="fw-bold <?= $s[2] ?>"><?= $num($s[1]) ?></div>
                            <small class="text-muted"><?= esc($s[0]) ?></small>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($enTurno !== []): ?>
                <div class="small text-muted mb-1">Agentes con sesión abierta. Pulsa la placa para ver solo sus boletas:</div>
                <div class="dgs-nav">
                    <?php foreach ($enTurno as $ag): ?>
                        <a class="dgs-nav-item" href="/admin/app-transito/boletas?placa=<?= urlencode((string) $ag['placa']) ?>"
                           title="Último uso: <?= $ag['ultimo_uso'] !== '' ? esc(formatear_fecha($ag['ultimo_uso'])) : 'todavía sin uso' ?>">
                            <i class="bi bi-person-badge me-1" aria-hidden="true"></i><?= esc($ag['nombre']) ?>
                            <span class="font-monospace small text-muted ms-1"><?= esc($ag['placa']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="small text-muted"><i class="bi bi-moon-stars me-1" aria-hidden="true"></i>Ningún agente tiene la app abierta en este momento.</div>
            <?php endif; ?>
            <div class="small text-muted mt-3 border-top pt-2">
                <i class="bi bi-clock-history me-1" aria-hidden="true"></i>Última actividad desde la app:
                <?php if ($ultimaApp !== ''): ?>
                    <strong><?= esc(formatear_fecha($ultimaApp)) ?></strong>
                    <?= $sesionesVigentes === 0 ? ' (la sesión ya caducó)' : '' ?>
                <?php else: ?>
                    sin registros
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===================== VERIFICACIONES FÍSICAS ===================== -->
<?php
$verifTotal       = (int) ($verif['total'] ?? 0);
$verifDictamen    = (int) ($verif['con_dictamen'] ?? 0);
$verifSinDictamen = (int) ($verif['pendientes'] ?? 0);
$verifVencidas    = (int) ($verif['vencidas_sin_dictamen'] ?? 0);
$verifProximas    = (array) ($verif['proximas'] ?? []);
$verifRezagadas   = (array) ($verif['rezagadas'] ?? []);
?>
<section id="verificaciones" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-clipboard2-check me-1 text-info"></i>Verificaciones físicas</h2>
    <div class="row g-2 g-md-3 mb-3">
        <?php foreach ([
            ['Citas registradas',      $verifTotal, ''],
            ['Con dictamen capturado', $verifDictamen, 'con_dictamen'],
            ['Todavía sin dictamen',   $verifSinDictamen, 'sin_dictamen'],
            ['Vencidas sin dictamen',  $verifVencidas, 'vencidas'],
        ] as $k): ?>
            <div class="col-6 col-lg-3">
                <a class="stat-card-elevated dgs-kpi p-3" href="/admin/verificaciones<?= $k[2] !== '' ? '?estado=' . $k[2] : '' ?>"
                   title="Ver en la agenda de verificaciones exactamente esta cifra">
                    <div class="dgs-kpi-valor"><?= $num($k[1]) ?></div>
                    <small class="text-muted"><?= esc($k[0]) ?></small>
                </a>
            </div>
        <?php endforeach; ?>
        <div class="col-12">
            <a class="small" href="/admin/verificaciones"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Abrir la agenda completa de verificaciones físicas (todas las citas y sus dictámenes)</a>
        </div>
        <div class="col-12">
            <a class="small" href="/admin/solicitudes?tramite=UR-TT-T-02"><i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Abrir en el listado las solicitudes de verificación física (UR-TT-T-02)</a>
        </div>
    </div>
    <div class="row g-2 g-md-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h3 class="h6 mb-0 fw-bold">Próximas citas en la agenda</h3>
                    <a class="small" href="/admin/verificaciones?estado=proximas" title="Ver todas las citas futuras en la agenda">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($verifProximas === []): ?>
                        <div class="empty-state">
                            <div class="empty-titulo"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>Sin citas programadas</div>
                            <p class="empty-texto mb-0">Ningún expediente tiene una inspección futura agendada.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($verifProximas as $c): ?>
                            <a class="dgs-fila" href="<?= esc(site_url('admin/solicitudes/' . rawurlencode((string) $c['folio']))) ?>" title="Abrir el expediente <?= esc($c['folio']) ?>">
                                <span class="dgs-fila-etiqueta">
                                    <span class="fw-semibold font-monospace"><?= esc($c['folio']) ?></span>
                                    <small class="text-muted d-block"><?= esc(tramite_nombre((string) $c['tramite'])) ?> · <?= esc($c['estatus']) ?><?= $c['resultado'] !== '' ? ' · ' . esc($c['resultado']) : '' ?></small>
                                </span>
                                <span class="dgs-fila-valor text-nowrap"><?= esc(formatear_fecha((string) $c['fecha_cita'], 'd/m/Y H:i')) ?></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h3 class="h6 mb-0 fw-bold text-danger">Citas que ya pasaron sin resultado</h3>
                    <a class="small text-muted" href="/admin/verificaciones?estado=vencidas"><?= $num($verifVencidas) ?> en total</a>
                </div>
                <div class="card-body p-0">
                    <?php if ($verifRezagadas === []): ?>
                        <div class="empty-state">
                            <div class="empty-titulo"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Al día</div>
                            <p class="empty-texto mb-0">No hay inspecciones pasadas que deban su dictamen.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($verifRezagadas as $c): ?>
                            <a class="dgs-fila" href="<?= esc(site_url('admin/solicitudes/' . rawurlencode((string) $c['folio']))) ?>" title="Capturar el dictamen en el expediente">
                                <span class="dgs-fila-etiqueta">
                                    <span class="fw-semibold font-monospace"><?= esc($c['folio']) ?></span>
                                    <small class="text-muted d-block"><?= esc($c['estatus']) ?></small>
                                </span>
                                <span class="dgs-fila-valor text-nowrap"><?= esc(formatear_fecha((string) $c['fecha_cita'], 'd/m/Y H:i')) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($verifVencidas > count($verifRezagadas)): ?>
                            <div class="p-2 small text-muted border-top">Mostrando las <?= $num(count($verifRezagadas)) ?> más antiguas de <?= $num($verifVencidas) ?>.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== CATÁLOGOS ===================== -->
<section id="catalogos" class="dgs-seccion">
    <h2 class="dgs-titulo"><i class="bi bi-journal-bookmark me-1 text-primary"></i>Catálogos que sostienen la operación</h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-lg-3">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/tarifas">
                <div class="dgs-kpi-valor"><?= $num((int) ($catalogos['tarifas'] ?? 0)) ?></div>
                <small class="text-muted">tarifas registradas</small>
                <div class="small text-success mt-1"><?= $num((int) ($catalogos['tarifas_vigentes'] ?? 0)) ?> vigentes hoy</div>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/concesiones">
                <div class="dgs-kpi-valor"><?= $num((int) ($catalogos['concesiones'] ?? 0)) ?></div>
                <small class="text-muted">concesiones del padrón</small>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/formatos">
                <div class="dgs-kpi-valor"><?= $num((int) ($catalogos['formatos'] ?? 0)) ?></div>
                <small class="text-muted">formatos oficiales publicados</small>
                <?php if (! empty($catalogos['formatos_faltantes'])): ?>
                    <div class="small text-danger mt-1"><?= $num(count($catalogos['formatos_faltantes'])) ?> sin formato</div>
                <?php endif; ?>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-card-elevated dgs-kpi p-3" href="/admin/convocatorias">
                <div class="dgs-kpi-valor"><?= $num((int) ($catalogos['convocatorias'] ?? 0)) ?></div>
                <small class="text-muted">convocatorias UR-01</small>
            </a>
        </div>
    </div>

    <?php if (! empty($catalogos['formatos_faltantes'])): ?>
        <div class="alert alert-warning mt-3 mb-0 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            Trámites sin formato oficial cargado:
            <?= esc(implode(', ', array_column($catalogos['formatos_faltantes'], 'nombre'))) ?>.
            <a href="/admin/formatos?estado=sin" class="alert-link">Cargar ahora</a>
        </div>
    <?php endif; ?>

    <?php if (! empty($catalogos['concesiones_por_estatus']) || ! empty($catalogos['convocatorias_por_estatus'])): ?>
        <div class="row g-2 g-md-3 mt-2">
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Concesiones por estatus</h3></div>
                    <div class="card-body p-0">
                        <?php $maxCon = max(1, ...array_map(static fn ($r) => (int) $r['total'], $catalogos['concesiones_por_estatus'] ?: [['total' => 1]])); ?>
                        <?php foreach ($catalogos['concesiones_por_estatus'] ?? [] as $c): ?>
                            <a class="dgs-fila" href="/admin/concesiones?estatus=<?= urlencode((string) $c['estatus']) ?>" title="Ver solo las concesiones en este estatus">
                                <span class="dgs-fila-etiqueta"><?= esc($c['estatus']) ?></span>
                                <span class="dgs-barra" aria-hidden="true"><span style="width: <?= esc((string) $porcentaje((int) $c['total'], $maxCon)) ?>%"></span></span>
                                <span class="dgs-fila-valor"><?= $num((int) $c['total']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-2"><h3 class="h6 mb-0 fw-bold">Convocatorias por estatus</h3></div>
                    <div class="card-body p-0">
                        <?php foreach ($catalogos['convocatorias_por_estatus'] ?? [] as $c): ?>
                            <a class="dgs-fila" href="/admin/convocatorias">
                                <span class="dgs-fila-etiqueta"><?= esc($c['estatus']) ?></span>
                                <span class="dgs-fila-valor"><?= $num((int) $c['total']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <?php if (empty($catalogos['convocatorias_por_estatus'])): ?><div class="p-3 text-muted small">Sin convocatorias.</div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // La UMA y el catálogo oficial son los que convierten una falta en pesos.
    $umaVigente     = (float) ($catalogos['uma_vigente'] ?? 0);
    $umaActualizado = (string) ($catalogos['uma_actualizado'] ?? '');
    $sinMapear      = (int) ($catalogos['checklist_sin_mapear'] ?? 0);
    ?>
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h3 class="h6 mb-0 fw-bold"><i class="bi bi-sliders me-1 text-primary" aria-hidden="true"></i>Configuración que fija el dinero</h3>
            <a class="small" href="/admin/app-transito">Editar los parámetros de la app</a>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <div class="small text-muted">UMA usada para calcular multas</div>
                    <?php if ($umaVigente > 0): ?>
                        <div class="fs-4 fw-bold">$<?= number_format($umaVigente, 2) ?></div>
                        <?php if ($umaActualizado !== ''): ?>
                            <div class="small text-muted">actualizada el <?= esc(formatear_fecha($umaActualizado)) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="fw-bold text-danger">Sin configurar</div>
                        <div class="small text-muted">Ningún monto de infracción se puede calcular hasta cargar el valor de la UMA vigente.</div>
                    <?php endif; ?>
                </div>
                <div class="col-6 col-md-4">
                    <div class="small text-muted">Catálogo oficial de infracciones</div>
                    <div class="fs-4 fw-bold"><?= $num((int) ($catalogos['infracciones_oficiales'] ?? 0)) ?></div>
                    <div class="small text-muted">fundamentos con monto en UMA</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="small text-muted">Casillas de la boleta física</div>
                    <div class="fs-4 fw-bold"><?= $num((int) ($catalogos['checklist_total'] ?? 0)) ?></div>
                    <div class="small <?= $sinMapear > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>"><?= $num($sinMapear) ?> sin fundamento asignado</div>
                </div>
            </div>
            <div class="small text-muted mt-3 border-top pt-2">
                <i class="bi bi-gear me-1" aria-hidden="true"></i>Parámetros de sistema: <?= $num((int) ($catalogos['parametros_sistema'] ?? 0)) ?> ·
                Parámetros de la app de tránsito: <?= $num((int) ($catalogos['parametros_app'] ?? 0)) ?>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
