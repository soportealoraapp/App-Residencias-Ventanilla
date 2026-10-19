<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\AppTransitoController::index() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, string> $parametros */
$parametros     = $__view['parametros'] ?? [];
/** @var array<int, object> $agentes */
$agentes        = $__view['agentes'] ?? [];
/** @var int $totalBoletas */
$totalBoletas   = (int) ($__view['totalBoletas'] ?? 0);
/** @var array<int, object> $ultimasBoletas */
$ultimasBoletas = $__view['ultimasBoletas'] ?? [];
/** @var int $totalCatalogo */
$totalCatalogo  = (int) ($__view['totalCatalogo'] ?? 0);
/** @var float $valorUma */
$valorUma       = (float) ($__view['valorUma'] ?? 0);
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Configuración App Agentes de Tránsito<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <!-- Header banner minimalista -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill text-bg-primary px-3 py-2 fw-semibold">
                    <i class="bi bi-phone me-1"></i> App Móvil Operativa
                </span>
                <span class="badge rounded-pill bg-dark-subtle text-dark border px-3 py-2">
                    Versión <?= esc($parametros['app_version'] ?? '1.0.0') ?>
                </span>
            </div>
            <h2 class="h3 fw-bold mt-2 mb-1 text-dark">Configuración y Parámetros Operativos</h2>
            <p class="text-muted small mb-0">
                Gestiona el valor de la UMA, parámetros de sincronización, agentes autorizados y catálogo de boletas de campo.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/agentes" target="_blank" class="btn btn-outline-primary d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-box-arrow-up-right me-2"></i> Abrir App Web (/agentes)
            </a>
            <a href="/admin/app-transito/boletas" class="btn btn-primary d-inline-flex align-items-center shadow-sm">
                <i class="bi bi-receipt-cutoff me-2"></i> Ver Boletas Sincronizadas
            </a>
        </div>
    </div>

    <!-- Tarjetas de métricas clave -->
    <div class="row g-3 mb-4">
        <!-- UMA VIGENTE -->
        <div class="col-sm-6 col-xl-3">
            <a class="card border-0 shadow-sm rounded-4 h-100 bg-white text-decoration-none stat-card-elevated" href="#formulario-parametros" aria-label="Ir al formulario de parámetros">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Valor UMA Vigente</span>
                        <div class="rounded-3 p-2 bg-primary-subtle text-primary">
                            <i class="bi bi-currency-dollar fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark">$<?= number_format($valorUma, 2) ?></h3>
                        <span class="text-muted small">MXN / UMA</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Toca para ajustar la base de cálculo de infracciones</p>
                </div>
            </a>
        </div>

        <!-- BOLETAS RECIBIDAS -->
        <div class="col-sm-6 col-xl-3">
            <a class="card border-0 shadow-sm rounded-4 h-100 bg-white text-decoration-none stat-card-elevated" href="/admin/app-transito/boletas" aria-label="Ver boletas sincronizadas">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Boletas recibidas</span>
                        <div class="rounded-3 p-2 bg-success-subtle text-success">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalBoletas) ?></h3>
                        <span class="text-success small fw-semibold">Sincronizadas</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Toca para abrir el libro de boletas de campo</p>
                </div>
            </a>
        </div>

        <!-- AGENTES ACTIVOS -->
        <div class="col-sm-6 col-xl-3">
            <a class="card border-0 shadow-sm rounded-4 h-100 bg-white text-decoration-none stat-card-elevated" href="#agentes-autorizados" aria-label="Ir a la lista de agentes autorizados">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Agentes Registrados</span>
                        <div class="rounded-3 p-2 bg-info-subtle text-info">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= count($agentes) ?></h3>
                        <span class="text-muted small">con credencial</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Toca para revisar quién puede capturar boletas</p>
                </div>
            </a>
        </div>

        <!-- CATÁLOGO FALTAS -->
        <div class="col-sm-6 col-xl-3">
            <a class="card border-0 shadow-sm rounded-4 h-100 bg-white text-decoration-none stat-card-elevated" href="/admin/app-transito/catalogo" aria-label="Abrir catálogo de infracciones">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Faltas en Catálogo</span>
                        <div class="rounded-3 p-2 bg-warning-subtle text-warning">
                            <i class="bi bi-journal-bookmark fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalCatalogo) ?></h3>
                        <span class="small text-primary ms-auto">Abrir &rarr;</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Reglamento de Movilidad de Uriangato</p>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- FORMULARIO DE PARÁMETROS DEL SISTEMA (UMA, ETC.) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100" id="formulario-parametros">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 bg-primary-subtle text-primary">
                            <i class="bi bi-sliders fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Parámetros del Sistema</h5>
                            <small class="text-muted">Afectan el cálculo de multas y comportamiento de la app</small>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 py-3">
                    <form action="/admin/app-transito/guardar-parametros" method="POST" id="formParametros">
                        <?= csrf_field() ?>

                        <!-- VALOR UMA -->
                        <div class="mb-3">
                            <label for="valor_uma_vigente" class="form-label fw-semibold small text-dark mb-1">
                                Valor UMA Vigente ($ MXN) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 fw-semibold">$</span>
                                <input inputmode="decimal" type="number" step="0.01" min="1" class="form-control form-control-lg border-start-0 fw-bold text-primary"
                                    id="valor_uma_vigente" name="valor_uma_vigente"
                                    value="<?= esc($parametros['valor_uma_vigente'] ?? '117.31') ?>" required>
                                <span class="input-group-text bg-light">MXN</span>
                            </div>
                            <div class="form-text mt-1 text-muted small" id="umaPreviewCalc">
                                Ejemplo: Una multa de <strong>10 UMAS</strong> = <strong>$<?= number_format($valorUma * 10, 2) ?> MXN</strong>
                            </div>
                        </div>

                        <!-- FOTOGRAFÍAS OBLIGATORIAS -->
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="fotos_obligatorias" class="form-label fw-semibold small text-dark mb-1">
                                    Fotos Obligatorias
                                </label>
                                <select class="form-select" id="fotos_obligatorias" name="fotos_obligatorias">
                                    <option value="1" <?= ($parametros['fotos_obligatorias'] ?? '3') === '1' ? 'selected' : '' ?>>1 foto mínima</option>
                                    <option value="2" <?= ($parametros['fotos_obligatorias'] ?? '3') === '2' ? 'selected' : '' ?>>2 fotos</option>
                                    <option value="3" <?= ($parametros['fotos_obligatorias'] ?? '3') === '3' ? 'selected' : '' ?>>3 fotos (Recomendado)</option>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="sync_intervalo_segundos" class="form-label fw-semibold small text-dark mb-1">
                                    Intervalo de sincronización (s)
                                </label>
                                <input inputmode="numeric" type="number" min="30" max="3600" class="form-control"
                                    id="sync_intervalo_segundos" name="sync_intervalo_segundos"
                                    value="<?= esc($parametros['sync_intervalo_segundos'] ?? '300') ?>" required>
                            </div>
                        </div>

                        <!-- VERSIÓN Y MUNICIPIO -->
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label for="app_version" class="form-label fw-semibold small text-dark mb-1">
                                    Versión de App Móvil
                                </label>
                                <input autocomplete="off" autocapitalize="characters" spellcheck="false" type="text" class="form-control" id="app_version" name="app_version"
                                    value="<?= esc($parametros['app_version'] ?? '1.0.0') ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="municipio" class="form-label fw-semibold small text-dark mb-1">
                                    Municipio Oficial
                                </label>
                                <input autocomplete="off" autocapitalize="words" spellcheck="false" type="text" class="form-control" id="municipio" name="municipio"
                                    value="<?= esc($parametros['municipio'] ?? 'Uriangato, Guanajuato') ?>" required>
                            </div>
                        </div>

                        <!-- MODO ESTRICTO FOTOS -->
                        <div class="form-check form-switch mb-4 p-3 rounded-3 bg-light border">
                            <input class="form-check-input ms-0 me-3" type="checkbox" role="switch"
                                id="modo_estricto_fotos" name="modo_estricto_fotos" value="true"
                                <?= ($parametros['modo_estricto_fotos'] ?? 'true') === 'true' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold small text-dark" for="modo_estricto_fotos">
                                Modo Estricto de Evidencias
                                <div class="text-muted fw-normal small">Exige capturar placa y contexto antes de emitir la boleta</div>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center shadow-sm">
                            <i class="bi bi-check2-circle me-2 fs-5"></i> Guardar Cambios de Configuración
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- AGENTES AUTORIZADOS -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100" id="agentes-autorizados">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-3 p-2 bg-info-subtle text-info">
                            <i class="bi bi-people fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Agentes Operativos</h5>
                            <small class="text-muted">Personal autorizado para emitir boletas desde el dispositivo</small>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center"
                        data-bs-toggle="modal" data-bs-target="#modalNuevoAgente">
                        <i class="bi bi-person-plus me-1"></i> + Nuevo Agente
                    </button>
                </div>
                <div class="card-body px-3 px-md-4 py-3">
                    <!-- Teléfono: una ficha por agente, la tabla de seis columnas no cabe -->
                    <div class="d-block d-md-none">
                        <?php if (empty($agentes)): ?>
                            <div class="text-center text-muted py-4">No hay agentes registrados.</div>
                        <?php else: ?>
                            <?php foreach ($agentes as $ag): ?>
                                <div class="p-3 border-bottom">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <a class="badge font-monospace bg-dark text-white px-2 py-1 text-decoration-none" href="/admin/app-transito/boletas?placa=<?= esc(rawurlencode((string) $ag->placa)) ?>" title="Ver las boletas de <?= esc($ag->placa, 'attr') ?>">
                                                <?= esc($ag->placa) ?>
                                            </a>
                                            <div class="fw-semibold text-dark mt-1"><?= esc($ag->nombre_completo) ?></div>
                                            <div class="small text-muted"><?= esc($ag->sector ?? 'Sin sector') ?> · <?= esc(ucfirst($ag->rol)) ?></div>
                                        </div>
                                        <?php if ((int) $ag->activo === 1): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 text-nowrap">
                                                <i class="bi bi-check-circle me-1"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 text-nowrap">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="editarAgente(<?= esc(json_encode([
                                                'id'              => (int) $ag->id,
                                                'placa'           => $ag->placa,
                                                'nombre_completo' => $ag->nombre_completo,
                                                'sector'          => $ag->sector,
                                                'rol'             => $ag->rol,
                                                'activo'          => (int) $ag->activo,
                                            ]), 'attr') ?>)">
                                            <i class="bi bi-pencil-square me-1"></i>Editar
                                        </button>
                                        <form action="/admin/app-transito/agentes/toggle/<?= (int) $ag->id ?>" method="POST" class="d-inline" data-no-loading="true">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-<?= (int) $ag->activo === 1 ? 'warning' : 'success' ?>">
                                                <i class="bi bi-power me-1"></i><?= (int) $ag->activo === 1 ? 'Desactivar' : 'Activar' ?>
                                            </button>
                                        </form>
                                        <?php if (in_array('administrador', (array) (session('roles') ?? []), true)): ?>
                                            <form action="/admin/app-transito/agentes/eliminar/<?= (int) $ag->id ?>" method="POST" class="d-inline" data-no-loading="true"
                                                onsubmit="return confirm('¿Eliminar definitivamente al agente <?= esc($ag->placa, 'js') ?>? Si ya emitió boletas no se podrá eliminar; en ese caso desactívalo.');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash me-1"></i>Eliminar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="/admin/app-transito/boletas?placa=<?= esc(rawurlencode((string) $ag->placa)) ?>" class="btn btn-sm btn-link text-decoration-none">
                                            Ver boletas
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th>Placa</th>
                                    <th>Nombre del Agente</th>
                                    <th>Sector</th>
                                    <th>Rol</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-end">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($agentes)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            No hay agentes registrados.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($agentes as $ag): ?>
                                        <tr>
                                            <td>
                                                <a class="badge font-monospace bg-dark text-white px-2 py-1 text-decoration-none" href="/admin/app-transito/boletas?placa=<?= esc(rawurlencode((string) $ag->placa)) ?>" title="Ver las boletas de <?= esc($ag->placa, 'attr') ?>">
                                                    <?= esc($ag->placa) ?>
                                                </a>
                                            </td>
                                            <td class="fw-semibold text-dark">
                                                <?= esc($ag->nombre_completo) ?>
                                            </td>
                                            <td class="small text-muted">
                                                <?= esc($ag->sector ?? 'Sin sector') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    <?= esc(ucfirst($ag->rol)) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($ag->activo == 1): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">
                                                        <i class="bi bi-check-circle me-1"></i> Activo
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2">
                                                        Inactivo
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 me-2 text-primary"
                                                    title="Editar agente" aria-label="Editar agente <?= esc($ag->placa, 'attr') ?>"
                                                    onclick="editarAgente(<?= esc(json_encode([
                                                        'id'              => (int) $ag->id,
                                                        'placa'           => $ag->placa,
                                                        'nombre_completo' => $ag->nombre_completo,
                                                        'sector'          => $ag->sector,
                                                        'rol'             => $ag->rol,
                                                        'activo'          => (int) $ag->activo,
                                                    ]), 'attr') ?>)">
                                                    <i class="bi bi-pencil-square fs-5"></i>
                                                </button>
                                                <form action="/admin/app-transito/agentes/toggle/<?= $ag->id ?>" method="POST" class="d-inline" data-no-loading="true">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 text-<?= $ag->activo == 1 ? 'warning' : 'success' ?>"
                                                        title="<?= $ag->activo == 1 ? 'Desactivar agente' : 'Activar agente' ?>">
                                                        <i class="bi bi-power fs-5"></i>
                                                    </button>
                                                </form>
                                                <?php if (in_array('administrador', (array) (session('roles') ?? []), true)): ?>
                                                    <form action="/admin/app-transito/agentes/eliminar/<?= $ag->id ?>" method="POST" class="d-inline ms-2" data-no-loading="true"
                                                        onsubmit="return confirm('¿Eliminar definitivamente al agente <?= esc($ag->placa, 'js') ?>? Si ya emitió boletas no se podrá eliminar; en ese caso desactívalo.');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 text-danger" title="Eliminar agente" aria-label="Eliminar agente <?= esc($ag->placa, 'attr') ?>">
                                                            <i class="bi bi-trash fs-5"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN: ÚLTIMAS BOLETAS SINCRONIZADAS -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-header bg-white border-0 pt-4 px-3 px-md-4 pb-2 d-flex justify-content-between align-items-center gap-2 flex-wrap">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 p-2 bg-success-subtle text-success">
                    <i class="bi bi-receipt fs-5"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Últimas Boletas Recibidas</h5>
                    <small class="text-muted">Boletas sincronizadas en tiempo real desde la app móvil</small>
                </div>
            </div>
            <a href="/admin/app-transito/boletas" class="btn btn-sm btn-outline-secondary">
                Ver todas las boletas &rarr;
            </a>
        </div>
        <div class="card-body px-3 px-md-4 py-3">
            <?php if (empty($ultimasBoletas)): ?>
                <div class="empty-state">
                    <i class="bi bi-inbox" aria-hidden="true"></i>
                    <div class="empty-titulo">Aún no hay boletas sincronizadas</div>
                    <div class="empty-texto">Cuando un agente en campo crea y sincronice una boleta, aparecerá aquí inmediatamente.</div>
                </div>
            <?php else: ?>
                <!-- Teléfono: ficha por boleta, la tabla de siete columnas desborda -->
                <div class="d-block d-md-none">
                    <?php foreach ($ultimasBoletas as $b): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <a class="fw-bold font-monospace text-primary text-decoration-none" href="/admin/app-transito/boletas/editar/<?= (int) $b->id ?>" title="Abrir boleta <?= esc($b->folio, 'attr') ?>">
                                    <?= esc($b->folio) ?> <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>
                                </a>
                                <span class="small text-muted text-nowrap">
                                    <?= formatear_fecha((string) $b->fecha_infraccion, 'd/m/Y') ?> · <?= esc(substr((string) $b->hora_infraccion, 0, 5)) ?> hrs
                                </span>
                            </div>
                            <div class="small mt-1">
                                <span class="badge bg-dark text-white font-monospace"><?= esc($b->vehiculo_placas ?: 'Sin placas') ?></span>
                                <span class="text-muted"><?= esc($b->vehiculo_marca) ?> <?= esc($b->vehiculo_linea) ?></span>
                            </div>
                            <div class="small mt-1">
                                <a href="/admin/app-transito/boletas?falta=<?= esc(rawurlencode((string) $b->falta_fundamento_legal)) ?>" class="fw-semibold text-danger text-decoration-none">
                                    <?= esc($b->falta_fundamento_legal) ?>
                                </a>
                                <div class="text-muted"><?= esc($b->falta_descripcion) ?></div>
                            </div>
                            <div class="d-flex flex-wrap gap-1 align-items-center mt-2 small text-muted">
                                <span>Agente:</span>
                                <a class="badge bg-light text-dark border text-decoration-none" href="/admin/app-transito/boletas?placa=<?= esc(rawurlencode((string) $b->agente_placa)) ?>">
                                    <?= esc($b->agente_nombre ?? $b->agente_placa) ?>
                                </a>
                                <?php
                                    $garantias = is_string($b->garantias_retenidas) ? json_decode($b->garantias_retenidas, true) : $b->garantias_retenidas;
                                    $garantias = is_array($garantias) ? $garantias : [];
                                    foreach ($garantias as $g): ?>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= esc(ucfirst((string) $g)) ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($garantias === []): ?>
                                        <span class="badge bg-light text-muted border">Sin garantías</span>
                                    <?php endif; ?>
                                <?php if (!empty($b->foto_placa_url) || !empty($b->foto_contexto_url)): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-camera me-1"></i>Fotos</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">Sin fotos</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th>Folio</th>
                            <th>Agente</th>
                            <th>Fecha/Hora</th>
                            <th>Vehículo / Placa</th>
                            <th>Infracción</th>
                            <th>Garantía</th>
                            <th>Evidencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasBoletas as $b): ?>
                                <tr>
                                    <td>
                                        <a class="fw-bold font-monospace text-primary text-decoration-none" href="/admin/app-transito/boletas/editar/<?= (int) $b->id ?>" title="Abrir boleta <?= esc($b->folio, 'attr') ?>">
                                            <?= esc($b->folio) ?> <i class="bi bi-box-arrow-up-right small" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <a class="fw-semibold text-dark small text-decoration-none" href="/admin/app-transito/boletas?placa=<?= esc(rawurlencode((string) $b->agente_placa)) ?>" title="Ver todas las boletas de este agente"><?= esc($b->agente_nombre ?? $b->agente_placa) ?></a>
                                        <a class="badge bg-light text-muted border small text-decoration-none" href="/admin/app-transito/boletas?placa=<?= esc(rawurlencode((string) $b->agente_placa)) ?>" title="Ver todas las boletas de esta placa"><?= esc($b->agente_placa) ?></a>
                                    </td>
                                    <td class="small text-muted">
                                        <?= formatear_fecha((string) $b->fecha_infraccion, 'd/m/Y') ?><br>
                                        <span class="text-secondary"><?= esc(substr((string) $b->hora_infraccion, 0, 5)) ?> hrs</span>
                                    </td>
                                    <td>
                                        <a class="badge bg-dark text-white font-monospace text-decoration-none" href="/admin/app-transito/boletas?q=<?= esc(rawurlencode((string) $b->vehiculo_placas)) ?>" title="Ver las boletas de esta placa"><?= esc($b->vehiculo_placas ?: 'Sin placas') ?></a>
                                        <div class="small text-muted"><?= esc($b->vehiculo_marca) ?> <?= esc($b->vehiculo_linea) ?></div>
                                    </td>
                                    <td class="small">
                                        <a class="fw-semibold text-danger text-decoration-none" href="/admin/app-transito/boletas?falta=<?= esc(rawurlencode((string) $b->falta_fundamento_legal)) ?>" title="Ver todas las boletas con esta falta">
                                            <?= esc($b->falta_fundamento_legal) ?>
                                        </a>
                                        <div class="text-truncate text-muted" style="max-width: 250px;"><?= esc($b->falta_descripcion) ?></div>
                                    </td>
                                    <td>
                                        <?php 
                                            $garantias = is_string($b->garantias_retenidas) ? json_decode($b->garantias_retenidas, true) : (array)$b->garantias_retenidas;
                                            if (!empty($garantias)): 
                                                foreach ($garantias as $g): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary small me-1"><?= esc(ucfirst((string)$g)) ?></span>
                                                <?php endforeach;
                                            else: ?>
                                                <span class="text-muted small">Ninguna</span>
                                            <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($b->foto_placa_url) || !empty($b->foto_contexto_url)): ?>
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="bi bi-camera me-1"></i> Fotos adjuntas
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin fotos</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL: AGREGAR NUEVO AGENTE -->
<div class="modal fade" id="modalNuevoAgente" tabindex="-1" aria-labelledby="modalNuevoAgenteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalNuevoAgenteLabel">Registrar Nuevo Agente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="/admin/app-transito/agentes/guardar" method="POST" id="formAgente">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="agente_id" value="">
                <div class="modal-body">
                    <p class="text-muted small mb-3">El agente usará este número de placa y contraseña para ingresar a la app en su teléfono.</p>

                    <div class="mb-3">
                        <label for="placa" class="form-label small fw-semibold">Número de Placa / Credencial <span class="text-danger">*</span></label>
                        <input autocomplete="off" autocapitalize="characters" spellcheck="false" type="text" class="form-control text-uppercase" id="placa" name="placa" placeholder="Ej. AGT-305" required>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label small fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
                        <input autocomplete="off" autocapitalize="words" spellcheck="false" type="text" class="form-control" id="nombre_completo" name="nombre_completo" placeholder="Ej. Oficial Juan Pérez Morales" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="sector" class="form-label small fw-semibold">Sector o Zona</label>
                            <input autocomplete="off" autocapitalize="words" spellcheck="false" type="text" class="form-control" id="sector" name="sector" value="Sector Centro">
                        </div>
                        <div class="col-sm-6">
                            <label for="rol" class="form-label small fw-semibold">Rol</label>
                            <select class="form-select" id="rol" name="rol">
                                <option value="operativo" selected>Operativo</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="coordinador">Coordinador</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small fw-semibold" id="agentePasswordLabel">Contraseña Inicial <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 4 caracteres" minlength="4" autocomplete="new-password" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold" id="agenteSubmit">Registrar Agente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Reutiliza el modal de alta para editar un agente existente.
function editarAgente(agente) {
    document.getElementById('modalNuevoAgenteLabel').innerText = 'Editar Agente ' + agente.placa;
    document.getElementById('agente_id').value = agente.id;
    document.getElementById('placa').value = agente.placa;
    document.getElementById('nombre_completo').value = agente.nombre_completo;
    document.getElementById('sector').value = agente.sector || '';
    document.getElementById('rol').value = agente.rol;
    const password = document.getElementById('password');
    password.required = false;
    password.value = '';
    password.placeholder = 'Vacío = conservar la actual';
    document.getElementById('agentePasswordLabel').innerText = 'Nueva contraseña (opcional)';
    document.getElementById('agenteSubmit').innerText = 'Guardar cambios';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalNuevoAgente')).show();
}

document.getElementById('modalNuevoAgente')?.addEventListener('hidden.bs.modal', function () {
    document.getElementById('formAgente').reset();
    document.getElementById('agente_id').value = '';
    document.getElementById('modalNuevoAgenteLabel').innerText = 'Registrar Nuevo Agente';
    const password = document.getElementById('password');
    password.required = true;
    password.placeholder = 'Mínimo 4 caracteres';
    document.getElementById('agentePasswordLabel').innerHTML = 'Contraseña Inicial <span class="text-danger">*</span>';
    document.getElementById('agenteSubmit').innerText = 'Registrar Agente';
});

// Cálculo en vivo de ejemplo de UMA
document.getElementById('valor_uma_vigente')?.addEventListener('input', function(e) {
    const val = parseFloat(e.target.value) || 0;
    const calc = (val * 10).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('umaPreviewCalc').innerHTML = `Ejemplo: Una multa de <strong>10 UMAS</strong> = <strong>$${calc} MXN</strong>`;
});
</script>

<?= $this->endSection() ?>
