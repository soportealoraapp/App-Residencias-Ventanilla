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
use App\Libraries\FeatureFlags;
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

        $porTramite = [];
        $tramites = ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-04', 'UR-TT-T-05'];
        if (FeatureFlags::habilitarUrTtT06()) {
            $tramites[] = 'UR-TT-T-06';
        }
        $tramites[] = 'UR-TT-T-07';
        foreach ($tramites as $t) {
            $porTramite[$t] = $solicitudModel->where('tramite', $t)->countAllResults();
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

        $ultimasActividades = [];
        foreach ($ultimasActividadesRaw as $h) {
            $usuario = $userModel->find($h->usuario_id);
            $solicitud = $solicitudModel->find($h->solicitud_id);
            $ultimasActividades[] = [
                'historial' => $h,
                'usuario'   => $usuario,
                'solicitud' => $solicitud,
            ];
        }

        return view('admin/dashboard', [
            'estadisticas'       => $estadisticas,
            'ultimasActividades' => $ultimasActividades,
        ]);
    }

    public function index()
    {
        return $this->listaSolicitudes();
    }

    public function listaSolicitudes()
    {
        $solicitudModel = new SolicitudModel();
        $solicitudDatoModel = new SolicitudDatoModel();

        $tramite = $this->request->getGet('tramite') ?? '';
        $estatus = $this->request->getGet('estatus') ?? '';
        $q = $this->request->getGet('q') ?? '';

        if ($tramite !== '') {
            $solicitudModel->where('solicitudes.tramite', $tramite);
        }
        if ($estatus !== '') {
            $solicitudModel->where('solicitudes.estatus', $estatus);
        }
        if ($q !== '') {
            $qLike = '%' . $q . '%';
            $solicitudModel
                ->groupStart()
                    ->like('solicitudes.folio', $q)
                    ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'rfc' AND sd.valor LIKE " . $solicitudModel->escape($qLike) . ")")
                    ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'razon_social_o_nombre' AND sd.valor LIKE " . $solicitudModel->escape($qLike) . ")")
                    ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave = 'solicitante_nombre' AND sd.valor LIKE " . $solicitudModel->escape($qLike) . ")")
                ->groupEnd();
        }

        $solicitudModel->orderBy('solicitudes.fecha_solicitud', 'DESC');
        $solicitudes = $solicitudModel->paginate(20);
        $pager = $solicitudModel->pager;

        $filtros = [
            'tramite' => $tramite,
            'estatus' => $estatus,
            'q'       => $q,
        ];

        return view('admin/solicitudes_lista', [
            'solicitudes' => $solicitudes,
            'pager'       => $pager,
            'filtros'     => $filtros,
        ]);
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

        $rutaCompleta = WRITEPATH . 'uploads/documentos/' . $doc->ruta_interna;
        if (!file_exists($rutaCompleta)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return Services::response()->download($rutaCompleta, $doc->nombre_original, true);
    }

    public function evaluacionConvocatoria(int $convocatoriaId)
    {
        try {
            $convocatoriaModel = new \App\Models\ConvocatoriaModel();
            $convocatoria = $convocatoriaModel->find($convocatoriaId);
            
            if (!$convocatoria) {
                $convocatoria = $convocatoriaModel->orderBy('id', 'DESC')->first();
            }

            if (!$convocatoria) {
                return view('admin/convocatorias/sin_convocatoria');
            }

            $solicitudModel = new SolicitudModel();
            $solicitudDatoModel = new SolicitudDatoModel();

            $solicitudesRaw = $solicitudModel->where('convocatoria_id', $convocatoria->id)->findAll();
            $solicitudes = [];
            foreach ($solicitudesRaw as $sol) {
                $datos = $solicitudDatoModel->porSolicitudAgrupado((int)$sol->id);
                $solicitudes[] = [
                    'solicitud' => $sol,
                    'datos'     => $datos,
                ];
            }

            return view('admin/convocatorias/evaluacion', [
                'convocatoria' => $convocatoria,
                'solicitudes'  => $solicitudes,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'AdminController::evaluacionConvocatoria error: ' . $e->getMessage());
            return view('admin/convocatorias/sin_convocatoria');
        }
    }

    public function seleccionarGanadorConvocatoria(int $convocatoriaId)
    {
        $session = Services::session();
        $userId = (int) $session->get('user_id');
        $solicitudId = (int) $this->request->getPost('solicitud_id');

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);
        if (! $solicitud || (int)$solicitud->convocatoria_id !== $convocatoriaId) {
            return redirect()->back()->with('error', 'Solicitud no encontrada para esta convocatoria.');
        }

        $participantes = $solicitudModel->where('convocatoria_id', $convocatoriaId)->findAll();
        $estadoService = new EstadoSolicitudService();

        foreach ($participantes as $part) {
            $partId = (int) $part->id;
            if ($partId === $solicitudId) {
                $estadoService->cambiarEstatus($partId, 'Seleccionado', $userId, 'Dictamen comparativo: Seleccionado como GANADOR de la Convocatoria #' . $convocatoriaId);
            } else {
                $estadoService->cambiarEstatus($partId, 'No seleccionado', $userId, 'Dictamen comparativo: No seleccionado en Convocatoria #' . $convocatoriaId);
            }
        }

        return redirect()->to('/admin/convocatorias/' . $convocatoriaId . '/evaluacion')
            ->with('message', '¡Participante seleccionado exitosamente como GANADOR de la Convocatoria!');
    }

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

        return view('admin/formatos', [
            'tramitesInfo' => $tramitesInfo,
            'mapa'         => $mapa,
        ]);
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

        $directorio = WRITEPATH . 'uploads/formatos/';
        if (! is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $nombreOriginal = $file->getName();
        $extension = $file->getClientExtension() ?: 'pdf';
        $nombreInterno = $tramite . '_' . bin2hex(random_bytes(8)) . '.' . $extension;

        $file->move($directorio, $nombreInterno);

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
            $rutaVieja = $directorio . $existente->ruta_interna;
            if (file_exists($rutaVieja)) {
                unlink($rutaVieja);
            }
            $formatoModel->update($existente->id, $data);
        } else {
            $formatoModel->insert($data);
        }

        return redirect()->to('/admin/formatos')->with('message', 'Formato de ' . $tramite . ' subido correctamente.');
    }

    public function eliminarFormato()
    {
        $tramite = $this->request->getPost('tramite');
        $formatoModel = new FormatoTramiteModel();
        $formato = $formatoModel->where('tramite', $tramite)->first();

        if ($formato) {
            $ruta = WRITEPATH . 'uploads/formatos/' . $formato->ruta_interna;
            if (file_exists($ruta)) {
                unlink($ruta);
            }
            $formatoModel->delete($formato->id);
        }

        return redirect()->to('/admin/formatos')->with('message', 'Formato eliminado.');
    }
}
