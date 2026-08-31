<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$oldTipoPersona = old('tipo_persona', 'fisica');
$files = [
    'solicitud_escrita' => ['Solicitud por escrito', 'bi-file-text', 'Documento que describe la necesidad del permiso.'],
    'proyecto_vehiculos' => ['Proyecto de cantidad de vehículos', 'bi-truck', 'Relación o proyecto de las unidades que prestarán el servicio.'],
    'frecuencia_servicios' => ['Frecuencia de servicios', 'bi-calendar3', 'Documento o propuesta de frecuencia del servicio.'],
    'documento_identidad' => ['Acta constitutiva o acta de nacimiento', 'bi-person-badge', 'Acta constitutiva para persona moral o acta de nacimiento para persona física.'],
    'poliza_seguro' => ['Fondo de garantía o póliza de seguro', 'bi-shield-check', 'Comprobante de cobertura para la operación autorizada.'],
];
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>UR-TT-T-04 Permiso Eventual de Transporte - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Permiso Eventual de Transporte</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-primary p-2 rounded-3 me-3 flex-shrink-0">
                        <i class="bi bi-bus-front fs-3"></i>
                    </div>
                    <div>
                        <div class="badge bg-light text-primary mb-1 fw-bold">UR-TT-T-04</div>
                        <h1 class="h4 mb-0 text-white">Permiso Eventual de Transporte</h1>
                        <div class="small opacity-75">Solicitud para cubrir temporalmente una necesidad extraordinaria de servicio público</div>
                    </div>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <div class="alert alert-primary bg-primary bg-opacity-10 border-0 small mb-4">
                    <i class="bi bi-info-circle me-2"></i>Este permiso se expide de forma eventual ante contingencias, sustituciones emergentes o eventos especiales autorizados.
                </div>

                <?= form_open_multipart('/portal/tramites/permiso-eventual/guardar', ['id' => 'formPermisoEventual', 'novalidate' => 'novalidate']) ?>

                <!-- SECCIÓN 1: Solicitante -->
                <fieldset class="mb-4">
                    <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                        <i class="bi bi-person-vcard me-2 fs-5"></i>1. Datos del Solicitante o Concesionario
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nombre_razon_social" class="form-label fw-semibold">
                                Nombre o Razón Social <span class="text-danger">*</span>
                            </label>
                            <input id="nombre_razon_social" class="form-control" name="nombre_razon_social" value="<?= esc(old('nombre_razon_social')) ?>" maxlength="180" placeholder="Nombre completo o denominación social" required>
                            <?= validation_show_error('nombre_razon_social') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="tipo_persona" class="form-label fw-semibold">
                                Tipo de persona <span class="text-danger">*</span>
                            </label>
                            <select id="tipo_persona" class="form-select" name="tipo_persona" required>
                                <option value="">Selecciona...</option>
                                <option value="fisica" <?= $oldTipoPersona === 'fisica' ? 'selected' : '' ?>>Persona física</option>
                                <option value="moral" <?= $oldTipoPersona === 'moral' ? 'selected' : '' ?>>Persona moral</option>
                            </select>
                            <?= validation_show_error('tipo_persona') ?>
                        </div>
                        <div class="col-md-4">
                            <label for="rfc" class="form-label fw-semibold">
                                RFC <span class="text-danger">*</span>
                            </label>
                            <input id="rfc" class="form-control text-uppercase" name="rfc" maxlength="13" value="<?= esc(old('rfc')) ?>" placeholder="ABCD123456XYZ" required>
                            <?= validation_show_error('rfc') ?>
                        </div>
                        <div class="col-md-8">
                            <label for="domicilio" class="form-label fw-semibold">
                                Domicilio fiscal o particular <span class="text-danger">*</span>
                            </label>
                            <input id="domicilio" class="form-control" name="domicilio" value="<?= esc(old('domicilio')) ?>" maxlength="250" placeholder="Calle, número, colonia, municipio" required>
                            <?= validation_show_error('domicilio') ?>
                        </div>
                    </div>
                </fieldset>

                <!-- SECCIÓN 2: Servicio Eventual -->
                <fieldset class="mb-4">
                    <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                        <i class="bi bi-signpost-2 me-2 fs-5"></i>2. Datos del Servicio Eventual
                    </legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de servicio <span class="text-danger">*</span></label>
                            <input class="form-control" name="tipo_servicio" value="<?= esc(old('tipo_servicio')) ?>" placeholder="Ej. Transporte urbano / suburbano" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de unidad(es) <span class="text-danger">*</span></label>
                            <input class="form-control" name="tipo_unidad" value="<?= esc(old('tipo_unidad')) ?>" placeholder="Ej. Autobús, Minibús, Vagoneta" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Cantidad de unidades <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="cantidad_unidades" min="1" max="50" value="<?= esc(old('cantidad_unidades', '1')) ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Lugar o origen/destino del servicio <span class="text-danger">*</span></label>
                            <input class="form-control" name="lugar_servicio" value="<?= esc(old('lugar_servicio')) ?>" placeholder="Recorrido o puntos de prestación" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Zona o ruta <span class="text-danger">*</span></label>
                            <input class="form-control" name="zona_servicio" value="<?= esc(old('zona_servicio')) ?>" placeholder="Ruta específica o sector del municipio" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Motivo de la necesidad <span class="text-danger">*</span></label>
                            <select class="form-select" name="motivo_necesidad" required>
                                <option value="">Selecciona...</option>
                                <option value="descompostura" <?= old('motivo_necesidad') === 'descompostura' ? 'selected' : '' ?>>Descompostura o mantenimiento de unidad titular</option>
                                <option value="falta_unidades" <?= old('motivo_necesidad') === 'falta_unidades' ? 'selected' : '' ?>>Incremento extraordinario de demanda</option>
                                <option value="otra_necesidad" <?= old('motivo_necesidad') === 'otra_necesidad' ? 'selected' : '' ?>>Evento especial u otra necesidad justificada</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descripción detallada de la justificación <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="descripcion_necesidad" rows="3" maxlength="500" placeholder="Exposición clara de motivos por los cuales se solicita el permiso eventual" required><?= esc(old('descripcion_necesidad')) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Periodo o vigencia solicitada <span class="text-danger">*</span></label>
                            <input class="form-control" name="vigencia_observacion" value="<?= esc(old('vigencia_observacion', 'Durante el tiempo que subsista la contingencia justificada')) ?>" required>
                        </div>
                    </div>
                </fieldset>

                <!-- SECCIÓN 3: Documentación -->
                <fieldset class="mb-4">
                    <legend class="h6 border-bottom pb-2 mb-3 text-primary d-flex align-items-center">
                        <i class="bi bi-file-earmark-arrow-up me-2 fs-5"></i>3. Documentación Requerida
                        <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx. 10 MB)</span>
                    </legend>
                    <div class="row g-3">
                        <?php foreach ($files as $name => [$label, $icon, $help]): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-light bg-opacity-25">
                                    <label for="<?= $name ?>" class="form-label fw-semibold">
                                        <i class="bi <?= $icon ?> text-primary me-1"></i><?= esc($label) ?> <span class="text-danger">*</span>
                                    </label>
                                    <input id="<?= $name ?>" type="file" class="form-control" name="<?= $name ?>" accept="application/pdf,image/jpeg,image/png" required>
                                    <div class="form-text small"><?= esc($help) ?></div>
                                    <?= validation_show_error($name) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                    <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x me-1"></i>Cancelar
                    </a>
                    <button class="btn btn-primary btn-lg shadow-sm" type="submit">
                        <i class="bi bi-send-check me-2"></i>Enviar Solicitud
                    </button>
                </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <!-- Columna Lateral -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 tramite-sidebar-card">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0 text-primary fw-bold">
                    <i class="bi bi-info-circle me-2"></i>Información del Trámite
                </h2>
            </div>
            <div class="card-body text-center p-4">
                <div class="small text-muted text-uppercase fw-semibold mb-1">Costo de Derechos</div>
                <div class="mb-3">
                    <span class="badge bg-primary rounded-pill d-inline-block shadow-sm tramite-cost-badge">
                        <?= formatear_dinero((float) $tarifaMonto) ?>
                    </span>
                </div>
                <div class="alert alert-info small py-2 text-start" role="alert">
                    <i class="bi bi-info-circle me-1"></i>Tarifa oficial conforme a la Ley de Ingresos Municipal.
                </div>
                <div class="small text-muted border-top pt-3 text-start">
                    <div class="fw-semibold text-dark mb-2">
                        <i class="bi bi-list-check text-primary me-1"></i>Flujo del Trámite:
                    </div>
                    <ol class="ps-3 mb-0">
                        <li>Registro de solicitud y expediente digital.</li>
                        <li>Revisión y dictamen técnico de movilidad.</li>
                        <li>Notificación y pago de derechos.</li>
                        <li>Emisión oficial del permiso eventual.</li>
                    </ol>
                </div>
            </div>
            <div class="card-footer bg-light border-0 p-3">
                <div class="d-flex align-items-center text-muted small">
                    <i class="bi bi-shield-check text-primary fs-4 me-2"></i>
                    <span>Dirección de Movilidad y Transporte · H. Ayuntamiento de Uriangato, Gto.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
