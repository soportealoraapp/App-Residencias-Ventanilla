<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Detalle solicitud <?= esc($solicitud->folio) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$claveEtiquetas = [
    'rfc' => 'RFC',
    'RFC' => 'RFC',
    'razon_social_o_nombre' => 'Razón social / Nombre',
    'solicitante_nombre' => 'Nombre del solicitante',
    'solicitante_email' => 'Correo electrónico',
    'solicitante_telefono' => 'Teléfono',
    'domicilio' => 'Domicilio',
    'periodo' => 'Periodo',
    'tipo' => 'Tipo',
    'num_camiones' => 'Número de camiones',
    'num_vehiculos' => 'Número de vehículos',
    'placas' => 'Placas',
    'concesion_id' => 'ID Concesión',
    'num_concesion' => 'Número de concesión',
    'titular_concesion' => 'Titular de la concesión',
    'motivo' => 'Motivo',
    'observaciones' => 'Observaciones',
    'fecha_inicio' => 'Fecha de inicio',
    'fecha_fin' => 'Fecha de fin',
    'origen' => 'Origen',
    'destino' => 'Destino',
    'ruta' => 'Ruta',
    'descripcion' => 'Descripción',
    'importe' => 'Importe',
    'cantidad' => 'Cantidad',
];
?>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="mb-0">Datos Generales</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1">Folio</label>
                    <div class="fs-4 fw-bold font-monospace user-select-all border rounded p-2 bg-light">
                        🔍 <?= esc($solicitud->folio) ?>
                    </div>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Trámite</dt>
                    <dd class="col-sm-8">
                        <?= tramite_nombre($solicitud->tramite) ?>
                        <div class="small text-muted"><?= esc($solicitud->tramite) ?></div>
                    </dd>
                    <dt class="col-sm-4">Ciudadano</dt>
                    <dd class="col-sm-8">
                        <?php if ($ciudadano): ?>
                            <div><?= esc($ciudadano->nombre_completo ?? $ciudadano->username ?? '—') ?></div>
                            <?php if (!empty($ciudadano->email)): ?>
                                <div class="small text-muted"><?= esc($ciudadano->email) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($ciudadano->telefono)): ?>
                                <div class="small text-muted"><?= esc($ciudadano->telefono) ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4">Fecha solicitud</dt>
                    <dd class="col-sm-8"><?= formatear_fecha($solicitud->fecha_solicitud) ?></dd>
                    <dt class="col-sm-4">Fecha resolución</dt>
                    <dd class="col-sm-8"><?= formatear_fecha($solicitud->fecha_resolucion ?? null) ?></dd>
                    <dt class="col-sm-4">Monto</dt>
                    <dd class="col-sm-8 fw-bold fs-5"><?= formatear_dinero((float)$solicitud->monto) ?></dd>
                    <dt class="col-sm-4">Estatus</dt>
                    <dd class="col-sm-8">
                        <span class="badge fs-6 estatus-badge <?= estatus_badge($solicitud->estatus) ?>">
                            <?= esc($solicitud->estatus) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100 border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">Cambio de estatus</h6>
            </div>
            <div class="card-body">
                <?php if (empty($estatusSiguientes)): ?>
                    <div class="text-center py-4">
                        <div class="fs-1 text-muted mb-2">🔒</div>
                        <p class="text-muted mb-0">Este estatus es terminal. No hay transiciones disponibles.</p>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= site_url('admin/solicitudes/cambiar-estatus/' . $solicitud->id) ?>" id="formCambiarEstatus">
                        <?= csrf_field() ?>
                        <input type="hidden" name="solicitud_id" value="<?= $solicitud->id ?>">
                        <div class="mb-3">
                            <label for="nuevo_estatus" class="form-label fw-bold">Nuevo estatus</label>
                            <select name="nuevo_estatus" id="nuevo_estatus" class="form-select" required>
                                <option value="" disabled selected>-- selecciona --</option>
                                <?php foreach ($estatusSiguientes as $est): ?>
                                    <option value="<?= esc($est) ?>"><?= esc($est) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                Estatus actual: <span class="badge estatus-badge <?= estatus_badge($solicitud->estatus) ?>"><?= esc($solicitud->estatus) ?></span>
                            </div>
                        </div>
                        <div class="mb-3" id="grupoComentario" style="display:none;">
                            <label for="comentario" class="form-label fw-bold">
                                Comentario / Motivo
                                <span class="text-danger">*</span>
                            </label>
                            <textarea name="comentario" id="comentario" rows="4" class="form-control"
                                placeholder="Explica el motivo de la prevención o rechazo..."
                                ></textarea>
                            <div class="form-text text-danger">
                                Este campo es obligatorio para estatus "Prevención" o "Rechazado".
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-outline-primary btn-sm fw-bold">
                                ✅ Cambiar estatus
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
</div>

<?php if ($solicitud->tramite === 'UR-TT-T-02'): ?>
<div class="card mb-3 border-warning shadow-sm">
    <div class="card-header bg-warning bg-opacity-25 d-flex justify-content-between align-items-center py-2">
        <h6 class="mb-0 text-dark fw-bold">
            <i class="bi bi-paint-bucket me-2"></i>Inspección Física y Dictamen de Despintado (UR-02)
        </h6>
        <?php if (!empty($verificacion->fecha_cita)): ?>
            <span class="badge bg-primary fs-6"><i class="bi bi-calendar-event me-1"></i>Cita: <?= date('d/m/Y H:i', strtotime($verificacion->fecha_cita)) ?> hrs</span>
        <?php else: ?>
            <span class="badge bg-secondary">Sin cita programada</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="small text-muted mb-1">Estado de la Inspección</div>
                    <div class="fw-bold mb-2">
                        <?php if (!empty($verificacion->resultado)): ?>
                            <?php if ($verificacion->resultado === 'aprobado'): ?>
                                <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Aprobado (Despintado Conforme)</span>
                            <?php else: ?>
                                <span class="badge bg-danger fs-6"><i class="bi bi-x-circle me-1"></i>Rechazado (No Conforme)</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-hourglass-split me-1"></i>Pendiente de Dictamen</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($verificacion->observaciones)): ?>
                        <div class="small text-muted border-top pt-2">
                            <strong>Observaciones del inspector:</strong><br>
                            <?= nl2br(esc($verificacion->observaciones)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <form method="post" action="<?= site_url('admin/solicitudes/dictamen-ur02/' . $solicitud->id) ?>">
                    <?= csrf_field() ?>
                    <h6 class="fw-bold mb-2 text-dark">Registrar o Actualizar Dictamen</h6>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">Resultado de la inspección <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="resultado" id="dict_aprobado" value="aprobado" <?= (!empty($verificacion->resultado) && $verificacion->resultado === 'aprobado') ? 'checked' : '' ?> required>
                                <label class="form-check-label text-success fw-bold" for="dict_aprobado">
                                    <i class="bi bi-check2-circle me-1"></i>Aprobar (Despintado)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="resultado" id="dict_rechazado" value="rechazado" <?= (!empty($verificacion->resultado) && $verificacion->resultado === 'rechazado') ? 'checked' : '' ?> required>
                                <label class="form-check-label text-danger fw-bold" for="dict_rechazado">
                                    <i class="bi bi-x-circle me-1"></i>Rechazar (Incumple)
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label for="obs_verificacion" class="form-label small fw-bold">Observaciones del dictamen <span class="text-danger">*</span></label>
                        <textarea name="observaciones" id="obs_verificacion" rows="2" class="form-control form-control-sm" required placeholder="Describe las condiciones físicas de la unidad inspeccionada..."><?= esc($verificacion->observaciones ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm fw-bold w-100 shadow-sm">
                        <i class="bi bi-file-earmark-check me-1"></i>Guardar Dictamen de Verificación
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($solicitud->tramite === 'UR-TT-T-04'): ?>
<div class="card mb-3 border-primary shadow-sm">
    <div class="card-header bg-primary bg-opacity-10"><h6 class="mb-0 text-primary fw-bold"><i class="bi bi-clipboard2-check me-2"></i>Evaluación provisional UR-04</h6></div>
    <div class="card-body">
        <form method="post" action="<?= site_url('admin/solicitudes/ur04/evaluacion/' . $solicitud->id) ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12"><label class="form-label small fw-bold">Observaciones de revisión documental</label><textarea name="observaciones_revision" class="form-control" rows="2"><?= esc($datosSolicitud['observaciones_revision'] ?? '') ?></textarea></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Resultado del estudio técnico</label><select name="resultado_estudio_tecnico" class="form-select"><option value="">Pendiente</option><option value="favorable" <?= ($datosSolicitud['resultado_estudio_tecnico'] ?? '') === 'favorable' ? 'selected' : '' ?>>Favorable</option><option value="no_favorable" <?= ($datosSolicitud['resultado_estudio_tecnico'] ?? '') === 'no_favorable' ? 'selected' : '' ?>>No favorable</option></select></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Resultado de inspección</label><select name="resultado_inspeccion" class="form-select"><option value="">Pendiente</option><option value="aprobada" <?= ($datosSolicitud['resultado_inspeccion'] ?? '') === 'aprobada' ? 'selected' : '' ?>>Aprobada</option><option value="no_aprobada" <?= ($datosSolicitud['resultado_inspeccion'] ?? '') === 'no_aprobada' ? 'selected' : '' ?>>No aprobada</option></select></div>
                <div class="col-12"><label class="form-label small fw-bold">Observaciones del estudio técnico</label><textarea name="observaciones_estudio_tecnico" class="form-control" rows="2"><?= esc($datosSolicitud['observaciones_estudio_tecnico'] ?? '') ?></textarea></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Resultado de revista mecánica</label><select name="resultado_revista_mecanica" class="form-select"><option value="">Pendiente</option><option value="aprobada" <?= ($datosSolicitud['resultado_revista_mecanica'] ?? '') === 'aprobada' ? 'selected' : '' ?>>Aprobada</option><option value="no_aprobada" <?= ($datosSolicitud['resultado_revista_mecanica'] ?? '') === 'no_aprobada' ? 'selected' : '' ?>>No aprobada</option></select></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Seguro validado</label><select name="seguro_validado" class="form-select"><option value="">Pendiente</option><option value="si" <?= ($datosSolicitud['seguro_validado'] ?? '') === 'si' ? 'selected' : '' ?>>Sí</option><option value="no" <?= ($datosSolicitud['seguro_validado'] ?? '') === 'no' ? 'selected' : '' ?>>No</option></select></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Número de póliza</label><input name="numero_poliza" class="form-control" value="<?= esc($datosSolicitud['numero_poliza'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Aseguradora</label><input name="aseguradora" class="form-control" value="<?= esc($datosSolicitud['aseguradora'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label small fw-bold">Observaciones de inspección</label><textarea name="observaciones_inspeccion" class="form-control" rows="2"><?= esc($datosSolicitud['observaciones_inspeccion'] ?? '') ?></textarea></div>
                <div class="col-12"><label class="form-label small fw-bold">Observaciones de revista / seguro</label><textarea name="observaciones_revista_mecanica" class="form-control" rows="2"><?= esc($datosSolicitud['observaciones_revista_mecanica'] ?? '') ?></textarea><textarea name="observaciones_seguro" class="form-control mt-2" rows="2"><?= esc($datosSolicitud['observaciones_seguro'] ?? '') ?></textarea></div>
            </div>
            <button class="btn btn-primary btn-sm mt-3" type="submit"><i class="bi bi-save me-1"></i>Guardar evaluación</button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($solicitud->tramite === 'UR-TT-T-05' && in_array($solicitud->estatus, ['Recibido', 'En validación'], true)): ?>
<div class="card mb-3 border-primary shadow-sm">
    <div class="card-header bg-primary bg-opacity-10"><h6 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-check me-2"></i>Validación rápida UR-05</h6></div>
    <div class="card-body">
        <p class="small text-muted">Marca los criterios provisionales de seguridad vial para autorizar el pago.</p>
        <form method="post" action="<?= site_url('admin/solicitudes/ur05/validacion/' . $solicitud->id) ?>">
            <?= csrf_field() ?>
            <div class="row g-2 mb-3">
                <?php foreach (['afluencia_baja' => 'La calle tiene baja afluencia vehicular', 'sin_transporte_publico' => 'No circula transporte público', 'horario_no_entorpece' => 'El horario no entorpece el tráfico'] as $campo => $etiqueta): ?>
                <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="<?= $campo ?>" value="1" <?= ($datosSolicitud[$campo] ?? '') === '1' ? 'checked' : '' ?>><span class="form-check-label"><?= esc($etiqueta) ?></span></label></div>
                <?php endforeach; ?>
            </div>
            <textarea name="observaciones_validacion" class="form-control mb-3" rows="2" placeholder="Observaciones de la validación..."><?= esc($datosSolicitud['observaciones_validacion'] ?? '') ?></textarea>
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2-circle me-1"></i>Validar y autorizar pago</button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Datos del trámite (captura)</h6>
    </div>
    <div class="card-body">
        <?php if (empty($datosSolicitud)): ?>
            <p class="text-muted mb-0">Sin datos capturados para este trámite.</p>
        <?php else: ?>
            <dl class="row mb-0">
                <?php foreach ($datosSolicitud as $clave => $valor): ?>
                    <?php
                        $etiqueta = $claveEtiquetas[$clave] ?? ucwords(str_replace('_', ' ', $clave));
                        if (is_string($valor) && in_array(strtolower($clave), ['fecha_inicio', 'fecha_fin', 'fecha'], true)) {
                            $valorMostrar = formatear_fecha($valor);
                        } elseif (is_string($valor) && strtolower($clave) === 'importe' && is_numeric($valor)) {
                            $valorMostrar = formatear_dinero((float)$valor);
                        } else {
                            $valorMostrar = esc($valor);
                        }
                    ?>
                    <dt class="col-sm-4 col-md-3 text-muted small pt-1 pb-1 border-bottom"><?= esc($etiqueta) ?> <span class="text-secondary fw-normal">(<?= esc($clave) ?>)</span></dt>
                    <dd class="col-sm-8 col-md-9 pt-1 pb-1 border-bottom mb-0"><?= $valorMostrar !== '' ? $valorMostrar : '<span class="text-muted">—</span>' ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Documentos adjuntos (<?= count($documentos) ?>)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tipo</th>
                    <th>Nombre original</th>
                    <th>Tamaño</th>
                    <th>SHA256</th>
                    <th>Validado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($documentos)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            No hay documentos adjuntos a esta solicitud.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($doc->tipo_documento) ?></td>
                            <td><?= esc($doc->nombre_original) ?></td>
                            <td class="text-nowrap"><?= number_format(ceil($doc->tamano_bytes / 1024)) ?> KB</td>
                            <td>
                                <?php if (!empty($doc->hash_sha256)): ?>
                                    <code class="small" title="<?= esc($doc->hash_sha256) ?>">
                                        <?= esc(substr($doc->hash_sha256, 0, 12)) ?>...
                                    </code>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($doc->validado)): ?>
                                    <span class="badge bg-success estatus-badge">✓ Validado</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary estatus-badge">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <a class="btn btn-outline-primary btn-sm"
                                    href="<?= site_url('admin/solicitudes/descargar-documento/' . $doc->id) ?>">
                                    ⬇ Descargar
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    title="Marcar como validado (solo UI demo)">
                                    ✓ Validar
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h6 class="mb-0">Historial de estatus (<?= count($historial) ?>)</h6>
    </div>
    <div class="card-body p-0">
        <?php if (empty($historial)): ?>
            <p class="text-muted m-4">Sin historial de estatus.</p>
        <?php else: ?>
            <div class="list-group list-group-flush rounded-0">
                <?php foreach ($historial as $idx => $item): ?>
                    <?php $h = $item['historial']; $u = $item['usuario']; ?>
                    <div class="list-group-item border-0 border-bottom ps-5 position-relative">
                        <div class="position-absolute start-0 top-0 bottom-0 w-100 ps-3 d-flex align-items-start pt-3" style="pointer-events:none;">
                            <div class="position-relative" style="width: 24px;">
                                <div class="rounded-circle border border-2 border-primary bg-white position-absolute start-0"
                                    style="width: 16px; height: 16px; top: 2px;"></div>
                                <?php if ($idx < count($historial) - 1): ?>
                                    <div class="position-absolute bg-primary-subtle"
                                        style="width: 2px; left: 7px; top: 18px; bottom: -24px;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php if (!empty($h->estatus_anterior)): ?>
                                    <span class="badge bg-light text-dark border text-decoration-line-through estatus-badge">
                                        <?= esc($h->estatus_anterior) ?>
                                    </span>
                                    <span class="text-muted small">→</span>
                                <?php endif; ?>
                                <span class="badge estatus-badge fs-6 <?= estatus_badge($h->estatus_nuevo) ?>">
                                    <?= esc($h->estatus_nuevo) ?>
                                </span>
                                <span class="small text-muted">
                                    <?= formatear_fecha($h->fecha) ?>
                                </span>
                            </div>
                            <div class="small text-muted mt-1 mt-md-0">
                                <?php if ($u): ?>
                                    👤 <?= esc($u->nombre_completo ?? $u->username ?? 'Usuario') ?>
                                <?php else: ?>
                                    👤 <span class="text-muted">Usuario desconocido</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($h->comentario)): ?>
                            <div class="mt-2 p-2 bg-light rounded border-start border-3 border-primary small">
                                💬 <?= esc($h->comentario) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectEstatus = document.getElementById('nuevo_estatus');
    const grupoComentario = document.getElementById('grupoComentario');
    const comentario = document.getElementById('comentario');
    if (!selectEstatus || !grupoComentario || !comentario) return;

    function actualizarComentario() {
        const valor = selectEstatus.value;
        const requiere = (valor === 'Prevención' || valor === 'Rechazado');
        grupoComentario.style.display = requiere ? '' : 'none';
        comentario.required = requiere;
        if (!requiere) comentario.value = '';
    }

    selectEstatus.addEventListener('change', actualizarComentario);
    actualizarComentario();

    const form = document.getElementById('formCambiarEstatus');
    if (form) {
        form.addEventListener('submit', function (e) {
            const valor = selectEstatus.value;
            const requiere = (valor === 'Prevención' || valor === 'Rechazado');
            if (requiere && comentario.value.trim() === '') {
                e.preventDefault();
                comentario.classList.add('is-invalid');
                comentario.focus();
                alert('El comentario/motivo es obligatorio para Prevención o Rechazado.');
                return false;
            }
            comentario.classList.remove('is-invalid');
            return true;
        });
    }
});
</script>
<?= $this->endSection() ?>
