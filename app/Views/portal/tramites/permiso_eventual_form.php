<?php declare(strict_types=1); ?>
<?php $validation = \Config\Services::validation(); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4"><div class="col">
    <nav aria-label="breadcrumb"><ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
        <li class="breadcrumb-item active">Permiso Eventual de Transporte</li>
    </ol></nav>
</div></div>

<div class="row g-4"><div class="col-lg-8">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white py-3">
            <h1 class="h4 mb-1"><i class="bi bi-bus-front me-2"></i>UR-TT-T-04 · Permiso Eventual de Transporte</h1>
            <div class="small opacity-75">Prototipo de solicitud digital</div>
        </div>
        <div class="card-body">
            <?= form_open_multipart('/portal/tramites/permiso-eventual/guardar', ['novalidate' => 'novalidate']) ?>
            <fieldset class="mb-4"><legend class="h6 border-bottom pb-2">Datos del solicitante</legend>
                <div class="row g-3">
                    <div class="col-md-8"><label class="form-label">Nombre o razón social *</label><input class="form-control" name="nombre_razon_social" value="<?= old('nombre_razon_social') ?>" required><?= validation_show_error('nombre_razon_social') ?></div>
                    <div class="col-md-4"><label class="form-label">Tipo de persona *</label><select class="form-select" name="tipo_persona" required><option value="">Selecciona...</option><option value="fisica" <?= old('tipo_persona') === 'fisica' ? 'selected' : '' ?>>Física</option><option value="moral" <?= old('tipo_persona') === 'moral' ? 'selected' : '' ?>>Moral</option></select><?= validation_show_error('tipo_persona') ?></div>
                    <div class="col-md-4"><label class="form-label">RFC *</label><input class="form-control text-uppercase" name="rfc" maxlength="13" value="<?= old('rfc') ?>" required><?= validation_show_error('rfc') ?></div>
                    <div class="col-md-8"><label class="form-label">Domicilio *</label><input class="form-control" name="domicilio" value="<?= old('domicilio') ?>" required><?= validation_show_error('domicilio') ?></div>
                </div>
            </fieldset>
            <fieldset class="mb-4"><legend class="h6 border-bottom pb-2">Datos del servicio</legend>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Tipo de servicio *</label><input class="form-control" name="tipo_servicio" value="<?= old('tipo_servicio') ?>" placeholder="Ej. Transporte urbano" required></div>
                    <div class="col-md-6"><label class="form-label">Tipo de unidades *</label><input class="form-control" name="tipo_unidad" value="<?= old('tipo_unidad') ?>" placeholder="Ej. Autobús" required></div>
                    <div class="col-md-4"><label class="form-label">Cantidad de unidades *</label><input type="number" min="1" max="50" class="form-control" name="cantidad_unidades" value="<?= old('cantidad_unidades', '1') ?>" required></div>
                    <div class="col-md-8"><label class="form-label">Lugar donde se prestará el servicio *</label><input class="form-control" name="lugar_servicio" value="<?= old('lugar_servicio') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Zona o ruta *</label><input class="form-control" name="zona_servicio" value="<?= old('zona_servicio') ?>" required></div>
                    <div class="col-md-6"><label class="form-label">Motivo de la necesidad *</label><select class="form-select" name="motivo_necesidad" required><option value="">Selecciona...</option><option value="descompostura">Descompostura de unidad</option><option value="falta_unidades">Falta de unidades</option><option value="otra_necesidad">Otra necesidad temporal</option></select></div>
                    <div class="col-12"><label class="form-label">Descripción de la necesidad *</label><textarea class="form-control" name="descripcion_necesidad" rows="3" required><?= old('descripcion_necesidad') ?></textarea></div>
                    <div class="col-12"><label class="form-label">Vigencia solicitada *</label><input class="form-control" name="vigencia_observacion" value="<?= old('vigencia_observacion', 'Durante el tiempo que permanezca la necesidad del servicio') ?>" required></div>
                </div>
            </fieldset>
            <fieldset class="mb-4"><legend class="h6 border-bottom pb-2">Documentos requeridos</legend>
                <p class="small text-muted">PDF, JPG o PNG. Máximo 10 MB por archivo.</p>
                <?php $files = ['solicitud_escrita' => 'Solicitud por escrito', 'proyecto_vehiculos' => 'Proyecto de cantidad de vehículos', 'frecuencia_servicios' => 'Frecuencia de servicios', 'documento_identidad' => 'Acta constitutiva o acta de nacimiento', 'poliza_seguro' => 'Fondo de garantía o póliza de seguro']; ?>
                <?php foreach ($files as $name => $label): ?><div class="mb-3"><label class="form-label"><?= esc($label) ?> *</label><input type="file" class="form-control" name="<?= $name ?>" accept="application/pdf,image/jpeg,image/png" required><?= validation_show_error($name) ?></div><?php endforeach; ?>
            </fieldset>
            <div class="d-flex justify-content-between gap-2"><a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary" type="submit"><i class="bi bi-send me-2"></i>Registrar solicitud</button></div>
            <?= form_close() ?>
        </div>
    </div>
</div><div class="col-lg-4"><div class="card border-0 bg-light"><div class="card-body"><h2 class="h5">Información del prototipo</h2><p class="small text-muted">Costo de referencia: <strong><?= formatear_dinero((float) $tarifaMonto) ?></strong></p><p class="small text-muted mb-0">La solicitud inicia en revisión documental. El pago se integrará después de la autorización administrativa.</p></div></div></div></div>
<?= $this->endSection() ?>