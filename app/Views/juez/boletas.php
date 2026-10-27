<?= $this->extend('layouts/juez') ?>
<?= $this->section('pageTitle') ?>Boletas por calificar<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php if (session('message')): ?><div class="alert alert-success"><?= esc(session('message')) ?></div><?php endif; ?>
<?php foreach ((array) session('errors') as $error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endforeach; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr><th class="ps-4">Folio y fecha</th><th>Agente</th><th>Vehículo</th><th>Infractor</th><th>Faltas</th><th class="text-end pe-4">Acción</th></tr></thead>
                <tbody>
                <?php if (empty($boletas)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-5">No hay boletas recibidas pendientes de calificación.</td></tr>
                <?php endif; ?>
                <?php foreach ($boletas as $boleta): ?>
                    <tr>
                        <td class="ps-4"><div class="boleta-folio fw-bold font-monospace"><?= esc($boleta->folio) ?></div><small class="text-muted"><?= esc($boleta->fecha_infraccion) ?> <?= esc(substr((string) $boleta->hora_infraccion, 0, 5)) ?></small></td>
                        <td><div><?= esc($boleta->agente_nombre ?: $boleta->agente_placa) ?></div><small class="text-muted"><?= esc($boleta->agente_placa) ?></small></td>
                        <td><div><?= esc(trim(($boleta->vehiculo_marca ?? '') . ' ' . ($boleta->vehiculo_linea ?? ''))) ?></div><small class="font-monospace"><?= esc($boleta->vehiculo_placas ?: 'Sin placas') ?></small></td>
                        <td><?= esc($boleta->infractor_nombre ?: 'Nombre pendiente') ?></td>
                        <td>
                            <?php if ($boleta->faltas_validas): ?><span class="badge text-bg-light border"><?= (int) $boleta->numero_faltas ?></span>
                            <?php else: ?><span class="badge text-bg-danger">Datos dañados</span><?php endif; ?>
                        </td>
                        <td class="text-end pe-4"><a href="/juez/boletas/<?= (int) $boleta->id ?>" class="btn btn-sm btn-outline-primary"><?= $boleta->faltas_validas ? 'Abrir' : 'Revisar' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
