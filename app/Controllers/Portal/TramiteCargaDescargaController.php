<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\DocumentoModel;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\DocumentoUploader;
use App\Libraries\BanbajioMockGateway;
use App\Models\AuditoriaModel;
use Config\Services;
use DateTime;

class TramiteCargaDescargaController extends Controller
{
    protected SolicitudModel $solicitudModel;
    protected SolicitudDatoModel $solicitudDatoModel;
    protected DocumentoModel $documentoModel;
    protected AuditoriaModel $auditoriaModel;
    protected TarifarioService $tarifarioService;
    protected EstadoSolicitudService $estadoService;
    protected DocumentoUploader $uploader;
    protected BanbajioMockGateway $gateway;

    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
        $this->solicitudModel = new SolicitudModel();
        $this->solicitudDatoModel = new SolicitudDatoModel();
        $this->documentoModel = new DocumentoModel();
        $this->auditoriaModel = new AuditoriaModel();
        $this->tarifarioService = new TarifarioService();
        $this->estadoService = new EstadoSolicitudService();
        $this->uploader = new DocumentoUploader();
        $this->gateway = new BanbajioMockGateway();
    }

    public function formulario()
    {
        $periodos = [
            'dia'      => 'Día',
            'mes'      => 'Mes',
            'semestre' => 'Semestre',
            'anio'     => 'Año',
        ];
        $tiposSolicitante = [
            'particular' => 'Particular',
            'empresa'    => 'Empresa',
        ];

        return view('portal/tramites/carga_descarga_form', [
            'periodos'        => $periodos,
            'tiposSolicitante' => $tiposSolicitante,
        ]);
    }

    public function calcularMontoAjax()
    {
        $request = Services::request();
        $response = Services::response();
        $response->setContentType('application/json');

        $tipoSolicitante = $request->getPost('tipo_solicitante') ?? '';
        $periodo = $request->getPost('periodo') ?? '';
        $numCamiones = (int)($request->getPost('num_camiones') ?? 1);

        if (!in_array($tipoSolicitante, ['particular', 'empresa'], true)) {
            return $response->setJSON(['success' => false, 'mensaje' => 'Tipo de solicitante inválido']);
        }
        if (!in_array($periodo, ['dia', 'mes', 'semestre', 'anio'], true)) {
            return $response->setJSON(['success' => false, 'mensaje' => 'Periodo inválido']);
        }
        if ($numCamiones < 1 || $numCamiones > 15) {
            return $response->setJSON(['success' => false, 'mensaje' => 'Número de camiones inválido']);
        }

        try {
            $monto = $this->tarifarioService->calcularMontoUrTtT07($tipoSolicitante, $periodo, $numCamiones);
            if ($monto === null) {
                return $response->setJSON(['success' => false, 'mensaje' => 'No hay tarifa configurada para esta combinación.', csrf_token() => csrf_hash()]);
            }
            $placeholder = $this->tarifarioService->esPlaceholderT07($tipoSolicitante, $periodo, $numCamiones);
            return $response->setJSON([
                'success'   => true,
                'monto'     => $monto,
                'placeholder' => $placeholder,
                'mensaje'   => 'OK',
                csrf_token() => csrf_hash(),
            ]);
        } catch (\Exception $e) {
            return $response->setJSON(['success' => false, 'mensaje' => 'Error al calcular monto: ' . $e->getMessage(), csrf_token() => csrf_hash()]);
        }
    }

    public function guardar()
    {
        $request = Services::request();
        $session = Services::session();
        $userId = (int)$session->get('user_id');
        $validation = Services::validation();

        $tipoSolicitante = $request->getPost('tipo_solicitante') ?? 'particular';
        $esMudanza = !empty($request->getPost('es_mudanza'));
        $numCamiones = $request->getPost('num_camiones');

        $rules = [
            'tipo_solicitante'        => 'required|in_list[particular,empresa]',
            'razon_social_o_nombre'   => 'required|min_length[3]|max_length[180]',
            'rfc'                     => 'required|regex_match[/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/]',
            'domicilio_negocio'       => 'required|max_length[250]',
            'direccion_carga_descarga' => 'required|max_length[250]',
            'periodo'                 => 'required|in_list[dia,mes,semestre,anio]',
            'horario_inicio'          => 'required',
            'horario_fin'             => 'required',
            'es_mudanza'              => 'permit_empty',
        ];

        if ($tipoSolicitante === 'empresa') {
            $rules['num_camiones'] = 'required|is_natural_no_zero|less_than_equal_to[15]';
        } else {
            $rules['num_camiones'] = 'permit_empty';
        }

        $fileRules = [
            'uploaded[identificacion_oficial]',
            'max_size[identificacion_oficial,10240]',
            'mime_in[identificacion_oficial,image/png,image/jpeg,application/pdf]',
        ];
        $rules['identificacion_oficial'] = implode('|', $fileRules);

        $fileRules2 = [
            'uploaded[tarjeta_circulacion]',
            'max_size[tarjeta_circulacion,10240]',
            'mime_in[tarjeta_circulacion,image/png,image/jpeg,application/pdf]',
        ];
        $rules['tarjeta_circulacion'] = implode('|', $fileRules2);

        if (!$esMudanza) {
            $fileRules3 = [
                'uploaded[documento_carga_descarga]',
                'max_size[documento_carga_descarga,10240]',
                'mime_in[documento_carga_descarga,image/png,image/jpeg,application/pdf]',
            ];
            $rules['documento_carga_descarga'] = implode('|', $fileRules3);
        } else {
            $rules['documento_carga_descarga'] = 'permit_empty';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $folio = FolioGenerator::generar();
        $numCamionesFinal = $tipoSolicitante === 'empresa' ? (int)$numCamiones : 1;
        $monto = $this->tarifarioService->calcularMontoUrTtT07($tipoSolicitante, $request->getPost('periodo'), $numCamionesFinal);

        if ($monto === null) {
            return redirect()->back()->withInput()->with('error', 'No hay tarifa configurada para esta combinación.');
        }

        $solicitudId = $this->solicitudModel->insert([
            'folio'           => $folio,
            'tramite'         => 'UR-TT-T-07',
            'ciudadano_id'    => $userId,
            'estatus'         => 'Recibido',
            'monto'           => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if ($solicitudId === false) {
            return redirect()->back()->withInput()->with('error', 'Error al crear la solicitud.');
        }

        $datosGuardar = [
            'tipo_solicitante'         => $tipoSolicitante,
            'razon_social_o_nombre'    => $request->getPost('razon_social_o_nombre'),
            'rfc'                      => $request->getPost('rfc'),
            'domicilio_negocio'        => $request->getPost('domicilio_negocio'),
            'direccion_carga_descarga' => $request->getPost('direccion_carga_descarga'),
            'periodo'                  => $request->getPost('periodo'),
            'num_camiones'             => $tipoSolicitante === 'empresa' ? (string)$numCamionesFinal : '',
            'horario_inicio'           => $request->getPost('horario_inicio'),
            'horario_fin'              => $request->getPost('horario_fin'),
            'es_mudanza'               => $esMudanza ? '1' : '0',
        ];
        $this->solicitudDatoModel->guardarDatos((int)$solicitudId, $datosGuardar);

        $fileId = $request->getFile('identificacion_oficial');
        if ($fileId !== null && $fileId->isValid()) {
            $doc = $this->uploader->subir($fileId, 'identificacion_oficial', (int)$solicitudId, $userId);
            if ($doc === null) {
                return redirect()->back()->withInput()->with('error', 'Error al subir el documento: Identificación Oficial');
            }
        }

        $fileTc = $request->getFile('tarjeta_circulacion');
        if ($fileTc !== null && $fileTc->isValid()) {
            $doc = $this->uploader->subir($fileTc, 'tarjeta_circulacion', (int)$solicitudId, $userId);
            if ($doc === null) {
                return redirect()->back()->withInput()->with('error', 'Error al subir el documento: Tarjeta de Circulación');
            }
        }

        if (!$esMudanza) {
            $fileCd = $request->getFile('documento_carga_descarga');
            if ($fileCd !== null && $fileCd->isValid()) {
                $doc = $this->uploader->subir($fileCd, 'documento_carga_descarga', (int)$solicitudId, $userId);
                if ($doc === null) {
                    return redirect()->back()->withInput()->with('error', 'Error al subir el documento: Documento Carga/Descarga');
                }
            }
        }

        $this->estadoService->cambiarEstatus((int)$solicitudId, 'Pago pendiente', null, 'Sistema: paso automático después de registro');
        $this->auditoriaModel->registrar('solicitudes', $solicitudId, 'creada_t07', $userId, [
            'folio' => $folio,
            'tramite' => 'UR-TT-T-07',
        ]);

        return redirect()->to('/portal/tramites/carga-descarga/resumen/' . $folio);
    }

    public function resumen(string $folio)
    {
        $session = Services::session();
        $userId = (int)$session->get('user_id');

        $solicitud = $this->solicitudModel->findByFolio($folio);
        if ($solicitud === null || (int)$solicitud->ciudadano_id !== $userId) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada o acceso denegado.');
        }

        $datos = $this->solicitudDatoModel->porSolicitudAgrupado((int)$solicitud->id);
        $documentos = $this->documentoModel->porSolicitud((int)$solicitud->id);

        $placeholder = false;
        if (isset($datos['tipo_solicitante'], $datos['periodo'])) {
            $nc = isset($datos['num_camiones']) && $datos['num_camiones'] !== '' ? (int)$datos['num_camiones'] : 1;
            $placeholder = $this->tarifarioService->esPlaceholderT07($datos['tipo_solicitante'], $datos['periodo'], $nc);
        }

        return view('portal/tramites/carga_descarga_resumen', [
            'solicitud'   => $solicitud,
            'datos'       => $datos,
            'documentos'  => $documentos,
            'placeholder' => $placeholder,
        ]);
    }

    public function pagar(int $solicitudId)
    {
        $session = Services::session();
        $userId = (int)$session->get('user_id');

        $solicitud = $this->solicitudModel->find($solicitudId);
        if ($solicitud === null || (int)$solicitud->ciudadano_id !== $userId) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada o acceso denegado.');
        }

        $resultado = $this->gateway->crearCargo($solicitud->folio, (float)$solicitud->monto, 'UR-TT-T-07 Permiso Carga y Descarga');

        if (empty($resultado['success'])) {
            return redirect()->back()->with('error', 'Error en el gateway de pago: ' . ($resultado['mensaje'] ?? 'desconocido'));
        }

        $gatewayRef = $resultado['referencia'] ?? 'MOCK-REF';
        $fechaPago = (new DateTime())->format('Y-m-d H:i:s');

        $this->solicitudModel->update($solicitudId, [
            'fecha_pago' => $fechaPago,
        ]);

        $this->estadoService->cambiarEstatus($solicitudId, 'Pagado', null, 'Pago procesado vía mock BanBajío ref: ' . $gatewayRef);

        $datos = $this->solicitudDatoModel->porSolicitudAgrupado($solicitudId);
        $periodo = $datos['periodo'] ?? 'dia';
        $this->estadoService->calcularVigenciaT07($solicitudId, $periodo);

        $this->estadoService->cambiarEstatus($solicitudId, 'Permiso emitido', null, 'Emisión automática después de pago');
        $this->estadoService->cambiarEstatus($solicitudId, 'Vigente', null, 'Permiso activo');

        return redirect()->to('/portal/tramites/carga-descarga/resumen/' . $solicitud->folio)
            ->with('success', 'Pago exitoso! Permiso emitido exitosamente.');
    }

    public function descargarDocumento(string $folio, int $documentoId)
    {
        $session = Services::session();
        $userId = (int)$session->get('user_id');

        $solicitud = $this->solicitudModel->findByFolio($folio);
        if ($solicitud === null || (int)$solicitud->ciudadano_id !== $userId) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Acceso denegado.');
        }

        $documento = $this->documentoModel->find($documentoId);
        if ($documento === null || (int)$documento->solicitud_id !== (int)$solicitud->id) {
            return redirect()->back()->with('error', 'Documento no encontrado.');
        }

        $rutaInterna = $documento->ruta_interna ?? '';
        if ($rutaInterna === '') {
            return redirect()->back()->with('error', 'Ruta de documento inválida.');
        }

        $directorio = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'documentos' . DIRECTORY_SEPARATOR;
        $rutaCompleta = $directorio . $rutaInterna;

        if (!file_exists($rutaCompleta)) {
            return redirect()->back()->with('error', 'Archivo no encontrado en servidor.');
        }

        return $this->response->download($rutaCompleta, null, true)
            ->setFileName($documento->nombre_original ?? 'documento');
    }
}
