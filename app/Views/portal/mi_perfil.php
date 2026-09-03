<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Mi Perfil - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3 mb-md-4 align-items-center">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mi Perfil</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1"><i class="bi bi-person-gear text-primary me-2"></i>Mi Perfil</h1>
        <p class="text-muted small mb-0">Edita tu información personal y documento de identificación.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">

        <?= form_open_multipart('/portal/mi-perfil') ?>

            <h6 class="text-uppercase text-muted fw-bold small mb-3"><i class="bi bi-person-vcard me-1"></i> Datos personales</h6>

            <div class="mb-3">
                <label for="curp" class="form-label small fw-semibold">CURP</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person-vcard"></i></span>
                    <input type="text" class="form-control bg-light" id="curp" name="curp" value="<?= esc($usuario->curp ?? '') ?>" readonly>
                </div>
                <div class="form-text">La CURP no se puede editar.</div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label for="nombre" class="form-label small fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= esc(explode(' ', $usuario->nombre_completo ?? '')[0] ?? '') ?>" required>
                </div>
                <div class="col-sm-6">
                    <label for="apellido" class="form-label small fw-semibold">Apellido(s) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?= esc($usuario->apellido ?? '') ?>" required>
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label for="rfc" class="form-label small fw-semibold">RFC <span class="text-muted">(opcional)</span></label>
                    <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" value="<?= esc($usuario->rfc ?? '') ?>" maxlength="13">
                </div>
                <div class="col-sm-6">
                    <label for="email" class="form-label small fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" value="<?= esc($usuario->email ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="telefono" class="form-label small fw-semibold">Teléfono <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?= esc($usuario->telefono ?? '') ?>" required>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="text-uppercase text-muted fw-bold small mb-3"><i class="bi bi-card-image me-1"></i> Identificación Oficial (INE)</h6>

            <div class="card border-warning mb-3">
                <div class="card-body p-3">
                    <?php 
                    $storage = new \App\Libraries\SupabaseStorage();
                    $ineFrenteUrl = !empty($usuario->ine_frente) ? $storage->urlPublica('ine', $usuario->ine_frente) : null;
                    $ineReversoUrl = !empty($usuario->ine_reverso) ? $storage->urlPublica('ine', $usuario->ine_reverso) : null;
                    $tieneINE = $ineFrenteUrl || $ineReversoUrl;
                    ?>
                    
                    <?php if ($tieneINE): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        Tienes INE cargada. Puedes reemplazarla seleccionando archivos nuevos.
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning py-2 small mb-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        No has cargado tu INE. Agrégala para completar tu perfil.
                    </div>
                    <?php endif; ?>
                    
                    <p class="text-muted small mb-3">Sube una foto clara de tu credencial por ambos lados. Formatos aceptados: JPG o PNG. Tamaño máximo: 5 MB por imagen.</p>
                    
                    <?php if ($tieneINE): ?>
                    <div class="row g-2 mb-3">
                        <?php if ($ineFrenteUrl): ?>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-muted">INE Frente actual</label>
                            <div class="border rounded p-2 text-center bg-light">
                                <img src="<?= esc($ineFrenteUrl) ?>" alt="INE Frente" class="img-fluid" style="max-height: 180px; object-fit: contain;">
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($ineReversoUrl): ?>
                        <div class="col-sm-6">
                            <label class="form-label small fw-semibold text-muted">INE Reverso actual</label>
                            <div class="border rounded p-2 text-center bg-light">
                                <img src="<?= esc($ineReversoUrl) ?>" alt="INE Reverso" class="img-fluid" style="max-height: 180px; object-fit: contain;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label for="ine_frente" class="form-label small fw-semibold">Frente <?= $tieneINE ? '(reemplazar)' : '' ?></label>
                            <input type="file" class="form-control" id="ine_frente" name="ine_frente" accept="image/jpeg,image/png">
                            <div class="form-text"><?= $tieneINE ? 'Deja vacío para mantener el actual' : 'Foto frontal con nombre visible' ?></div>
                        </div>
                        <div class="col-sm-6">
                            <label for="ine_reverso" class="form-label small fw-semibold">Reverso <?= $tieneINE ? '(reemplazar)' : '' ?></label>
                            <input type="file" class="form-control" id="ine_reverso" name="ine_reverso" accept="image/jpeg,image/png">
                            <div class="form-text"><?= $tieneINE ? 'Deja vacío para mantener el actual' : 'Foto trasera con código visible' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="text-uppercase text-muted fw-bold small mb-3"><i class="bi bi-geo-alt me-1"></i> Ubicación</h6>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label for="estado" class="form-label small fw-semibold">Estado <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="estado" name="estado" value="<?= esc($usuario->estado ?? 'Guanajuato') ?>" required>
                </div>
                <div class="col-sm-6">
                    <label for="ciudad" class="form-label small fw-semibold">Ciudad / Municipio <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?= esc($usuario->ciudad ?? 'Uriangato') ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="domicilio" class="form-label small fw-semibold">Dirección <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted"><i class="bi bi-geo-alt"></i></span>
                    <input type="text" class="form-control" id="domicilio" name="domicilio" value="<?= esc($usuario->domicilio ?? '') ?>" required>
                </div>
            </div>

            <hr class="my-4">

            <h6 class="text-uppercase text-muted fw-bold small mb-3"><i class="bi bi-shield-lock me-1"></i> Seguridad</h6>

            <div class="row g-2 mb-3">
                <div class="col-sm-6">
                    <label for="username_display" class="form-label small fw-semibold">Usuario</label>
                    <input type="text" class="form-control bg-light" id="username_display" value="<?= esc($usuario->username ?? '') ?>" readonly>
                    <div class="form-text">El nombre de usuario no se puede editar.</div>
                </div>
                <div class="col-sm-6 d-flex align-items-end">
                    <a href="/auth/forgot" class="btn btn-outline-warning w-100">
                        <i class="bi bi-key me-1"></i>Cambiar contraseña
                    </a>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                    <i class="bi bi-check-lg me-2"></i>Guardar cambios
                </button>
            </div>
        <?= form_close() ?>

    </div>
</div>

<?= $this->endSection() ?>
