<?php declare(strict_types=1);
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
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/mis-solicitudes') ?>">Mis solicitudes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Detalle de solicitud</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                <div>
                    <div class="small text-muted mb-1"><?= esc(tramite_nombre($solicitud->tramite)) ?></div>
                    <h2 class="h4 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Folio: <code><?= esc($solicitud->folio) ?></code></h2>
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
                    $tipoNombre = match ($doc->tipo_documento) {
                        'identificacion_oficial' => 'Identificación oficial',
                        'tarjeta_circulacion' => 'Tarjeta de circulación',
                        'documento_carga_descarga' => 'Documento de carga/descarga',
                        default => $doc->tipo_documento,
                    };
                    $tamanoKB = round((int)$doc->tamano_bytes / 1024, 1);
                    $tamanoStr = $tamanoKB >= 1024 ? round($tamanoKB / 1024, 2) . ' MB' : $tamanoKB . ' KB';
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
                                <div class="small text-muted text-truncate" style="max-width: 200px;">
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
<?= $this->endSection() ?>
