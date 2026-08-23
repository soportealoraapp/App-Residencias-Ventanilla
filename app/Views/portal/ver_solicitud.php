<?php declare(strict_types=1);
$mapEstatusClass = [
    'Recibido' => 'bg-info text-dark',
    'Pago pendiente' => 'bg-warning text-dark',
    'Pagado' => 'bg-primary',
    'Permiso emitido' => 'bg-success',
    'Vigente' => 'bg-success',
    'Rechazado' => 'bg-danger',
    'Vencido' => 'bg-secondary',
];
$estatusClass = $mapEstatusClass[$solicitud->estatus] ?? 'bg-secondary';
$labelsDatos = [
    'tipo_solicitante' => 'Tipo de solicitante',
    'razon_social_o_nombre' => 'Nombre / Razón social',
    'rfc' => 'RFC',
    'domicilio_negocio' => 'Domicilio del negocio o particular',
    'direccion_carga_descarga' => 'Dirección de carga/descarga',
    'periodo' => 'Periodo',
    'num_camiones' => 'Número de camiones',
    'horario_inicio' => 'Horario inicio',
    'horario_fin' => 'Horario fin',
    'es_mudanza' => '¿Es mudanza?',
];
$periodoLabels = ['dia' => 'Día', 'mes' => 'Mes', 'semestre' => 'Semestre', 'anio' => 'Año'];
$tipoLabels = ['particular' => 'Particular', 'empresa' => 'Empresa'];
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
                <span class="badge estatus-badge <?= $estatusClass ?> fs-6"><?= esc($solicitud->estatus) ?></span>
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
                        if ($clave === 'es_mudanza') $valor = $valor === '1' ? 'Sí' : 'No';
                    ?>
                    <dt class="col-sm-5 col-md-4 text-muted small pt-1"><?= esc($label) ?></dt>
                    <dd class="col-sm-7 col-md-8 fw-medium mb-2"><?= nl2br(esc($valor)) ?></dd>
                <?php endforeach; ?>
                </dl>
            </div>
        </div>

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
                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <i class="bi <?= $icono ?> fs-3 me-3"></i>
                            <div>
                                <div class="fw-semibold small"><?= esc($tipoNombre) ?></div>
                                <div class="small text-muted">
                                    <?= esc($doc->nombre_original) ?>
                                    <span class="mx-1">·</span>
                                    <?= $tamanoStr ?>
                                </div>
                            </div>
                        </div>
                        <a href="<?= site_url('/portal/tramites/carga-descarga/' . $solicitud->folio . '/descargar/' . $doc->id) ?>" class="btn btn-sm btn-outline-primary">
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
        <div class="card shadow-sm sticky-top" style="top: 90px;">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Historial de estatus</h5>
            </div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                <div class="small text-muted">Sin registros.</div>
                <?php else: ?>
                <ol class="timeline list-unstyled mb-0">
                <?php foreach ($historial as $h):
                    $stClass = $mapEstatusClass[$h->estatus_nuevo] ?? 'bg-secondary';
                    $esUltimo = $h === end($historial);
                ?>
                    <li class="timeline-item">
                        <div class="small text-muted mb-1"><?= formatear_fecha($h->fecha) ?></div>
                        <div class="mb-1">
                            <span class="badge estatus-badge <?= $stClass ?>"><?= esc($h->estatus_nuevo) ?></span>
                        </div>
                        <?php if (!empty($h->comentario)): ?>
                        <div class="small text-muted"><?= esc($h->comentario) ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
            <?php if ($solicitud->estatus === 'Pago pendiente'): ?>
            <div class="card-footer bg-white border-top">
                <a href="<?= site_url('/portal/tramites/carga-descarga/resumen/' . $solicitud->folio) ?>" class="btn btn-success w-100">
                    <i class="bi bi-cash-coin me-2"></i>Ir a pagar
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
