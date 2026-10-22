<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\DocumentoModel;
use App\Models\HistorialEstatusModel;
use App\Models\AuditoriaModel;
use App\Models\UserModel;
use App\Models\FormatoTramiteModel;
use App\Libraries\EstadoSolicitudService;
use Config\Services;
use DateTime;

class AdminController extends Controller
{
    public function dashboard()
    {
        $solicitudModel = new SolicitudModel();
        $historialModel = new HistorialEstatusModel();
        $userModel = new UserModel();

        $total = $solicitudModel->countAllResults();
        $recibido = $solicitudModel->where('estatus', 'Recibido')->countAllResults();
        $enRevision = $solicitudModel->where('estatus', 'En revisión')->countAllResults();
        $pendientes = $recibido + $enRevision;
        $pagoPendiente = $solicitudModel->where('estatus', 'Pago pendiente')->countAllResults();
        $pagado = $solicitudModel->where('estatus', 'Pagado')->countAllResults();
        $vigente = $solicitudModel->where('estatus', 'Vigente')->countAllResults();
        $pagadosVigentes = $pagado + $vigente;
        $vencido = $solicitudModel->where('estatus', 'Vencido')->countAllResults();
        $concluido = $solicitudModel->where('estatus', 'Concluido')->countAllResults();
        $vencidosConcluidos = $vencido + $concluido;
        $rechazados = $solicitudModel->where('estatus', 'Rechazado')->countAllResults();

        $hoy = new DateTime();
        $hoyInicio = $hoy->format('Y-m-d') . ' 00:00:00';
        $hoyFin = $hoy->format('Y-m-d') . ' 23:59:59';
        $montoHoy = (float)$solicitudModel
            ->where('estatus', 'Pagado')
            ->where('fecha_pago >=', $hoyInicio)
            ->where('fecha_pago <=', $hoyFin)
            ->selectSum('monto')
            ->get()
            ->getRow()
            ->monto ?? 0.0;

        // Volumetría y dinero por trámite: el mismo agregador que usa el desglose.
        $desglose    = new \App\Libraries\DesgloseSistema();
        $filasTramite = $desglose->porTramite();
        $porTramite  = [];
        foreach ($filasTramite as $fila) {
            $porTramite[$fila['tramite']] = (int) $fila['total'];
        }

        $estadisticas = [
            'total'              => $total,
            'pendientes'         => $pendientes,
            'recibido'           => $recibido,
            'en_revision'        => $enRevision,
            'pago_pendiente'     => $pagoPendiente,
            'pagados_vigentes'   => $pagadosVigentes,
            'pagado'             => $pagado,
            'vigente'            => $vigente,
            'vencidos_concluidos' => $vencidosConcluidos,
            'vencido'            => $vencido,
            'concluido'          => $concluido,
            'rechazados'         => $rechazados,
            'monto_hoy'          => $montoHoy,
            'por_tramite'        => $porTramite,
        ];

        $ultimasActividadesRaw = $historialModel
            ->orderBy('fecha', 'DESC')
            ->limit(10)
            ->findAll();

        $comoObjeto = static fn (array|object|null $registro): ?object => is_array($registro)
            ? (object) $registro
            : $registro;

        $ultimasActividades = [];
        foreach ($ultimasActividadesRaw as $h) {
            $usuario = $userModel->find($h->usuario_id);
            $solicitud = $solicitudModel->find($h->solicitud_id);
            $ultimasActividades[] = [
                'historial' => $comoObjeto($h),
                'usuario'   => $comoObjeto($usuario),
                'solicitud' => $comoObjeto($solicitud),
            ];
        }

        return view('admin/dashboard', [
            'estadisticas'       => $estadisticas,
            'ultimasActividades' => $ultimasActividades,
            'filasTramite'       => $filasTramite,
            'tramitesActivos'    => $desglose->tramitesActivos(),
            'alertas'            => $desglose->alertas(),
            'tiempos'            => $desglose->tiempos(),
            'hoy'                => $hoy->format('Y-m-d'),
        ]);
    }

    public function index()
    {
        return $this->listaSolicitudes();
    }

    public function auditoria()
    {
        $filtros = [
            'entidad' => trim((string) $this->request->getGet('entidad')),
            'accion' => trim((string) $this->request->getGet('accion')),
            'entidad_id' => trim((string) $this->request->getGet('entidad_id')),
            'usuario_id' => trim((string) $this->request->getGet('usuario_id')),
            'fecha_desde' => trim((string) $this->request->getGet('fecha_desde')),
            'fecha_hasta' => trim((string) $this->request->getGet('fecha_hasta')),
        ];

        $auditoriaModel = new AuditoriaModel();
        $registros = $auditoriaModel->buscar($filtros);

        // Resumen real de la tabla: da las etiquetas de filtro con su conteo, así
        // el administrador ve de una vez dónde se concentra la actividad.
        $resumen = (new \App\Libraries\DesgloseSistema())->auditoria();

        return view('admin/auditoria', [
            'registros' => $registros,
            'pager' => $auditoriaModel->pager,
            'filtros' => $filtros,
            'resumen' => $resumen,
        ]);
    }

    /** Columnas por las que se puede ordenar el listado. */
    private const ORDENES_LISTADO = [
        'fecha_solicitud' => 'Fecha',
        'folio'           => 'Folio',
        'tramite'         => 'Trámite',
        'estatus'         => 'Estatus',
        'monto'           => 'Monto',
    ];

    /**
     * Columna de fecha sobre la que actúan los filtros mes/desde/hasta. Tenerla
     * explícita permite que una tarjeta de "cobros de hoy" abra exactamente los
     * registros que sumó, en lugar de confundirlos con la fecha de solicitud.
     */
    private const COLUMNAS_FECHA = [
        'solicitud'  => 'solicitudes.fecha_solicitud',
        'pago'       => 'solicitudes.fecha_pago',
        'resolucion' => 'solicitudes.fecha_resolucion',
    ];

    public function listaSolicitudes()
    {
        $filtros = $this->filtrosListado();

        // Tres consultas con las mismas condiciones: subtotal de dinero, conteo y
        // página. Se usan modelos independientes porque paginate() limpia el
        // estado del builder al terminar.
        $resumen = new SolicitudModel();
        $this->aplicarFiltrosListado($resumen, $filtros);
        $montoFiltrado = (float) ($resumen->selectSum('monto')->get()->getRow()->monto ?? 0);

        $conteo = new SolicitudModel();
        $this->aplicarFiltrosListado($conteo, $filtros);
        $totalFiltrado = $conteo->countAllResults();

        $listado = new SolicitudModel();
        $this->aplicarFiltrosListado($listado, $filtros);
        $listado->orderBy('solicitudes.' . $filtros['orden'], $filtros['direccion']);
        $listado->orderBy('solicitudes.id', 'DESC');
        $solicitudes = $listado->paginate(20);

        return view('admin/solicitudes_lista', [
            'solicitudes'   => $solicitudes,
            'pager'         => $listado->pager,
            'filtros'       => $filtros,
            'montos'        => ['filtrado' => $montoFiltrado, 'total' => $totalFiltrado],
            'camposOrden'   => self::ORDENES_LISTADO,
        ]);
    }

    /**
     * Filtros del listado desde la consulta HTTP, validados uno por uno.
     *
     * @return array<string, string>
     */
    private function filtrosListado(): array
    {
        $mes = trim((string) $this->request->getGet('mes'));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) !== 1) {
            $mes = '';
        }
        $fechas = [];
        foreach (['desde', 'hasta'] as $campo) {
            $valor = trim((string) $this->request->getGet($campo));
            $fechas[$campo] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) === 1 ? $valor : '';
        }

        $orden = (string) $this->request->getGet('orden');
        if (! array_key_exists($orden, self::ORDENES_LISTADO)) {
            $orden = 'fecha_solicitud';
        }
        $direccion = strtolower((string) $this->request->getGet('dir')) === 'asc' ? 'ASC' : 'DESC';
        $campoFecha = (string) $this->request->getGet('fecha_campo');
        if (! array_key_exists($campoFecha, self::COLUMNAS_FECHA)) {
            $campoFecha = 'solicitud';
        }

        return [
            'tramite'   => trim((string) ($this->request->getGet('tramite') ?? '')),
            'estatus'   => trim((string) ($this->request->getGet('estatus') ?? '')),
            'q'         => trim((string) ($this->request->getGet('q') ?? '')),
            'origen'    => in_array((string) $this->request->getGet('origen'), ['presencial', 'digital'], true) ? (string) $this->request->getGet('origen') : '',
            'pago'      => in_array((string) $this->request->getGet('pago'), ['cobrado', 'sin_cobrar'], true) ? (string) $this->request->getGet('pago') : '',
            'resuelto'  => in_array((string) $this->request->getGet('resuelto'), ['si', 'no'], true) ? (string) $this->request->getGet('resuelto') : '',
            'mes'       => $mes,
            'desde'     => $fechas['desde'],
            'hasta'     => $fechas['hasta'],
            'fecha_campo' => $campoFecha,
            'orden'     => $orden,
            'direccion' => $direccion,
        ];
    }

    /**
     * Traduce los filtros validados a condiciones del modelo.
     *
     * @param array<string, string> $f
     */
    private function aplicarFiltrosListado(SolicitudModel $modelo, array $f): void
    {
        if ($f['tramite'] !== '') {
            $modelo->where('solicitudes.tramite', $f['tramite']);
        }
        if ($f['estatus'] !== '') {
            $modelo->where('solicitudes.estatus', $f['estatus']);
        }
        if ($f['q'] !== '') {
            $qLike = '%' . $f['q'] . '%';
            $modelo
                ->groupStart()
                    ->like('solicitudes.folio', $f['q'])
                    ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'rfc' AND sd.valor LIKE " . $modelo->escape($qLike) . ")")
                    ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'razon_social_o_nombre' AND sd.valor LIKE " . $modelo->escape($qLike) . ")")
                    ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'solicitante_nombre' AND sd.valor LIKE " . $modelo->escape($qLike) . ")")
                ->groupEnd();
        }
        // Canal de captura: ventanilla marca la solicitud con origen = presencial.
        if ($f['origen'] === 'presencial') {
            $modelo->where("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'origen' AND sd.valor = 'presencial')", null, false);
        } elseif ($f['origen'] === 'digital') {
            $modelo->where("NOT EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'origen' AND sd.valor = 'presencial')", null, false);
        }
        if ($f['pago'] === 'cobrado') {
            $modelo->where('solicitudes.fecha_pago IS NOT NULL', null, false);
        } elseif ($f['pago'] === 'sin_cobrar') {
            $modelo->where('solicitudes.fecha_pago IS NULL', null, false);
        }
        if ($f['resuelto'] === 'si') {
            $modelo->where('solicitudes.fecha_resolucion IS NOT NULL', null, false);
        } elseif ($f['resuelto'] === 'no') {
            $modelo->where('solicitudes.fecha_resolucion IS NULL', null, false);
        }
        if ($f['mes'] !== '' || $f['desde'] !== '' || $f['hasta'] !== '') {
            $columna = self::COLUMNAS_FECHA[$f['fecha_campo']];
            if ($f['mes'] !== '') {
                $inicio = $f['mes'] . '-01 00:00:00';
                $fin    = date('Y-m-01 00:00:00', strtotime($inicio . ' +1 month'));
                $modelo->where($columna . ' >=', $inicio);
                $modelo->where($columna . ' <', $fin);
            }
            if ($f['desde'] !== '') {
                $modelo->where($columna . ' >=', $f['desde'] . ' 00:00:00');
            }
            if ($f['hasta'] !== '') {
                $modelo->where($columna . ' <=', $f['hasta'] . ' 23:59:59');
            }
        }
    }

    public function verSolicitud(string $folio)
    {
        $solicitudModel = new SolicitudModel();
        $solicitudDatoModel = new SolicitudDatoModel();
        $documentoModel = new DocumentoModel();
        $historialModel = new HistorialEstatusModel();
        $userModel = new UserModel();

        $solicitud = $solicitudModel->findByFolio($folio);
        if (!$solicitud) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $datosSolicitud = $solicitudDatoModel->porSolicitudAgrupado($solicitud->id);
        $documentos = $documentoModel->porSolicitud($solicitud->id);
        $historial = $historialModel->porSolicitud($solicitud->id);

        $historialCompleto = [];
        foreach ($historial as $h) {
            $usuario = $userModel->find($h->usuario_id);
            $historialCompleto[] = [
                'historial' => $h,
                'usuario'   => $usuario,
            ];
        }

        $ciudadano = null;
        if (!empty($solicitud->ciudadano_id)) {
            $ciudadano = $userModel->find($solicitud->ciudadano_id);
        }

        $estatusSiguientes = EstadoSolicitudService::TRANSICIONES_VALIDAS[$solicitud->tramite][$solicitud->estatus] ?? [];
        $verificacion = (new \App\Models\VerificacionFisicaModel())->primerPorSolicitud((int)$solicitud->id);

        return view('admin/solicitudes_ver', [
            'solicitud'         => $solicitud,
            'datosSolicitud'    => $datosSolicitud,
            'documentos'        => $documentos,
            'historial'         => $historialCompleto,
            'ciudadano'         => $ciudadano,
            'estatusSiguientes' => $estatusSiguientes,
            'verificacion'      => $verificacion,
        ]);
    }

    public function registrarDictamenUr02(int $solicitudId)
    {
        $session = Services::session();
        $userId = (int) $session->get('user_id');
        $resultado = (string) $this->request->getPost('resultado');
        $observaciones = trim((string) $this->request->getPost('observaciones'));

        if (! in_array($resultado, ['aprobado', 'rechazado'], true)) {
            return redirect()->back()->with('error', 'El resultado debe ser aprobado o rechazado.');
        }
        if ($observaciones === '') {
            return redirect()->back()->with('error', 'Las observaciones son obligatorias para emitir el dictamen.');
        }

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);
        if (! $solicitud || $solicitud->tramite !== 'UR-TT-T-02') {
            return redirect()->back()->with('error', 'Solicitud no válida para verificación de despintado.');
        }

        $verificacionModel = new \App\Models\VerificacionFisicaModel();
        $verificacion = $verificacionModel->primerPorSolicitud($solicitudId);
        if ($verificacion) {
            $verificacionModel->update($verificacion->id, [
                'resultado'     => $resultado,
                'observaciones' => $observaciones,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        } else {
            $verificacionModel->insert([
                'solicitud_id'  => $solicitudId,
                'fecha_cita'    => date('Y-m-d H:i:s'),
                'resultado'     => $resultado,
                'observaciones' => $observaciones,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $nuevoEstatus = ($resultado === 'aprobado') ? 'Verificado' : 'Rechazado';
        $estadoService = new EstadoSolicitudService();
        $estadoService->cambiarEstatus($solicitudId, $nuevoEstatus, $userId, 'Dictamen de inspección física: ' . $observaciones);

        return redirect()->to('/admin/solicitudes/ver/' . $solicitud->folio)
            ->with('message', '¡Dictamen de verificación física registrado con éxito con estatus: ' . $nuevoEstatus . '!');
    }

    public function guardarEvaluacionUr04(int $solicitudId)
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);
        if ($solicitud === null || $solicitud->tramite !== 'UR-TT-T-04') {
            return redirect()->back()->with('error', 'Solicitud no válida para evaluación UR-04.');
        }

        $datosModel = new SolicitudDatoModel();
        $datos = $datosModel->porSolicitudAgrupado($solicitudId);
        $campos = [
            'observaciones_revision',
            'resultado_estudio_tecnico',
            'observaciones_estudio_tecnico',
            'resultado_inspeccion',
            'observaciones_inspeccion',
            'resultado_revista_mecanica',
            'observaciones_revista_mecanica',
            'seguro_validado',
            'numero_poliza',
            'aseguradora',
            'observaciones_seguro',
        ];

        foreach ($campos as $campo) {
            $datos[$campo] = trim((string) $this->request->getPost($campo));
        }
        $datosModel->guardarDatos($solicitudId, $datos);

        return redirect()->to('/admin/solicitudes/' . $solicitud->folio)
            ->with('message', 'Evaluación provisional de UR-04 guardada.');
    }

    public function validarCierreCalleUr05(int $solicitudId)
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);
        if ($solicitud === null || $solicitud->tramite !== 'UR-TT-T-05') {
            return redirect()->back()->with('error', 'Solicitud no válida para validación UR-05.');
        }

        $datosModel = new SolicitudDatoModel();
        $datos = $datosModel->porSolicitudAgrupado($solicitudId);
        $criterios = [
            'afluencia_baja' => 'Criterio: afluencia vehicular baja',
            'sin_transporte_publico' => 'Criterio: sin transporte público',
            'horario_no_entorpece' => 'Criterio: horario sin afectación al tráfico',
        ];
        $aprobado = true;
        foreach ($criterios as $campo => $comentario) {
            $cumple = $this->request->getPost($campo) === '1';
            $datos[$campo] = $cumple ? '1' : '0';
            $aprobado = $aprobado && $cumple;
        }
        $datos['observaciones_validacion'] = trim((string) $this->request->getPost('observaciones_validacion'));
        $datosModel->guardarDatos($solicitudId, $datos);

        $estadoService = new EstadoSolicitudService();
        $usuarioId = (int) session('user_id');
        $comentario = $datos['observaciones_validacion'] !== ''
            ? $datos['observaciones_validacion']
            : ($aprobado ? 'Criterios de cierre validados.' : 'No se cumplen los criterios de seguridad vial.');
        if ($aprobado && $solicitud->estatus === 'Recibido' && ! $estadoService->cambiarEstatus($solicitudId, 'En validación', $usuarioId, 'Solicitud recibida para validación inmediata.')) {
            return redirect()->back()->with('error', 'No se pudo iniciar la validación de UR-05.');
        }
        $nuevoEstatus = $aprobado ? 'Pago pendiente' : 'Rechazado';
        if (! $estadoService->cambiarEstatus($solicitudId, $nuevoEstatus, $usuarioId, $comentario)) {
            return redirect()->back()->with('error', 'No se pudo aplicar la validación desde el estado actual.');
        }

        return redirect()->to('/admin/solicitudes/' . $solicitud->folio)
            ->with('message', 'Validación de criterios UR-05 registrada.');
    }

    public function cambiarEstatus(int $solicitudId)
    {
        $solicitudModel = new SolicitudModel();
        $estadoService = new EstadoSolicitudService();

        $nuevoEstatus = $this->request->getPost('nuevo_estatus') ?? '';
        $comentario = $this->request->getPost('comentario') ?? null;

        $userId = session('user_id');
        if (!$userId) {
            return redirect()->back()->with('errors', ['Sesión expirada']);
        }

        $resultado = $estadoService->cambiarEstatus($solicitudId, $nuevoEstatus, (int)$userId, $comentario);

        $solicitud = $solicitudModel->find($solicitudId);

        if (!$resultado) {
            return redirect()->back()->with('errors', ['No se pudo cambiar estatus: transición inválida o falta comentario']);
        }

        return redirect()->to(site_url('admin/solicitudes/' . $solicitud->folio))->with('message', 'Estatus actualizado exitosamente');
    }

    public function descargarDocumento(int $documentoId)
    {
        $documentoModel = new DocumentoModel();
        $doc = $documentoModel->find($documentoId);
        if (!$doc) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $storage = new \App\Libraries\SupabaseStorage();
        $url = $storage->urlFirmada('documentos', $doc->ruta_interna);
        if ($url === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return redirect()->to($url);
    }

    /**
     * Marca o quita la revisión de un documento del expediente. La ventanilla
     * necesita dejar constancia de que el papel recibido coincide con lo que
     * el ciudadano subió al portal.
     */
    public function validarDocumento(int $documentoId)
    {
        $documentoModel = new DocumentoModel();
        $doc = $documentoModel->find($documentoId);
        if (!$doc) {
            return redirect()->back()->with('error', 'Documento no encontrado.');
        }

        $userId = (int) (Services::session()->get('user_id') ?? 0);
        $validado = (int) $doc->validado === 1 ? 0 : 1;

        $documentoModel->update($documentoId, ['validado' => $validado]);

        (new AuditoriaModel())->registrar('documentos', $documentoId, $validado === 1 ? 'documento_validado' : 'documento_validacion_revocada', $userId, [
            'solicitud_id'    => (int) $doc->solicitud_id,
            'tipo_documento'  => (string) $doc->tipo_documento,
            'nombre_original' => (string) $doc->nombre_original,
        ]);

        return redirect()->back()->with('message', $validado === 1
            ? 'Documento validado. El ciudadano puede continuar con su trámite.'
            : 'Se quitó la validación del documento.');
    }

    /**
     * Las convocatorias UR-01 (evaluación comparativa y designación de ganador)
     * viven en Admin\ConvocatoriasController, que además aporta el alta, edición
     * y baja que antes no existían.
     */

    public function formatos()
    {
        $formatoModel = new FormatoTramiteModel();
        $formatos = $formatoModel->todos();

        $mapa = [];
        foreach ($formatos as $f) {
            $mapa[$f->tramite] = $f;
        }

        $tramitesInfo = [
            'UR-TT-T-01' => 'Concesión de Transporte',
            'UR-TT-T-02' => 'Constancia de Despintado',
            'UR-TT-T-03' => 'Orden de Plaqueo',
            'UR-TT-T-04' => 'Permiso Eventual de Transporte',
            'UR-TT-T-05' => 'Permiso para Cierre de Calle',
            'UR-TT-T-06' => 'Cesión de Concesión',
            'UR-TT-T-07' => 'Permiso de Carga y Descarga',
        ];

        // Filtro rápido: permite ver de una vez qué trámites siguen sin formato oficial.
        $conFormato = count(array_intersect_key($tramitesInfo, $mapa));
        $sinFormato = count($tramitesInfo) - $conFormato;

        $estado = (string) ($this->request->getGet('estado') ?? '');
        if ($estado === 'con') {
            $tramitesInfo = array_intersect_key($tramitesInfo, $mapa);
        } elseif ($estado === 'sin') {
            $tramitesInfo = array_diff_key($tramitesInfo, $mapa);
        } else {
            $estado = '';
        }

        return view('admin/formatos', [
            'tramitesInfo' => $tramitesInfo,
            'mapa'         => $mapa,
            'estado'       => $estado,
            'conteos'      => ['con' => $conFormato, 'sin' => $sinFormato],
        ]);
    }

    /**
     * Abre el formato oficial cargado en un trámite para que la ventanilla lo
     * revise antes de entregarlo al ciudadano.
     */
    public function verFormato(string $tramite)
    {
        $formato = (new FormatoTramiteModel())->porTramite($tramite);
        if ($formato === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Este trámite no tiene formato oficial cargado.');
        }

        $storage = new \App\Libraries\SupabaseStorage();
        $url = $storage->urlFirmada('formatos', $formato->ruta_interna);
        if ($url === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('No se pudo obtener el archivo del formato.');
        }

        return redirect()->to($url);
    }

    public function subirFormato()
    {
        $session = Services::session();
        $userId = (int) $session->get('user_id');

        $tramite = $this->request->getPost('tramite');
        $tramitesValidos = ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-04', 'UR-TT-T-05', 'UR-TT-T-06', 'UR-TT-T-07'];

        if (! in_array($tramite, $tramitesValidos, true)) {
            return redirect()->back()->with('error', 'Trámite no válido.');
        }

        $file = $this->request->getFile('formato');
        if ($file === null || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Debes seleccionar un archivo.');
        }

        if (! in_array($file->getClientMimeType(), ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return redirect()->back()->with('error', 'El archivo debe ser PDF, JPG o PNG.');
        }

        if ($file->getSize() > 10485760) {
            return redirect()->back()->with('error', 'El archivo no debe exceder 10 MB.');
        }

        $nombreOriginal = $file->getName();
        $extension = $file->getClientExtension() ?: 'pdf';
        $nombreInterno = $tramite . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        $contenido = file_get_contents($file->getTempName());
        $storage = new \App\Libraries\SupabaseStorage();
        $ok = $storage->subir('formatos', $nombreInterno, $contenido, $file->getClientMimeType());
        if (! $ok) {
            return redirect()->back()->with('error', 'Error al subir el archivo a almacenamiento.');
        }

        $formatoModel = new FormatoTramiteModel();
        $existente = $formatoModel->where('tramite', $tramite)->first();

        $data = [
            'tramite'        => $tramite,
            'nombre'         => $this->request->getPost('nombre') ?: 'Formato de ' . $tramite,
            'descripcion'    => $this->request->getPost('descripcion') ?? null,
            'nombre_archivo' => $nombreOriginal,
            'ruta_interna'   => $nombreInterno,
            'mime_type'      => $file->getClientMimeType(),
            'tamano_bytes'   => $file->getSize(),
            'usuario_id'     => $userId,
            'activo'         => 1,
        ];

        if ($existente) {
            $storage->eliminar('formatos', [$existente->ruta_interna]);
            $formatoModel->update($existente->id, $data);
            $formatoId = (int) $existente->id;
            $accion = 'reemplazar';
        } else {
            $formatoModel->insert($data);
            $formatoId = (int) $formatoModel->getInsertID();
            $accion = 'crear';
        }

        if ($formatoId > 0) {
            (new AuditoriaModel())->registrar('formatos_tramite', $formatoId, $accion, $userId, [
                'tramite' => $tramite,
                'nombre_archivo' => $nombreOriginal,
                'mime_type' => $file->getClientMimeType(),
                'tamano_bytes' => $file->getSize(),
            ]);
        }

        return redirect()->to('/admin/formatos')->with('message', 'Formato de ' . $tramite . ' subido correctamente.');
    }

    public function eliminarFormato()
    {
        $tramite = $this->request->getPost('tramite');
        $formatoModel = new FormatoTramiteModel();
        $formato = $formatoModel->where('tramite', $tramite)->first();

        if ($formato) {
            $storage = new \App\Libraries\SupabaseStorage();
            $storage->eliminar('formatos', [$formato->ruta_interna]);
            $formatoModel->delete($formato->id);
            (new AuditoriaModel())->registrar('formatos_tramite', (int) $formato->id, 'eliminar', (int) session('user_id'), [
                'tramite' => $tramite,
                'nombre_archivo' => $formato->nombre_archivo,
            ]);
        }

        return redirect()->to('/admin/formatos')->with('message', 'Formato eliminado.');
    }
}
