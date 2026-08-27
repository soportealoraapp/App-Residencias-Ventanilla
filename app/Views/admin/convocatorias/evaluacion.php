<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Evaluación Comparativa · Convocatoria #<?= esc($convocatoria->id) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/admin/dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= site_url('/admin/solicitudes?tramite=UR-TT-T-01') ?>">Solicitudes UR-01</a></li>
                <li class="breadcrumb-item active" aria-current="page">Convocatoria #<?= esc($convocatoria->id) ?></li>
            </ol>
        </nav>
    </div>
</div>

<!-- CABECERA DE LA CONVOCATORIA -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-dark text-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center">
            <div class="bg-primary text-white p-2 rounded-3 me-3">
                <i class="bi bi-award fs-3"></i>
            </div>
            <div>
                <h1 class="h4 mb-0 text-white">Convocatoria #<?= esc($convocatoria->id) ?> · Otorgamiento de Concesiones</h1>
                <div class="small opacity-75">Publicada el <?= date('d/m/Y', strtotime($convocatoria->fecha_publicacion)) ?> · Estatus: <strong><?= esc($convocatoria->estatus) ?></strong></div>
            </div>
        </div>
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2">
                <i class="bi bi-people-fill me-1"></i><?= count($solicitudes) ?> Solicitantes Participantes
            </span>
        </div>
    </div>
    <div class="card-body bg-light">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="small text-muted mb-1">Bases de la Convocatoria:</div>
                <div class="fw-semibold text-dark"><?= esc($convocatoria->bases) ?></div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0 border-start ps-md-4">
                <div class="small text-muted">Periodo de registro:</div>
                <div class="fw-bold text-primary">
                    <?= date('d/m/Y', strtotime($convocatoria->periodo_registro_inicio)) ?> 
                    <span class="text-muted mx-1">→</span> 
                    <?= date('d/m/Y', strtotime($convocatoria->periodo_registro_fin)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
    $tieneGanador = false;
    $ganadorFolio = null;
    foreach ($solicitudes as $item) {
        $sol = is_array($item) ? (object)$item['solicitud'] : (is_object($item) && isset($item->solicitud) ? $item->solicitud : $item);
        if ($sol && isset($sol->estatus) && $sol->estatus === 'Seleccionado') {
            $tieneGanador = true;
            $ganadorFolio = $sol->folio;
            break;
        }
    }
?>

<?php if ($tieneGanador): ?>
    <div class="alert alert-success d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-trophy-fill fs-2 me-3 text-success"></i>
        <div>
            <h5 class="alert-heading mb-1 fw-bold"> Dictamen Comparativo Concluido</h5>
            <div>El H. Ayuntamiento ha seleccionado como ganador a la solicitud con Folio: <strong><code><?= esc($ganadorFolio) ?></code></strong>. Las demás postulaciones han sido actualizadas a <code>No seleccionado</code>.</div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-3 me-3 text-primary"></i>
        <div>
            <strong>Evaluación Comparativa de Expedientes:</strong> Revisa la tabla comparativa de candidatos y haz clic en el botón <strong>"Seleccionar Ganador"</strong> en la propuesta elegida. Esta acción asignará el estatus <code>Seleccionado</code> y marcará a los competidores como <code>No seleccionado</code>.
        </div>
    </div>
<?php endif; ?>

<!-- TABLA COMPARATIVA DE SOLICITANTES -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 text-dark fw-bold">
            <i class="bi bi-table me-2 text-primary"></i>Cuadro Comparativo de Solicitantes
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Folio / Estado</th>
                    <th>Postulante</th>
                    <th>Tipo Persona / RFC</th>
                    <th>Servicio / Unidades</th>
                    <th>Fecha Registro</th>
                    <th>Monto Derechos</th>
                    <th class="text-end">Acción de Dictamen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-folder-x fs-1 d-block mb-2 text-secondary"></i>
                            No hay participantes registrados en esta convocatoria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $item): 
                        $sol = is_array($item) ? (object)$item['solicitud'] : (is_object($item) && isset($item->solicitud) ? $item->solicitud : $item);
                        $datos = is_array($item) && isset($item['datos']) ? $item['datos'] : [];
                        $esGanador = ($sol->estatus === 'Seleccionado');
                        $esNoSeleccionado = ($sol->estatus === 'No seleccionado');
                    ?>
                    <tr class="<?= $esGanador ? 'table-success border-success' : ($esNoSeleccionado ? 'opacity-75 bg-light' : '') ?>">
                        <td>
                            <div class="fw-bold font-monospace mb-1">
                                <?php if ($esGanador): ?>
                                    <i class="bi bi-trophy-fill text-warning me-1"></i>
                                <?php endif; ?>
                                <code><?= esc($sol->folio) ?></code>
                            </div>
                            <div>
                                <?php if ($esGanador): ?>
                                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Seleccionado</span>
                                <?php elseif ($esNoSeleccionado): ?>
                                    <span class="badge bg-secondary opacity-75"><i class="bi bi-x-circle me-1"></i>No seleccionado</span>
                                <?php else: ?>
                                    <span class="badge bg-primary"><?= esc($sol->estatus) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= esc($datos['solicitante_nombre'] ?? '—') ?></div>
                            <div class="small text-muted"><?= esc($datos['domicilio'] ?? 'Uriangato, Gto.') ?></div>
                        </td>
                        <td>
                            <div class="badge bg-light text-dark border me-1">
                                <?= esc(ucfirst($datos['tipo_persona'] ?? 'fisica')) ?>
                            </div>
                            <div class="small font-monospace text-uppercase text-muted"><?= esc($datos['rfc'] ?? '—') ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-primary"><?= esc($datos['tipo_servicio'] ?? 'Transporte Público') ?></div>
                            <div class="small text-muted"><i class="bi bi-bus-front me-1"></i><?= esc($datos['num_vehiculos'] ?? '1') ?> vehículo(s)</div>
                        </td>
                        <td>
                            <div class="small text-muted"><?= formatear_fecha($sol->fecha_solicitud) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">$ <?= number_format((float)$sol->monto, 2) ?></div>
                        </td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a href="<?= site_url('/admin/solicitudes/' . $sol->folio) ?>" class="btn btn-sm btn-outline-secondary" title="Ver Expediente Completo">
                                    <i class="bi bi-folder2-open me-1"></i>Expediente
                                </a>
                                <?php if (! $esGanador): ?>
                                    <form method="post" action="<?= site_url('admin/convocatorias/' . $convocatoria->id . '/seleccionar') ?>" class="d-inline" onsubmit="return confirm('¿Confirmas seleccionar a la solicitud <?= esc($sol->folio) ?> como GANADORA de la convocatoria? Esto actualizará a los demás participantes a No seleccionado.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="solicitud_id" value="<?= esc($sol->id) ?>">
                                        <button type="submit" class="btn btn-sm btn-success fw-bold shadow-sm ms-1">
                                            <i class="bi bi-trophy me-1"></i>Seleccionar Ganador
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
