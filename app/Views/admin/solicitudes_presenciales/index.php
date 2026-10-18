<?= $this->extend('layouts/admin') ?>
<?= $this->section('pageTitle') ?>Registrar Solicitud Presencial<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
$__view = get_defined_vars();
/** @var array<string, array{nombre:string,icono:string}> $tramites */
$tramites = $__view['tramites'] ?? [];
/** @var array<int, object> $ciudadanos */
$ciudadanos = $__view['ciudadanos'] ?? [];
/** @var string $q */
$q = $__view['q'] ?? '';
?>

<?= view('layouts/_ayuda', [
    'tono'   => 'info',
    'titulo' => 'Atención presencial',
    'texto'  => 'Registra una solicitud de trámite en nombre de un ciudadano que acude físicamente a la ventanilla. Busca al ciudadano (o registra sin cuenta vinculada), selecciona el trámite y captura los datos. Los documentos pueden adjuntarse en el momento o entregarse después.',
]) ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Nueva Solicitud Presencial</h5>
    <a href="/admin/solicitudes" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Volver a solicitudes
    </a>
</div>

<!-- Paso 1: Buscar ciudadano -->
<div class="card mb-3 border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-person-search me-1 text-primary"></i>Paso 1: Buscar ciudadano</h6>
    </div>
    <div class="card-body">
        <form method="get" action="/admin/solicitudes/nueva" class="row g-2 align-items-end">
            <div class="col-12 col-md-8">
                <label for="q" class="form-label small fw-semibold">Buscar por nombre, username, email, RFC o CURP</label>
                <input type="text" name="q" id="q" class="form-control form-control-sm"
                    placeholder="Escribe al menos 2 caracteres..." value="<?= esc($q) ?>">
            </div>
            <div class="col-12 col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-search me-1"></i>Buscar
                </button>
            </div>
        </form>

        <?php if ($q !== ''): ?>
            <hr class="my-3">
            <?php if (empty($ciudadanos)): ?>
                <div class="alert alert-warning py-2 small mb-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>No se encontraron ciudadanos con "<strong><?= esc($q) ?></strong>".
                    Puedes continuar sin vincular una cuenta.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>RFC</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ciudadanos as $c): ?>
                            <tr>
                                <td class="fw-semibold"><?= esc($c->nombre_completo ?? $c->username) ?></td>
                                <td class="small text-muted"><?= esc($c->email ?? '—') ?></td>
                                <td class="small font-monospace"><?= esc($c->rfc ?? '—') ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-seleccionar-ciudadano"
                                        data-id="<?= (int)$c->id ?>"
                                        data-nombre="<?= esc($c->nombre_completo ?? $c->username) ?>">
                                        Seleccionar
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Ciudadano seleccionado -->
<div class="card mb-3 border-0 shadow-sm <?= ($q === '') ? 'd-none' : '' ?>" id="cardCiudadano">
    <div class="card-body d-flex align-items-center gap-3 py-2">
        <div class="flex-grow-1">
            <span class="small fw-semibold text-muted">Ciudadano seleccionado:</span>
            <strong id="nombreCiudadano"><?= $ciudadanos ? esc($ciudadanos[0]->nombre_completo ?? $ciudadanos[0]->username) : 'Ninguno' ?></strong>
            <input type="hidden" id="ciudadanoId" value="">
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCambiarCiudadano">
            <i class="bi bi-arrow-repeat me-1"></i>Cambiar
        </button>
    </div>
</div>

<!-- Paso 2: Seleccionar trámite -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-2">
        <h6 class="mb-0 small fw-bold"><i class="bi bi-1-circle me-1 text-primary"></i>Paso 2: Seleccionar trámite</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($tramites as $clave => $info): ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="border rounded-3 p-3 h-100 d-flex flex-column gap-2 tramite-option"
                    data-tramite="<?= esc($clave) ?>" style="cursor:pointer; transition: all 0.15s;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi <?= esc($info['icono']) ?> fs-4 text-primary"></i>
                        <div>
                            <div class="fw-bold small"><?= esc($info['nombre']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;"><?= esc($clave) ?></div>
                        </div>
                    </div>
                    <div class="mt-auto">
                        <a href="/admin/solicitudes/nueva/<?= esc($clave) ?>"
                            class="btn btn-sm btn-primary w-100 btn-tramite">
                            <i class="bi bi-arrow-right me-1"></i>Continuar
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
            El ciudadano debe tener una cuenta en el portal. Si no la tiene, puede autorregistrarse en <a href="/auth/register">/auth/register</a>,
            o el operador puede crearla desde <a href="/admin/usuarios/nuevo">Usuarios y ciudadanos → Nuevo</a>.
        </p>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectedCiudadanoId = document.getElementById('ciudadanoId');
    const selectedNombre = document.getElementById('nombreCiudadano');
    const cardCiudadano = document.getElementById('cardCiudadano');

    // Select citizen
    document.querySelectorAll('.btn-seleccionar-ciudadano').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            selectedCiudadanoId.value = id;
            selectedNombre.textContent = nombre;
            cardCiudadano.classList.remove('d-none');
            cardCiudadano.scrollIntoView({behavior: 'smooth', block: 'center'});
            // Update all tramite links
            document.querySelectorAll('.btn-tramite').forEach(a => {
                const url = new URL(a.href);
                url.searchParams.set('ciudadano_id', id);
                a.href = url.toString();
            });
        });
    });

    // Cambiar ciudadano
    document.getElementById('btnCambiarCiudadano')?.addEventListener('click', function() {
        selectedCiudadanoId.value = '';
        cardCiudadano.classList.add('d-none');
        document.querySelectorAll('.btn-tramite').forEach(a => {
            const url = new URL(a.href);
            url.searchParams.delete('ciudadano_id');
            a.href = url.toString();
        });
        document.getElementById('q').focus();
    });

    // Hover effect on tramite cards
    document.querySelectorAll('.tramite-option').forEach(card => {
        card.addEventListener('mouseenter', () => card.style.borderColor = 'var(--primary-color, #2563eb)');
        card.addEventListener('mouseleave', () => card.style.borderColor = '');
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
