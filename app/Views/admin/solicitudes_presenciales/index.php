<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Registrar Solicitud Presencial<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$__view = get_defined_vars();
/** @var array<string, array{nombre:string,icono:string}> $tramites */
$tramites = $__view['tramites'] ?? [];
/** @var object|null $ciudadano Ciudadano ya identificado */
$ciudadano = $__view['ciudadano'] ?? null;
/** @var array<int, object> $ciudadanos Resultados de búsqueda */
$ciudadanos = $__view['ciudadanos'] ?? [];
/** @var string $q */
$q = $__view['q'] ?? '';
/** @var string $curp */
$curp = $__view['curp'] ?? '';
/** @var bool $curpNoEncontrada */
$curpNoEncontrada = (bool) ($__view['curpNoEncontrada'] ?? false);
/** @var object|null $curpInactiva */
$curpInactiva = $__view['curpInactiva'] ?? null;
/** @var string $avisoDuplicado Mensaje del alta detenida por homónimo */
$avisoDuplicado = (string) ($__view['avisoDuplicado'] ?? '');
/** @var object|null $duplicadoNombre Persona con el mismo nombre */
$duplicadoNombre = $__view['duplicadoNombre'] ?? null;
$ciudadanoId = $ciudadano ? (int) $ciudadano->id : 0;
?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Atención presencial',
    'texto'  => 'Registra un trámite a nombre de un ciudadano que acude a ventanilla. 1) Búscalo por CURP o por nombre; si no aparece, dalo de alta con su nombre (la CURP es opcional). 2) Elige el trámite. 3) Captura los datos; los de su cuenta se llenan automáticamente.',
]) ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Nueva Solicitud Presencial</h5>
    <a href="/admin/solicitudes" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a solicitudes
    </a>
</div>

<!-- Paso 1: Identificar al ciudadano -->
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-1-circle me-1 text-primary"></i>Paso 1: Identificar al ciudadano</h6>
    </div>
    <div class="card-body">
        <?php if ($ciudadano): ?>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <i class="bi bi-person-check-fill fs-2 text-success"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold"><?= esc($ciudadano->nombre_completo ?? $ciudadano->username) ?></div>
                    <div class="small text-muted">
                        CURP: <span class="font-monospace"><?= esc($ciudadano->curp ?? '—') ?></span>
                        · Tel: <?= esc($ciudadano->telefono ?? '—') ?>
                        <?php if (!empty($ciudadano->email)): ?> · <?= esc($ciudadano->email) ?><?php endif; ?>
                    </div>
                    <?php if (!empty($ciudadano->domicilio)): ?>
                        <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= esc($ciudadano->domicilio) ?></div>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="/admin/usuarios/editar/<?= $ciudadanoId ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i>Editar datos
                    </a>
                    <a href="/admin/solicitudes/nueva" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-repeat me-1"></i>Cambiar ciudadano
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form method="get" action="/admin/solicitudes/nueva" class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <label for="curp" class="form-label small fw-semibold">CURP del ciudadano</label>
                    <input type="text" name="curp" id="curp" class="form-control text-uppercase font-monospace"
                        maxlength="18" minlength="18" required autocomplete="off" spellcheck="false" autofocus
                        placeholder="Ej. LOGM900101MGTPRR05" value="<?= esc($curp) ?>">
                </div>
                <div class="col-12 col-md-3 d-grid">
                    <button type="submit" class="btn btn-primary" data-no-loading="1">
                        <i class="bi bi-search me-1"></i>Buscar por CURP
                    </button>
                </div>
            </form>

            <?php if ($curpInactiva): ?>
                <div class="alert alert-warning py-2 small mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    La cuenta de <strong><?= esc($curpInactiva->nombre_completo ?? $curpInactiva->username) ?></strong> con esta CURP está <strong>desactivada</strong>.
                    Actívala en <a href="/admin/usuarios/editar/<?= (int) $curpInactiva->id ?>" class="alert-link">Usuarios y ciudadanos</a> para poder registrarle trámites.
                </div>
            <?php endif; ?>

            <?php if ($curpNoEncontrada): ?>
                <div class="border border-primary-subtle rounded-3 p-3 mt-3 bg-primary-subtle bg-opacity-25">
                    <div class="fw-semibold mb-1"><i class="bi bi-person-plus me-1 text-primary"></i>No hay ninguna cuenta con la CURP <span class="font-monospace"><?= esc($curp) ?></span></div>
                    <p class="small text-muted mb-3">Captura sus datos para darlo de alta. Su usuario será la CURP y se generará una contraseña temporal para entregársela.</p>
                    <?= view('admin/solicitudes_presenciales/_alta_ciudadano', [
                        'curp'   => $curp,
                        'sufijo' => 'curp',
                        'boton'  => 'Crear cuenta y continuar',
                    ]) ?>
                </div>
            <?php endif; ?>

            <?php if ($avisoDuplicado !== ''): ?>
                <div class="alert alert-warning mt-3">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i><?= esc($avisoDuplicado) ?></div>
                    <p class="small mb-2">Revisa las coincidencias de abajo: si es la misma persona, selecciónala para conservar su historial y sus trámites. Si es alguien distinto, confirma el alta.</p>
                    <?php if ($duplicadoNombre !== null): ?>
                        <?= view('admin/solicitudes_presenciales/_alta_ciudadano', [
                            'curp'      => '',
                            'sufijo'    => 'duplicado',
                            'confirmar' => true,
                            'boton'     => 'Registrar de todos modos',
                        ]) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <details class="mt-3" <?= ($q !== '' || $ciudadanos) ? 'open' : '' ?>>
                <summary class="small text-primary" style="cursor:pointer;">¿No trae su CURP? Buscar por nombre, correo o RFC</summary>
                <form method="get" action="/admin/solicitudes/nueva" class="row g-2 align-items-end mt-1">
                    <div class="col-12 col-md-6">
                        <input type="text" name="q" class="form-control form-control-sm" autocomplete="off" spellcheck="false"
                            enterkeyhint="search" minlength="2" placeholder="Escribe al menos 2 caracteres..." value="<?= esc($q) ?>">
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button type="submit" class="btn btn-outline-primary btn-sm" data-no-loading="1"><i class="bi bi-search me-1"></i>Buscar</button>
                    </div>
                </form>

                <?php if ($q !== '' && empty($ciudadanos) && $duplicadoNombre === null): ?>
                    <div class="border border-primary-subtle rounded-3 p-3 mt-2 bg-primary-subtle bg-opacity-25">
                        <div class="fw-semibold small mb-1"><i class="bi bi-person-plus me-1 text-primary"></i>Nadie coincide con «<?= esc($q) ?>»</div>
                        <p class="small text-muted mb-3">Dalo de alta con su nombre. La CURP es opcional: con ella se genera una cuenta con contraseña temporal; sin ella, el trámite queda a su nombre y podrá reclamar la cuenta después.</p>
                        <?= view('admin/solicitudes_presenciales/_alta_ciudadano', [
                            'curp'   => '',
                            'sufijo' => 'nombre',
                            'boton'  => 'Registrar y continuar',
                        ]) ?>
                    </div>
                <?php endif; ?>
            </details>

            <?php if (!empty($ciudadanos)): ?>
                <div class="list-group mt-3">
                    <?php foreach ($ciudadanos as $c): ?>
                        <a href="/admin/solicitudes/nueva?ciudadano_id=<?= (int) $c->id ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-2">
                            <div class="overflow-hidden">
                                <div class="fw-semibold small"><?= esc($c->nombre_completo ?? $c->username) ?></div>
                                <div class="small text-muted text-truncate">
                                    <span class="font-monospace"><?= esc($c->curp ?? 'sin CURP') ?></span> · <?= esc($c->email ?? '—') ?>
                                </div>
                            </div>
                            <span class="btn btn-sm btn-outline-primary flex-shrink-0">Seleccionar</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Paso 2: Seleccionar trámite -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-2-circle me-1 text-primary"></i>Paso 2: Seleccionar trámite</h6>
        <?php if (! $ciudadano): ?>
            <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-lock me-1"></i>Primero identifica al ciudadano</span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($tramites as $clave => $info): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column gap-2 tramite-option position-relative <?= $ciudadano ? '' : 'opacity-50' ?>"
                    data-tramite="<?= esc($clave) ?>" style="transition: all 0.15s;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= esc($info['icono']) ?> fs-4 text-primary"></i>
                        <div>
                            <div class="fw-bold small"><?= esc($info['nombre']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;"><?= esc($clave) ?></div>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <?php if ($ciudadano): ?>
                            <a href="/admin/solicitudes/nueva/<?= esc($clave) ?>?ciudadano_id=<?= $ciudadanoId ?>"
                                class="btn btn-sm btn-primary w-100 stretched-link">
                                <i class="bi bi-arrow-right me-1"></i>Continuar
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-secondary w-100" disabled aria-disabled="true">
                                <i class="bi bi-lock me-1"></i>Identifica al ciudadano
                            </button>
                        <?php endif; ?>
                        <a href="/admin/solicitudes?tramite=<?= esc($clave) ?>"
                            class="small text-decoration-none mt-2 d-inline-block position-relative" style="z-index: 2;">
                            <i class="bi bi-list-check me-1"></i>Ver solicitudes de este trámite
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Nota sobre cuentas -->
<div class="card mt-3 border-0 shadow-sm">
    <div class="card-body py-2">
        <p class="small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>
            La CURP identifica al ciudadano y no puede repetirse entre cuentas, pero es opcional: sin ella el trámite queda
            a nombre de la persona y podrá reclamar su cuenta al registrarse en <a href="/auth/register">/auth/register</a>.
            Si ya se registró en línea, aquí aparecerá al buscar su CURP o su nombre.
        </p>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
// Resalte de la ficha de trámite al pasar el cursor (escritorio), solo si está habilitada.
document.querySelectorAll('.tramite-option:not(.opacity-50)').forEach(card => {
    card.addEventListener('mouseenter', () => card.style.borderColor = 'var(--primary-color, #2563eb)');
    card.addEventListener('mouseleave', () => card.style.borderColor = '');
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
