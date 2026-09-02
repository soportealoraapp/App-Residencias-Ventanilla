<?php declare(strict_types=1);
$validation = \Config\Services::validation();
$oldNombre = old('solicitante_nombre', '');
$oldDomicilio = old('solicitante_domicilio', '');
$oldTipoCesion = old('tipo_cesion', '');
$oldNumTitulo = old('numero_titulo_concesion', '');
$oldPlacas = old('vehiculo_placas', '');
$oldNumSerie = old('vehiculo_num_serie', '');
?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('content') ?>
<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/tramites') ?>">Trámites</a></li>
                <li class="breadcrumb-item active" aria-current="page">Cesión de Concesión</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-badge fs-3 me-3"></i>
                    <div>
                        <h1 class="h4 mb-0">UR-TT-T-06 · Cesión de Concesión</h1>
                        <div class="small opacity-75">Formulario de solicitud</div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?= form_open_multipart('/portal/tramites/cesion-concesion/guardar', ['id' => 'formCesionConcesion', 'novalidate' => 'novalidate']) ?>
                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-person-fill me-2"></i>SECCIÓN 1 - Datos del solicitante
                        </legend>

                        <div class="mb-3">
                            <label for="solicitante_nombre" class="form-label fw-semibold">Nombre completo del solicitante <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="solicitante_nombre" name="solicitante_nombre" required maxlength="180" value="<?= esc($oldNombre) ?>" placeholder="Ingresa nombre completo">
                            <?php if ($validation->getError('solicitante_nombre')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('solicitante_nombre') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="solicitante_domicilio" class="form-label fw-semibold">Domicilio del solicitante <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="solicitante_domicilio" name="solicitante_domicilio" rows="2" required maxlength="250" placeholder="Calle, número, colonia, CP, municipio, estado"><?= esc($oldDomicilio) ?></textarea>
                            <?php if ($validation->getError('solicitante_domicilio')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('solicitante_domicilio') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="tipo_cesion" class="form-label fw-semibold">Tipo de cesión <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo_cesion" name="tipo_cesion" required>
                                <option value="">Selecciona...</option>
                                <?php foreach ($tipos_cesion as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $oldTipoCesion === $k ? 'selected' : '' ?>><?= esc($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($validation->getError('tipo_cesion')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('tipo_cesion') ?></div>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-car-front-fill me-2"></i>SECCIÓN 2 - Concesión original
                        </legend>

                        <div class="mb-3">
                            <label for="numero_titulo_concesion" class="form-label fw-semibold">Número de título de concesión <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numero_titulo_concesion" name="numero_titulo_concesion" required maxlength="50" value="<?= esc($oldNumTitulo) ?>" placeholder="Ej: CONC-2025-00123">
                            <div id="concesion_resultado" class="mt-2"></div>
                            <?php if ($validation->getError('numero_titulo_concesion')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('numero_titulo_concesion') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="vehiculo_placas" class="form-label fw-semibold">Placas del vehículo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="vehiculo_placas" name="vehiculo_placas" required maxlength="10" value="<?= esc($oldPlacas) ?>" placeholder="Ej: ABC123">
                                <?php if ($validation->getError('vehiculo_placas')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('vehiculo_placas') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="vehiculo_num_serie" class="form-label fw-semibold">Número de serie (VIN) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="vehiculo_num_serie" name="vehiculo_num_serie" required maxlength="20" value="<?= esc($oldNumSerie) ?>" placeholder="Ej: 1HGCM82633A004352">
                                <?php if ($validation->getError('vehiculo_num_serie')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('vehiculo_num_serie') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>SECCIÓN 3 - Documentos base comunes
                            <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx 10 MB)</span>
                        </legend>

                        <div class="mb-3">
                            <label for="titulo_concesion_archivo" class="form-label fw-semibold">
                                <i class="bi bi-file-earmark-richtext me-1"></i>Título concesión original <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="titulo_concesion_archivo" name="titulo_concesion_archivo" accept="image/png,image/jpeg,application/pdf" required>
                            <?php if ($validation->getError('titulo_concesion_archivo')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('titulo_concesion_archivo') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="documentos_capacidad" class="form-label fw-semibold">
                                <i class="bi bi-person-check me-1"></i>Documentos de capacidad legal (identificaciones, poderes) <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control" id="documentos_capacidad" name="documentos_capacidad[]" accept="image/png,image/jpeg,application/pdf" multiple required>
                            <div class="form-text">Selecciona uno o más archivos.</div>
                            <?php if ($validation->getError('documentos_capacidad')): ?>
                                <div class="text-danger small mt-1"><?= $validation->getError('documentos_capacidad') ?></div>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <fieldset class="mb-4">
                        <legend class="h6 border-bottom pb-2 mb-3 text-primary">
                            <i class="bi bi-folder2-open me-2"></i>SECCIÓN 4 - Documentos específicos por tipo de cesión
                            <span class="small text-muted fw-normal ms-2">(PDF, JPG o PNG · Máx 10 MB)</span>
                        </legend>

                        <div id="seccion-muerte_incapacidad" class="seccion-condicional" style="display: none;">
                            <div class="alert alert-info small py-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Documentos requeridos para <strong>Muerte o Incapacidad del titular</strong>
                            </div>
                            <div class="mb-3">
                                <label for="acta_defuncion_o_sentencia" class="form-label fw-semibold">Acta de defunción o sentencia de incapacidad <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="acta_defuncion_o_sentencia" name="acta_defuncion_o_sentencia" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('acta_defuncion_o_sentencia')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('acta_defuncion_o_sentencia') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="curatela_documento" class="form-label fw-semibold">Documento de curatela o nombramiento de tutor <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="curatela_documento" name="curatela_documento" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('curatela_documento')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('curatela_documento') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="beneficiario_identificacion" class="form-label fw-semibold">Identificación oficial del beneficiario <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="beneficiario_identificacion" name="beneficiario_identificacion" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('beneficiario_identificacion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('beneficiario_identificacion') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="beneficiario_acta_nacimiento" class="form-label fw-semibold">Acta de nacimiento del beneficiario <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="beneficiario_acta_nacimiento" name="beneficiario_acta_nacimiento" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('beneficiario_acta_nacimiento')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('beneficiario_acta_nacimiento') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="seccion-cesion_derechos" class="seccion-condicional" style="display: none;">
                            <div class="alert alert-info small py-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Documentos requeridos para <strong>Cesión voluntaria de derechos</strong>
                            </div>
                            <div class="mb-3">
                                <label for="cedente_identificacion" class="form-label fw-semibold">Identificación oficial del cedente (titular actual) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="cedente_identificacion" name="cedente_identificacion" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('cedente_identificacion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('cedente_identificacion') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="cesionario_identificacion" class="form-label fw-semibold">Identificación oficial del cesionario <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="cesionario_identificacion" name="cesionario_identificacion" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('cesionario_identificacion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('cesionario_identificacion') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="cesionario_acta_nacimiento" class="form-label fw-semibold">Acta de nacimiento del cesionario <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="cesionario_acta_nacimiento" name="cesionario_acta_nacimiento" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('cesionario_acta_nacimiento')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('cesionario_acta_nacimiento') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="revocacion_notarial" class="form-label fw-semibold">Revocación notarial de poder (si aplica) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="revocacion_notarial" name="revocacion_notarial" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('revocacion_notarial')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('revocacion_notarial') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="contrato_cesion_notarial" class="form-label fw-semibold">Contrato de cesión notarializado <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="contrato_cesion_notarial" name="contrato_cesion_notarial" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('contrato_cesion_notarial')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('contrato_cesion_notarial') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="seccion-mandamiento_judicial" class="seccion-condicional" style="display: none;">
                            <div class="alert alert-info small py-2 mb-3">
                                <i class="bi bi-info-circle me-1"></i>Documentos requeridos para <strong>Mandamiento judicial</strong>
                            </div>
                            <div class="mb-3">
                                <label for="resolucion_judicial_certificada" class="form-label fw-semibold">Resolución judicial certificada <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="resolucion_judicial_certificada" name="resolucion_judicial_certificada" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('resolucion_judicial_certificada')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('resolucion_judicial_certificada') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="interesado_identificacion" class="form-label fw-semibold">Identificación oficial del interesado <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="interesado_identificacion" name="interesado_identificacion" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('interesado_identificacion')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('interesado_identificacion') ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label for="interesado_acta_nacimiento" class="form-label fw-semibold">Acta de nacimiento del interesado <span class="text-danger">*</span></label>
                                <input type="file" class="form-control doc-condicional" id="interesado_acta_nacimiento" name="interesado_acta_nacimiento" accept="image/png,image/jpeg,application/pdf">
                                <?php if ($validation->getError('interesado_acta_nacimiento')): ?>
                                    <div class="text-danger small mt-1"><?= $validation->getError('interesado_acta_nacimiento') ?></div>
                                <?php endif; ?>
                            </div>
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
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body p-3 text-center">
                <div class="fw-semibold text-dark mb-2"><i class="bi bi-file-earmark-arrow-down text-primary me-1"></i>¿Qué necesitas para este trámite?</div>
                <p class="small text-muted mb-3">Descarga el formato oficial para conocer los documentos y requisitos antes de iniciar.</p>
                <a href="<?= site_url('/portal/formato/UR-TT-T-06') ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-download me-1"></i>Descargar formato
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 tramite-sidebar-card">
            </div>
            <div class="card-body text-center p-4">
                <div class="small text-muted mb-1 text-uppercase fw-semibold">Tarifa Oficial Vigente</div>
                <div class="mb-3">
                    <?php
                    $tarifario = new \App\Libraries\TarifarioService();
                    $montoEstimado = $tarifario->calcularMontoUrTtT06();
                    ?>
                    <?php if ($montoEstimado !== null): ?>
                        <span class="badge bg-primary rounded-pill d-inline-block shadow-sm tramite-cost-badge">
                            $ <?= number_format($montoEstimado, 2, '.', ',') ?>
                        </span>
                        <div class="alert alert-info small mt-3 mb-0 py-2 text-start" role="alert">
                            <i class="bi bi-info-circle me-1"></i>
                            Tarifa oficial conforme a la Ley de Ingresos Municipal.
                        </div>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-pill d-inline-block tramite-cost-badge">
                            N/D
                        </span>
                        <div class="alert alert-danger small mt-3 mb-0 py-2" role="alert">
                            <i class="bi bi-x-circle me-1"></i>
                            Tarifa en actualización en la Dirección de Movilidad.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="small text-muted border-top pt-3 text-start">
                    <div class="fw-semibold text-dark mb-1"><i class="bi bi-shield-check text-primary me-1"></i>Procedimiento Oficial:</div>
                    <ul class="ps-3 mb-0 text-muted">
                        <li>Validación exhaustiva de antecedentes de la concesión.</li>
                        <li>Cotejo presencial de firmas de cedente y cesionario.</li>
                        <li>Emisión del nuevo título de concesión.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-4">
            <div class="card-body small">
                <div class="fw-semibold mb-2"><i class="bi bi-info-circle me-1 text-primary"></i>Información importante</div>
                <ul class="mb-0 ps-3 text-muted">
                    <li>Los documentos deberán estar legibles y completos.</li>
                    <li>Una vez enviada, la solicitud pasará a revisión documental manual.</li>
                    <li>Te notificaremos por los medios registrados cuando haya cambios.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function() {
    const tipoCesionSel = document.getElementById('tipo_cesion');
    const seccionesCondicionales = document.querySelectorAll('.seccion-condicional');
    const docCondicionales = document.querySelectorAll('.doc-condicional');
    const numTituloInput = document.getElementById('numero_titulo_concesion');
    const placasInput = document.getElementById('vehiculo_placas');
    const numSerieInput = document.getElementById('vehiculo_num_serie');
    const concesionResultado = document.getElementById('concesion_resultado');
    const baseUrl = '<?= site_url() ?>';

    function limpiarDocsCondicionales() {
        docCondicionales.forEach(input => {
            input.required = false;
            if (input.type === 'file') {
                input.value = '';
            }
        });
    }

    function marcarRequiredSeccion(seccionId) {
        const seccion = document.getElementById(seccionId);
        if (seccion) {
            const inputs = seccion.querySelectorAll('input[type="file"].doc-condicional');
            inputs.forEach(input => {
                input.required = true;
            });
        }
    }

    function actualizarSecciones() {
        const valor = tipoCesionSel.value;
        seccionesCondicionales.forEach(sec => {
            sec.style.display = 'none';
        });
        limpiarDocsCondicionales();
        if (valor) {
            const targetId = 'seccion-' + valor;
            const target = document.getElementById(targetId);
            if (target) {
                target.style.display = 'block';
                marcarRequiredSeccion(targetId);
            }
        }
    }

    function validarConcesion() {
        const valor = numTituloInput.value.trim();
        concesionResultado.innerHTML = '';
        if (!valor) {
            placasInput.readOnly = false;
            numSerieInput.readOnly = false;
            return;
        }
        const url = baseUrl + '/portal/tramites/cesion-concesion/validar-concesion/' + encodeURIComponent(valor);
        fetch(url, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
        .then(r => r.json())
        .then(json => {
            if (json && json.success) {
                placasInput.value = json.vehiculo_placas || '';
                numSerieInput.value = json.vehiculo_num_serie || '';
                placasInput.readOnly = true;
                numSerieInput.readOnly = true;
                let html = '<div class="alert alert-success small py-2 mb-0" role="alert"><i class="bi bi-check-circle-fill me-1"></i><strong>Concesión VIGENTE ✔️</strong>';
                if (json.titular_actual) {
                    html += '<br><span class="opacity-75">Titular: ' + json.titular_actual + '</span>';
                }
                if (json.vigencia_fin) {
                    html += '<br><span class="opacity-75">Vigencia hasta: ' + json.vigencia_fin + '</span>';
                }
                html += '</div>';
                concesionResultado.innerHTML = html;
            } else {
                placasInput.readOnly = false;
                numSerieInput.readOnly = false;
                concesionResultado.innerHTML = '<div class="text-danger small mt-1"><i class="bi bi-x-circle me-1"></i>' + (json ? json.mensaje : 'Concesión no encontrada o vencida') + '</div>';
            }
        })
        .catch(() => {
            placasInput.readOnly = false;
            numSerieInput.readOnly = false;
            concesionResultado.innerHTML = '<div class="text-danger small mt-1">Error al validar la concesión.</div>';
        });
    }

    tipoCesionSel.addEventListener('change', actualizarSecciones);
    numTituloInput.addEventListener('blur', validarConcesion);

    actualizarSecciones();
    <?php if ($oldNumTitulo): ?>
        setTimeout(validarConcesion, 150);
    <?php endif; ?>
})();
</script>
<?= $this->endSection() ?>
