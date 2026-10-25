<?php declare(strict_types=1);

/**
 * Variables inyectadas por Ciudadano\PortalController::verSolicitud() vía view(..., $data).
 * Se leen desde get_defined_vars() para que el analizador estático no las marque
 * como "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var object|null $solicitud */
$solicitud    = $__view['solicitud'] ?? null;
/** @var array<string, mixed> $datos */
$datos        = (array) ($__view['datos'] ?? []);
/** @var list<object> $documentos */
$documentos   = (array) ($__view['documentos'] ?? []);
/** @var list<object> $historial */
$historial    = (array) ($__view['historial'] ?? []);
/** @var object|null $verificacion */
$verificacion = $__view['verificacion'] ?? null;

$labelsDatos = [
    'tipo_solicitante' => 'Tipo de solicitante',
    'razon_social_o_nombre' => 'Nombre / Razón social',
    'rfc' => 'RFC',
    'RFC' => 'RFC',
    'domicilio_negocio' => 'Domicilio del negocio o particular',
    'direccion_carga_descarga' => 'Dirección de carga/descarga',
    'periodo' => 'Periodo',
    'num_camiones' => 'Número de camiones',
    'num_vehiculos' => 'Número de vehículos',
    'horario_inicio' => 'Horario inicio',
    'horario_fin' => 'Horario fin',
    'es_mudanza' => '¿Es mudanza?',
    'numero_titulo_concesion' => 'Número de título de concesión',
    'nombre_concesionario' => 'Nombre del concesionario',
    'tipo_persona' => 'Tipo de persona',
    'numero_factura' => 'Número de factura',
    'vehiculo_placas' => 'Placas del vehículo',
    'vehiculo_num_serie' => 'Número de serie (VIN)',
    'tramite_concepto' => 'Concepto del trámite',
    'motivo' => 'Motivo de la solicitud',
    'solicitante' => 'Nombre del solicitante',
    'solicitante_nombre' => 'Nombre del solicitante',
    'solicitante_email' => 'Correo electrónico',
    'solicitante_telefono' => 'Teléfono',
    'tipo_servicio' => 'Tipo de servicio',
    'observaciones' => 'Observaciones',
    'calle' => 'Calle o vialidad',
    'entre_calles' => 'Entre calles',
    'colonia' => 'Colonia',
    'fecha_evento' => 'Fecha del evento',
    'horario' => 'Horario solicitado',
    'tipo_evento' => 'Tipo de evento',
    'cesionario_nombre' => 'Nombre del cesionario',
    'cesionario_rfc' => 'RFC del cesionario',
    'cedente_nombre' => 'Nombre del cedente',
];
$periodoLabels = ['dia' => 'Día', 'mes' => 'Mes', 'semestre' => 'Semestre', 'anio' => 'Año'];
$tipoLabels = ['particular' => 'Particular', 'empresa' => 'Empresa', 'fisica' => 'Persona Física', 'moral' => 'Persona Moral'];
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col-12 col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/mis-solicitudes') ?>">Mis solicitudes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle de solicitud</li>
            </ol>
        </nav>
    </div>
    <div class="col-12 col-md-4 text-md-end mb-2 mb-md-0 d-flex flex-wrap gap-2 justify-content-md-end">
        <?php if ((float) $solicitud->monto > 0): ?>
            <a href="<?= site_url('documentos/hoja-pago/solicitud/' . $solicitud->folio) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-success">
                <i class="bi bi-receipt me-1"></i>Hoja de pago
            </a>
        <?php endif ?>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimir
        </button>
        <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>
</div>

<!-- STEPPER VISUAL DE PROGRESO DEL TRÁMITE -->
<?php
    $estatusActual = mb_strtolower((string)$solicitud->estatus);
    $pasoActual = 1;
    if (str_contains($estatusActual, 'pago') || (float)$solicitud->monto > 0 && empty($solicitud->fecha_pago)) {
        $pasoActual = 2;
    } elseif (str_contains($estatusActual, 'revis') || str_contains($estatusActual, 'cit') || str_contains($estatusActual, 'inspec')) {
        $pasoActual = 3;
    } elseif (str_contains($estatusActual, 'aprob') || str_contains($estatusActual, 'concluid') || str_contains($estatusActual, 'vigent')) {
        $pasoActual = 4;
    } elseif (str_contains($estatusActual, 'prevenc') || str_contains($estatusActual, 'rechaz')) {
        $pasoActual = 3;
    } elseif (!empty($solicitud->fecha_pago)) {
        $pasoActual = 3;
    }
?>
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-diagram-3 text-primary me-2"></i>Evolución del trámite</h6>
            <span class="badge estatus-badge <?= estatus_badge($solicitud->estatus) ?> fs-6"><?= esc($solicitud->estatus) ?></span>
        </div>
        <div class="row text-center g-2 position-relative">
            <!-- Paso 1 -->
            <div class="col-3">
                <div class="p-2 rounded <?= $pasoActual >= 1 ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-light text-muted' ?>">
                    <i class="bi <?= $pasoActual > 1 ? 'bi-check-circle-fill text-success' : 'bi-1-circle-fill' ?> fs-4 d-block mb-1"></i>
                    <div class="fw-bold small d-none d-sm-block">Radicación</div>
                    <div class="text-muted" style="font-size: 0.72rem;">Registrada</div>
                </div>
            </div>
            <!-- Paso 2 -->
            <div class="col-3">
                <div class="p-2 rounded <?= $pasoActual >= 2 ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-light text-muted' ?>">
                    <i class="bi <?= $pasoActual > 2 ? 'bi-check-circle-fill text-success' : ($pasoActual === 2 ? 'bi-cash-coin text-warning' : 'bi-2-circle') ?> fs-4 d-block mb-1"></i>
                    <div class="fw-bold small d-none d-sm-block">Pago / Derechos</div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= !empty($solicitud->fecha_pago) ? 'Liquidado' : ((float)$solicitud->monto > 0 ? 'Pendiente' : 'Exento') ?></div>
                </div>
            </div>
            <!-- Paso 3 -->
            <div class="col-3">
                <div class="p-2 rounded <?= $pasoActual >= 3 ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-light text-muted' ?>">
                    <i class="bi <?= $pasoActual > 3 ? 'bi-check-circle-fill text-success' : ($pasoActual === 3 ? 'bi-hourglass-split text-info' : 'bi-3-circle') ?> fs-4 d-block mb-1"></i>
                    <div class="fw-bold small d-none d-sm-block">Inspección / Revisión</div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= $pasoActual > 3 ? 'Validado' : ($pasoActual === 3 ? 'En Proceso' : 'Por evaluar') ?></div>
                </div>
            </div>
            <!-- Paso 4 -->
            <div class="col-3">
                <div class="p-2 rounded <?= $pasoActual >= 4 ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-light text-muted' ?>">
                    <i class="bi <?= $pasoActual >= 4 ? 'bi-patch-check-fill text-success' : 'bi-4-circle' ?> fs-4 d-block mb-1"></i>
                    <div class="fw-bold small d-none d-sm-block">Dictamen Final</div>
                    <div class="text-muted" style="font-size: 0.72rem;"><?= $pasoActual >= 4 ? 'Aprobado' : 'Por emitir' ?></div>
                </div>
            </div>
        </div>
        <?php
        // En el teléfono el nombre de cada paso se oculta para que quepan los cuatro
        // iconos. Esta línea recupera el nombre del paso en curso sin agrandar la tarjeta.
        $nombresPaso = ['Radicación', 'Pago / Derechos', 'Inspección / Revisión', 'Dictamen Final'];
        $pasoEnCurso = max(1, min(4, (int) $pasoActual));
        ?>
        <div class="d-sm-none text-center small text-muted mt-3 mb-0">
            Vas en el paso <?= $pasoEnCurso ?> de 4: <strong class="text-dark"><?= esc($nombresPaso[$pasoEnCurso - 1]) ?></strong>
        </div>
    </div>
</div>

<!-- ALERTAS CONTEXTUALES DE ESTATUS -->
<?php if ($solicitud->estatus === 'Pago pendiente'): ?>
    <div class="alert alert-warning border-warning d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-cash-stack fs-1 text-warning flex-shrink-0"></i>
        <div class="flex-grow-1">
            <h5 class="alert-heading h6 fw-bold mb-1">Pago pendiente de derechos municipales</h5>
            <p class="small mb-0">Esta solicitud requiere cubrir el pago de derechos por <strong><?= formatear_dinero((float)$solicitud->monto) ?></strong> para ser turnada a revisión técnica por la Dirección de Movilidad.</p>
        </div>
        <?php
            $pagoUrl = match($solicitud->tramite) {
                'UR-TT-T-07' => site_url('/portal/tramites/carga-descarga/resumen/' . $solicitud->folio),
                'UR-TT-T-04' => site_url('/portal/tramites/permiso-eventual/resumen/' . $solicitud->folio),
                'UR-TT-T-05' => site_url('/portal/tramites/cierre-calle/resumen/' . $solicitud->folio),
                default      => '#',
            };
        ?>
        <?php if ($pagoUrl !== '#'): ?>
            <a href="<?= $pagoUrl ?>" class="btn btn-warning btn-sm fw-semibold flex-shrink-0">
                <i class="bi bi-credit-card me-1"></i>Pagar en línea
            </a>
        <?php endif; ?>
    </div>
<?php elseif (in_array($solicitud->estatus, ['Concluido', 'Aprobado'], true)): ?>
    <div class="alert alert-success border-success d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-shield-check fs-1 text-success flex-shrink-0"></i>
        <div class="flex-grow-1">
            <h5 class="alert-heading h6 fw-bold mb-1">¡Trámite concluido favorablemente!</h5>
            <p class="small mb-0">Tu solicitud cuenta con autorización y validez oficial emitida por la Dirección de Movilidad y Transporte de Uriangato.</p>
        </div>
    </div>
<?php elseif (str_contains($estatusActual, 'prevenc')): ?>
    <div class="alert alert-danger border-danger d-flex align-items-center gap-3 p-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle fs-1 text-danger flex-shrink-0"></i>
        <div class="flex-grow-1">
            <h5 class="alert-heading h6 fw-bold mb-1">Prevención / Aclaración Requerida</h5>
            <p class="small mb-0">El evaluador técnico ha emitido observaciones. Revisa el comentario en el historial y acude o remite la documentación complementaria solicitada.</p>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                <div>
                    <div class="small text-muted mb-1"><?= esc(tramite_nombre($solicitud->tramite)) ?></div>
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h4 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Folio: <code><?= esc($solicitud->folio) ?></code></h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="btnCopiarFolioDetalle" title="Copiar folio al portapapeles">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <span class="badge estatus-badge <?= estatus_badge($solicitud->estatus) ?> fs-6"><?= esc($solicitud->estatus) ?></span>
            </div>
            <div class="card-body">
                <div class="row mb-4 g-3">
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-light bg-opacity-50">
                            <div class="small text-muted mb-1">Fecha de solicitud</div>
                            <div class="fw-semibold"><?= formatear_fecha($solicitud->fecha_solicitud) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-light bg-opacity-50">
                            <div class="small text-muted mb-1">Monto</div>
                            <div class="fw-semibold text-primary fs-5"><?= formatear_dinero((float)$solicitud->monto) ?></div>
                        </div>
                    </div>
                    <?php if (!empty($solicitud->fecha_pago)): ?>
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-light bg-opacity-50">
                            <div class="small text-muted mb-1">Fecha de pago</div>
                            <div class="fw-semibold"><?= formatear_fecha($solicitud->fecha_pago) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($solicitud->fecha_vigencia_inicio) && !empty($solicitud->fecha_vigencia_fin)): ?>
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-light bg-opacity-50">
                            <div class="small text-muted mb-1">Vigencia</div>
                            <div class="fw-semibold small">
                                <?= formatear_fecha($solicitud->fecha_vigencia_inicio) ?>
                                <span class="text-muted mx-1">→</span>
                                <?= formatear_fecha($solicitud->fecha_vigencia_fin) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <h5 class="mb-3"><i class="bi bi-clipboard-data me-2 text-primary"></i>Datos del trámite</h5>
                <dl class="row mb-0">
                <?php foreach ($labelsDatos as $clave => $label): ?>
                    <?php
                        if (!array_key_exists($clave, $datos)) continue;
                        $valor = $datos[$clave];
                        if ($valor === '' || $valor === null) continue;
                        if ($clave === 'periodo' && isset($periodoLabels[$valor])) $valor = $periodoLabels[$valor];
                        if ($clave === 'tipo_solicitante' && isset($tipoLabels[$valor])) $valor = $tipoLabels[$valor];
                        if ($clave === 'tipo_persona' && isset($tipoLabels[$valor])) $valor = $tipoLabels[$valor];
                        if ($clave === 'es_mudanza') $valor = $valor === '1' ? 'Sí' : 'No';
                    ?>
                    <dt class="col-sm-5 col-md-4 text-muted small pt-1"><?= esc($label) ?></dt>
                    <dd class="col-sm-7 col-md-8 fw-medium mb-2"><?= nl2br(esc((string)$valor)) ?></dd>
                <?php endforeach; ?>
                <?php foreach ($datos as $clave => $valor): ?>
                    <?php
                        if (array_key_exists($clave, $labelsDatos)) continue;
                        if ($valor === '' || $valor === null) continue;
                        $label = ucwords(str_replace('_', ' ', $clave));
                    ?>
                    <dt class="col-sm-5 col-md-4 text-muted small pt-1"><?= esc($label) ?></dt>
                    <dd class="col-sm-7 col-md-8 fw-medium mb-2"><?= nl2br(esc((string)$valor)) ?></dd>
                <?php endforeach; ?>
                </dl>
            </div>
        </div>

        <?php if ($solicitud->tramite === 'UR-TT-T-02'): ?>
        <div class="card shadow-sm mb-4 border-primary border-opacity-50">
            <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0 text-primary fw-bold">
                    <i class="bi bi-calendar2-check me-2"></i>Inspección Física de Despintado
                </h5>
                <?php if (!empty($verificacion->fecha_cita)): ?>
                    <span class="badge bg-primary fs-6"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($verificacion->fecha_cita)) ?> hrs</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-circle me-1"></i>Pendiente de agendar</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($verificacion->fecha_cita)): ?>
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="small text-muted mb-1">Fecha y hora de la cita</div>
                                <div class="fw-bold fs-5 text-dark"><?= date('d/m/Y H:i', strtotime($verificacion->fecha_cita)) ?> hrs</div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="border rounded p-3 bg-light">
                                <div class="small text-muted mb-1">Dictamen de verificación</div>
                                <div>
                                    <?php if ($verificacion->resultado === 'aprobado'): ?>
                                        <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Aprobado (Despintado Conforme)</span>
                                    <?php elseif ($verificacion->resultado === 'rechazado'): ?>
                                        <span class="badge bg-danger fs-6"><i class="bi bi-x-circle me-1"></i>Rechazado (No Conforme)</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark fs-6"><i class="bi bi-hourglass-split me-1"></i>Inspección programada</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($verificacion->observaciones)): ?>
                        <div class="alert alert-secondary small mb-3">
                            <strong>Observaciones del inspector:</strong><br>
                            <?= nl2br(esc($verificacion->observaciones)) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($verificacion->resultado)): ?>
                        <div class="d-flex justify-content-end">
                            <a href="<?= site_url('/portal/tramites/ur-02/solicitud/' . $solicitud->folio . '/cita') ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-repeat me-1"></i>Reagendar Fecha de Cita
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small mb-3">Tu solicitud requiere una inspección física presencial en el patio de control municipal para verificar el despintado total de la unidad.</p>
                    <a href="<?= site_url('/portal/tramites/ur-02/solicitud/' . $solicitud->folio . '/cita') ?>" class="btn btn-primary">
                        <i class="bi bi-calendar-plus me-2"></i>Agendar Cita de Inspección Física
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Documentos adjuntos</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($documentos)): ?>
                <div class="text-center py-4 text-muted small">No hay documentos adjuntos.</div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                <?php foreach ($documentos as $doc):
                    $tipoNombre = tipo_documento_nombre($doc->tipo_documento);
                    $tamanoStr  = formato_bytes((int) $doc->tamano_bytes);
                    $icono = match ($doc->mime_type) {
                        'application/pdf' => 'bi-filetype-pdf text-danger',
                        'image/png', 'image/jpeg' => 'bi-filetype-image text-success',
                        default => 'bi-file-earmark text-secondary',
                    };
                ?>
                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
                        <div class="d-flex align-items-center min-w-0">
                            <i class="bi <?= $icono ?> fs-3 me-3 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold small text-truncate"><?= esc($tipoNombre) ?></div>
                                <div class="small text-muted text-break">
                                    <?= esc($doc->nombre_original) ?>
                                    <span class="mx-1">·</span>
                                    <?= $tamanoStr ?>
                                </div>
                            </div>
                        </div>
                        <a href="<?= site_url('/portal/solicitud/' . $solicitud->folio . '/descargar/' . $doc->id) ?>" class="btn btn-sm btn-outline-primary ms-auto">
                            <i class="bi bi-download me-1"></i>Descargar
                        </a>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fs-6 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Historial de estatus</h5>
            </div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                <div class="small text-muted">Sin registros.</div>
                <?php else: ?>
                <ol class="timeline list-unstyled mb-0">
                <?php foreach ($historial as $h): ?>
                    <li class="timeline-item">
                        <div class="timeline-badge"></div>
                        <div class="small text-muted mb-1"><?= formatear_fecha($h->fecha) ?></div>
                        <div class="mb-1">
                            <span class="badge estatus-badge <?= estatus_badge($h->estatus_nuevo) ?>"><?= esc($h->estatus_nuevo) ?></span>
                        </div>
                        <?php if (!empty($h->comentario)): ?>
                        <div class="small text-muted mt-1 bg-light p-2 rounded"><?= esc($h->comentario) ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
            <?php if ($solicitud->estatus === 'Pago pendiente'): ?>
            <?php
                $pagoUrl = match($solicitud->tramite) {
                    'UR-TT-T-07' => site_url('/portal/tramites/carga-descarga/resumen/' . $solicitud->folio),
                    'UR-TT-T-04' => site_url('/portal/tramites/permiso-eventual/resumen/' . $solicitud->folio),
                    'UR-TT-T-05' => site_url('/portal/tramites/cierre-calle/resumen/' . $solicitud->folio),
                    default      => site_url('/portal/solicitud/' . $solicitud->folio),
                };
            ?>
            <div class="card-footer bg-white border-top p-3">
                <a href="<?= $pagoUrl ?>" class="btn btn-success btn-lg w-100 shadow-sm" style="background-color: #0e9f6e; border-color: #0e9f6e;">
                    <i class="bi bi-cash-coin me-2"></i>Ir a pagar
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnCopiar = document.getElementById('btnCopiarFolioDetalle');
    if (btnCopiar) {
        btnCopiar.addEventListener('click', function() {
            const folio = '<?= esc($solicitud->folio) ?>';
            if (navigator.clipboard && folio) {
                navigator.clipboard.writeText(folio).then(() => {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = 'bi bi-check-lg text-success';
                        setTimeout(() => {
                            icon.className = 'bi bi-clipboard';
                        }, 2000);
                    }
                });
            }
        });
    }
});
</script>
<?= $this->endSection() ?>
