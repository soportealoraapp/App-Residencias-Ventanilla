<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Formatos de Trámite<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-0">Administra los formatos oficiales que los ciudadanos pueden descargar antes de iniciar un trámite.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Trámite</th>
                        <th>Formato actual</th>
                        <th>Subido</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tramitesInfo as $codigo => $nombre): ?>
                    <?php $formato = $mapa[$codigo] ?? null; ?>
                    <tr>
                        <td><code class="fw-bold"><?= esc($codigo) ?></code></td>
                        <td class="fw-semibold"><?= esc($nombre) ?></td>
                        <td>
                            <?php if ($formato): ?>
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-file-earmark-check me-1"></i><?= esc($formato->nombre_archivo) ?>
                                </span>
                                <div class="small text-muted mt-1">
                                    <?= round(($formato->tamano_bytes ?? 0) / 1024) ?> KB · <?= esc($formato->mime_type) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-dash-circle me-1"></i>Sin formato</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted">
                            <?php if ($formato): ?>
                                <?= date('d/m/Y H:i', strtotime($formato->created_at)) ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                    data-bs-toggle="modal" data-bs-target="#modalSubir"
                                    data-tramite="<?= esc($codigo) ?>"
                                    data-nombre="<?= esc($nombre) ?>"
                                    data-existe="<?= $formato ? '1' : '0' ?>">
                                <i class="bi bi-upload me-1"></i><?= $formato ? 'Reemplazar' : 'Subir' ?>
                            </button>
                            <?php if ($formato): ?>
                                <form method="POST" action="/admin/formatos/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar el formato de <?= esc($codigo) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="tramite" value="<?= esc($codigo) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Subir Formato -->
<div class="modal fade" id="modalSubir" tabindex="-1" aria-labelledby="modalSubirLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/formatos/subir" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalSubirLabel">
                        <i class="bi bi-upload me-2"></i>Subir Formato
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tramite" id="modalTramite">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Trámite</label>
                        <div class="form-control bg-light" id="modalTramiteNombre" readonly></div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold">Nombre del formato</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: Formato de Solicitud UR-TT-T-02">
                        <div class="form-text">Opcional. Si se deja vacío, se usará el nombre por defecto.</div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2" placeholder="Describe brevemente el contenido del formato..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="formato" class="form-label fw-semibold">Archivo <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="formato" name="formato" accept="application/pdf,image/jpeg,image/png" required>
                        <div class="form-text">PDF, JPG o PNG. Máximo 10 MB.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-cloud-upload me-1"></i>Subir formato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
document.getElementById('modalSubir').addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var tramite = button.getAttribute('data-tramite');
    var nombre = button.getAttribute('data-nombre');

    document.getElementById('modalTramite').value = tramite;
    document.getElementById('modalTramiteNombre').textContent = tramite + ' — ' + nombre;
    document.getElementById('nombre').value = 'Formato de ' + nombre;
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
