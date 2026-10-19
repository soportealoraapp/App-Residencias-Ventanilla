<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\AppTransitoController::boletas() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var list<object> $boletas */
$boletas = $__view['boletas'] ?? [];
/** @var list<object> $agentes */
$agentes = $__view['agentes'] ?? [];
/** @var array<string, string> $filtros */
$filtros = $__view['filtros'] ?? ['placa' => '', 'fecha' => '', 'q' => '', 'estado' => '', 'falta' => ''];
$filtros += ['placa' => '', 'fecha' => '', 'q' => '', 'estado' => '', 'falta' => ''];
/** @var float $valorUma */
$valorUma = (float) ($__view['valorUma'] ?? 0);
/** @var array<string, string> $estados */
$estados = $__view['estados'] ?? [];
?>
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
            <p class="text-muted small mb-0">Boletas levantadas por los agentes de tránsito desde el sitio.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border p-2">
                <i class="bi bi-currency-dollar me-1 text-primary"></i> UMA Vigente: <strong>$<?= number_format($valorUma, 2) ?> MXN</strong>
            </span>
        </div>
    </div>

    <!-- Barra de Filtros y Accesos Rápidos -->
    <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
        <div class="card-body p-3">
            <?php
            // Las etiquetas conservan los demás filtros: cambiar de estado no
            // borra el agente ni la fecha que ya se estaban revisando.
            $urlBoletas = static function (array $cambios) use ($filtros): string {
                $base = [
                    'q'      => (string) ($filtros['q'] ?? ''),
                    'placa'  => (string) ($filtros['placa'] ?? ''),
                    'fecha'  => (string) ($filtros['fecha'] ?? ''),
                    'estado' => (string) ($filtros['estado'] ?? ''),
                    'falta'  => (string) ($filtros['falta'] ?? ''),
                ];
                $limpia = array_filter(array_merge($base, $cambios), static fn ($v): bool => $v !== '' && $v !== null);

                return '/admin/app-transito/boletas' . ($limpia === [] ? '' : '?' . http_build_query($limpia));
            };
            $estadosDisponibles = $estados;
            ?>
            <div class="dgs-nav mb-3">
                <a href="<?= $urlBoletas([]) ?>" class="dgs-nav-item <?= array_filter($filtros) === [] ? 'fw-bold' : '' ?>">
                    <i class="bi bi-list-ul" aria-hidden="true"></i>Todas
                </a>
                <?php if ((string) $filtros['falta'] !== ''): ?>
                    <!-- Llegamos del catálogo de faltas: el filtro debe verse y poder quitarse. -->
                    <a href="<?= $urlBoletas(['falta' => '']) ?>" class="dgs-nav-item fw-bold bg-primary text-white border-0" title="Quitar el filtro de esta falta">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>Falta: <?= esc($filtros['falta']) ?>
                        <i class="bi bi-x-lg small" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <a href="<?= $urlBoletas(['fecha' => date('Y-m-d')]) ?>" class="dgs-nav-item <?= ($filtros['fecha'] ?? '') === date('Y-m-d') ? 'fw-bold' : '' ?>">
                    <i class="bi bi-calendar-event" aria-hidden="true"></i>Las de hoy
                </a>
                <?php foreach ($estadosDisponibles as $claveEstado => $nombreEstado): ?>
                    <a href="<?= $urlBoletas(['estado' => (string) $claveEstado]) ?>" class="dgs-nav-item <?= ($filtros['estado'] ?? '') === (string) $claveEstado ? 'fw-bold' : '' ?>">
                        <?= esc($nombreEstado) ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="/admin/app-transito/boletas" class="row g-2 align-items-center">
                <input type="hidden" name="estado" value="<?= esc($filtros['estado']) ?>">
                <input type="hidden" name="falta" value="<?= esc($filtros['falta']) ?>">
                <div class="col-12 col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input autocomplete="off" spellcheck="false" enterkeyhint="search" type="text" class="form-control border-start-0" name="q" placeholder="Buscar folio, placa o infractor..." value="<?= esc($filtros['q'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <select name="placa" class="form-select">
                        <option value="">-- Todos los agentes --</option>
                        <?php foreach ($agentes as $ag): ?>
                            <option value="<?= esc($ag->placa) ?>" <?= ($filtros['placa'] ?? '') === $ag->placa ? 'selected' : '' ?>>
                                <?= esc($ag->placa) ?> - <?= esc($ag->nombre_completo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" name="fecha" class="form-control" value="<?= esc($filtros['fecha'] ?? '') ?>" title="Filtrar por fecha">
                </div>
                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Filtrar</button>
                    <?php if (array_filter($filtros) !== []): ?>
                        <a href="/admin/app-transito/boletas" class="btn btn-outline-secondary" title="Limpiar"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Boletas en teléfono: tarjetas apiladas, la tabla exige demasiado ancho -->
    <div class="d-block d-md-none mb-3">
        <?php if (empty($boletas)): ?>
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body text-center text-muted py-5">Sin boletas con los filtros seleccionados.</div>
            </div>
        <?php endif; ?>
        <?php foreach ($boletas as $b): ?>
            <?php
                $minUmaM = (float) ($b->falta_monto_min_uma ?? 0);
                $maxUmaM = (float) ($b->falta_monto_max_uma ?? 0);
            ?>
            <div class="card border-0 shadow-sm rounded-4 mb-2">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <span class="fw-bold font-monospace text-primary"><?= esc($b->folio) ?></span>
                        <a href="<?= $urlBoletas(['estado' => (string) ($b->estado ?? 'recibida')]) ?>" class="badge bg-light text-dark border text-decoration-none">
                            <?= esc(ucfirst((string) ($b->estado ?? 'recibida'))) ?>
                        </a>
                    </div>
                    <div class="small text-muted mb-2">
                        <?= esc($b->fecha_infraccion) ?> <?= esc(substr((string) $b->hora_infraccion, 0, 5)) ?> hrs
                        · <a href="<?= $urlBoletas(['placa' => (string) $b->agente_placa]) ?>" class="text-decoration-none"><?= esc($b->agente_nombre ?? $b->agente_placa) ?></a>
                    </div>
                    <div class="small mb-1">
                        <?php if (!empty($b->falta_fundamento_legal)): ?>
                            <a class="text-decoration-none" href="<?= esc($urlBoletas(['falta' => (string) $b->falta_fundamento_legal])) ?>" title="Ver todas las boletas con esta falta"><?= esc($b->falta_fundamento_legal) ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted mb-2">
                        <?php if (!empty($b->vehiculo_placas)): ?>
                            <a class="badge bg-dark font-monospace text-white me-1 text-decoration-none" href="<?= esc($urlBoletas(['q' => (string) $b->vehiculo_placas])) ?>" title="Buscar este vehículo"><?= esc($b->vehiculo_placas) ?></a>
                        <?php else: ?>
                            <span class="badge bg-dark font-monospace text-white me-1">S/P</span>
                        <?php endif; ?>
                        <?= esc($b->infractor_nombre ?: ($b->conductor_ausente ? 'Conductor ausente' : 'Sin nombre')) ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <strong class="small">$<?= number_format($minUmaM * $valorUma, 2) ?> - $<?= number_format($maxUmaM * $valorUma, 2) ?></strong>
                        <a href="/admin/app-transito/boletas/editar/<?= (int) $b->id ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                            <i class="bi bi-eye me-1"></i>Abrir
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Lista de Boletas -->
    <div class="card border-0 shadow-sm rounded-4 bg-white d-none d-md-block">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-sticky-header">
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
                                        <div class="d-flex align-items-center gap-1">
                                            <a class="fw-bold font-monospace text-primary text-decoration-none" href="/admin/app-transito/boletas/editar/<?= (int) $b->id ?>" title="Abrir la boleta"><?= esc($b->folio) ?></a>
                                            <button type="button" class="btn-copy" onclick="copiarFolio('<?= esc($b->folio) ?>', this)" title="Copiar Folio">
                                                <i class="bi bi-clipboard"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted"><?= esc($b->fecha_infraccion) ?> <?= esc(substr($b->hora_infraccion, 0, 5)) ?> hrs</small>
                                        <a href="<?= $urlBoletas(['estado' => (string) ($b->estado ?? 'recibida')]) ?>"
                                           class="badge bg-light text-dark border small ms-1 text-decoration-none">
                                            <?= esc(ucfirst((string) ($b->estado ?? 'recibida')));
                                        ?></a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?= esc($b->agente_nombre ?? $b->agente_placa) ?></div>
                                        <a href="<?= $urlBoletas(['placa' => (string) $b->agente_placa]) ?>" class="badge bg-light text-muted border small text-decoration-none"><?= esc($b->agente_placa) ?></a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small">
                                            <?= $b->conductor_ausente ? '<span class="text-warning fw-normal"><i class="bi bi-exclamation-triangle"></i> Conductor Ausente</span>' : esc($b->infractor_nombre ?: 'Sin nombre') ?>
                                        </div>
                                        <div class="small text-muted">
                                            <?php if (!empty($b->vehiculo_placas)): ?>
                                                <a class="badge bg-dark font-monospace text-white me-1 text-decoration-none" href="<?= esc($urlBoletas(['q' => (string) $b->vehiculo_placas])) ?>" title="Buscar este vehículo"><?= esc($b->vehiculo_placas) ?></a>
                                            <?php else: ?>
                                                <span class="badge bg-dark font-monospace text-white me-1">S/P</span>
                                            <?php endif; ?>
                                            <?= esc($b->vehiculo_marca) ?> <?= esc($b->vehiculo_linea) ?> (<?= esc($b->vehiculo_color) ?>)
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-danger small">
                                            <?php if (!empty($b->falta_fundamento_legal)): ?>
                                                <a class="text-decoration-none" href="<?= esc($urlBoletas(['falta' => (string) $b->falta_fundamento_legal])) ?>" title="Ver todas las boletas con esta falta"><?= esc($b->falta_fundamento_legal) ?></a>
                                            <?php endif; ?>
                                        </div>
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
                                            <i class="bi bi-eye me-1"></i>Ver Detalle
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
                                            <div class="modal-footer border-0 pt-0 d-flex justify-content-between flex-wrap gap-2">
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                                                        <i class="bi bi-printer me-1"></i>Imprimir
                                                    </button>
                                                    <a href="/admin/app-transito/boletas/editar/<?= (int) $b->id ?>" class="btn btn-outline-primary">
                                                        <i class="bi bi-pencil me-1"></i>Editar
                                                    </a>
                                                    <?php if (in_array('administrador', (array) (session('roles') ?? []), true)): ?>
                                                        <form action="/admin/app-transito/boletas/eliminar/<?= (int) $b->id ?>" method="POST" class="d-inline" data-no-loading="true"
                                                            onsubmit="return confirm('¿Eliminar la boleta <?= esc($b->folio, 'js') ?>? Quedará una copia en la auditoría.');">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Eliminar</button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
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

<script>
function copiarFolio(texto, btn) {
    navigator.clipboard.writeText(texto).then(function() {
        var icon = btn.querySelector('i');
        var originalClass = icon.className;
        icon.className = 'bi bi-check2';
        btn.classList.add('copied');
        setTimeout(function() {
            icon.className = originalClass;
            btn.classList.remove('copied');
        }, 2000);
    });
}
</script>

<?= $this->endSection() ?>
