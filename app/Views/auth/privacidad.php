<?php declare(strict_types=1); ?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('title') ?>Aviso de Privacidad - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="auth-header">
    <div class="auth-brand-icon bg-warning" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
        <i class="bi bi-lock"></i>
    </div>
    <h2 class="h4 mb-1 fw-bold">Aviso de Privacidad Simplificado</h2>
    <p class="text-muted small mb-0">Ventanilla Digital · Uriangato, Gto.</p>
</div>

<div class="auth-body">

    <div class="small" style="text-align: justify; line-height: 1.7;">

        <p class="fw-semibold text-dark">Para el uso de la página web y trámites digitales</p>

        <p>El Departamento de Tecnologías de la Información y Telecomunicaciones, perteneciente a la Dirección de Tecnologías de la Información y Telecomunicaciones, con domicilio en Av. José María Morelos número 1, colonia Zona Centro, C.P. 38980, Uriangato, Guanajuato, es el Responsable del tratamiento y resguardo de sus datos personales. El Sujeto Obligado es el Municipio de Uriangato, Guanajuato.</p>

        <h6 class="fw-bold text-dark">Finalidad del tratamiento</h6>
        <p>Los datos personales solicitados serán utilizados para la creación de un Expediente Ciudadano Digital para la elaboración de Trámites y Servicios.</p>

        <h6 class="fw-bold text-dark">Transferencia de datos personales</h6>
        <p>Se informa que sus datos serán transferidos a las siguientes dependencias:</p>

        <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm small">
                <thead class="table-light">
                    <tr>
                        <th>Datos personales</th>
                        <th>Responsable</th>
                        <th>Finalidad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>CURP, Nombre, Dirección, Teléfono</td>
                        <td>Catastro y Predial</td>
                        <td>Llevar un control del pago predial</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h6 class="fw-bold text-dark">Negativa para el tratamiento</h6>
        <p>Podrá manifestar su negativa directamente en la Unidad de Transparencia ubicada en Av. José María Morelos número 1, Zona Centro, o al teléfono 445 45 7 50 22 Ext. 126 (8:30 a 16:00 hrs), así como al correo: accesoalainformacion@uriangato.gob.mx.</p>

        <h6 class="fw-bold text-dark">Consulta del Aviso Integral</h6>
        <p>Puede consultarlo en <a href="https://uriangato.gob.mx" target="_blank" class="text-primary fw-semibold">https://uriangato.gob.mx</a>.</p>

        <p class="text-muted fst-italic">Última actualización: 09 de febrero del 2026.</p>

    </div>

    <div class="d-grid gap-2 mt-4">
        <button type="button" class="btn btn-primary btn-lg shadow-sm" onclick="window.close();">
            <i class="bi bi-arrow-left me-2"></i>Cerrar y volver
        </button>
    </div>

    <div class="mt-3 text-center small text-muted">
        <p class="mb-0">Si no acepta el aviso de privacidad, <a href="/auth/register" class="text-danger fw-semibold text-decoration-none">vuelva al registro</a>.</p>
    </div>
</div>

<?= $this->endSection() ?>
