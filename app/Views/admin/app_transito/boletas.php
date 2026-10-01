<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Boletas de Infracción de Campo<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="container-fluid p-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <a href="/admin/app-transito" class="text-decoration-none text-muted small">&larr; Volver a Configuración</a>
                <span class="text-muted small">/</span>
                <span class="badge bg-primary-subtle text-primary">Sincronización en Campo</span>
            </div>
            <h2 class="h3 fw-bold mt-1 mb-0 text-dark">Registro de Boletas de Infracción</h2>
            <p class="text-muted small mb-0">Boletas levantadas por agentes de tránsito y transmitidas a la base de datos de Supabase.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border p-2">
                <i class="bi bi-currency-dollar me-1 text-primary"></i> UMA Vigente: <strong>$<?= number_format($valorUma, 2) ?> MXN</strong>
            </span>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/admin/app-transito/boletas" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0" name="q" placeholder="Buscar folio, placa o infractor..." value="<?= esc($filtros['q'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="placa" class="form-select">
                        <option value="">-- Todos los agentes --</option>
                        <?php foreach ($agentes as $ag): ?>
                            <option value="<?= esc($ag->placa) ?>" <?= ($filtros['placa'] ?? '') === $ag->placa ? 'selected' : '' ?>>
                                <?= esc($ag->placa) ?> - <?= esc($ag->nombre_completo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="fecha" class="form-control" value="<?= esc($filtros['fecha'] ?? '') ?>" title="Filtrar por fecha">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Filtrar</button>
                    <?php if (!empty($filtros['q']) || !empty($filtros['placa']) || !empty($filtros['fecha'])): ?>
                        <a href="/admin/app-transito/boletas" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Boletas -->
    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small text-muted text-uppercase">
                        <tr>
                            <th class="ps-4">Folio / Fecha</th>
                            <th>Agente</th>
                            <th>Infractor & Vehículo</th>
                            <th>Falta Cometida</th>
                            <th>Monto Estimado</th>
                            <th>Garantías</th>
                            <th class="text-end pe-4">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($boletas)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    No se encontraron boletas con los filtros seleccionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($boletas as $b): ?>
                                <?php
                                    $minUma = (float)($b->falta_monto_min_uma ?? 0);
                                    $maxUma = (float)($b->falta_monto_max_uma ?? 0);
                                    $minPesos = $minUma * $valorUma;
                                    $maxPesos = $maxUma * $valorUma;
                                    $garantias = is_string($b->garantias_retenidas) ? json_decode($b->garantias_retenidas, true) : (array)$b->garantias_retenidas;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold font-monospace text-primary"><?= esc($b->folio) ?></div>
                                        <small class="text-muted"><?= esc($b->fecha_infraccion) ?> <?= esc(substr($b->hora_infraccion, 0, 5)) ?> hrs</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?= esc($b->agente_nombre ?? $b->agente_placa) ?></div>
                                        <span class="badge bg-light text-muted border small"><?= esc($b->agente_placa) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small">
                                            <?= $b->conductor_ausente ? '<span class="text-warning fw-normal"><i class="bi bi-exclamation-triangle"></i> Conductor Ausente</span>' : esc($b->infractor_nombre ?: 'Sin nombre') ?>
                                        </div>
                                        <div class="small text-muted">
                                            <span class="badge bg-dark font-monospace text-white me-1"><?= esc($b->vehiculo_placas ?: 'S/P') ?></span>
                                            <?= esc($b->vehiculo_marca) ?> <?= esc($b->vehiculo_linea) ?> (<?= esc($b->vehiculo_color) ?>)
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-danger small"><?= esc($b->falta_fundamento_legal) ?></div>
                                        <div class="text-truncate text-muted small" style="max-width: 220px;"><?= esc($b->falta_descripcion) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small">$<?= number_format($minPesos, 2) ?> - $<?= number_format($maxPesos, 2) ?></div>
                                        <small class="text-muted"><?= $minUma ?> a <?= $maxUma ?> UMAs</small>
                                    </td>
                                    <td>
                                        <?php if (!empty($garantias)): ?>
                                            <?php foreach ($garantias as $g): ?>
                                                <span class="badge bg-secondary-subtle text-secondary small me-1"><?= esc(ucfirst((string)$g)) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Ninguna</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                            data-bs-toggle="modal" data-bs-target="#modalBoleta<?= $b->id ?>">
                                            Ver Detalle
                                        </button>
                                    </td>
                                </tr>

                                <!-- MODAL DETALLE BOLETA -->
                                <div class="modal fade" id="modalBoleta<?= $b->id ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-0 pb-0">
                                                <div>
                                                    <span class="badge bg-primary-subtle text-primary font-monospace"><?= esc($b->folio) ?></span>
                                                    <h5 class="modal-title fw-bold mt-1">Detalle de la Boleta de Infracción</h5>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="row g-3 mb-3">
                                                    <div class="col-sm-6">
                                                        <label class="small text-muted text-uppercase fw-semibold d-block">Agente Responsable</label>
                                                        <div class="fw-semibold text-dark"><?= esc($b->agente_nombre ?? $b->agente_placa) ?> (Placa: <?= esc($b->agente_placa) ?>)</div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="small text-muted text-uppercase fw-semibold d-block">Fecha y Hora</label>
                                                        <div class="fw-semibold text-dark"><?= esc($b->fecha_infraccion) ?> a las <?= esc($b->hora_infraccion) ?></div>
                                                    </div>
                                                </div>

                                                <div class="p-3 bg-light rounded-3 mb-3">
                                                    <label class="small text-muted text-uppercase fw-semibold d-block">Lugar de los Hechos</label>
                                                    <div class="text-dark fw-semibold mb-1"><?= esc($b->lugar) ?></div>
                                                    <?php if (!empty($b->latitud) && !empty($b->longitud)): ?>
                                                        <a href="https://maps.google.com/?q=<?= esc($b->latitud) ?>,<?= esc($b->longitud) ?>" target="_blank" class="small text-primary text-decoration-none">
                                                            <i class="bi bi-geo-alt-fill me-1"></i> Ver en Google Maps (<?= esc($b->latitud) ?>, <?= esc($b->longitud) ?>)
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="row g-3 mb-3">
                                                    <div class="col-sm-6">
                                                        <div class="p-3 border rounded-3 h-100">
                                                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Conductor / Infractor</h6>
                                                            <?php if ($b->conductor_ausente): ?>
                                                                <span class="badge bg-warning text-dark mb-2">Conductor Ausente</span>
                                                            <?php else: ?>
                                                                <div class="fw-semibold text-dark"><?= esc($b->infractor_nombre ?: 'No registrado') ?></div>
                                                                <div class="small text-muted">Licencia: <?= esc($b->infractor_licencia ?: 'No presentó') ?></div>
                                                                <div class="small text-muted">Domicilio: <?= esc($b->infractor_domicilio ?: 'No especificado') ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <div class="p-3 border rounded-3 h-100">
                                                            <h6 class="fw-bold small text-uppercase text-muted mb-2">Vehículo Involucrado</h6>
                                                            <div class="fw-semibold text-dark">
                                                                Placas: <span class="badge bg-dark font-monospace text-white"><?= esc($b->vehiculo_placas ?: 'Sin Placas') ?></span>
                                                            </div>
                                                            <div class="small text-muted mt-1"><?= esc($b->vehiculo_marca) ?> <?= esc($b->vehiculo_linea) ?> (<?= esc($b->vehiculo_color) ?>)</div>
                                                            <div class="small text-muted">Tipo: <?= esc(ucfirst($b->vehiculo_tipo ?? 'particular')) ?></div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="p-3 border rounded-3 mb-3">
                                                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Falta y Fundamento Legal</h6>
                                                    <div class="fw-bold text-danger"><?= esc($b->falta_fundamento_legal) ?></div>
                                                    <p class="text-dark small mb-2"><?= esc($b->falta_descripcion) ?></p>
                                                    <div class="small text-muted">
                                                        Hechos narrados por el agente:<br>
                                                        <em class="text-dark"><?= esc($b->hechos ?: 'Sin observaciones adicionales.') ?></em>
                                                    </div>
                                                </div>

                                                <!-- FOTOGRAFÍAS DE EVIDENCIA -->
                                                <h6 class="fw-bold small text-uppercase text-muted mb-2">Evidencias Fotográficas</h6>
                                                <div class="row g-2">
                                                    <?php if (!empty($b->foto_placa_url)): ?>
                                                        <div class="col-4">
                                                            <div class="border rounded-3 p-1 text-center bg-light">
                                                                <img src="<?= esc($b->foto_placa_url) ?>" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;" alt="Foto Placa">
                                                                <small class="d-block text-muted mt-1">Placa</small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($b->foto_contexto_url)): ?>
                                                        <div class="col-4">
                                                            <div class="border rounded-3 p-1 text-center bg-light">
                                                                <img src="<?= esc($b->foto_contexto_url) ?>" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;" alt="Foto Contexto">
                                                                <small class="d-block text-muted mt-1">Contexto</small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($b->foto_documento_url)): ?>
                                                        <div class="col-4">
                                                            <div class="border rounded-3 p-1 text-center bg-light">
                                                                <img src="<?= esc($b->foto_documento_url) ?>" class="img-fluid rounded" style="max-height: 140px; object-fit: cover;" alt="Foto Documento">
                                                                <small class="d-block text-muted mt-1">Documento / Garantía</small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (empty($b->foto_placa_url) && empty($b->foto_contexto_url) && empty($b->foto_documento_url)): ?>
                                                        <div class="col-12 text-muted small py-2">No se adjuntaron fotografías a esta boleta.</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
