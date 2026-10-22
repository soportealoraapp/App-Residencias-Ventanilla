<?php declare(strict_types=1);

/**
 * Variables inyectadas por Admin\SolicitudesController::ver() vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime (CI4 las define en el scope del view).
 */
$__view = get_defined_vars();

/** @var \App\Entities\Solicitud|null $solicitud */
$solicitud = $__view['solicitud'] ?? null;

/** @var \App\Models\UserModel|null $ciudadano */
$ciudadano = $__view['ciudadano'] ?? null;

/** @var array<int, object> $documentos */
$documentos = $__view['documentos'] ?? [];

/** @var array<int, array{historial: object, usuario: \App\Models\UserModel|null}> $historial */
$historial = $__view['historial'] ?? [];

/** @var array<int, string> $estatusSiguientes */
$estatusSiguientes = $__view['estatusSiguientes'] ?? [];

/** @var array<string, mixed> $datosSolicitud */
$datosSolicitud = $__view['datosSolicitud'] ?? [];

/** @var \App\Models\VerificacionFisicaModel|null $verificacion */
$verificacion = $__view['verificacion'] ?? null;

/**
 * URL del listado de solicitudes con los filtros que se indiquen. Cada etiqueta
 * del expediente apunta a la lista que reproduce exactamente ese valor.
 *
 * @param array<string, string> $cambios
 */
$urlSolicitudes = static function (array $cambios): string {
    $limpia = array_filter($cambios, static fn ($v): bool => $v !== '' && $v !== null);

    return site_url('admin/solicitudes' . ($limpia === [] ? '' : '?' . http_build_query($limpia)));
};

/** Trazabilidad de un solo registro (solicitud o documento) en la auditoría. */
$urlAuditoriaRegistro = static function (string $entidad, int $entidadId): string {
    return site_url('admin/auditoria?' . http_build_query(['entidad' => $entidad, 'entidad_id' => (string) $entidadId]));
};
?>
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
                    <div class="d-flex align-items-center gap-2 fs-5 fw-bold font-monospace user-select-all border rounded p-2 bg-light">
                        <i class="bi bi-barcode-fill text-primary" aria-hidden="true"></i>
                        <span class="flex-grow-1 text-truncate"><?= esc($solicitud->folio) ?></span>
                        <button type="button" class="btn-copy" data-bs-toggle="tooltip" data-bs-title="Copiar folio"
                            onclick="copiarTexto('<?= esc($solicitud->folio) ?>', this)">
                            <i class="bi bi-clipboard" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline">Copiar</span>
                        </button>
                    </div>
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Trámite</dt>
                    <dd class="col-sm-8">
                        <a href="<?= $urlSolicitudes(['tramite' => (string) $solicitud->tramite]) ?>" class="fw-semibold text-decoration-none" title="Ver todas las solicitudes de este trámite">
                            <?= tramite_nombre($solicitud->tramite) ?> <i class="bi bi-arrow-right-short" aria-hidden="true"></i>
                        </a>
                        <div class="small text-muted"><?= esc($solicitud->tramite) ?></div>
                    </dd>
                    <dt class="col-sm-4">Ciudadano</dt>
                    <dd class="col-sm-8">
                        <?php if ($ciudadano): ?>
                            <a href="<?= $urlSolicitudes(['q' => (string) ($ciudadano->nombre_completo ?: $ciudadano->username ?? '')]) ?>" class="text-decoration-none" title="Ver otras solicitudes capturadas con este nombre">
                                <?= esc($ciudadano->nombre_completo ?? $ciudadano->username ?? '—') ?>
                            </a>
                            <?php if (!empty($ciudadano->email)): ?>
                                <div class="small text-muted">
                                    <a href="<?= site_url('admin/usuarios?' . http_build_query(['q' => (string) $ciudadano->email])) ?>" class="text-decoration-none" title="Ver la cuenta registrada">
                                        <i class="bi bi-person-gear me-1" aria-hidden="true"></i><?= esc($ciudadano->email) ?>
                                    </a>
                                </div>
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
                        <a href="<?= $urlSolicitudes(['estatus' => (string) $solicitud->estatus]) ?>" class="text-decoration-none" title="Ver todas las solicitudes en este estatus">
                            <span class="badge fs-6 estatus-badge <?= estatus_badge($solicitud->estatus) ?>">
                                <?= esc($solicitud->estatus) ?>
                            </span>
                        </a>
                        <a href="<?= $urlAuditoriaRegistro('solicitudes', (int) $solicitud->id) ?>" class="small text-muted ms-1" title="Eventos de auditoría de este folio">
                            <i class="bi bi-clock-history" aria-hidden="true"></i> Trazabilidad
                        </a>
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
                        <i class="bi bi-lock-fill fs-1 text-muted d-block mb-2" aria-hidden="true"></i>
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
                                <i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Cambiar estatus
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
                <div class="col-md-6"><label class="form-label small fw-bold">Número de póliza</label><input autocomplete="off" autocapitalize="characters" spellcheck="false" name="numero_poliza" class="form-control" value="<?= esc($datosSolicitud['numero_poliza'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label small fw-bold">Aseguradora</label><input autocomplete="off" autocapitalize="words" spellcheck="false" name="aseguradora" class="form-control" value="<?= esc($datosSolicitud['aseguradora'] ?? '') ?>"></div>
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
        <p class="small text-muted">Para autorizar el pago deben cumplirse los 3 criterios de seguridad vial. Si alguno no se cumple, rechaza la solicitud indicando el motivo.</p>
        <form method="post" action="<?= site_url('admin/solicitudes/ur05/validacion/' . $solicitud->id) ?>" id="formValidacionUr05" data-no-loading="1">
            <?= csrf_field() ?>
            <div class="row g-2 mb-3">
                <?php foreach (['afluencia_baja' => 'La calle tiene baja afluencia vehicular', 'sin_transporte_publico' => 'No circula transporte público', 'horario_no_entorpece' => 'El horario no entorpece el tráfico'] as $campo => $etiqueta): ?>
                <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="<?= $campo ?>" value="1" <?= ($datosSolicitud[$campo] ?? '') === '1' ? 'checked' : '' ?>><span class="form-check-label"><?= esc($etiqueta) ?></span></label></div>
                <?php endforeach; ?>
            </div>
            <label for="observaciones_validacion" class="form-label small fw-semibold">Observaciones <span class="text-muted fw-normal">(obligatorias para rechazar)</span></label>
            <textarea name="observaciones_validacion" id="observaciones_validacion" class="form-control mb-3" rows="2" placeholder="Observaciones de la validación..."><?= esc($datosSolicitud['observaciones_validacion'] ?? '') ?></textarea>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success btn-sm" type="submit" name="accion" value="autorizar"><i class="bi bi-check2-circle me-1"></i>Autorizar pago</button>
                <button class="btn btn-outline-danger btn-sm" type="submit" name="accion" value="rechazar"><i class="bi bi-x-circle me-1"></i>Rechazar solicitud</button>
            </div>
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
    <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
        <h6 class="mb-0">Documentos adjuntos (<?= count($documentos) ?>)</h6>
        <a href="<?= $urlAuditoriaRegistro('documentos', (int) $solicitud->id) ?>" class="small text-muted text-decoration-none" title="Validaciones y movimientos de estos documentos">
            <i class="bi bi-clock-history" aria-hidden="true"></i> Auditoría de documentos
        </a>
    </div>

    <?php if (empty($documentos)): ?>
        <div class="empty-state">
            <i class="bi bi-file-earmark-x fs-1 text-muted d-block mb-2" aria-hidden="true"></i>
            <p class="mb-0 text-muted">No hay documentos adjuntos a esta solicitud.</p>
        </div>
    <?php else: ?>
        <?php
        // Una sola fuente de verdad para el escritorio y el teléfono.
        $archivos = [];
        foreach ($documentos as $doc): ?>
            <?php $archivos[] = [
                'id'             => (int) $doc->id,
                'tipo'           => (string) $doc->tipo_documento,
                'nombre'         => (string) $doc->nombre_original,
                'kb'             => number_format(ceil((int) $doc->tamano_bytes / 1024)),
                'hash'           => (string) ($doc->hash_sha256 ?? ''),
                'validado'       => !empty($doc->validado),
            ]; ?>
        <?php endforeach; ?>

        <!-- Teléono: la tabla de seis columnas no cabe en una pantalla angosta -->
        <div class="d-block d-md-none">
            <?php foreach ($archivos as $a): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                        <span class="fw-bold small"><?= esc($a['tipo']) ?></span>
                        <?php if ($a['validado']): ?>
                            <span class="badge bg-success estatus-badge"><i class="bi bi-check2" aria-hidden="true"></i> Validado</span>
                        <?php else: ?>
                            <span class="badge bg-secondary estatus-badge">Pendiente</span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-break"><?= esc($a['nombre']) ?></div>
                    <div class="small text-muted mt-1">
                        <?= $a['kb'] ?> KB
                        <?php if ($a['hash'] !== ''): ?>
                            · <code title="<?= esc($a['hash']) ?>"><?= esc(substr($a['hash'], 0, 12)) ?>...</code>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <a class="btn btn-outline-primary btn-sm" href="<?= site_url('admin/solicitudes/descargar-documento/' . $a['id']) ?>">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>Descargar
                        </a>
                        <form method="post" action="<?= site_url('admin/solicitudes/validar-documento/' . $a['id']) ?>" class="d-inline">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm <?= $a['validado'] ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                <i class="bi <?= $a['validado'] ? 'bi-x-circle' : 'bi-check2-circle' ?> me-1" aria-hidden="true"></i><?= $a['validado'] ? 'Quitar validación' : 'Validar' ?>
                            </button>
                        </form>
                        <a class="btn btn-link btn-sm text-decoration-none" href="<?= $urlAuditoriaRegistro('documentos', $a['id']) ?>">
                            Trazabilidad
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="table-responsive d-none d-md-block">
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
                    <?php foreach ($archivos as $a): ?>
                        <tr>
                            <td class="fw-bold"><?= esc($a['tipo']) ?></td>
                            <td class="text-break"><?= esc($a['nombre']) ?></td>
                            <td class="text-nowrap"><?= $a['kb'] ?> KB</td>
                            <td>
                                <?php if ($a['hash'] !== ''): ?>
                                    <code class="small" title="<?= esc($a['hash']) ?>"><?= esc(substr($a['hash'], 0, 12)) ?>...</code>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['validado']): ?>
                                    <span class="badge bg-success estatus-badge"><i class="bi bi-check2" aria-hidden="true"></i> Validado</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary estatus-badge">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <a class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" data-bs-title="Descargar documento"
                                    href="<?= site_url('admin/solicitudes/descargar-documento/' . $a['id']) ?>">
                                    <i class="bi bi-download me-1" aria-hidden="true"></i>Descargar
                                </a>
                                <form method="post" action="<?= site_url('admin/solicitudes/validar-documento/' . $a['id']) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm <?= $a['validado'] ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                        data-bs-toggle="tooltip" data-bs-title="<?= $a['validado'] ? 'Revocar la validación' : 'Marcar el documento como revisado' ?>">
                                        <i class="bi <?= $a['validado'] ? 'bi-x-circle' : 'bi-check2-circle' ?> me-1" aria-hidden="true"></i><?= $a['validado'] ? 'Revocar' : 'Validar' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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
                                    <i class="bi bi-person-circle me-1" aria-hidden="true"></i><?= esc($u->nombre_completo ?? $u->username ?? 'Usuario') ?>
                                <?php else: ?>
                                    <i class="bi bi-person-question me-1" aria-hidden="true"></i><span class="text-muted">Usuario desconocido</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($h->comentario)): ?>
                            <div class="mt-2 p-2 bg-light rounded border-start border-3 border-primary small">
                                <i class="bi bi-chat-left-text me-1 text-primary" aria-hidden="true"></i><?= esc($h->comentario) ?>
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

    // UR-05: autorizar exige los 3 criterios; rechazar exige motivo y confirmación.
    const formUr05 = document.getElementById('formValidacionUr05');
    formUr05?.addEventListener('submit', function (e) {
        const accion = e.submitter ? e.submitter.value : '';
        const obs = document.getElementById('observaciones_validacion');
        if (accion === 'autorizar') {
            const sinMarcar = [...formUr05.querySelectorAll('input[type=checkbox]')].some(c => !c.checked);
            if (sinMarcar) {
                e.preventDefault();
                alert('Para autorizar el pago marca los 3 criterios. Si alguno no se cumple, usa "Rechazar solicitud".');
            }
        } else if (accion === 'rechazar') {
            if (obs.value.trim() === '') {
                e.preventDefault();
                obs.classList.add('is-invalid');
                obs.focus();
                alert('Escribe el motivo del rechazo en Observaciones.');
            } else if (!confirm('¿Rechazar esta solicitud? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        }
    });

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
