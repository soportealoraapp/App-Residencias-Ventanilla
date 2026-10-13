<?php declare(strict_types=1); ?>
<?= $this->extend('layouts/portal') ?>
<?= $this->section('title') ?>Mis Solicitudes - Portal Ciudadano Uriangato<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="row mb-3 mb-md-4 align-items-center">
    <div class="col-12 col-sm-8 mb-2 mb-sm-0">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="<?= site_url('/portal/dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mis Solicitudes</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1"><i class="bi bi-list-check text-primary me-2"></i>Mis Solicitudes</h1>
        <p class="text-muted small mb-0">Consulta en tiempo real el estatus, historial de dictamen y comprobantes de tus trámites municipales.</p>
    </div>
    <div class="col-12 col-sm-4 text-sm-end">
        <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm w-100 w-sm-auto shadow-sm">
            <i class="bi bi-plus-lg me-1"></i>Nuevo trámite
        </a>
    </div>
</div>

<?php if (!empty($solicitudes)): ?>
<!-- BARRA DE BÚSQUEDA Y FILTROS RÁPIDOS -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-3">
        <div class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="busquedaSolicitudes" class="form-control border-start-0 bg-light"
                           placeholder="Buscar por folio o nombre del trámite..."
                           aria-label="Buscar solicitudes">
                </div>
            </div>
            <div class="col-12 col-md-7">
                <div class="d-flex flex-wrap gap-1 justify-content-md-end" id="filtroEstatusPills">
                    <button type="button" class="btn btn-sm btn-outline-primary active filtro-pill py-1 px-2" data-estatus="todos">
                        Todos <span class="badge bg-secondary ms-1" id="count-todos"><?= count($solicitudes) ?></span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filtro-pill py-1 px-2" data-estatus="pago">
                        <i class="bi bi-cash-stack me-1 text-warning"></i>Pago pendiente
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filtro-pill py-1 px-2" data-estatus="revision">
                        <i class="bi bi-hourglass-split me-1 text-info"></i>En revisión
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filtro-pill py-1 px-2" data-estatus="aprobado">
                        <i class="bi bi-check-circle me-1 text-success"></i>Aprobados / Concluidos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary filtro-pill py-1 px-2" data-estatus="observacion">
                        <i class="bi bi-exclamation-triangle me-1 text-danger"></i>Observación / Rechazo
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($solicitudes)): ?>
        <div class="text-center py-5 text-muted px-3">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex p-4 mb-3">
                <i class="bi bi-inbox fs-1"></i>
            </div>
            <h5 class="fw-bold text-dark mb-1">Aún no tienes solicitudes registradas</h5>
            <p class="text-muted small mb-3 max-w-md mx-auto" style="max-width: 420px;">
                Inicia un nuevo trámite digital de movilidad, transporte público, permisos de carga o cierre de calle desde la comodidad de tu hogar.
            </p>
            <a href="<?= site_url('/portal/tramites') ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-grid me-1"></i>Explorar catálogo de trámites
            </a>
        </div>
        <?php else: ?>
        <!-- Mobile Card List (Visible on < 768px) -->
        <div class="d-block d-md-none" id="contenedorCardsMovil">
            <?php foreach ($solicitudes as $s): 
                $estatusRaw = mb_strtolower((string)$s->estatus);
                $grupoEstatus = 'revision';
                if (str_contains($estatusRaw, 'pago')) {
                    $grupoEstatus = 'pago';
                } elseif (str_contains($estatusRaw, 'aprobad') || str_contains($estatusRaw, 'concluid') || str_contains($estatusRaw, 'vigente') || str_contains($estatusRaw, 'pagado')) {
                    $grupoEstatus = 'aprobado';
                } elseif (str_contains($estatusRaw, 'prevenci') || str_contains($estatusRaw, 'rechazad') || str_contains($estatusRaw, 'vencid')) {
                    $grupoEstatus = 'observacion';
                }
            ?>
                <div class="p-3 border-bottom item-solicitud" 
                     data-folio="<?= esc(mb_strtolower($s->folio)) ?>" 
                     data-tramite="<?= esc(mb_strtolower(tramite_nombre($s->tramite))) ?>" 
                     data-grupo="<?= $grupoEstatus ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold font-monospace text-primary"><?= esc($s->folio) ?></span>
                                <button type="button" class="btn btn-link p-0 text-muted btn-copiar-folio" data-folio="<?= esc($s->folio) ?>" title="Copiar folio">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div class="small fw-semibold text-dark mt-1"><?= esc(tramite_nombre($s->tramite)) ?></div>
                        </div>
                        <span class="badge estatus-badge <?= estatus_badge($s->estatus) ?>"><?= esc($s->estatus) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 small text-muted bg-light p-2 rounded">
                        <div><i class="bi bi-calendar3 me-1"></i><?= formatear_fecha($s->fecha_solicitud) ?></div>
                        <div class="fw-bold text-dark fs-6"><?= formatear_dinero((float)$s->monto) ?></div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary flex-fill">
                            <i class="bi bi-eye me-1"></i>Ver detalle
                        </a>
                        <?php if ($s->estatus === 'Pago pendiente'): ?>
                            <?php
                                $pagoUrl = match($s->tramite) {
                                    'UR-TT-T-07' => site_url('/portal/tramites/carga-descarga/resumen/' . $s->folio),
                                    'UR-TT-T-04' => site_url('/portal/tramites/permiso-eventual/resumen/' . $s->folio),
                                    'UR-TT-T-05' => site_url('/portal/tramites/cierre-calle/resumen/' . $s->folio),
                                    default      => site_url('/portal/solicitud/' . $s->folio),
                                };
                            ?>
                            <a href="<?= $pagoUrl ?>" class="btn btn-sm btn-success flex-fill">
                                <i class="bi bi-cash-coin me-1"></i>Pagar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Desktop/Tablet Table (Visible on >= 768px) -->
        <div class="table-responsive d-none d-md-block" id="contenedorTablaDesktop">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 170px;">Folio</th>
                        <th>Trámite</th>
                        <th style="width: 150px;">Estatus</th>
                        <th style="width: 150px;">Fecha solicitud</th>
                        <th style="width: 130px;">Monto</th>
                        <th class="text-end" style="width: 180px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="solicitudesTbody">
                <?php foreach ($solicitudes as $s): 
                    $estatusRaw = mb_strtolower((string)$s->estatus);
                    $grupoEstatus = 'revision';
                    if (str_contains($estatusRaw, 'pago')) {
                        $grupoEstatus = 'pago';
                    } elseif (str_contains($estatusRaw, 'aprobad') || str_contains($estatusRaw, 'concluid') || str_contains($estatusRaw, 'vigente') || str_contains($estatusRaw, 'pagado')) {
                        $grupoEstatus = 'aprobado';
                    } elseif (str_contains($estatusRaw, 'prevenci') || str_contains($estatusRaw, 'rechazad') || str_contains($estatusRaw, 'vencid')) {
                        $grupoEstatus = 'observacion';
                    }
                ?>
                    <tr class="item-solicitud" 
                        data-folio="<?= esc(mb_strtolower($s->folio)) ?>" 
                        data-tramite="<?= esc(mb_strtolower(tramite_nombre($s->tramite))) ?>" 
                        data-grupo="<?= $grupoEstatus ?>">
                        <td>
                            <div class="d-inline-flex align-items-center gap-1">
                                <code class="fw-bold text-primary fs-6"><?= esc($s->folio) ?></code>
                                <button type="button" class="btn btn-link p-0 text-muted btn-copiar-folio" data-folio="<?= esc($s->folio) ?>" title="Copiar folio">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><?= esc(tramite_nombre($s->tramite)) ?></div>
                        </td>
                        <td>
                            <span class="badge estatus-badge <?= estatus_badge($s->estatus) ?>"><?= esc($s->estatus) ?></span>
                        </td>
                        <td class="small text-nowrap text-muted">
                            <i class="bi bi-calendar3 me-1"></i><?= formatear_fecha($s->fecha_solicitud) ?>
                        </td>
                        <td class="fw-semibold text-dark"><?= formatear_dinero((float)$s->monto) ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?= site_url('/portal/solicitud/' . $s->folio) ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-eye me-1"></i>Detalle
                            </a>
                            <?php if ($s->estatus === 'Pago pendiente'): ?>
                            <?php
                                $pagoUrl = match($s->tramite) {
                                    'UR-TT-T-07' => site_url('/portal/tramites/carga-descarga/resumen/' . $s->folio),
                                    'UR-TT-T-04' => site_url('/portal/tramites/permiso-eventual/resumen/' . $s->folio),
                                    'UR-TT-T-05' => site_url('/portal/tramites/cierre-calle/resumen/' . $s->folio),
                                    default      => site_url('/portal/solicitud/' . $s->folio),
                                };
                            ?>
                            <a href="<?= $pagoUrl ?>" class="btn btn-sm btn-success">
                                <i class="bi bi-cash-coin me-1"></i>Pagar
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Alerta de Cero Resultados al Filtrar -->
        <div id="sinResultadosFiltro" class="d-none text-center py-5 text-muted px-3">
            <i class="bi bi-search fs-2 mb-2 d-block opacity-50"></i>
            <h6 class="fw-bold text-dark">No se encontraron solicitudes</h6>
            <p class="small text-muted mb-3">No hay solicitudes que coincidan con los filtros o el término de búsqueda actual.</p>
            <button type="button" id="btnRestablecerFiltros" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Restablecer filtros
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.getElementById('busquedaSolicitudes');
    const pills = document.querySelectorAll('.filtro-pill');
    const sinResultados = document.getElementById('sinResultadosFiltro');
    const contenedorCards = document.getElementById('contenedorCardsMovil');
    const contenedorTabla = document.getElementById('contenedorTablaDesktop');
    const btnRestablecer = document.getElementById('btnRestablecerFiltros');
    const btnsCopiar = document.querySelectorAll('.btn-copiar-folio');

    let filtroEstatusActual = 'todos';

    // Funcionalidad copiar folio
    btnsCopiar.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const folio = this.getAttribute('data-folio');
            if (navigator.clipboard && folio) {
                navigator.clipboard.writeText(folio).then(() => {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.className = 'bi bi-check-lg text-success';
                        setTimeout(() => {
                            icon.className = 'bi bi-clipboard';
                        }, 2000);
                    }
                });
            }
        });
    });

    function aplicarFiltros() {
        const query = (inputBusqueda ? inputBusqueda.value : '').toLowerCase().trim();
        const items = document.querySelectorAll('.item-solicitud');
        let visibles = 0;

        items.forEach(item => {
            const folio = item.getAttribute('data-folio') || '';
            const tramite = item.getAttribute('data-tramite') || '';
            const grupo = item.getAttribute('data-grupo') || '';

            const coincideTexto = !query || folio.includes(query) || tramite.includes(query);
            const coincideGrupo = (filtroEstatusActual === 'todos') || (grupo === filtroEstatusActual);

            if (coincideTexto && coincideGrupo) {
                item.style.display = '';
                visibles++;
            } else {
                item.style.display = 'none';
            }
        });

        if (sinResultados) {
            if (visibles === 0 && items.length > 0) {
                sinResultados.classList.remove('d-none');
                if (contenedorCards) contenedorCards.classList.add('d-none');
                if (contenedorTabla) contenedorTabla.classList.add('d-none');
            } else {
                sinResultados.classList.add('d-none');
                if (contenedorCards) contenedorCards.classList.remove('d-none');
                if (contenedorTabla) contenedorTabla.classList.remove('d-none');
            }
        }
    }

    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', aplicarFiltros);
    }

    pills.forEach(pill => {
        pill.addEventListener('click', function() {
            pills.forEach(p => {
                p.classList.remove('active', 'btn-primary');
                p.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('active', 'btn-primary');

            filtroEstatusActual = this.getAttribute('data-estatus') || 'todos';
            aplicarFiltros();
        });
    });

    if (btnRestablecer) {
        btnRestablecer.addEventListener('click', function() {
            if (inputBusqueda) inputBusqueda.value = '';
            filtroEstatusActual = 'todos';
            pills.forEach(p => {
                if (p.getAttribute('data-estatus') === 'todos') {
                    p.classList.remove('btn-outline-secondary');
                    p.classList.add('active', 'btn-primary');
                } else {
                    p.classList.remove('active', 'btn-primary');
                    p.classList.add('btn-outline-secondary');
                }
            });
            aplicarFiltros();
        });
    }
});
</script>
<?= $this->endSection() ?>





