<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$citaActual = !empty($verificacion->fecha_cita) ? $verificacion->fecha_cita : '';
$fechaMinima = date('Y-m-d\TH:i', strtotime('+1 day 09:00'));
$fechaMaxima = date('Y-m-d\TH:i', strtotime('+30 days 15:00'));
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Agendar Cita de Verificación Física - UR-02<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/mis-solicitudes') ?>">Mis solicitudes</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/solicitud/' . $solicitud->folio) ?>">Folio <?= esc($solicitud->folio) ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Agendar Cita</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-primary p-2 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-calendar-event fs-3"></i>
                    </div>
                    <div>
                        <div class="badge bg-light text-primary mb-1 fw-bold">UR-TT-T-02 · Inspección Física</div>
                        <h1 class="h4 mb-0 text-white">Agendar Cita de Verificación</h1>
                        <div class="small opacity-75">Folio de solicitud: <strong><?= esc($solicitud->folio) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($citaActual)): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="bi bi-info-circle-fill fs-3 me-3 text-primary"></i>
                        <div>
                            <strong>Cita agendada previamente:</strong><br>
                            Fecha y hora programada: <span class="badge bg-primary fs-6"><?= date('d/m/Y H:i', strtotime($citaActual)) ?> hrs</span>.
                            <div class="small text-muted mt-1">Si seleccionas una nueva fecha abajo, tu cita se actualizará automáticamente.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-primary bg-primary bg-opacity-10 border-0 mb-4">
                        <h6 class="fw-bold text-primary mb-1"><i class="bi bi-geo-alt-fill me-1"></i>Lugar de la inspección</h6>
                        <p class="small text-muted mb-0">Patio de Control e Inspección de la Dirección de Movilidad y Transporte Municipal, Uriangato, Gto. Horario de atención de 09:00 a 15:00 hrs de Lunes a Viernes.</p>
                    </div>
                <?php endif; ?>

                <?= form_open('/portal/tramites/ur-02/solicitud/' . $solicitud->folio . '/cita/guardar', ['id' => 'formCita']) ?>
                    <div class="mb-4">
                        <label for="fecha_cita" class="form-label fw-bold text-dark fs-5">
                            <i class="bi bi-calendar-plus text-primary me-2"></i>Selecciona la Fecha y Hora de la Cita <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" class="form-control form-control-lg" id="fecha_cita" name="fecha_cita" required min="<?= $fechaMinima ?>" max="<?= $fechaMaxima ?>" value="<?= !empty($citaActual) ? date('Y-m-d\TH:i', strtotime($citaActual)) : $fechaMinima ?>">
                        <div class="form-text mt-2">
                            Las citas se programan a partir del siguiente día hábil en múltiplos de 30 minutos (09:00 a 15:00 hrs).
                        </div>
                        <?php if ($validation->getError('fecha_cita')): ?>
                            <div class="text-danger small mt-1"><?= $validation->getError('fecha_cita') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Requisitos indispensables para el día de la cita:</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>Presentar el vehículo completamente <strong>limpio, despintado y sin franjas ni cromática oficial</strong>.</li>
                            <li>Llevar original de la identificación oficial del titular.</li>
                            <li>Llevar comprobante impreso o digital de esta solicitud (Folio: <code><?= esc($solicitud->folio) ?></code>).</li>
                            <li>Presentarse 10 minutos antes de la hora programada.</li>
                        </ul>
                    </div>

                    <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                        <a href="<?= site_url('/portal/solicitud/' . $solicitud->folio) ?>" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-arrow-left me-1"></i>Volver al Detalle
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                            <i class="bi bi-calendar-check me-2"></i><?= !empty($citaActual) ? 'Reagendar Cita' : 'Confirmar Cita' ?>
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
