<?php
$estatus = $estatus ?? '';
$clases = [
    'Recibido' => 'bg-secondary',
    'Pago pendiente' => 'bg-warning text-dark',
    'Pagado' => 'bg-primary',
    'Permiso emitido' => 'bg-info text-dark',
    'Vigente' => 'bg-success',
    'Vencido' => 'bg-secondary opacity-75',
    'Rechazado' => 'bg-danger',
    'En revisión documental' => 'bg-primary',
    'Prevención' => 'bg-warning text-dark',
    'Dictaminado aprobado' => 'bg-info text-dark',
    'Concluido' => 'bg-success',
];
$clase = $clases[$estatus] ?? 'bg-secondary';
?>
<span class="badge badge-estatus <?= $clase ?>"><?= esc($estatus) ?></span>
