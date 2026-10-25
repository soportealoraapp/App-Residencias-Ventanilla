<?= $this->extend('layouts/juez') ?>
<?= $this->section('pageTitle') ?>Calificar boleta <?= esc($boleta->folio) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>

<a href="/juez/boletas" class="btn btn-link px-0 mb-3"><i class="bi bi-arrow-left"></i> Volver a pendientes</a>
<?php foreach ((array) ($errors ?? []) as $error): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endforeach; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Datos de la boleta</h2>
            <dl class="row mb-0 small">
                <dt class="col-sm-4">Folio</dt><dd class="col-sm-8 font-monospace"><?= esc($boleta->folio) ?></dd>
                <dt class="col-sm-4">Fecha y lugar</dt><dd class="col-sm-8"><?= esc($boleta->fecha_infraccion) ?> · <?= esc($boleta->lugar) ?></dd>
                <dt class="col-sm-4">Agente</dt><dd class="col-sm-8"><?= esc($boleta->agente_nombre ?: $boleta->agente_placa) ?> (<?= esc($boleta->agente_placa) ?>)</dd>
                <dt class="col-sm-4">Vehículo</dt><dd class="col-sm-8"><?= esc(trim(($boleta->vehiculo_marca ?? '') . ' ' . ($boleta->vehiculo_linea ?? ''))) ?> · <?= esc($boleta->vehiculo_placas ?: 'Sin placas') ?></dd>
                <dt class="col-sm-4">Hechos</dt><dd class="col-sm-8"><?= esc($boleta->hechos ?: 'Sin descripción adicional') ?></dd>
            </dl>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body">
            <h2 class="h5">Evidencias</h2>
            <div class="row g-2">
                <?php foreach (['foto_placa_url' => 'Placa', 'foto_contexto_url' => 'Contexto', 'foto_documento_url' => 'Documento'] as $campo => $etiqueta): ?>
                    <?php if (! empty($boleta->{$campo})): ?><div class="col-4"><a href="<?= esc($boleta->{$campo}) ?>" target="_blank" rel="noopener"><img src="<?= esc($boleta->{$campo}) ?>" alt="<?= esc($etiqueta) ?>" class="img-fluid rounded border" style="height:100px;width:100%;object-fit:cover"></a><small class="d-block text-center text-muted"><?= esc($etiqueta) ?></small></div><?php endif; ?>
                <?php endforeach; ?>
                <?php if (empty($boleta->foto_placa_url) && empty($boleta->foto_contexto_url) && empty($boleta->foto_documento_url)): ?><p class="text-muted small mb-0">Sin evidencias disponibles.</p><?php endif; ?>
            </div>
        </div></div>
    </div>
</div>

<form method="post" action="/juez/boletas/<?= (int) $boleta->id ?>/calificar">
    <?= csrf_field() ?>
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <label for="nombre_infractor" class="form-label fw-semibold">Nombre completo del infractor</label>
        <input id="nombre_infractor" name="nombre_infractor" class="form-control" maxlength="180" required value="<?= esc($nombre ?? '') ?>">
        <div class="form-text">Debe confirmarse un nombre real antes de generar el cobro.</div>
    </div></div>

    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <h2 class="h5">UMA por falta</h2>
        <p class="text-muted small">Los rangos provienen de la copia histórica almacenada en esta boleta.</p>
        <?php foreach ($faltas as $indice => $falta): ?>
            <?php
                $fundamento = $falta['fundamentoLegal'] ?? $falta['fundamento_legal'] ?? '';
                $descripcion = $falta['descripcion'] ?? $falta['descripcion_oficial'] ?? '';
                $minimo = $falta['montoMinUma'] ?? $falta['monto_min_uma'] ?? '';
                $maximo = $falta['montoMaxUma'] ?? $falta['monto_max_uma'] ?? '';
                $seleccion = $selecciones[$indice] ?? $minimo;
            ?>
            <div class="border rounded p-3 mb-3">
                <div class="fw-semibold text-danger"><?= esc($fundamento) ?></div>
                <div class="small text-muted mb-2"><?= esc($descripcion) ?></div>
                <label for="uma_<?= $indice ?>" class="form-label">UMA asignada (rango <?= number_format((float) $minimo, 2) ?>–<?= number_format((float) $maximo, 2) ?>)</label>
                <input id="uma_<?= $indice ?>" type="number" inputmode="decimal" step="0.01" min="<?= esc((string) $minimo) ?>" max="<?= esc((string) $maximo) ?>" name="uma[<?= $indice ?>]" value="<?= esc((string) $seleccion) ?>" class="form-control" required>
            </div>
        <?php endforeach; ?>
    </div></div>

    <?php if ($previa !== null): ?>
        <div class="card border-primary shadow-sm mb-3"><div class="card-body">
            <h2 class="h5">Vista previa</h2>
            <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Falta</th><th>UMA</th><th>Subtotal</th></tr></thead><tbody>
                <?php foreach ($previa['detalles'] as $detalle): ?><tr><td><?= esc($detalle['fundamento_legal']) ?></td><td><?= number_format((float) $detalle['uma_asignada'], 2) ?></td><td>$<?= number_format((float) $detalle['subtotal_pesos'], 2) ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
            <dl class="row mb-2"><dt class="col-7">Valor UMA utilizado</dt><dd class="col-5 text-end">$<?= number_format((float) $previa['valor_uma'], 2) ?></dd><dt class="col-7">Monto base</dt><dd class="col-5 text-end fw-bold">$<?= number_format((float) $previa['monto_base'], 2) ?></dd><dt class="col-7">Descuento estimado</dt><dd class="col-5 text-end">-$<?= number_format((float) $previa['estimacion']['monto_descuento'], 2) ?></dd><dt class="col-7">Recargo estimado</dt><dd class="col-5 text-end">$<?= number_format((float) $previa['estimacion']['monto_recargo'], 2) ?></dd><dt class="col-7">Total estimado hoy</dt><dd class="col-5 text-end fw-bold">$<?= number_format((float) $previa['estimacion']['monto_final'], 2) ?></dd></dl>
            <div class="alert alert-info mb-0 small">El monto base queda fijado al calificar. El descuento o recargo y el total aplicable se recalculan al registrar el pago.</div>
        </div></div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 justify-content-end mb-4">
        <button type="submit" formaction="/juez/boletas/<?= (int) $boleta->id ?>/vista-previa" class="btn btn-outline-primary">Actualizar vista previa</button>
        <button type="submit" class="btn btn-primary" onclick="return confirm('¿Confirmar esta calificación y generar el cobro pendiente?')">Confirmar calificación</button>
    </div>
</form>

<?= $this->endSection() ?>
