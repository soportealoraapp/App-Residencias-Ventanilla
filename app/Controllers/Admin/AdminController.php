<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\DocumentoModel;
use App\Models\HistorialEstatusModel;
use App\Models\AuditoriaModel;
use App\Models\UserModel;
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
        $tramites = ['UR-TT-T-07'];
        if (FeatureFlags::habilitarUrTtT06()) {
            $tramites[] = 'UR-TT-T-06';
        }
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

        return view('admin/solicitudes_ver', [
            'solicitud'         => $solicitud,
            'datosSolicitud'    => $datosSolicitud,
            'documentos'        => $documentos,
            'historial'         => $historialCompleto,
            'ciudadano'         => $ciudadano,
            'estatusSiguientes' => $estatusSiguientes,
        ]);
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
}
