<?php declare(strict_types=1);

/** Etiqueta del estatus de un cobro. Recibe $cobro. */
$__view = get_defined_vars();
$cobro = $__view['cobro'] ?? null;

if ($cobro === null) {
    return;
}
if ($cobro->estatus === 'pendiente' && (int) $cobro->pendiente_documentos === 1): ?>
    <span class="badge text-bg-danger"><i class="bi bi-exclamation-circle me-1"></i>Por completar</span>
<?php elseif ($cobro->estatus === 'pendiente'): ?>
    <span class="badge text-bg-warning"><i class="bi bi-hourglass-split me-1"></i>Pendiente</span>
<?php elseif ($cobro->estatus === 'aprobado'): ?>
    <span class="badge text-bg-success"><i class="bi bi-check2-circle me-1"></i>Aprobado</span>
<?php else: ?>
    <span class="badge text-bg-secondary"><i class="bi bi-x-circle me-1"></i>Cancelado</span>
<?php endif;
