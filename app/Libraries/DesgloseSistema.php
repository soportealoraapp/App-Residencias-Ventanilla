<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\VerificacionFisicaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Agregador de métricas del sistema para el desglose del administrador.
 *
 * Es de solo lectura: nunca escribe. Toda la agrupación temporal se hace en PHP
 * (recortando el timestamp) y no con funciones de fecha del motor, para que el
 * mismo código funcione igual en PostgreSQL (Supabase) y en SQLite (pruebas).
 *
 * Cada sección se envuelve en try/catch: si una tabla aún no existe en el
 * entorno, el desglose sigue mostrando el resto en lugar de romper la página.
 */
final class DesgloseSistema
{
    /** Estatus que ya no esperan ninguna acción del personal. */
    private const ESTATUS_CERRADOS = ['Concluido', 'Rechazado', 'Vencido', 'No seleccionado'];

    private BaseConnection $db;

    /** @var list<array<string, ?string>>|null */
    private ?array $filasSolicitudes = null;

    /** @var array<int, true> Ids de solicitudes creadas en ventanilla. */
    private ?array $presenciales = null;

    /** @var array<string, mixed> Secciones ya calculadas dentro de esta petición. */
    private array $secciones = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    /**
     * Calcula una sección la primera vez y la devuelve repetida sin volver a la base.
     *
     * `alertas()` reutiliza secciones pesadas y la pantalla también las muestra,
     * así que sin esta memoria la misma consulta iría dos veces por petición.
     *
     * @param callable(): mixed $calcula
     * @return mixed
     */
    private function seccion(string $clave, callable $calcula)
    {
        return $this->secciones[$clave] ??= $calcula();
    }

    /**
     * Indicadores generales de cabecera.
     *
     * @return array<string, int|float>
     */
    public function kpis(): array
    {
        $filas  = $this->solicitudes();
        $cobro  = 0.0;
        $pendio = 0.0;
        $cobras = 0;
        $abieta = 0;
        $gente    = [];

        foreach ($filas as $f) {
            $monto = (float) ($f['monto'] ?? 0);
            if (($f['fecha_pago'] ?? '') !== '') {
                $cobro += $monto;
                $cobras++;
            }
            if ($f['estatus'] === 'Pago pendiente') {
                $pendio += $monto;
            }
            if (! in_array((string) $f['estatus'], self::ESTATUS_CERRADOS, true)) {
                $abieta++;
            }
            if (($f['ciudadano_id'] ?? '') !== null && $f['ciudadano_id'] !== '') {
                $gente[(int) $f['ciudadano_id']] = true;
            }
        }

        return [
            'solicitudes_total'      => count($filas),
            'en_tramite'             => $abieta,
            'cobradas'               => $cobras,
            'ingresos_cobrados'      => round($cobro, 2),
            'ingresos_por_cobrar'    => round($pendio, 2),
            'ticket_promedio'        => $cobras > 0 ? round($cobro / $cobras, 2) : 0.0,
            'ciudadanos_activos'     => count($gente),
            'documentos'             => $this->conteo('documentos'),
            'eventos_auditoria'      => $this->conteo('auditoria'),
            'movimientos_estatus'    => $this->conteo('historial_estatus'),
            'cuentas'                => $this->conteo('users'),
            'boletas_transito'       => $this->conteo('boletas_infracciones'),
            'solicitudes_mes_actual' => $this->conteoDesde('solicitudes', 'fecha_solicitud', date('Y-m-01') . ' 00:00:00'),
        ];
    }

    /**
     * Distribución del total de solicitudes por cada estatus del maestro.
     *
     * @return array{lista: list<array<string, float|string>>, total: int}
     */
    public function porEstatus(): array
    {
        $conteos = [];
        foreach ($this->solicitudes() as $f) {
            $clave = (string) ($f['estatus'] ?? 'sin estatus');
            $conteos[$clave] = ($conteos[$clave] ?? 0) + 1;
        }

        // Primero los del maestro (orden estable), después cualquier valor fuera de él.
        $orden   = EstadoSolicitudService::ESTATUS_MAESTRO;
        $ordenados = array_values(array_filter($orden, static fn ($e) => isset($conteos[$e])));
        $extra   = array_keys($conteos);
        sort($extra);
        foreach ($extra as $e) {
            if (! in_array($e, $ordenados, true)) {
                $ordenados[] = $e;
            }
        }

        $total = array_sum($conteos);
        $lista = [];
        foreach ($ordenados as $e) {
            $n = $conteos[$e];
            $lista[] = [
                'estatus' => $e,
                'total'   => $n,
                'pct'     => $total > 0 ? round($n * 100 / $total, 1) : 0.0,
                'cerrado' => in_array($e, self::ESTATUS_CERRADOS, true) ? 1 : 0,
            ];
        }

        return ['lista' => $lista, 'total' => $total];
    }

    /**
     * Una fila por trámite con su volumen y su dinero.
     *
     * @return list<array<string, float|string>>
     */
    public function porTramite(): array
    {
        $acum = [];
        foreach ($this->solicitudes() as $f) {
            $t = (string) ($f['tramite'] ?? '');
            if ($t === '') {
                continue;
            }
            $acum[$t] ??= ['total' => 0, 'cobradas' => 0, 'abierta' => 0, 'cobrado' => 0.0, 'por_cobrar' => 0.0, 'presencial' => 0];
            $acum[$t]['total']++;
            $monto = (float) ($f['monto'] ?? 0);

            if (($f['fecha_pago'] ?? '') !== '') {
                $acum[$t]['cobradas']++;
                $acum[$t]['cobrado'] += $monto;
            }
            if ($f['estatus'] === 'Pago pendiente') {
                $acum[$t]['por_cobrar'] += $monto;
            }
            if (! in_array((string) $f['estatus'], self::ESTATUS_CERRADOS, true)) {
                $acum[$t]['abierta']++;
            }
            if ($this->esPresencial((int) $f['id'])) {
                $acum[$t]['presencial']++;
            }
        }

        $totalGeneral = array_sum(array_column($acum, 'total'));
        $lista        = [];
        foreach ($acum as $t => $d) {
            $lista[] = [
                'tramite'     => $t,
                'nombre'      => tramite_nombre($t),
                'total'       => $d['total'],
                'pct'         => $totalGeneral > 0 ? round($d['total'] * 100 / $totalGeneral, 1) : 0.0,
                'cobradas'    => $d['cobradas'],
                'abiertas'    => $d['abierta'],
                'presencial'  => $d['presencial'],
                'digital'     => $d['total'] - $d['presencial'],
                'cobrado'     => round($d['cobrado'], 2),
                'por_cobrar'  => round($d['por_cobrar'], 2),
                'promedio'    => $d['total'] > 0 ? round($d['cobrado'] / max(1, $d['cobradas']), 2) : 0.0,
            ];
        }

        usort($lista, static fn ($a, $b) => $b['total'] <=> $a['total']);

        return $lista;
    }

    /**
     * Solicitudes del portal digital frente a las registradas en ventanilla.
     *
     * @return list<array<string, float|string|int>>
     */
    public function porCanal(): array
    {
        $filas = $this->solicitudes();
        $pres  = 0;
        $monto = ['presencial' => 0.0, 'digital' => 0.0];
        foreach ($filas as $f) {
            $canal = $this->esPresencial((int) $f['id']) ? 'presencial' : 'digital';
            $pres += $canal === 'presencial' ? 1 : 0;
            $monto[$canal] += (float) ($f['monto'] ?? 0);
        }

        $total = count($filas);

        return [
            [
                'canal'    => 'digital',
                'nombre'   => 'Portal digital',
                'total'    => $total - $pres,
                'pct'      => $total > 0 ? round(($total - $pres) * 100 / $total, 1) : 0.0,
                'monto'    => round($monto['digital'], 2),
                'origen'   => 'digital',
            ],
            [
                'canal'    => 'presencial',
                'nombre'   => 'Ventanilla (captura del operador)',
                'total'    => $pres,
                'pct'      => $total > 0 ? round($pres * 100 / $total, 1) : 0.0,
                'monto'    => round($monto['presencial'], 2),
                'origen'   => 'presencial',
            ],
        ];
    }

    /**
     * Serie de los últimos N meses: solicitudes recibidas, cobros e ingresos.
     *
     * @return list<array<string, float|int|string>>
     */
    public function serieMensual(int $meses = 12): array
    {
        $series = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $clave = date('Y-m', strtotime('-' . $i . ' month'));
            $series[$clave] = [
                'mes'        => $clave,
                'etiqueta'   => $this->etiquetaMes($clave),
                'solicitudes'=> 0,
                'cobradas'   => 0,
                'ingresos'   => 0.0,
            ];
        }

        foreach ($this->solicitudes() as $f) {
            $m = $this->mes($f['fecha_solicitud'] ?? null);
            if ($m !== '' && isset($series[$m])) {
                $series[$m]['solicitudes']++;
            }
            $p = $this->mes($f['fecha_pago'] ?? null);
            if ($p !== '' && isset($series[$p])) {
                $series[$p]['cobradas']++;
                $series[$p]['ingresos'] += (float) ($f['monto'] ?? 0);
            }
        }

        $lista = array_values($series);
        $max   = max(1, max(array_column($lista, 'solicitudes')));
        foreach ($lista as &$fila) {
            $fila['pct_altura'] = round($fila['solicitudes'] * 100 / $max, 1);
            $fila['ingresos']   = round((float) $fila['ingresos'], 2);
        }

        return $lista;
    }

    /**
     * Transiciones de estatus más frecuentes (quién movió qué y hacia dónde).
     *
     * @return list<array<string, int|string>>
     */
    public function flujoEstatus(int $limite = 12): array
    {
        try {
            $filas = $this->db->table('historial_estatus')
                ->select('estatus_anterior, estatus_nuevo, COUNT(*) as total')
                ->groupBy('estatus_anterior')
                ->groupBy('estatus_nuevo')
                ->orderBy('total', 'DESC')
                ->limit($limite)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::flujoEstatus: ' . $e->getMessage());

            return [];
        }

        return array_map(static fn (array $r): array => [
            'anterior' => (string) ($r['estatus_anterior'] ?? ''),
            'nuevo'    => (string) ($r['estatus_nuevo'] ?? ''),
            'total'    => (int) $r['total'],
        ], $filas);
    }

    /**
     * Tiempos de gestión y solicitudes que se están quedando sin atención.
     *
     * @return array<string, int|float|list<array<string, string>>>
     */
    public function tiempos(): array
    {
        return $this->seccion('tiempos', fn (): array => $this->calcularTiempos());
    }

    private function calcularTiempos(): array
    {
        $duracion = [];
        $demoras  = [];
        $sinResolver = 0;

        foreach ($this->solicitudes() as $f) {
            $dias = $this->dias($f['fecha_solicitud'], $f['fecha_resolucion'] ?? null);
            if ($dias !== null) {
                $duracion[] = $dias;
            } else {
                $sinResolver++;
                $espera = $this->dias($f['fecha_solicitud'], date('Y-m-d H:i:s'));
                if ($espera !== null && $espera >= 7 && ! in_array((string) $f['estatus'], self::ESTATUS_CERRADOS, true)) {
                    $demoras[] = [
                        'folio'   => (string) $f['folio'],
                        'tramite' => tramite_nombre((string) $f['tramite']),
                        'estatus' => (string) $f['estatus'],
                        'dias'    => (int) round($espera),
                        'url'     => '/admin/solicitudes/' . $f['folio'],
                    ];
                }
            }
        }

        usort($demoras, static fn ($a, $b) => $b['dias'] <=> $a['dias']);

        return [
            'promedio_dias' => $duracion === [] ? 0.0 : round(array_sum($duracion) / count($duracion), 1),
            'maximo_dias'   => $duracion === [] ? 0 : (int) round(max($duracion)),
            'resueltas'     => count($duracion),
            'sin_resolver'  => $sinResolver,
            'demoradas'     => array_slice($demoras, 0, 10),
            'total_demoradas' => count($demoras),
        ];
    }

    /**
     * Permisos que están por vencer o ya vencieron sin cerrar su expediente.
     *
     * @return array<string, int|list<array<string, string>>>
     */
    public function vigencias(): array
    {
        return $this->seccion('vigencias', fn (): array => $this->calcularVigencias());
    }

    private function calcularVigencias(): array
    {
        $hoy      = new \DateTimeImmutable('today');
        $en30     = $hoy->modify('+30 days');
        $en90     = $hoy->modify('+90 days');
        $porVencer = [];
        $vencidas  = 0;

        foreach ($this->solicitudes() as $f) {
            $fin = $f['fecha_vigencia_fin'] ?? null;
            if ($fin === null || $fin === '' || strlen((string) $fin) < 10) {
                continue;
            }
            try {
                $fecha = new \DateTimeImmutable(substr((string) $fin, 0, 10));
            } catch (\Throwable) {
                continue;
            }

            if ($fecha < $hoy) {
                if ((string) $f['estatus'] === 'Vigente') {
                    $vencidas++;
                }
                continue;
            }
            if ($fecha <= $en30) {
                $porVencer[] = [
                    'folio'    => (string) $f['folio'],
                    'tramite'  => tramite_nombre((string) $f['tramite']),
                    'estatus'  => (string) $f['estatus'],
                    'vence'    => $fecha->format('d/m/Y'),
                    'dias'     => $hoy->diff($fecha)->days,
                    'url'      => '/admin/solicitudes/' . $f['folio'],
                ];
            }
        }

        usort($porVencer, static fn ($a, $b) => $a['dias'] <=> $b['dias']);

        return [
            'por_vencer_30'  => count($porVencer),
            'por_vencer_90'  => $this->conteoVigenciaHasta($en90),
            'vencidas'       => $vencidas,
            'detalle'        => array_slice($porVencer, 0, 10),
        ];
    }

    /**
     * Carga de archivos: volumen, espacio y estado de validacion.
     *
     * @return array<string, int|float|list<array<string, int|string>>>
     */
    public function documentos(): array
    {
        return $this->seccion('documentos', fn (): array => $this->calcularDocumentos());
    }

    private function calcularDocumentos(): array
    {
        try {
            $tipos = $this->db->table('documentos')
                // El mismo criterio que DocumentoModel::resumenPorTipo(): NULL y
                // cadena vacía son «sin clasificar», así el enlace del desglose
                // abre exactamente los archivos que acaba de contar.
                ->select("COALESCE(NULLIF(tipo_documento, ''), 'sin_clasificar') as tipo, COUNT(*) as total, SUM(tamano_bytes) as bytes, SUM(CASE WHEN validado = 1 THEN 1 ELSE 0 END) as validados", false)
                ->groupBy('tipo')
                ->orderBy('total', 'DESC')
                ->get()
                ->getResultArray();
            $totales = $this->db->table('documentos')
                ->select('COUNT(*) as total, SUM(tamano_bytes) as bytes, SUM(CASE WHEN validado = 1 THEN 1 ELSE 0 END) as validados')
                ->get()
                ->getRowArray();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::documentos: ' . $e->getMessage());

            return ['total' => 0, 'bytes' => 0, 'validados' => 0, 'por_validar' => 0, 'tipos' => [], 'del_mes' => 0];
        }

        try {
            $delMes = (int) $this->db->table('documentos')
                ->where('fecha_carga >=', date('Y-m-01') . ' 00:00:00')
                ->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::documentos (mes): ' . $e->getMessage());
            $delMes = 0;
        }

        return [
            'total'       => (int) ($totales['total'] ?? 0),
            'bytes'       => (float) ($totales['bytes'] ?? 0),
            'validados'   => (int) ($totales['validados'] ?? 0),
            'por_validar' => (int) ($totales['total'] ?? 0) - (int) ($totales['validados'] ?? 0),
            'del_mes'     => $delMes,
            'tipos'       => array_map(static fn (array $r): array => [
                'tipo'      => (string) ($r['tipo'] ?? 'sin_clasificar'),
                'total'     => (int) $r['total'],
                'bytes'     => (float) ($r['bytes'] ?? 0),
                'validados' => (int) ($r['validados'] ?? 0),
            ], $tipos),
        ];
    }

    /**
     * Cuentas del sistema: roles, actividad y completitud del perfil.
     *
     * @return array<string, int|list<array<string, int|string>>>
     */
    public function cuentas(): array
    {
        $roles = [];
        try {
            $roles = $this->db->table('user_roles')
                ->select('roles.nombre as rol, COUNT(*) as total')
                ->join('roles', 'roles.id = user_roles.role_id')
                ->groupBy('roles.nombre')
                ->orderBy('total', 'DESC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::cuentas (roles): ' . $e->getMessage());
        }

        $bandas = ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'con_solicitud' => 0, 'nuevos_mes' => 0, 'con_curp' => 0, 'con_ine' => 0];
        try {
            $fila = $this->db->table('users')
                ->select('COUNT(*) as total, SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos, SUM(CASE WHEN activo = 1 THEN 0 ELSE 1 END) as inactivos, SUM(CASE WHEN curp IS NOT NULL AND curp <> \'\' THEN 1 ELSE 0 END) as con_curp, SUM(CASE WHEN ine_frente IS NOT NULL AND ine_frente <> \'\' THEN 1 ELSE 0 END) as con_ine')
                ->get()
                ->getRowArray();
            $bandas['total']      = (int) ($fila['total'] ?? 0);
            $bandas['activos']    = (int) ($fila['activos'] ?? 0);
            $bandas['inactivos']  = (int) ($fila['inactivos'] ?? 0);
            $bandas['con_curp']   = (int) ($fila['con_curp'] ?? 0);
            $bandas['con_ine']    = (int) ($fila['con_ine'] ?? 0);

            $bandas['con_solicitud'] = (int) $this->db->table('solicitudes')
                ->distinct()->select('ciudadano_id')->countAllResults();

            $bandas['nuevos_mes'] = (int) $this->db->table('users')
                ->where('created_at >=', date('Y-m-01') . ' 00:00:00')->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::cuentas (users): ' . $e->getMessage());
        }

        return [
            'total'      => $bandas['total'],
            'activos'    => $bandas['activos'],
            'inactivos'  => $bandas['inactivos'],
            'con_solicitud' => $bandas['con_solicitud'],
            'nuevos_mes' => $bandas['nuevos_mes'],
            'con_curp'   => $bandas['con_curp'],
            'con_ine'    => $bandas['con_ine'],
            'roles'      => array_map(static fn (array $r): array => [
                'rol'   => (string) $r['rol'],
                'total' => (int) $r['total'],
            ], $roles),
        ];
    }

    /**
     * Resumen de la bitácora de auditoría.
     *
     * @return array<string, int|list<array<string, int|string>>>
     */
    public function auditoria(int $limite = 10): array
    {
        $porEntidad = [];
        $porAccion  = [];
        $topUsuarios = [];
        $fallidos   = 0;

        try {
            $porEntidad = $this->db->table('auditoria')
                ->select('entidad, COUNT(*) as total')->groupBy('entidad')->orderBy('total', 'DESC')
                ->limit($limite)->get()->getResultArray();
            $porAccion = $this->db->table('auditoria')
                ->select('accion, COUNT(*) as total')->groupBy('accion')->orderBy('total', 'DESC')
                ->limit($limite)->get()->getResultArray();
            $topUsuarios = $this->db->table('auditoria')
                ->select('auditoria.usuario_id, users.username, users.nombre_completo, COUNT(*) as total')
                ->join('users', 'users.id = auditoria.usuario_id')
                ->where('auditoria.usuario_id IS NOT NULL', null, false)
                ->groupBy('auditoria.usuario_id, users.username, users.nombre_completo')
                ->orderBy('total', 'DESC')->limit($limite)->get()->getResultArray();
            $fallidos = (int) $this->db->table('auditoria')
                ->where('accion', 'login_fallido')->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::auditoria: ' . $e->getMessage());
        }

        return [
            'total'           => $this->conteo('auditoria'),
            'del_mes'         => $this->conteoDesde('auditoria', 'fecha', date('Y-m-01') . ' 00:00:00'),
            'entidades'       => array_map(static fn (array $r): array => ['entidad' => (string) $r['entidad'], 'total' => (int) $r['total']], $porEntidad),
            'acciones'        => array_map(static fn (array $r): array => ['accion' => (string) $r['accion'], 'total' => (int) $r['total']], $porAccion),
            'operadores'      => array_map(static fn (array $r): array => [
                'usuario_id' => (int) $r['usuario_id'],
                'nombre'     => (string) ($r['nombre_completo'] ?: $r['username']),
                'total'      => (int) $r['total'],
            ], $topUsuarios),
            'fallidos'        => $fallidos,
        ];
    }

    /**
     * Operación de la app de agentes de tránsito: boletas, catálogo y sesiones.
     *
     * @return array<string, int|float|string|list<array<string, int|string>>>
     */
    public function appTransito(): array
    {
        return $this->seccion('appTransito', fn (): array => $this->calcularAppTransito());
    }

    private function calcularAppTransito(): array
    {
        $res = ['total' => 0, 'estados' => [], 'agentes' => [], 'faltas' => [], 'del_mes' => 0, 'con_foto' => 0, 'sin_placas' => 0, 'ausente' => 0, 'uma_promedio' => 0.0, 'sesiones_total' => 0, 'sesiones_vigentes' => 0, 'sesiones_vencidas' => 0, 'sesiones_revocadas' => 0, 'agentes_en_turno' => [], 'ultima_actividad_app' => ''];

        try {
            $res['total']    = (int) $this->db->table('boletas_infracciones')->countAllResults();
            $res['del_mes']  = $this->conteoDesde('boletas_infracciones', 'created_at', date('Y-m-01') . ' 00:00:00');
            $res['estados']  = array_map(static fn (array $r): array => ['estado' => (string) $r['estado'], 'total' => (int) $r['total']],
                $this->db->table('boletas_infracciones')->select('estado, COUNT(*) as total')->groupBy('estado')->orderBy('total', 'DESC')->get()->getResultArray());
            $res['agentes']  = array_map(static fn (array $r): array => [
                'placa'   => (string) $r['agente_placa'],
                'nombre'  => (string) ($r['agente_nombre'] ?: $r['agente_placa']),
                'total'   => (int) $r['total'],
            ], $this->db->table('boletas_infracciones')
                ->select('agente_placa, MAX(agente_nombre) as agente_nombre, COUNT(*) as total')
                ->groupBy('agente_placa')->orderBy('total', 'DESC')->limit(10)->get()->getResultArray());
            $res['faltas']   = array_map(static fn (array $r): array => [
                'fundamento' => (string) $r['falta_fundamento_legal'],
                'total'      => (int) $r['total'],
            ], $this->db->table('boletas_infracciones')
                ->select('falta_fundamento_legal, COUNT(*) as total')
                ->where('falta_fundamento_legal IS NOT NULL', null, false)
                ->groupBy('falta_fundamento_legal')->orderBy('total', 'DESC')->limit(10)->get()->getResultArray());

            $fila = $this->db->table('boletas_infracciones')
                ->select('COUNT(*) as total, SUM(CASE WHEN foto_placa_url IS NOT NULL AND foto_placa_url <> \'\' THEN 1 ELSE 0 END) as con_foto, SUM(CASE WHEN sin_placas = 1 THEN 1 ELSE 0 END) as sin_placas, SUM(CASE WHEN conductor_ausente = 1 THEN 1 ELSE 0 END) as ausente, AVG((COALESCE(falta_monto_min_uma, 0) + COALESCE(falta_monto_max_uma, 0)) / 2) as uma_prom')
                ->get()->getRowArray();
            $res['con_foto']    = (int) ($fila['con_foto'] ?? 0);
            $res['sin_placas']  = (int) ($fila['sin_placas'] ?? 0);
            $res['ausente']     = (int) ($fila['ausente'] ?? 0);
            $res['uma_promedio'] = round((float) ($fila['uma_prom'] ?? 0), 2);
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::appTransito: ' . $e->getMessage());
        }

        try {
            $res['agentes_activos'] = (int) $this->db->table('agentes_transito')->where('activo', 1)->countAllResults();
            $res['agentes_total']   = (int) $this->db->table('agentes_transito')->countAllResults();
            $res['faltas_catalogo'] = (int) $this->db->table('catalogo_infracciones_app')->where('activo', 1)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::appTransito (apoyo): ' . $e->getMessage());
        }

        // Sesiones de la app móvil. Cada inicio de sesión deja un token vigente,
        // así que estas cifras dicen cuántos agentes pueden estar en turno y fue
        // lo último que la app hizo dentro del sistema.
        try {
            $ahora = date('Y-m-d H:i:s');
            $res['sesiones_total']     = (int) $this->db->table('agentes_transito_tokens')->countAllResults();
            $res['sesiones_vigentes']  = (int) $this->db->table('agentes_transito_tokens')
                ->where('revocado_en IS NULL', null, false)->where('expira_en >=', $ahora)->countAllResults();
            $res['sesiones_vencidas']  = (int) $this->db->table('agentes_transito_tokens')
                ->where('revocado_en IS NULL', null, false)->where('expira_en <', $ahora)->countAllResults();
            $res['sesiones_revocadas'] = (int) $this->db->table('agentes_transito_tokens')
                ->where('revocado_en IS NOT NULL', null, false)->countAllResults();

            $res['agentes_en_turno'] = array_map(static fn (array $r): array => [
                'placa'      => (string) $r['placa'],
                'nombre'     => (string) ($r['nombre_completo'] ?: $r['placa']),
                'ultimo_uso' => (string) ($r['ultimo_uso_en'] ?? ''),
                'expira_en'  => (string) ($r['expira_en'] ?? ''),
            ], $this->db->table('agentes_transito_tokens')
                ->select('agentes_transito.placa as placa, agentes_transito.nombre_completo as nombre_completo, agentes_transito_tokens.ultimo_uso_en as ultimo_uso_en, agentes_transito_tokens.expira_en as expira_en')
                ->join('agentes_transito', 'agentes_transito.id = agentes_transito_tokens.agente_id')
                ->where('agentes_transito_tokens.revocado_en IS NULL', null, false)
                ->where('agentes_transito_tokens.expira_en >=', $ahora)
                ->orderBy('agentes_transito_tokens.expira_en', 'DESC')
                ->limit(12)->get()->getResultArray());

            $ultima = $this->db->table('agentes_transito_tokens')
                ->select('MAX(ultimo_uso_en) as ultima_actividad')->get()->getRowArray();
            $res['ultima_actividad_app'] = (string) ($ultima['ultima_actividad'] ?? '');
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::appTransito (sesiones): ' . $e->getMessage());
        }

        return $res;
    }

    /**
     * Verificaciones físicas: citas de inspección y su dictamen.
     *
     * Es el paso intermedio de los trámites que requieren revisión en sitio, así
     * que el administrador puede ver qué sigue en la agenda y qué cita quedó sin
     * capturar su resultado.
     *
     * @return array<string, int|list<array<string, int|string>>>
     */
    public function verificaciones(): array
    {
        return $this->seccion('verificaciones', fn (): array => $this->calcularVerificaciones());
    }

    private function calcularVerificaciones(): array
    {
        $res = ['total' => 0, 'pendientes' => 0, 'con_dictamen' => 0, 'vencidas_sin_dictamen' => 0,
            'por_resultado' => [], 'proximas' => [], 'rezagadas' => []];

        try {
            $res['total']        = (int) $this->db->table('verificaciones_fisicas')->countAllResults();
            // La misma regla que usa el listado de verificaciones: cuenta vacio y nulo
            // como «sin dictamen» para que la cifra y su enlace coincidan.
            $res['pendientes']   = (int) $this->db->table('verificaciones_fisicas')
                ->where(VerificacionFisicaModel::SIN_DICTAMEN_SQL, null, false)->countAllResults();
            $res['con_dictamen'] = $res['total'] - $res['pendientes'];
            $res['por_resultado'] = array_map(static fn (array $r): array => [
                'resultado' => ($r['resultado'] === null || $r['resultado'] === '') ? 'sin_dictamen' : (string) $r['resultado'],
                'total'     => (int) $r['total'],
            ], $this->db->table('verificaciones_fisicas')
                ->select('resultado, COUNT(*) as total')
                ->groupBy('resultado')->orderBy('total', 'DESC')->get()->getResultArray());
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::verificaciones: ' . $e->getMessage());

            return $res;
        }

        // Cada cita se une a su expediente para que la pantalla tenga un destino real.
        $mapear = static fn (array $r): array => [
            'solicitud_id' => (int) $r['solicitud_id'],
            'folio'        => (string) $r['folio'],
            'tramite'      => (string) $r['tramite'],
            'estatus'      => (string) $r['estatus'],
            'fecha_cita'   => (string) $r['fecha_cita'],
            'resultado'    => (string) ($r['resultado'] ?? ''),
        ];
        $columnas = 'verificaciones_fisicas.solicitud_id as solicitud_id, solicitudes.folio as folio,
            solicitudes.tramite as tramite, solicitudes.estatus as estatus,
            verificaciones_fisicas.fecha_cita as fecha_cita, verificaciones_fisicas.resultado as resultado';
        $ahora = date('Y-m-d H:i:s');

        try {
            $res['proximas'] = array_map($mapear, $this->db->table('verificaciones_fisicas')
                ->select($columnas)
                ->join('solicitudes', 'solicitudes.id = verificaciones_fisicas.solicitud_id')
                ->where('verificaciones_fisicas.fecha_cita >=', $ahora)
                ->orderBy('verificaciones_fisicas.fecha_cita', 'ASC')->limit(8)->get()->getResultArray());
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::verificaciones (próximas): ' . $e->getMessage());
        }

        try {
            $res['rezagadas'] = array_map($mapear, $this->db->table('verificaciones_fisicas')
                ->select($columnas)
                ->join('solicitudes', 'solicitudes.id = verificaciones_fisicas.solicitud_id')
                ->where('verificaciones_fisicas.fecha_cita <', $ahora)
                ->where(VerificacionFisicaModel::SIN_DICTAMEN_SQL, null, false)
                ->orderBy('verificaciones_fisicas.fecha_cita', 'ASC')->limit(8)->get()->getResultArray());
            $res['vencidas_sin_dictamen'] = (int) $this->db->table('verificaciones_fisicas')
                ->where('fecha_cita <', $ahora)
                ->where(VerificacionFisicaModel::SIN_DICTAMEN_SQL, null, false)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::verificaciones (rezagadas): ' . $e->getMessage());
        }

        return $res;
    }

    /**
     * Estado de los catálogos que sostienen la operación.
     *
     * @return array<string, int|float|list<array<string, int|string>>>
     */
    public function catalogos(): array
    {
        return $this->seccion('catalogos', fn (): array => $this->calcularCatalogos());
    }

    private function calcularCatalogos(): array
    {
        $res = [
            'tarifas' => 0, 'tarifas_vigentes' => 0, 'concesiones' => 0, 'concesiones_por_estatus' => [],
            'formatos' => 0, 'formatos_faltantes' => [], 'convocatorias' => 0, 'convocatorias_por_estatus' => [],
            'infracciones_oficiales' => 0, 'parametros_sistema' => 0, 'parametros_app' => 0,
            'uma_vigente' => 0.0, 'uma_actualizado' => '', 'checklist_total' => 0, 'checklist_sin_mapear' => 0,
        ];

        try {
            $res['tarifas'] = (int) $this->db->table('tarifas')->countAllResults();
            $hoy = date('Y-m-d');
            $res['tarifas_vigentes'] = (int) $this->db->table('tarifas')
                ->where('vigente_desde <=', $hoy)->where('vigente_hasta >=', $hoy)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::catalogos (tarifas): ' . $e->getMessage());
        }

        try {
            $res['concesiones'] = (int) $this->db->table('concesiones')->countAllResults();
            $res['concesiones_por_estatus'] = array_map(static fn (array $r): array => [
                'estatus' => (string) ($r['estatus'] ?? 'sin dato'),
                'total'   => (int) $r['total'],
            ], $this->db->table('concesiones')->select('estatus, COUNT(*) as total')->groupBy('estatus')->orderBy('total', 'DESC')->get()->getResultArray());
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::catalogos (concesiones): ' . $e->getMessage());
        }

        try {
            $res['formatos'] = (int) $this->db->table('formatos_tramite')->where('activo', 1)->countAllResults();
            $conFormato = array_column(
                $this->db->table('formatos_tramite')->select('tramite')->where('activo', 1)->get()->getResultArray(),
                'tramite'
            );
            $faltan = [];
            foreach ($this->tramitesActivos() as $t) {
                if (! in_array($t, $conFormato, true)) {
                    $faltan[] = ['tramite' => $t, 'nombre' => tramite_nombre($t)];
                }
            }
            $res['formatos_faltantes'] = $faltan;
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::catalogos (formatos): ' . $e->getMessage());
        }

        try {
            $res['convocatorias'] = (int) $this->db->table('convocatorias')->countAllResults();
            $res['convocatorias_por_estatus'] = array_map(static fn (array $r): array => [
                'estatus' => (string) ($r['estatus'] ?? 'sin dato'),
                'total'   => (int) $r['total'],
            ], $this->db->table('convocatorias')->select('estatus, COUNT(*) as total')->groupBy('estatus')->get()->getResultArray());
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::catalogos (convocatorias): ' . $e->getMessage());
        }

        // Configuración que fija el dinero: el catálogo oficial, la UMA en curso y
        // las reglas de la app. Si falta la UMA, ninguna multa se puede calcular.
        try {
            $res['infracciones_oficiales'] = (int) $this->db->table('catalogo_infracciones')->countAllResults();
            $res['parametros_sistema']     = (int) $this->db->table('parametros_sistema')->countAllResults();
            $res['parametros_app']         = (int) $this->db->table('parametros_app')->countAllResults();

            $uma = $this->db->table('parametros_sistema')
                ->select('clave, valor, actualizado_en')->where('clave', 'valor_uma_vigente')->get()->getRowArray();
            if (is_array($uma) && is_numeric($uma['valor'] ?? null)) {
                $res['uma_vigente']     = (float) $uma['valor'];
                $res['uma_actualizado'] = (string) ($uma['actualizado_en'] ?? '');
            }
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::catalogos (configuración): ' . $e->getMessage());
        }

        try {
            $res['checklist_total']     = (int) $this->db->table('boleta_checklist_item')->countAllResults();
            $res['checklist_sin_mapear'] = (int) $this->db->table('boleta_checklist_item')
                ->where('catalogo_infraccion_id IS NULL', null, false)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::catalogos (checklist): ' . $e->getMessage());
        }

        return $res;
    }

    /**
     * Cosas que piden acción hoy, ordenadas por urgencia.
     *
     * @return list<array<string, int|string>>
     */
    public function alertas(): array
    {
        $tiempos   = $this->tiempos();
        $vigencias = $this->vigencias();
        $docs      = $this->documentos();
        $transito  = $this->appTransito();
        $verif     = $this->verificaciones();
        $catalogos = $this->catalogos();

        $sinPago = 0;
        $enRevision = 0;
        foreach ($this->solicitudes() as $f) {
            if ($f['estatus'] === 'Pago pendiente') {
                $sinPago++;
            }
            if (in_array((string) $f['estatus'], ['En revisión', 'En validación', 'Prevención'], true)) {
                $enRevision++;
            }
        }

        $alertas = [];
        if ($tiempos['total_demoradas'] > 0) {
            $alertas[] = ['nivel' => 'danger', 'rotulo' => 'Solicitudes sin movimiento hace más de 7 días', 'total' => $tiempos['total_demoradas'], 'url' => '/admin/desglose#flujo'];
        }
        if ($sinPago > 0) {
            $alertas[] = ['nivel' => 'warning', 'rotulo' => 'Pendientes de cobro', 'total' => $sinPago, 'url' => '/admin/solicitudes?estatus=Pago%20pendiente'];
        }
        if ($vigencias['por_vencer_30'] > 0) {
            $alertas[] = ['nivel' => 'warning', 'rotulo' => 'Permisos por vencer en 30 días', 'total' => $vigencias['por_vencer_30'], 'url' => '/admin/desglose#vigencias'];
        }
        if ($vigencias['vencidas'] > 0) {
            $alertas[] = ['nivel' => 'danger', 'rotulo' => 'Vigencias vencidas con expediente abierto', 'total' => $vigencias['vencidas'], 'url' => '/admin/solicitudes?estatus=Vencido'];
        }
        if ($docs['por_validar'] > 0) {
            $alertas[] = ['nivel' => 'info', 'rotulo' => 'Documentos sin validar', 'total' => $docs['por_validar'], 'url' => '/admin/desglose#documentos'];
        }
        if ($enRevision > 0) {
            $alertas[] = ['nivel' => 'info', 'rotulo' => 'En revisión o en dictamen', 'total' => $enRevision, 'url' => '/admin/solicitudes?estatus=En%20revisi%C3%B3n'];
        }
        foreach ($transito['estados'] as $estado) {
            if ($estado['estado'] === 'recibida' && $estado['total'] > 0) {
                $alertas[] = ['nivel' => 'info', 'rotulo' => 'Boletas recibidas sin procesar', 'total' => $estado['total'], 'url' => '/admin/app-transito/boletas?estado=' . rawurlencode((string) $estado['estado'])];
            }
        }
        foreach ($catalogos['formatos_faltantes'] as $falta) {
            $alertas[] = ['nivel' => 'muted', 'rotulo' => 'Sin formato oficial: ' . $falta['nombre'], 'total' => 1, 'url' => '/admin/formatos?estado=sin'];
        }
        if ($verif['vencidas_sin_dictamen'] > 0) {
            $alertas[] = ['nivel' => 'danger', 'rotulo' => 'Citas de verificación física sin dictamen capturado', 'total' => $verif['vencidas_sin_dictamen'], 'url' => '/admin/desglose#verificaciones'];
        }
        // Sin la UMA en curso el catálogo de infracciones no puede convertir a pesos.
        if ((float) $catalogos['uma_vigente'] <= 0) {
            $alertas[] = ['nivel' => 'danger', 'rotulo' => 'No hay valor de UMA vigente cargado', 'total' => 1, 'url' => '/admin/desglose#catalogos'];
        }
        if ($catalogos['checklist_sin_mapear'] > 0) {
            $alertas[] = ['nivel' => 'warning', 'rotulo' => 'Casillas de boleta sin fundamento asignado en el catálogo', 'total' => $catalogos['checklist_sin_mapear'], 'url' => '/admin/desglose#catalogos'];
        }

        return $alertas;
    }

    /**
     * Códigos de trámite habilitados según los feature flags.
     *
     * @return list<string>
     */
    public function tramitesActivos(): array
    {
        $tramites = ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-04', 'UR-TT-T-05'];
        if (FeatureFlags::habilitarUrTtT06()) {
            $tramites[] = 'UR-TT-T-06';
        }
        $tramites[] = 'UR-TT-T-07';

        return $tramites;
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /**
     * Todas las solicitudes con las columnas que usan los calculos.
     *
     * @return list<array<string, ?string>>
     */
    private function solicitudes(): array
    {
        if ($this->filasSolicitudes !== null) {
            return $this->filasSolicitudes;
        }

        try {
            $this->filasSolicitudes = $this->db->table('solicitudes')
                ->select('id, folio, tramite, estatus, ciudadano_id, monto, fecha_solicitud, fecha_resolucion, fecha_pago, fecha_vigencia_fin')
                ->orderBy('id', 'DESC')
                ->limit(20000)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::solicitudes: ' . $e->getMessage());
            $this->filasSolicitudes = [];
        }

        return $this->filasSolicitudes;
    }

    private function esPresencial(int $solicitudId): bool
    {
        if ($this->presenciales === null) {
            $this->presenciales = [];
            try {
                $filas = $this->db->table('solicitud_datos')
                    ->select('solicitud_id')
                    ->where('clave', 'origen')
                    ->where('valor', 'presencial')
                    ->get()
                    ->getResultArray();
                foreach ($filas as $f) {
                    $this->presenciales[(int) $f['solicitud_id']] = true;
                }
            } catch (\Throwable $e) {
                log_message('error', 'DesgloseSistema::esPresencial: ' . $e->getMessage());
            }
        }

        return isset($this->presenciales[$solicitudId]);
    }

    private function conteo(string $tabla): int
    {
        try {
            return (int) $this->db->table($tabla)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::conteo(' . $tabla . '): ' . $e->getMessage());

            return 0;
        }
    }

    private function conteoDesde(string $tabla, string $columna, string $desde): int
    {
        try {
            return (int) $this->db->table($tabla)->where($columna . ' >=', $desde)->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::conteoDesde(' . $tabla . '): ' . $e->getMessage());

            return 0;
        }
    }

    private function conteoVigenciaHasta(\DateTimeImmutable $hasta): int
    {
        try {
            return (int) $this->db->table('solicitudes')
                ->where('estatus', 'Vigente')
                ->where('fecha_vigencia_fin IS NOT NULL', null, false)
                ->where('fecha_vigencia_fin <=', $hasta->format('Y-m-d'))
                ->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'DesgloseSistema::conteoVigenciaHasta: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * YYYY-MM a partir de un timestamp guardado como texto; '' si no es usable.
     */
    private function mes(?string $fecha): string
    {
        if ($fecha === null || strlen($fecha) < 7) {
            return '';
        }
        $mes = substr($fecha, 0, 7);

        return preg_match('/^\d{4}-\d{2}$/', $mes) === 1 ? $mes : '';
    }

    private function etiquetaMes(string $yyyymm): string
    {
        $nombres = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun',
            7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
        $partes = explode('-', $yyyymm);
        if (count($partes) !== 2 || ! ctype_digit($partes[1])) {
            return $yyyymm;
        }

        return $nombres[(int) $partes[1]] . ' ' . $partes[0];
    }

    /**
     * Días transcurridos entre dos fechas; null si falta alguna o es inválida.
     */
    private function dias(?string $desde, ?string $hasta): ?float
    {
        if ($desde === null || $hasta === null || strlen($desde) < 10 || strlen($hasta) < 10) {
            return null;
        }
        try {
            $a = new \DateTimeImmutable(substr($desde, 0, 10));
            $b = new \DateTimeImmutable(substr($hasta, 0, 10));
        } catch (\Throwable) {
            return null;
        }

        return (float) $a->diff($b)->days;
    }
}
