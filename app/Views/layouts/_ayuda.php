<?php
/**
 * Cuadro de ayuda reutilizable (bajo ruido visual).
 *
 * Uso: <?= view('layouts/_ayuda', ['tono' => 'info', 'titulo' => '...', 'texto' => '...']) ?>
 *
 * @var string $tono   info | ok | alerta | peligro  (por defecto: info)
 * @var string $titulo Título en negrita (opcional)
 * @var string $texto  Párrafo de ayuda (opcional)
 * @var string $icono  Clase bootstrap-icon (opcional, se deduce del tono)
 */
$tono = $tono ?? 'info';
$titulo = $titulo ?? '';
$texto = $texto ?? '';

$iconosPorTono = [
    'info'    => 'bi-info-circle',
    'ok'      => 'bi-check-circle',
    'alerta'  => 'bi-exclamation-triangle',
    'peligro' => 'bi-shield-exclamation',
];
$icono = $icono ?? ($iconosPorTono[$tono] ?? $iconosPorTono['info']);
?>
<div class="ayuda ayuda-<?= esc($tono) ?> mb-3" role="note">
    <i class="bi <?= esc($icono) ?> ayuda-icono" aria-hidden="true"></i>
    <div class="ayuda-cuerpo">
        <?php if ($titulo !== ''): ?><span class="ayuda-titulo"><?= esc($titulo) ?></span><?php endif; ?>
        <?php if ($texto !== ''): ?><p class="mb-0"><?= $texto /* se permite HTML simple de ayuda */ ?></p><?php endif; ?>
    </div>
</div>
