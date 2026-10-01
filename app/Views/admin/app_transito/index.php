<?php declare(strict_types=1); ?>
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
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
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
                    <p class="text-muted small mt-2 mb-0">Base de cálculo automático para infracciones</p>
                </div>
            </div>
        </div>

        <!-- BOLETAS RECIBIDAS -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Boletas en Supabase</span>
                        <div class="rounded-3 p-2 bg-success-subtle text-success">
                            <i class="bi bi-shield-check fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalBoletas) ?></h3>
                        <span class="text-success small fw-semibold">Sincronizadas</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Generadas por agentes en operativos viales</p>
                </div>
            </div>
        </div>

        <!-- AGENTES ACTIVOS -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Agentes Registrados</span>
                        <div class="rounded-3 p-2 bg-info-subtle text-info">
                            <i class="bi bi-person-badge fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= count($agentes) ?></h3>
                        <span class="text-muted small">operativos</span>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Credenciales con acceso a la app móvil</p>
                </div>
            </div>
        </div>

        <!-- CATÁLOGO FALTAS -->
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 p-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-medium text-uppercase">Faltas en Catálogo</span>
                        <div class="rounded-3 p-2 bg-warning-subtle text-warning">
                            <i class="bi bi-journal-bookmark fs-5"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="fw-bold mb-0 text-dark"><?= number_format($totalCatalogo) ?></h3>
                        <a href="/admin/app-transito/catalogo" class="small text-decoration-none ms-auto">Editar &rarr;</a>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Reglamento de Movilidad de Uriangato</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- FORMULARIO DE PARÁMETROS DEL SISTEMA (UMA, ETC.) -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
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
                                <input type="number" step="0.01" min="1" class="form-control form-control-lg border-start-0 fw-bold text-primary"
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
                                    Intervalo Sync (seg)
                                </label>
                                <input type="number" min="30" max="3600" class="form-control"
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
                                <input type="text" class="form-control" id="app_version" name="app_version"
                                    value="<?= esc($parametros['app_version'] ?? '1.0.0') ?>" required>
                            </div>
                            <div class="col-sm-6">
                                <label for="municipio" class="form-label fw-semibold small text-dark mb-1">
                                    Municipio Oficial
                                </label>
                                <input type="text" class="form-control" id="municipio" name="municipio"
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
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
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
                <div class="card-body px-4 py-3">
                    <div class="table-responsive">
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
                                            No hay agentes registrados en la base de datos de Supabase.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($agentes as $ag): ?>
                                        <tr>
                                            <td>
                                                <span class="badge font-monospace bg-dark text-white px-2 py-1">
                                                    <?= esc($ag->placa) ?>
                                                </span>
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
                                            <td class="text-end">
                                                <form action="/admin/app-transito/agentes/toggle/<?= $ag->id ?>" method="POST" class="d-inline" data-no-loading="true">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-link text-decoration-none p-0 text-<?= $ag->activo == 1 ? 'warning' : 'success' ?>"
                                                        title="<?= $ag->activo == 1 ? 'Desactivar agente' : 'Activar agente' ?>">
                                                        <i class="bi bi-power fs-5"></i>
                                                    </button>
                                                </form>
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
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
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
        <div class="card-body px-4 py-3">
            <div class="table-responsive">
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
                        <?php if (empty($ultimasBoletas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    Aún no hay boletas sincronizadas en Supabase.<br>
                                    <small>Cuando un agente en campo cree y sincronice una boleta, aparecerá aquí inmediatamente.</small>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ultimasBoletas as $b): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold font-monospace text-primary">
                                            <?= esc($b->folio) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?= esc($b->agente_nombre ?? $b->agente_placa) ?></div>
                                        <span class="badge bg-light text-muted border small"><?= esc($b->agente_placa) ?></span>
                                    </td>
                                    <td class="small text-muted">
                                        <?= esc($b->fecha_infraccion) ?><br>
                                        <span class="text-secondary"><?= esc(substr($b->hora_infraccion, 0, 5)) ?> hrs</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark text-white font-monospace"><?= esc($b->vehiculo_placas ?: 'Sin placas') ?></span>
                                        <div class="small text-muted"><?= esc($b->vehiculo_marca) ?> <?= esc($b->vehiculo_linea) ?></div>
                                    </td>
                                    <td class="small">
                                        <div class="fw-semibold text-danger"><?= esc($b->falta_fundamento_legal) ?></div>
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
            <form action="/admin/app-transito/agentes/guardar" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p class="text-muted small mb-3">El agente usará este número de placa y contraseña para ingresar a la app en su teléfono.</p>

                    <div class="mb-3">
                        <label for="placa" class="form-label small fw-semibold">Número de Placa / Credencial <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="placa" name="placa" placeholder="Ej. AGT-305" required>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label small fw-semibold">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" placeholder="Ej. Oficial Juan Pérez Morales" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label for="sector" class="form-label small fw-semibold">Sector o Zona</label>
                            <input type="text" class="form-control" id="sector" name="sector" value="Sector Centro">
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
                        <label for="password" class="form-label small fw-semibold">Contraseña Inicial <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mínimo 4 caracteres" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Registrar Agente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Cálculo en vivo de ejemplo de UMA
document.getElementById('valor_uma_vigente')?.addEventListener('input', function(e) {
    const val = parseFloat(e.target.value) || 0;
    const calc = (val * 10).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('umaPreviewCalc').innerHTML = `Ejemplo: Una multa de <strong>10 UMAS</strong> = <strong>$${calc} MXN</strong>`;
});
</script>

<?= $this->endSection() ?>
