<?php declare(strict_types=1);
$labelsDatos = [
    'tipo_solicitante' => 'Tipo de solicitante',
    'razon_social_o_nombre' => 'Nombre / Razón social',
    'rfc' => 'RFC',
    'domicilio_negocio' => 'Domicilio del negocio o particular',
    'direccion_carga_descarga' => 'Dirección de carga/descarga',
    'periodo' => 'Periodo de vigencia',
    'num_camiones' => 'Número de camiones',
    'horario_inicio' => 'Horario de inicio',
    'horario_fin' => 'Horario de término',
    'es_mudanza' => 'Operación de mudanza',
];
$periodoLabels = ['dia' => 'Día', 'mes' => 'Mes', 'semestre' => 'Semestre', 'anio' => 'Año'];
$tipoLabels = ['particular' => 'Particular', 'empresa' => 'Empresa'];
$mapEstatusClass = [
    'Recibido' => 'bg-info text-dark',
    'Pago pendiente' => 'bg-warning text-dark',
    'Pagado' => 'bg-primary',
    'Permiso emitido' => 'bg-success',
    'Vigente' => 'bg-success',
    'Rechazado' => 'bg-danger',
];
$estatusClass = $mapEstatusClass[$solicitud->estatus] ?? 'bg-secondary';
$puedePagar = $solicitud->estatus === 'Pago pendiente';
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/mis-solicitudes') ?>">Mis solicitudes</a></li>
                <li class="breadcrumb-item active" aria-current="page">Resumen de solicitud</li>
            </ol>
        </nav>
    </div>
</div>

<?php if (!empty($placeholder)): ?>
<div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5 me-3"></i>
    <div>
        <strong>Monto pendiente de validación oficial.</strong> El importe mostrado es una estimación y podría ser ajustado por personal de la dirección antes de surtir efectos.
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                <div>
                    <div class="small text-muted mb-1">Permiso de Carga y Descarga · UR-TT-T-07</div>
                    <h1 class="h4 mb-0"><i class="bi bi-receipt text-primary me-2"></i>Folio: <code><?= esc($solicitud->folio) ?></code></h1>
                </div>
                <span class="badge estatus-badge <?= $estatusClass ?> fs-6 px-3 py-2"><?= esc($solicitud->estatus) ?></span>
            </div>
            <div class="card-body">
                <h5 class="mb-3 border-bottom pb-2"><i class="bi bi-calendar-check me-2 text-primary"></i>Fechas y pago</h5>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-light bg-opacity-50 h-100">
                            <div class="small text-muted mb-1">Fecha de solicitud</div>
                            <div class="fw-semibold"><?= formatear_fecha($solicitud->fecha_solicitud) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-primary bg-opacity-10 h-100">
                            <div class="small text-muted mb-1">Monto total</div>
                            <div class="fw-bold text-primary fs-3"><?= formatear_dinero((float)$solicitud->monto) ?></div>
                        </div>
                    </div>
                    <?php if (!empty($solicitud->fecha_pago)): ?>
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-success bg-opacity-10 h-100">
                            <div class="small text-muted mb-1">Fecha de pago</div>
                            <div class="fw-semibold text-success"><i class="bi bi-check-circle me-1"></i><?= formatear_fecha($solicitud->fecha_pago) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($solicitud->fecha_vigencia_inicio) && !empty($solicitud->fecha_vigencia_fin)): ?>
                    <div class="col-sm-6">
                        <div class="border rounded p-3 bg-info bg-opacity-10 h-100">
                            <div class="small text-muted mb-1">Vigencia del permiso</div>
                            <div class="fw-semibold small">
                                <div>Inicio: <?= formatear_fecha($solicitud->fecha_vigencia_inicio) ?></div>
                                <div>Término: <?= formatear_fecha($solicitud->fecha_vigencia_fin) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <h5 class="mb-3 border-bottom pb-2"><i class="bi bi-person-vcard me-2 text-primary"></i>Datos del solicitante</h5>
                <dl class="row mb-4">
                <?php foreach ($labelsDatos as $clave => $label): ?>
                    <?php
                        if (!array_key_exists($clave, $datos)) continue;
                        $valor = $datos[$clave];
                        if ($valor === '' || $valor === null) continue;
                        if ($clave === 'periodo' && isset($periodoLabels[$valor])) $valor = $periodoLabels[$valor];
                        if ($clave === 'tipo_solicitante' && isset($tipoLabels[$valor])) $valor = $tipoLabels[$valor];
                        if ($clave === 'es_mudanza') $valor = $valor === '1' ? 'Sí, se trata de una mudanza' : 'No';
                    ?>
                    <dt class="col-sm-5 col-md-4 text-muted small pt-1"><?= esc($label) ?></dt>
                    <dd class="col-sm-7 col-md-8 fw-medium mb-2"><?= nl2br(esc($valor)) ?></dd>
                <?php endforeach; ?>
                </dl>

                <h5 class="mb-3 border-bottom pb-2"><i class="bi bi-file-earmark-check me-2 text-primary"></i>Documentos adjuntos</h5>
                <?php if (empty($documentos)): ?>
                <div class="text-muted small">No hay documentos registrados.</div>
                <?php else: ?>
                <div class="row g-2">
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
                    <div class="col-md-6">
                        <div class="border rounded p-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center min-w-0">
                                <i class="bi <?= $icono ?> fs-3 me-3 flex-shrink-0"></i>
                                <div class="min-w-0">
                                    <div class="fw-semibold small text-truncate"><?= esc($tipoNombre) ?></div>
                                    <div class="small text-muted text-truncate" title="<?= esc($doc->nombre_original) ?>">
                                        <?= esc($doc->nombre_original) ?> · <?= $tamanoStr ?>
                                    </div>
                                </div>
                            </div>
                            <a href="<?= site_url('/portal/tramites/carga-descarga/' . $solicitud->folio . '/descargar/' . $doc->id) ?>" class="btn btn-sm btn-outline-primary flex-shrink-0 ms-2">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 tramite-sidebar-card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-bank me-2 text-primary"></i>Proceso de Pago</h5>
            </div>
            <div class="card-body">
                <div class="border rounded p-3 bg-light mb-3 text-center">
                    <div class="small text-muted mb-1">Total a pagar</div>
                    <div class="fw-bold text-primary fs-2"><?= formatear_dinero((float)$solicitud->monto) ?></div>
                </div>

                <div class="d-flex align-items-center p-3 border rounded mb-3 bg-warning-subtle">
                    <i class="bi bi-shield-lock fs-3 text-warning me-3"></i>
                    <div class="small">
                        <div class="fw-semibold text-dark">Pago seguro BanBajío</div>
                        <div class="text-muted">Plataforma encriptada SSL · Referencia: MOCK</div>
                    </div>
                </div>

                <?php if ($puedePagar): ?>
                <?= form_open('/portal/tramites/carga-descarga/pagar/' . $solicitud->id, ['id' => 'formPago']) ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg py-3">
                            <i class="bi bi-cash-coin me-2 fs-5"></i>Confirmar y pagar
                        </button>
                        <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Regresar después
                        </a>
                    </div>
                <?= form_close() ?>
                <div class="small text-muted text-center mt-3">
                    Al hacer clic en <strong>Confirmar y pagar</strong> serás redirigido al portal de BanBajío (modo demo).
                </div>
                <?php elseif ($solicitud->estatus === 'Vigente' || $solicitud->estatus === 'Permiso emitido' || $solicitud->estatus === 'Pagado'): ?>
                <div class="alert alert-success mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <strong>Pago completado.</strong> Tu permiso ha sido emitido exitosamente.
                </div>
                <div class="d-grid gap-2">
                    <a href="<?= site_url('/portal/solicitud/' . $solicitud->folio) ?>" class="btn btn-primary">
                        <i class="bi bi-eye me-1"></i>Ver detalle completo
                    </a>
                    <a href="<?= site_url('/portal/mis-solicitudes') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-list me-1"></i>Mis solicitudes
                    </a>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary mb-3 small">
                    <i class="bi bi-info-circle me-1"></i>
                    El estatus actual de la solicitud es <strong><?= esc($solicitud->estatus) ?></strong>. No requiere acción de pago en este momento.
                </div>
                <div class="d-grid gap-2">
                    <a href="<?= site_url('/portal/solicitud/' . $solicitud->folio) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>Ver detalle
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function() {
    const formPago = document.getElementById('formPago');
    if (formPago) {
        formPago.addEventListener('submit', function(e) {
            const btn = formPago.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Procesando...';
            }
        });
    }
})();
</script>
<?= $this->endSection() ?>
