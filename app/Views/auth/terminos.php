<?php declare(strict_types=1); ?>
<?= $this->extend('auth/layout_auth') ?>
<?= $this->section('title') ?>Términos y Condiciones - Ventanilla Digital Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="auth-header">
    <div class="auth-brand-icon bg-primary" style="background: linear-gradient(135deg, #0d47a1 0%, #1565c0 100%);">
        <i class="bi bi-shield-check"></i>
    </div>
    <h2 class="h4 mb-1 fw-bold">Términos y Condiciones</h2>
    <p class="text-muted small mb-0">Ventanilla Digital · Uriangato, Gto.</p>
</div>

<div class="auth-body">

    <div class="small" style="text-align: justify; line-height: 1.7;">

        <h6 class="fw-bold text-dark">1. Identidad del Titular</h6>
        <p>Este portal es gestionado y administrado por el Municipio de Uriangato, Guanajuato (en adelante, el Municipio). Nuestra sede oficial se localiza en Av. José María Morelos 1, Zona Centro, C.P. 38980, Uriangato, Gto. Para cualquier duda relacionada con este sitio, ponemos a su disposición el correo electrónico: webmaster@uriangato.gob.mx.</p>

        <h6 class="fw-bold text-dark">2. Uso del Sitio y Actualizaciones</h6>
        <p>El Municipio se faculta para actualizar, modificar o eliminar tanto el diseño como la información contenida en esta plataforma en cualquier momento y sin previo aviso. El contenido se ofrece "tal cual", sin garantías explícitas sobre su exactitud absoluta, por lo que su uso se realiza bajo el propio riesgo del visitante.</p>

        <h6 class="fw-bold text-dark">3. Responsabilidad del Usuario</h6>
        <p>Al navegar en este sitio, usted se compromete a actuar bajo la ley y el respeto a los derechos de el Municipio y de terceros. Queda estrictamente prohibida cualquier alteración, manipulación o intento de modificación técnica de nuestra web. El usuario será el único responsable ante cualquier daño o perjuicio que su incumplimiento pudiera causar a la administración municipal.</p>

        <h6 class="fw-bold text-dark">4. Política de Enlaces y Confidencialidad</h6>
        <p>Cualquier comunicación enviada a través de este portal será tratada como información pública, salvo aquello que la ley y nuestro Aviso de Privacidad protejan específicamente como datos personales. Asimismo, se requiere permiso por escrito para crear enlaces que dirijan a este sitio. El Municipio no se hace responsable por el contenido o las prácticas de seguridad de sitios externos enlazados desde aquí.</p>

        <h6 class="fw-bold text-dark">5. Limitación de Responsabilidad</h6>
        <p>No garantizamos que el acceso a la web sea ininterrumpido o esté libre de errores técnicos. El Municipio queda exento de cualquier responsabilidad por daños derivados de fallos en el sistema, virus informáticos o el uso indebido de las aplicaciones aquí alojadas.</p>

        <h6 class="fw-bold text-dark">6. Jurisdicción y Ley Aplicable</h6>
        <p>Para la resolución de cualquier conflicto derivado de la interpretación o uso de este sitio web, tanto el usuario como el Municipio acuerdan someterse a la legislación mexicana vigente. Ambas partes renuncian a cualquier otro fuero que pudiera corresponderles y aceptan la competencia de los tribunales con sede en Uriangato, Guanajuato.</p>

    </div>

    <div class="d-grid gap-2 mt-4">
        <button type="button" class="btn btn-primary btn-lg shadow-sm" onclick="window.close();">
            <i class="bi bi-arrow-left me-2"></i>Cerrar y volver
        </button>
    </div>

    <div class="mt-3 text-center small text-muted">
        <p class="mb-0">Si no acepta los términos, <a href="/auth/register" class="text-danger fw-semibold text-decoration-none">vuelva al registro</a>.</p>
    </div>
</div>

<?= $this->endSection() ?>
