<?php declare(strict_types=1);

/**
 * Variables inyectadas por el controlador del panel vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, int|float> $estadisticas */
$estadisticas = $__view['estadisticas'] ?? [];

/** @var list<array<string, float|int|string>> $filasTramite */
$filasTramite = $__view['filasTramite'] ?? [];

/** @var list<string> $tramitesActivos */
$tramitesActivos = $__view['tramitesActivos'] ?? [];

/** @var list<array<string, int|string>> $alertas */
$alertas = $__view['alertas'] ?? [];

/** @var array<string, int|float|list> $tiempos */
$tiempos = $__view['tiempos'] ?? [];

/** @var string $hoy */
$hoy = $__view['hoy'] ?? date('Y-m-d');

/** @var list<array<string, object|null>> $ultimasActividades */
$ultimasActividades = $__view['ultimasActividades'] ?? [];

// Respaldo: si el agregador no devolvió filas, se reconstruyen desde el conteo
// simple para que la pantalla nunca quede vacía.
if ($filasTramite === []) {
    foreach ($__view['estadisticas']['por_tramite'] ?? [] as $clave => $cantidad) {
        $filasTramite[] = [
            'tramite'    => (string) $clave,
            'nombre'     => tramite_nombre((string) $clave),
            'total'      => (int) $cantidad,
            'pct'        => 0.0,
            'cobradas'   => 0,
            'abiertas'   => (int) $cantidad,
            'presencial' => 0,
            'digital'    => 0,
            'cobrado'    => 0.0,
            'por_cobrar' => 0.0,
            'promedio'   => 0.0,
        ];
    }
}

$__roles = (array) (session('roles') ?? []);
$__esAdmin = in_array('administrador', $__roles, true);
// Ventanilla: respeta los permisos que definió el administrador.
$__permisosOp = \App\Libraries\PermisosOperador::todos();
$__puedeSolicitudes = $__esAdmin
    || !in_array('operador_ventanilla', $__roles, true)
    || ($__permisosOp['solicitudes'] ?? true);

/**
 * Arma la URL del listado de solicitudes con filtros. Toda métrica del panel
 * usa esta función, así el número que se ve es el mismo listado que se abre.
 */
$sol = static function (array $parametros): string {
    $limpios = array_filter($parametros, static fn ($v): bool => $v !== '' && $v !== null);
    return site_url('admin/solicitudes' . ($limpios === [] ? '' : '?' . http_build_query($limpios)));
};
$num = static fn (int|float|null $v): string => number_format((float) $v, 0, '.', ',');
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Inicio<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
if (! function_exists('badge_color_estatus')) {
    function badge_color_estatus(string $estatus): string
    {
        return match ($estatus) {
            'Recibido'       => 'bg-secondary',
            'En revisión'    => 'bg-primary',
            'Prevención'     => 'bg-warning text-dark',
            'Pago pendiente' => 'bg-info text-dark',
            'Pagado'         => 'bg-success',
            'Vigente'        => 'bg-success',
            'Vencido'        => 'bg-danger',
            'Rechazado'      => 'bg-danger',
            'Concluido'      => 'bg-dark',
            default          => 'bg-secondary',
        };
    }
}
?>

<div class="panel-titulo">
    <div>
        <h2><i class="bi bi-speedometer2" aria-hidden="true"></i> Panel de control</h2>
        <p class="panel-subtitulo"><?= $__esAdmin
            ? 'Vista general del sistema: solicitudes, catálogos, auditoría y configuración.'
            : 'Vista operativa de ventanilla: da seguimiento a las solicitudes recibidas.' ?></p>
    </div>
    <?php if ($__puedeSolicitudes): ?>
    <a href="/admin/solicitudes/nueva" class="btn btn-success btn-sm ms-auto">
        <i class="bi bi-plus-lg me-1"></i>Registrar Solicitud Presencial
    </a>
    <?php endif; ?>
</div>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cómo usar este inicio',
    'texto'  => $__puedeSolicitudes
        ? 'Cada tarjeta, cada número y cada barra es un enlace: tócalo y abrirás el listado ya filtrado con exactamente esos registros. En el desglose del sistema encontrarás el detalle de todo lo que pasa en la plataforma.'
        : 'Vista general del sistema. Las secciones disponibles para tu cuenta son las que el administrador activó en Permisos del Operador.',
]) ?>

<?php if ($__esAdmin && $alertas !== []): ?>
<div class="dgs-seccion">
    <h3 class="dgs-titulo"><i class="bi bi-bell me-2 text-danger" aria-hidden="true"></i>Atención requerida</h3>
    <div class="card border-0 shadow-sm overflow-hidden">
        <?php foreach ($alertas as $alerta): ?>
            <?php $destino = (string) ($alerta['url'] ?? ''); ?>
            <?php if ($destino === ''): ?>
                <?php continue; ?>
            <?php endif; ?>
            <a href="<?= esc($destino) ?>" class="dgs-fila dgs-fila--<?= esc((string) ($alerta['nivel'] ?? 'info')) ?>">
                <span class="dgs-fila-etiqueta"><?= esc((string) ($alerta['rotulo'] ?? 'Alerta')) ?></span>
                <span class="dgs-fila-valor"><?= $num((int) ($alerta['total'] ?? 0)) ?></span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($__puedeSolicitudes): ?>
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= site_url('admin/solicitudes') ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Total Solicitudes</span>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-dark fs-3"><?= number_format($estadisticas['total'] ?? 0) ?></h3>
                <small class="text-muted">Ver registro global</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['estatus' => 'Recibido']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Pendientes</span>
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-warning fs-3"><?= number_format($estadisticas['pendientes'] ?? 0) ?></h3>
                <small class="text-muted">Rec: <?= (int) ($estadisticas['recibido'] ?? 0) ?> | Rev: <?= (int) ($estadisticas['en_revision'] ?? 0) ?></small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['estatus' => 'En revisión']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">En Revisión</span>
                    <div class="stat-icon-wrapper bg-info-subtle text-info">
                        <i class="bi bi-search"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-info fs-3"><?= number_format($estadisticas['en_revision'] ?? 0) ?></h3>
                <small class="text-muted">En dictamen técnico</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['pago' => 'cobrado', 'estatus' => 'Pagado']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Vigentes / Pagados</span>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-success fs-3"><?= number_format($estadisticas['pagados_vigentes'] ?? 0) ?></h3>
                <small class="text-muted">Trámites autorizados</small>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-4 col-lg">
        <a href="<?= $sol(['estatus' => 'Rechazado']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Rechazados</span>
                    <div class="stat-icon-wrapper bg-danger-subtle text-danger">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-danger fs-3"><?= number_format($estadisticas['rechazados'] ?? 0) ?></h3>
                <small class="text-muted">Con observaciones</small>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<?php if ($__puedeSolicitudes): ?>
<div class="row g-2 g-md-3 mb-3 mb-md-4">
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['estatus' => 'Pago pendiente']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Pago pendiente</span>
                    <div class="stat-icon-wrapper bg-warning-subtle text-warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-warning fs-3"><?= number_format($estadisticas['pago_pendiente'] ?? 0) ?></h3>
                <small class="text-muted">Esperando cobro</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['pago' => 'cobrado', 'fecha_campo' => 'pago', 'desde' => $hoy, 'hasta' => $hoy]) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Monto cobrado hoy</span>
                    <div class="stat-icon-wrapper bg-success-subtle text-success">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-success fs-4"><?= formatear_dinero((float) ($estadisticas['monto_hoy'] ?? 0)) ?></h3>
                <small class="text-muted">Ingresos del día</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['estatus' => 'Vencido']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Vencidos</span>
                    <div class="stat-icon-wrapper bg-danger-subtle text-danger">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-danger fs-3"><?= number_format($estadisticas['vencido'] ?? 0) ?></h3>
                <small class="text-muted">Permiso sin renovarse</small>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-4 col-lg">
        <a href="<?= $sol(['estatus' => 'Concluido']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Concluidos</span>
                    <div class="stat-icon-wrapper bg-secondary-subtle text-secondary">
                        <i class="bi bi-archive"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-dark fs-3"><?= number_format($estadisticas['concluido'] ?? 0) ?></h3>
                <small class="text-muted">Expedientes cerrados</small>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-4 col-lg">
        <a href="<?= $sol(['resuelto' => 'no']) ?>" class="text-decoration-none">
            <div class="stat-card-elevated p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold">Sin resolución</span>
                    <div class="stat-icon-wrapper bg-primary-subtle text-primary">
                        <i class="bi bi-question-circle"></i>
                    </div>
                </div>
                <h3 class="mb-0 fw-bold text-primary fs-3"><?= number_format((int) ($tiempos['sin_resolver'] ?? 0)) ?></h3>
                <small class="text-muted">
                    <?php if (($tiempos['promedio_dias'] ?? 0) > 0): ?>
                        Duración promedio: <?= esc((string) $tiempos['promedio_dias']) ?> días
                    <?php else: ?>
                        Sin expedientes cerrados aún
                    <?php endif; ?>
                </small>
            </div>
        </a>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($filasTramite)): ?>
<div class="dgs-seccion">
    <h3 class="dgs-titulo"><i class="bi bi-pie-chart me-2 text-primary" aria-hidden="true"></i>Conteo por trámite</h3>
    <div class="row g-2 g-md-3">
        <?php foreach ($filasTramite as $fila): ?>
            <?php
            $clave = (string) $fila['tramite'];
            $total = (int) $fila['total'];
            $peso  = min(100.0, max(0.0, (float) $fila['pct']));
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm dgs-tarjeta">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                            <a href="<?= $sol(['tramite' => $clave]) ?>" class="dgs-enlace-titulo fw-semibold small">
                                <?= esc((string) $fila['nombre']) ?>
                            </a>
                            <?php if ($peso > 0.0): ?>
                                <span class="small text-muted text-nowrap"><?= esc((string) $peso) ?>%</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $sol(['tramite' => $clave]) ?>" class="text-decoration-none">
                            <h4 class="mb-2 fw-bold text-primary"><?= $num($total) ?> <span class="fs-6 fw-normal text-muted">solicitudes</span></h4>
                        </a>
                        <div class="dgs-barra w-100 mb-2" role="presentation">
                            <span style="width: <?= esc((string) $peso) ?>%;"></span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="<?= $sol(['tramite' => $clave, 'pago' => 'cobrado']) ?>" class="badge text-bg-success text-decoration-none">
                                <?= $num((int) $fila['cobradas']) ?> pagadas
                            </a>
                            <a href="<?= $sol(['tramite' => $clave, 'estatus' => 'Pago pendiente']) ?>" class="badge text-bg-warning text-decoration-none">
                                <?= formatear_dinero((float) $fila['por_cobrar']) ?> por cobrar
                            </a>
                            <a href="<?= $sol(['tramite' => $clave, 'origen' => 'presencial']) ?>" class="badge bg-light text-dark border text-decoration-none">
                                <?= $num((int) $fila['presencial']) ?> en ventanilla
                            </a>
                            <a href="<?= $sol(['tramite' => $clave, 'origen' => 'digital']) ?>" class="badge bg-light text-dark border text-decoration-none">
                                <?= $num((int) $fila['digital']) ?> en portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($__esAdmin && !empty($tramitesActivos)): ?>
<div class="dgs-seccion">
    <h3 class="dgs-titulo"><i class="bi bi-boxes me-2 text-secondary" aria-hidden="true"></i>Trámites habilitados</h3>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-2 d-flex flex-wrap gap-1">
            <?php foreach ($tramitesActivos as $codigo): ?>
                <a href="<?= $sol(['tramite' => (string) $codigo]) ?>" class="badge bg-light text-dark border text-decoration-none px-3 py-2">
                    <?= esc(tramite_nombre((string) $codigo)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($__esAdmin): ?>
<div class="card mb-3 mb-md-4 border-0 shadow-sm bg-primary text-white">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h6 class="mb-1 fw-bold"><i class="bi bi-graph-up-arrow me-2" aria-hidden="true"></i>Desglose completo del sistema</h6>
            <p class="mb-0 small opacity-75">Indicadores, ingresos, flujo de estatus, documentos, cuentas, auditoría y App Tránsito con cada cifra enlazada a su listado.</p>
        </div>
        <a href="/admin/desglose" class="btn btn-light btn-sm fw-semibold text-primary">Abrir desglose</a>
    </div>
</div>
<?php endif; ?>

<?php if ($__esAdmin): ?>
<div class="dgs-seccion">
    <h3 class="dgs-titulo"><i class="bi bi-compass me-2 text-primary" aria-hidden="true"></i>Secciones del sistema</h3>
    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            <a href="/admin/usuarios" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Usuarios y ciudadanos</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/auditoria" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Auditoría (quién hizo qué)</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/app-transito/boletas" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Boletas de campo de agentes</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/tarifas" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Tarifario y UMA</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/concesiones" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Concesiones</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/formatos" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Formatos oficiales por trámite</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/convocatorias" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Convocatorias</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
            <a href="/admin/permisos-operador" class="dgs-fila">
                <span class="dgs-fila-etiqueta">Permisos del operador</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-3 mb-md-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center gap-2 py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Últimas 10 actividades</h6>
        <div class="d-flex gap-1">
            <a href="<?= $sol([]) ?>" class="btn btn-outline-primary btn-sm">Ver todas</a>
            <?php if ($__esAdmin): ?>
                <a href="/admin/desglose#flujo" class="btn btn-outline-secondary btn-sm" title="Cómo se movieron los estatus">Flujo</a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Mobile Card Feed (< 768px) -->
    <div class="d-block d-md-none divide-y">
        <?php if (empty($ultimasActividades)): ?>
            <div class="text-center text-muted py-4">Sin actividades recientes</div>
        <?php else: ?>
            <?php foreach ($ultimasActividades as $act): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span class="small text-muted"><?= formatear_fecha($act['historial']->fecha) ?></span>
                        <span class="badge estatus-badge <?= badge_color_estatus($act['historial']->estatus_nuevo) ?>">
                            <?= esc($act['historial']->estatus_nuevo) ?>
                        </span>
                    </div>
                    <div class="fw-semibold small mb-1">
                        <?= esc($act['usuario']->nombre_completo ?? $act['usuario']->username ?? 'Sistema') ?>
                    </div>
                    <?php if ($act['solicitud']): ?>
                        <div class="small mb-1">
                            <?php if ($__puedeSolicitudes): ?>
                                <a href="<?= site_url('admin/solicitudes/' . $act['solicitud']->folio) ?>" class="fw-bold font-monospace">
                                    <?= esc($act['solicitud']->folio) ?>
                                </a>
                            <?php else: ?>
                                <span class="fw-bold font-monospace"><?= esc($act['solicitud']->folio) ?></span>
                            <?php endif; ?>
                            <span class="text-muted">· <?= tramite_nombre($act['solicitud']->tramite) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($act['historial']->comentario)): ?>
                        <div class="small text-muted bg-light p-2 rounded mt-2">
                            <?= esc($act['historial']->comentario) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Desktop Table (>= 768px) -->
    <div class="table-responsive d-none d-md-block">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Solicitud</th>
                    <th>Estatus nuevo</th>
                    <th>Comentario</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimasActividades)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Sin actividades recientes</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimasActividades as $act): ?>
                        <?php
                        $usuarioId = (int) ($act['historial']->usuario_id ?? 0);
                        $rutaUsuario = $usuarioId > 0 && $__esAdmin
                            ? site_url('admin/auditoria?usuario_id=' . $usuarioId)
                            : null;
                        $rutaEstatus = $__puedeSolicitudes && !empty($act['solicitud']->folio)
                            ? site_url('admin/solicitudes/' . $act['solicitud']->folio)
                            : null;
                        ?>
                        <tr>
                            <td class="text-nowrap small"><?= formatear_fecha($act['historial']->fecha) ?></td>
                            <td>
                                <?php if ($act['usuario']): ?>
                                    <?php if ($rutaUsuario !== null): ?>
                                        <a href="<?= esc($rutaUsuario) ?>" class="fw-semibold small text-decoration-none">
                                            <?= esc($act['usuario']->nombre_completo ?? $act['usuario']->username ?? '—') ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="fw-semibold small"><?= esc($act['usuario']->nombre_completo ?? $act['usuario']->username ?? '—') ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($act['solicitud']): ?>
                                    <?php if ($__puedeSolicitudes): ?>
                                        <a href="<?= site_url('admin/solicitudes/' . $act['solicitud']->folio) ?>" class="fw-bold text-decoration-none">
                                            <code><?= esc($act['solicitud']->folio) ?></code>
                                        </a>
                                    <?php else: ?>
                                        <span class="fw-bold"><code><?= esc($act['solicitud']->folio) ?></code></span>
                                    <?php endif; ?>
                                    <div class="small text-muted"><?= tramite_nombre($act['solicitud']->tramite) ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($act['historial']->estatus_anterior)): ?>
                                    <span class="badge estatus-badge bg-light text-dark border text-decoration-line-through me-1">
                                        <?= esc($act['historial']->estatus_anterior) ?>
                                    </span>
                                    <span class="text-muted mx-1">→</span>
                                <?php endif; ?>
                                <?php if ($rutaEstatus !== null): ?>
                                    <a href="<?= esc($rutaEstatus) ?>" class="badge estatus-badge <?= badge_color_estatus($act['historial']->estatus_nuevo) ?> text-decoration-none">
                                        <?= esc($act['historial']->estatus_nuevo) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge estatus-badge <?= badge_color_estatus($act['historial']->estatus_nuevo) ?>">
                                        <?= esc($act['historial']->estatus_nuevo) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= !empty($act['historial']->comentario) ? esc($act['historial']->comentario) : '<span class="text-muted">—</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($__puedeSolicitudes && !empty($tiempos['demoradas'])): ?>
<div class="dgs-seccion">
    <h3 class="dgs-titulo">
        <i class="bi bi-alarm me-2 text-danger" aria-hidden="true"></i>
        Expedientes sin movimiento
        <a href="<?= $sol(['resuelto' => 'no', 'dir' => 'asc']) ?>" class="btn btn-outline-secondary btn-sm ms-auto">Ver sin resolver</a>
    </h3>
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <span class="small text-muted">
                Promedio de resolución: <strong><?= esc((string) ($tiempos['promedio_dias'] ?? 0)) ?> días</strong>
                · Máximo: <strong><?= (int) ($tiempos['maximo_dias'] ?? 0) ?> días</strong>
            </span>
            <span class="small text-muted"><?= $num((int) ($tiempos['total_demoradas'] ?? 0)) ?> en total</span>
        </div>
        <?php foreach ($tiempos['demoradas'] as $demora): ?>
            <a href="<?= esc((string) $demora['url']) ?>" class="dgs-fila dgs-fila--danger">
                <span class="dgs-fila-etiqueta">
                    <span class="fw-bold font-monospace"><?= esc((string) $demora['folio']) ?></span>
                    <span class="d-block small text-muted"><?= esc((string) $demora['tramite']) ?> · <?= esc((string) $demora['estatus']) ?></span>
                </span>
                <span class="dgs-fila-valor"><?= (int) $demora['dias'] ?> días</span>
                <i class="bi bi-chevron-right dgs-fila-flecha" aria-hidden="true"></i>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
