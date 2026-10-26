<?php declare(strict_types=1);

/**
 * Ventanas de Cobrar, Mandar a completar y Cancelar (COB-02).
 *
 * Se incluyen una sola vez por página. Cada botón con data-cobro-accion abre
 * la ventana correspondiente y le pasa el cobro (id, folio, pagador, total).
 */
$__view = get_defined_vars();
/** @var string $base */
$base = $__view['base'] ?? '/admin/cobros';
/** @var array<string, string> $lugares */
$lugares = $__view['lugares'] ?? [];
/** @var string $lugarPorDefecto */
$lugarPorDefecto = $__view['lugarPorDefecto'] ?? 'ventanilla';
// Ruta relativa con sus filtros, para regresar al mismo listado después de la acción.
$volver = '/' . uri_string() . (($q = (string) service('request')->getUri()->getQuery()) !== '' ? '?' . $q : '');
?>
<div class="modal fade" id="modalCobrar" tabindex="-1" aria-labelledby="modalCobrarTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" data-base="<?= esc($base, 'attr') ?>" data-accion="cobrar">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="<?= esc($volver, 'attr') ?>">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalCobrarTitulo"><i class="bi bi-cash-coin me-2 text-success"></i>Registrar cobro</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><span data-campo="pagador" class="fw-semibold"></span><br><small class="text-muted" data-campo="concepto"></small></p>
                <p class="mb-3 small text-muted" data-campo="nota-multa" hidden>En multas, el descuento por pronto pago o el recargo se recalculan con la fecha de hoy; el total puede cambiar.</p>
                <div class="mb-3">
                    <label for="cobroLugar" class="form-label fw-semibold">¿Dónde se pagó?</label>
                    <select id="cobroLugar" name="lugar_pago" class="form-select" required>
                        <?php foreach ($lugares as $clave => $nombre): ?>
                            <option value="<?= esc($clave, 'attr') ?>" <?= $clave === $lugarPorDefecto ? 'selected' : '' ?>><?= esc($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-1">
                    <label for="cobroReferencia" class="form-label fw-semibold">Referencia del pago <span class="text-muted fw-normal" id="cobroReferenciaOpcional">(opcional)</span></label>
                    <input id="cobroReferencia" name="referencia_pago" class="form-control" maxlength="100" autocomplete="off" placeholder="Ej. folio del comprobante de BanBajío">
                </div>
            </div>
            <div class="modal-footer">
                <span class="me-auto">Total: <strong class="fs-5" data-campo="total"></strong></span>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Registrar cobro</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalMotivo" tabindex="-1" aria-labelledby="modalMotivoTitulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" data-base="<?= esc($base, 'attr') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="volver" value="<?= esc($volver, 'attr') ?>">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalMotivoTitulo"></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><span data-campo="pagador" class="fw-semibold"></span><br><small class="text-muted" data-campo="concepto"></small></p>
                <label for="cobroMotivo" class="form-label fw-semibold" data-campo="motivo-etiqueta"></label>
                <textarea id="cobroMotivo" name="motivo" class="form-control" rows="3" required maxlength="1000"></textarea>
                <div class="form-text" data-campo="motivo-ayuda"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Volver</button>
                <button type="submit" class="btn" data-campo="motivo-boton"></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('click', function (evento) {
    var boton = evento.target.closest('[data-cobro-accion]');
    if (!boton) { return; }
    var accion = boton.getAttribute('data-cobro-accion');
    var modal = document.getElementById(accion === 'cobrar' ? 'modalCobrar' : 'modalMotivo');
    var form = modal.querySelector('form');
    form.action = form.getAttribute('data-base') + '/' + boton.getAttribute('data-id') + '/' + accion;
    modal.querySelectorAll('[data-campo="pagador"]').forEach(function (el) { el.textContent = boton.getAttribute('data-pagador'); });
    modal.querySelectorAll('[data-campo="concepto"]').forEach(function (el) { el.textContent = boton.getAttribute('data-folio') + ' · ' + boton.getAttribute('data-concepto'); });

    if (accion === 'cobrar') {
        modal.querySelector('[data-campo="total"]').textContent = boton.getAttribute('data-total');
        modal.querySelector('[data-campo="nota-multa"]').hidden = boton.getAttribute('data-origen') !== 'boleta';
        form.querySelector('[name="referencia_pago"]').value = '';
    } else {
        var cancelar = accion === 'cancelar';
        modal.querySelector('#modalMotivoTitulo').textContent = cancelar ? 'Cancelar cobro' : 'Mandar a completar';
        modal.querySelector('[data-campo="motivo-etiqueta"]').textContent = cancelar ? 'Motivo de la cancelación' : '¿Qué falta?';
        modal.querySelector('[data-campo="motivo-ayuda"]').textContent = cancelar
            ? 'El cobro queda cancelado y ya no se puede cobrar. El motivo queda en el historial.'
            : 'El cobro sigue pendiente y se marca como incompleto. Ejemplo: falta la identificación oficial.';
        var enviar = modal.querySelector('[data-campo="motivo-boton"]');
        enviar.textContent = cancelar ? 'Cancelar cobro' : 'Mandar a completar';
        enviar.className = 'btn ' + (cancelar ? 'btn-danger' : 'btn-warning');
        form.querySelector('[name="motivo"]').value = '';
    }
    bootstrap.Modal.getOrCreateInstance(modal).show();
});

document.addEventListener('change', function (evento) {
    if (evento.target.id !== 'cobroLugar') { return; }
    var banco = evento.target.value === 'banbajio';
    var referencia = document.getElementById('cobroReferencia');
    referencia.required = banco;
    document.getElementById('cobroReferenciaOpcional').textContent = banco ? '(obligatoria para BanBajío)' : '(opcional)';
});
</script>
