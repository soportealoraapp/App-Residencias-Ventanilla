<?php declare(strict_types=1);

/**
 * Variables inyectadas por el controlador vía view(..., $data).
 * Se leen desde get_defined_vars() para que Intelephense no las marque como
 * "undefined" sin alterar el runtime de CI4.
 */
$__view = get_defined_vars();

/** @var array<string, array{nombre: string, descripcion: string, icono: string, rutas: string[]}> $definiciones */
$definiciones = $__view['definiciones'] ?? [];

/** @var array<string, bool> $permisos */
$permisos = $__view['permisos'] ?? [];
?>
<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Permisos del Operador<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$total = count($definiciones);
$activos = 0;
foreach ($definiciones as $clave => $_d) {
    if (!empty($permisos[$clave])) {
        $activos++;
    }
}
?>

<div class="panel-titulo">
    <div>
        <h2><i class="bi bi-shield-lock" aria-hidden="true"></i> Permisos del Operador</h2>
        <p class="panel-subtitulo">Decide qué secciones del panel puede usar la ventanilla. <?= $activos ?> de <?= $total ?> habilitadas.</p>
    </div>
</div>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Cómo funciona',
    'texto'  => 'Cada interruptor habilita o bloquea una sección completa para el rol <strong>Operador de ventanilla</strong>. Los cambios aplican de inmediato, sin necesidad de que el operador cierre sesión. El <strong>administrador siempre conserva acceso completo</strong>.',
]) ?>

<?= view('layouts/_ayuda', [
    'tono'   => 'alerta',
    'titulo' => 'Lo que no es configurable',
    'texto'  => 'Las <strong>bajas definitivas</strong> (eliminar usuarios, agentes, boletas, catálogos) y la pantalla de <strong>Permisos del Operador</strong> son exclusivas del administrador y nunca se habilitan a la ventanilla. El inicio del panel (dashboard) siempre está disponible.',
]) ?>

<form method="post" action="/admin/permisos-operador/guardar">
    <?= csrf_field() ?>
    <div class="row g-3">
        <?php foreach ($definiciones as $clave => $def): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm permiso-card">
                    <div class="card-body d-flex gap-3 align-items-start p-4">
                        <div class="stat-icon-wrapper bg-primary-subtle text-primary flex-shrink-0">
                            <i class="bi bi-<?= esc($def['icono']) ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <h3 class="h6 mb-1"><?= esc($def['nombre']) ?></h3>
                                <div class="form-check form-switch m-0 flex-shrink-0">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="perm-<?= esc($clave) ?>"
                                           name="permisos[<?= esc($clave) ?>]" value="1"
                                           aria-label="Permitir sección: <?= esc($def['nombre']) ?>"
                                           <?= !empty($permisos[$clave]) ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <p class="text-muted small mb-0"><?= esc($def['descripcion']) ?></p>
                            <label for="perm-<?= esc($clave) ?>" class="hint mt-2 mb-0 d-block">
                                <?= !empty($permisos[$clave]) ? '<i class="bi bi-check2-circle text-success me-1"></i>Permitido' : '<i class="bi bi-dash-circle text-muted me-1"></i>Bloqueado' ?>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center mt-4">
        <button type="submit" class="btn btn-primary shadow-sm" data-loading-text="Guardando...">
            <i class="bi bi-save2 me-2"></i>Guardar permisos
        </button>
        <button type="button" class="btn btn-outline-secondary" id="btnPermisosTodo"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Habilitar todas las secciones para la ventanilla">
            <i class="bi bi-check2-all me-1"></i>Permitir todo
        </button>
        <button type="button" class="btn btn-outline-secondary" id="btnPermisosNinguno"
                data-bs-toggle="tooltip" data-bs-placement="top" title="Bloquear todas las secciones (la ventanilla solo verá el inicio)">
            <i class="bi bi-x-square me-1"></i>Bloquear todo
        </button>
        <span class="hint ms-auto d-none d-md-inline">Se guardan exactamente los interruptores encendidos.</span>
    </div>
</form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function () {
    var switches = Array.prototype.slice.call(document.querySelectorAll('input[type="checkbox"][role="switch"][id^="perm-"]'));

    function marcar(estado) {
        switches.forEach(function (s) { s.checked = estado; });
        actualizarEtiquetas();
    }

    function actualizarEtiquetas() {
        switches.forEach(function (s) {
            var label = document.querySelector('label[for="' + s.id + '"]');
            if (!label) return;
            label.innerHTML = s.checked
                ? '<i class="bi bi-check2-circle text-success me-1"></i>Permitido'
                : '<i class="bi bi-dash-circle text-muted me-1"></i>Bloqueado';
        });
    }

    switches.forEach(function (s) { s.addEventListener('change', actualizarEtiquetas); });
    document.getElementById('btnPermisosTodo').addEventListener('click', function () { marcar(true); });
    document.getElementById('btnPermisosNinguno').addEventListener('click', function () { marcar(false); });
})();
</script>
<?= $this->endSection() ?>
