<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$oldTipo = old('tipo_solicitante', 'particular');
$oldRazon = old('razon_social_o_nombre', '');
$oldRfc = old('rfc', '');
$oldDomicilio = old('domicilio_negocio', '');
$oldDireccion = old('direccion_carga_descarga', '');
$oldPeriodo = old('periodo', '');
$oldNumCam = old('num_camiones', '');
$oldHInicio = old('horario_inicio', '');
$oldHFin = old('horario_fin', '');
$oldMudanza = old('es_mudanza') !== null;
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Permiso de Carga y Descarga</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-truck fs-3 me-3"></i>
                    <div>
                        <h1 class="h4 mb-0">UR-TT-T-07 · Permiso de Carga y Descarga</h1>
                        <div class="small opacity-75">Formulario de solicitud</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open_multipart('/portal/tramites/carga-descarga/guardar', ['id' => 'formCargaDescarga', 'novalidate' => 'novalidate']) ?>
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-person-vcard me-2"></i>Datos del solicitante
                        </legend>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tipo de solicitante <span class="text-danger">*</span></label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_solicitante" id="ts_particular" value="particular" <?= $oldTipo === 'particular' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ts_particular">Particular</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="tipo_solicitante" id="ts_empresa" value="empresa" <?= $oldTipo === 'empresa' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="ts_empresa">Empresa</label>
                                </div>
                            </div>
                            <?php if ($validation->getError('tipo_solicitante')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('tipo_solicitante') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="razon_social_o_nombre" class="form-label fw-semibold">
                                    <span id="lblNombre">Nombre completo</span> <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="razon_social_o_nombre" name="razon_social_o_nombre" required maxlength="180" value="<?= esc($oldRazon) ?>" placeholder="Ingresa nombre completo o razón social">
                                <?php if ($validation->getError('razon_social_o_nombre')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('razon_social_o_nombre') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="rfc" class="form-label fw-semibold">RFC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" required pattern="^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$" maxlength="13" value="<?= esc($oldRfc) ?>" placeholder="XAXX010101XXX">
                                <?php if ($validation->getError('rfc')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('rfc') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="domicilio_negocio" class="form-label fw-semibold">Domicilio del negocio o particular <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="domicilio_negocio" name="domicilio_negocio" rows="2" required maxlength="250" placeholder="Calle, número, colonia, CP, municipio, estado"><?= esc($oldDomicilio) ?></textarea>
                            <?php if ($validation->getError('domicilio_negocio')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('domicilio_negocio') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3" id="div_num_camiones" style="display: <?= $oldTipo === 'empresa' ? 'block' : 'none' ?>">
                            <label for="num_camiones" class="form-label fw-semibold">Número de camiones <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="num_camiones" name="num_camiones" min="1" max="15" value="<?= esc($oldNumCam) ?>" placeholder="1 a 15">
                            <?php if ($validation->getError('num_camiones')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('num_camiones') ?></div>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-geo-alt me-2"></i>Datos de operación
                        </legend>

                        <div class="mb-3">
                            <label for="direccion_carga_descarga" class="form-label fw-semibold">Dirección exacta donde se realizará la carga/descarga <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="direccion_carga_descarga" name="direccion_carga_descarga" rows="2" required maxlength="250" placeholder="Calle, número, referencias, colonia, CP"><?= esc($oldDireccion) ?></textarea>
                            <?php if ($validation->getError('direccion_carga_descarga')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('direccion_carga_descarga') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="periodo" class="form-label fw-semibold">Periodo de vigencia <span class="text-danger">*</span></label>
                                <select class="form-select" id="periodo" name="periodo" required>
                                    <option value="">Selecciona...</option>
                                    <?php foreach ($periodos as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= $oldPeriodo === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($validation->getError('periodo')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('periodo') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="horario_inicio" class="form-label fw-semibold">Horario inicio <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="horario_inicio" name="horario_inicio" required value="<?= esc($oldHInicio) ?>">
                                <?php if ($validation->getError('horario_inicio')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('horario_inicio') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="horario_fin" class="form-label fw-semibold">Horario fin <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="horario_fin" name="horario_fin" required value="<?= esc($oldHFin) ?>">
                                <?php if ($validation->getError('horario_fin')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('horario_fin') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="es_mudanza" name="es_mudanza" value="1" <?= $oldMudanza ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold" for="es_mudanza">
                                    <i class="bi bi-box-seam me-1 text-primary"></i>¿Se trata de una mudanza?
                                </label>
                            </div>
                            <div class="small text-muted ms-4 mt-1">Si marcas esta opción, no será necesario subir el documento de comprobación de carga/descarga.</div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Documentación requerida
                            <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx 10 MB)</span>
                        </legend>

                        <div class="mb-3">
                            <label for="identificacion_oficial" class="form-label fw-semibold">
                                <i class="bi bi-person-badge me-1"></i>Identificación oficial <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="identificacion_oficial" name="identificacion_oficial" accept="image/png,image/jpeg,application/pdf" required>
                            <?php if ($validation->getError('identificacion_oficial')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('identificacion_oficial') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="tarjeta_circulacion" class="form-label fw-semibold">
                                <i class="bi bi-card-checklist me-1"></i>Tarjeta de circulación <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="tarjeta_circulacion" name="tarjeta_circulacion" accept="image/png,image/jpeg,application/pdf" required>
                            <?php if ($validation->getError('tarjeta_circulacion')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('tarjeta_circulacion') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3" id="div_doc_carga">
                            <label for="documento_carga_descarga" class="form-label fw-semibold">
                                <i class="bi bi-receipt me-1"></i>Factura / remisión / orden de compra
                                <span id="req_doc" class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="documento_carga_descarga" name="documento_carga_descarga" accept="image/png,image/jpeg,application/pdf">
                            <div class="form-text">Factura, remisión u orden de compra. No requerido para mudanzas.</div>
                            <?php if ($validation->getError('documento_carga_descarga')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('documento_carga_descarga') ?></div>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <div class="d-grid d-md-flex justify-content-md-end gap-2 pt-3 border-top">
                        <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Guardar solicitud
                        </button>
                    </div>
                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm border-0 tramite-sidebar-sticky">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-primary fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i>Costo de Derechos</h5>
            </div>
            <div class="card-body text-center p-4">
                <div class="small text-muted mb-1 text-uppercase fw-semibold">Tarifa Oficial Calculada</div>
                <div class="mb-3">
                    <span id="badge_precio" class="badge bg-primary rounded-pill d-inline-block shadow-sm tramite-cost-badge">
                        $ 0.00
                    </span>
                </div>
                <div id="alerta_placeholder" class="alert alert-info small mb-3 py-2 d-none text-start" role="alert">
                    <i class="bi bi-info-circle me-1"></i>
                    Tarifa oficial conforme a la Ley de Ingresos Municipal vigente.
                </div>
                <div id="info_monto" class="small text-muted">
                    Selecciona periodo y tipo de solicitante para calcular el monto.
                </div>
            </div>
            <div class="card-footer bg-white border-0 pt-0 text-center pb-3">
                <button type="button" id="btn_recalcular" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-arrow-clockwise me-1"></i>Recalcular monto
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body small p-3">
                <div class="fw-semibold mb-2 text-dark"><i class="bi bi-info-circle me-1 text-primary"></i>Información Importante</div>
                <ul class="mb-0 ps-3 text-muted">
                    <li>Los documentos digitales deben ser legibles y completos.</li>
                    <li>El RFC se validará oficialmente durante el cotejo documental.</li>
                    <li>La orden de pago se habilita de forma segura tras validación del registro.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function() {
    const tsRadios = document.querySelectorAll('input[name="tipo_solicitante"]');
    const divNumCam = document.getElementById('div_num_camiones');
    const numCamInput = document.getElementById('num_camiones');
    const lblNombre = document.getElementById('lblNombre');
    const chkMudanza = document.getElementById('es_mudanza');
    const divDocCarga = document.getElementById('div_doc_carga');
    const docCargaInput = document.getElementById('documento_carga_descarga');
    const reqDoc = document.getElementById('req_doc');
    const badgePrecio = document.getElementById('badge_precio');
    const alertaPlaceholder = document.getElementById('alerta_placeholder');
    const btnRecalcular = document.getElementById('btn_recalcular');
    const periodoSel = document.getElementById('periodo');
    const form = document.getElementById('formCargaDescarga');

    const endpoint = '<?= site_url('/portal/tramites/carga-descarga/calcular-monto') ?>';

    function formatearDinero(n) {
        return '$ ' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function actualizarTipoSolicitanteUI() {
        const valor = document.querySelector('input[name="tipo_solicitante"]:checked').value;
        if (valor === 'empresa') {
            divNumCam.style.display = 'block';
            numCamInput.required = true;
            lblNombre.textContent = 'Razón social';
        } else {
            divNumCam.style.display = 'none';
            numCamInput.required = false;
            numCamInput.value = '';
            lblNombre.textContent = 'Nombre completo';
        }
        recalcularMonto();
    }

    function actualizarMudanzaUI() {
        const marcado = chkMudanza.checked;
        if (marcado) {
            docCargaInput.required = false;
            docCargaInput.value = '';
            reqDoc.classList.add('d-none');
            divDocCarga.classList.add('opacity-50');
        } else {
            docCargaInput.required = true;
            reqDoc.classList.remove('d-none');
            divDocCarga.classList.remove('opacity-50');
        }
    }

    let csrfActualHash = '<?= csrf_hash() ?>';
    const csrfActualName = '<?= csrf_token() ?>';

    function recalcularMonto() {
        const tipo = document.querySelector('input[name="tipo_solicitante"]:checked').value;
        const periodo = periodoSel.value;
        if (!periodo) {
            badgePrecio.textContent = '$ 0.00';
            alertaPlaceholder.classList.add('d-none');
            return;
        }
        let numCam = parseInt(numCamInput.value || '1', 10);
        if (isNaN(numCam) || numCam < 1) numCam = 1;
        if (numCam > 15) numCam = 15;

        const data = new FormData();
        data.append('tipo_solicitante', tipo);
        data.append('periodo', periodo);
        data.append('num_camiones', String(numCam));
        data.append(csrfActualName, csrfActualHash);

        fetch(endpoint, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            body: data
        })
        .then(r => r.json())
        .then(json => {
            if (json && json[csrfActualName]) {
                csrfActualHash = json[csrfActualName];
            }
            if (json && json.success) {
                badgePrecio.textContent = formatearDinero(json.monto);
                if (json.placeholder) {
                    alertaPlaceholder.classList.remove('d-none');
                } else {
                    alertaPlaceholder.classList.add('d-none');
                }
            } else {
                badgePrecio.textContent = 'N/D';
                alertaPlaceholder.classList.add('d-none');
            }
        })
        .catch(() => {
            badgePrecio.textContent = 'Error';
        });
    }

    tsRadios.forEach(r => r.addEventListener('change', actualizarTipoSolicitanteUI));
    chkMudanza.addEventListener('change', actualizarMudanzaUI);
    periodoSel.addEventListener('change', recalcularMonto);
    numCamInput.addEventListener('change', recalcularMonto);
    numCamInput.addEventListener('blur', recalcularMonto);
    btnRecalcular.addEventListener('click', recalcularMonto);

    actualizarTipoSolicitanteUI();
    actualizarMudanzaUI();
    setTimeout(recalcularMonto, 100);

    form.addEventListener('submit', function(e) {
        const inicio = document.getElementById('horario_inicio').value;
        const fin = document.getElementById('horario_fin').value;
        if (inicio && fin && inicio >= fin) {
            e.preventDefault();
            alert('El horario final debe ser mayor que el horario de inicio.');
            document.getElementById('horario_fin').focus();
            return;
        }
    });
})();
</script>
<?= $this->endSection() ?>
