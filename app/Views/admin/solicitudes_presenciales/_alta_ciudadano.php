<?php
/**
 * Formulario de alta rápida del ciudadano en ventanilla (COB-08).
 *
 * Solo el nombre y el apellido son obligatorios. Con CURP se crea una cuenta
 * con contraseña temporal; sin CURP se registra a la persona sin acceso, para
 * que su trámite tenga dueño y pueda reclamar la cuenta después.
 *
 * Lo incluyen tres pantallas del paso 1: la CURP no encontrada, la búsqueda por
 * nombre sin resultados y la confirmación de un homónimo.
 */
$__view = get_defined_vars();
/** @var string $curp CURP ya capturada; vacía cuando el alta es solo con nombre */
$curp = (string) ($__view['curp'] ?? '');
/** @var string $sufijo Prefijo de los id, para no repetirlos si hay dos formularios */
$sufijo = (string) ($__view['sufijo'] ?? 'alta');
/** @var bool $confirmar true cuando ventanilla confirma que es otra persona */
$confirmar = (bool) ($__view['confirmar'] ?? false);
/** @var string $boton Texto del botón de envío */
$boton = (string) ($__view['boton'] ?? 'Registrar y continuar');
?>

<?= form_open('/admin/solicitudes/nueva/registrar-ciudadano') ?>
    <?php if ($confirmar): ?>
        <input type="hidden" name="confirmar_duplicado" value="1">
    <?php endif; ?>
    <div class="row g-2">
        <?php if ($curp !== ''): ?>
            <input type="hidden" name="curp" value="<?= esc($curp, 'attr') ?>">
        <?php else: ?>
            <div class="col-md-4">
                <label for="<?= esc($sufijo, 'attr') ?>_curp" class="form-label small fw-semibold">CURP <span class="fw-normal text-muted">(opcional)</span></label>
                <input type="text" id="<?= esc($sufijo, 'attr') ?>_curp" name="curp" class="form-control form-control-sm text-uppercase font-monospace"
                    maxlength="18" minlength="18" autocomplete="off" spellcheck="false"
                    value="<?= esc(old('curp')) ?>" placeholder="Ej. LOGM900101MGTPRR05">
            </div>
        <?php endif; ?>
        <div class="col-md-4">
            <label for="<?= esc($sufijo, 'attr') ?>_nombre" class="form-label small fw-semibold">Nombre(s) <span class="text-danger">*</span></label>
            <input type="text" id="<?= esc($sufijo, 'attr') ?>_nombre" name="nombre" class="form-control form-control-sm"
                required minlength="2" maxlength="90" value="<?= esc(old('nombre')) ?>" placeholder="Ej. María Fernanda">
        </div>
        <div class="col-md-4">
            <label for="<?= esc($sufijo, 'attr') ?>_apellido" class="form-label small fw-semibold">Apellido(s) <span class="text-danger">*</span></label>
            <input type="text" id="<?= esc($sufijo, 'attr') ?>_apellido" name="apellido" class="form-control form-control-sm"
                required minlength="2" maxlength="120" value="<?= esc(old('apellido')) ?>" placeholder="Ej. López García">
        </div>
        <div class="col-md-4">
            <label for="<?= esc($sufijo, 'attr') ?>_telefono" class="form-label small fw-semibold">Teléfono</label>
            <input type="tel" id="<?= esc($sufijo, 'attr') ?>_telefono" name="telefono" class="form-control form-control-sm"
                minlength="7" maxlength="20" value="<?= esc(old('telefono')) ?>" placeholder="Ej. 445 123 4567">
        </div>
        <div class="col-md-4">
            <label for="<?= esc($sufijo, 'attr') ?>_domicilio" class="form-label small fw-semibold">Domicilio</label>
            <input type="text" id="<?= esc($sufijo, 'attr') ?>_domicilio" name="domicilio" class="form-control form-control-sm"
                maxlength="250" value="<?= esc(old('domicilio')) ?>" placeholder="Calle, número, colonia, CP">
        </div>
        <div class="col-md-2">
            <label for="<?= esc($sufijo, 'attr') ?>_email" class="form-label small fw-semibold">Correo</label>
            <input type="email" id="<?= esc($sufijo, 'attr') ?>_email" name="email" class="form-control form-control-sm"
                maxlength="255" value="<?= esc(old('email')) ?>" placeholder="Opcional">
        </div>
        <div class="col-md-2">
            <label for="<?= esc($sufijo, 'attr') ?>_rfc" class="form-label small fw-semibold">RFC</label>
            <input type="text" id="<?= esc($sufijo, 'attr') ?>_rfc" name="rfc" class="form-control form-control-sm text-uppercase font-monospace"
                minlength="12" maxlength="13" value="<?= esc(old('rfc')) ?>" placeholder="Opcional">
        </div>
    </div>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
        <p class="small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>Con CURP se genera una cuenta con contraseña temporal; sin CURP se registra sin acceso.
        </p>
        <button type="submit" class="btn <?= $confirmar ? 'btn-outline-danger' : 'btn-success' ?> btn-sm" data-loading-text="Registrando...">
            <i class="bi bi-person-plus me-1"></i><?= esc($boton) ?>
        </button>
    </div>
<?= form_close() ?>
