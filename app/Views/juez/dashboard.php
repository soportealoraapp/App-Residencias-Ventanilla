<?= $this->extend('layouts/juez') ?>
<?= $this->section('pageTitle') ?>Inicio del juez calificador<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="mb-4">
    <p class="text-muted mb-0">Revisa las boletas recibidas, fija las UMA de cada falta y genera su cobro pendiente.</p>
</div>

<?php if (session('message')): ?>
    <div class="alert alert-success"><?= esc(session('message')) ?></div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-receipt-cutoff fs-2 text-primary"></i>
                <h2 class="h5 mt-3">Boletas por calificar</h2>
                <div class="display-6 fw-bold mb-2"><?= (int) ($pendientes ?? 0) ?></div>
                <p class="text-muted small">Solo incluye boletas con estado recibida.</p>
                <a href="/juez/boletas" class="btn btn-primary">Abrir listado</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <i class="bi bi-cash-coin fs-2 text-secondary"></i>
                <h2 class="h5 mt-3">Cobros pendientes</h2>
                <?php if (empty($cobrosPendientes)): ?>
                    <p class="text-muted small mb-0">No hay cobros de multa pendientes de pago. Al confirmar una calificación se genera uno.</p>
                <?php else: ?>
                    <p class="text-muted small">Últimos cobros de multa pendientes de pago. Imprime la hoja de ayuda para entregarla al infractor.</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Boleta</th>
                                    <th scope="col">Pagador</th>
                                    <th scope="col" class="text-end">Total</th>
                                    <th scope="col"><span class="visually-hidden">Documento</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cobrosPendientes as $cobro): ?>
                                    <tr>
                                        <td class="font-monospace small"><?= esc((string) ($cobro->boleta_folio ?? '—')) ?></td>
                                        <td class="small"><?= esc((string) ($cobro->nombre_pagador ?? '')) ?></td>
                                        <td class="text-end small"><?= formatear_dinero((float) $cobro->total) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary text-nowrap" target="_blank" rel="noopener"
                                               href="<?= site_url('documentos/hoja-pago/boleta/' . (int) $cobro->boleta_id) ?>">
                                                <i class="bi bi-printer me-1"></i>Hoja de pago
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
