<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\DocumentoModel;
use App\Models\ConcesionModel;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\DocumentoUploader;
use App\Libraries\BanbajioMockGateway;
use App\Libraries\FeatureFlags;
use App\Models\AuditoriaModel;
use Config\Services;
use DateTime;

class TramiteCesionConcesionController extends Controller
{
    protected SolicitudModel $solicitudModel;
    protected SolicitudDatoModel $solicitudDatoModel;
    protected DocumentoModel $documentoModel;
    protected ConcesionModel $concesionModel;
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
        $this->concesionModel = new ConcesionModel();
        $this->auditoriaModel = new AuditoriaModel();
        $this->tarifarioService = new TarifarioService();
        $this->estadoService = new EstadoSolicitudService();
        $this->uploader = new DocumentoUploader();
        $this->gateway = new BanbajioMockGateway();
    }

    protected function validarFeature(): ?object
    {
        if (!FeatureFlags::habilitarUrTtT06()) {
            return redirect()->to('/portal/tramites')->with('error', 'Este trámite está deshabilitado temporalmente (FASE 2).');
        }
        return null;
    }

    public function formulario()
    {
        if ($redirect = $this->validarFeature()) {
            return $redirect;
        }

        $tiposCesion = [
            'muerte_incapacidad'  => 'Muerte o Incapacidad del titular',
            'cesion_derechos'     => 'Cesión voluntaria de derechos',
            'mandamiento_judicial' => 'Mandamiento judicial',
        ];

        return view('portal/tramites/cesion_concesion_form', [
            'tipos_cesion' => $tiposCesion,
            'concesion'    => null,
        ]);
    }

    public function validarConcesionAjax(string $numeroTitulo)
    {
        $response = Services::response();
        $response->setContentType('application/json');

        if (!FeatureFlags::habilitarUrTtT06()) {
            return $response->setJSON(['success' => false, 'mensaje' => 'Este trámite está deshabilitado temporalmente (FASE 2).']);
        }

        $numeroTituloDecoded = urldecode($numeroTitulo);
        $concesion = $this->concesionModel->findByNumeroTitulo($numeroTituloDecoded);

        if ($concesion === null || $concesion->estatus !== 'vigente') {
            return $response->setJSON(['success' => false, 'mensaje' => 'Número de título no encontrado o concesión no vigente']);
        }

        return $response->setJSON([
            'success'          => true,
            'titular_actual'   => $concesion->titular_actual ?? '',
            'vehiculo_placas'  => $concesion->vehiculo_placas ?? '',
            'vehiculo_num_serie' => $concesion->vehiculo_num_serie ?? '',
            'vigencia_inicio'  => $concesion->vigencia_inicio ?? '',
            'vigencia_fin'     => $concesion->vigencia_fin ?? '',
        ]);
    }

    public function guardar()
    {
        if ($redirect = $this->validarFeature()) {
            return $redirect;
        }

        $request = Services::request();
        $session = Services::session();
        $userId = (int)$session->get('user_id');

        $tipoCesion = $request->getPost('tipo_cesion') ?? '';

        $rules = [
            'solicitante_nombre' => [
                'rules' => 'required|min_length[3]|max_length[180]',
                'label' => 'Nombre del solicitante',
            ],
            'solicitante_domicilio' => [
                'rules' => 'required|max_length[250]',
                'label' => 'Domicilio del solicitante',
            ],
            'tipo_cesion' => [
                'rules' => 'required|in_list[muerte_incapacidad,cesion_derechos,mandamiento_judicial]',
                'label' => 'Tipo de cesión',
            ],
            'numero_titulo_concesion' => [
                'rules' => 'required|max_length[50]',
                'label' => 'Número de título de concesión',
            ],
            'vehiculo_placas' => [
                'rules' => 'required|max_length[10]',
                'label' => 'Placas del vehículo',
            ],
            'vehiculo_num_serie' => [
                'rules' => 'required|max_length[20]',
                'label' => 'Número de serie (VIN)',
            ],
            'titulo_concesion_archivo' => [
                'rules' => 'uploaded[titulo_concesion_archivo]|max_size[titulo_concesion_archivo,10240]|mime_in[titulo_concesion_archivo,image/png,image/jpeg,application/pdf]',
                'label' => 'Título de concesión (archivo)',
            ],
        ];

        $docCapacidadFiles = $request->getFiles();
        $hayDocCapacidad = isset($docCapacidadFiles['documentos_capacidad']) && is_array($docCapacidadFiles['documentos_capacidad']) && !empty($docCapacidadFiles['documentos_capacidad'][0]);

        if ($hayDocCapacidad) {
            $docs = $docCapacidadFiles['documentos_capacidad'];
            foreach ($docs as $idx => $doc) {
                if ($doc !== null && $doc->isValid() && !$doc->hasMoved()) {
                    $rules['documentos_capacidad.' . $idx] = [
                        'rules' => 'max_size[documentos_capacidad.' . $idx . ',10240]|mime_in[documentos_capacidad.' . $idx . ',image/png,image/jpeg,application/pdf]',
                        'label' => 'Documentos de acreditación de capacidad (' . ($idx + 1) . ')',
                    ];
                }
            }
        } else {
            $rules['documentos_capacidad'] = [
                'rules' => 'required',
                'label' => 'Documentos de capacidad legal, técnica y financiera',
            ];
        }

        $archivosCondicionales = [];
        if ($tipoCesion === 'muerte_incapacidad') {
            $archivosCondicionales = [
                'acta_defuncion_o_sentencia' => 'Acta de defunción o sentencia',
                'curatela_documento'         => 'Documento de curatela',
                'beneficiario_identificacion' => 'Identificación del beneficiario',
                'beneficiario_acta_nacimiento' => 'Acta de nacimiento del beneficiario',
            ];
        } elseif ($tipoCesion === 'cesion_derechos') {
            $archivosCondicionales = [
                'cedente_identificacion'      => 'Identificación del cedente',
                'cesionario_identificacion'   => 'Identificación del cesionario',
                'cesionario_acta_nacimiento'  => 'Acta de nacimiento del cesionario',
                'revocacion_notarial'         => 'Revocación notarial',
                'contrato_cesion_notarial'    => 'Contrato de cesión notarial',
            ];
        } elseif ($tipoCesion === 'mandamiento_judicial') {
            $archivosCondicionales = [
                'resolucion_judicial_certificada' => 'Resolución judicial certificada',
                'interesado_identificacion'       => 'Identificación del interesado',
                'interesado_acta_nacimiento'      => 'Acta de nacimiento del interesado',
            ];
        }

        foreach ($archivosCondicionales as $campo => $nombre) {
            $rules[$campo] = [
                'rules' => 'uploaded[' . $campo . ']|max_size[' . $campo . ',10240]|mime_in[' . $campo . ',image/png,image/jpeg,application/pdf]',
                'label' => $nombre,
            ];
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $numeroTitulo = $request->getPost('numero_titulo_concesion');
        $concesion = $this->concesionModel->findByNumeroTitulo($numeroTitulo);
        if ($concesion === null || $concesion->estatus !== 'vigente') {
            return redirect()->back()->withInput()->with('error', 'La concesión indicada no se encuentra vigente o no existe.');
        }

        $folio = FolioGenerator::generar();
        $monto = $this->tarifarioService->calcularMontoUrTtT06();
        if ($monto === null) {
            return redirect()->back()->withInput()->with('error', 'Tarifa no configurada para cesión.');
        }

        $solicitudId = $this->solicitudModel->insert([
            'folio'           => $folio,
            'tramite'         => 'UR-TT-T-06',
            'ciudadano_id'    => $userId,
            'estatus'         => 'Recibido',
            'monto'           => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if ($solicitudId === false) {
            return redirect()->back()->withInput()->with('error', 'Error al crear la solicitud.');
        }

        $datosGuardar = [
            'solicitante_nombre'              => $request->getPost('solicitante_nombre'),
            'solicitante_domicilio'           => $request->getPost('solicitante_domicilio'),
            'tipo_cesion'                     => $tipoCesion,
            'numero_titulo_concesion'         => $numeroTitulo,
            'vehiculo_placas'                 => $request->getPost('vehiculo_placas'),
            'vehiculo_num_serie'              => $request->getPost('vehiculo_num_serie'),
            'numero_titulo_original_titular'  => $concesion->titular_actual ?? '',
        ];
        $this->solicitudDatoModel->guardarDatos((int)$solicitudId, $datosGuardar);

        $fileTitulo = $request->getFile('titulo_concesion_archivo');
        if ($fileTitulo !== null && $fileTitulo->isValid()) {
            $doc = $this->uploader->subir($fileTitulo, 'titulo_concesion_original', (int)$solicitudId, $userId);
            if ($doc === null) {
                return redirect()->back()->withInput()->with('error', 'Error al subir el documento: Título concesión original.');
            }
        }

        if ($hayDocCapacidad) {
            $docCapFiles = $docCapacidadFiles['documentos_capacidad'];
            foreach ($docCapFiles as $docFile) {
                if ($docFile !== null && $docFile->isValid() && !$docFile->hasMoved()) {
                    $doc = $this->uploader->subir($docFile, 'documentos_capacidad', (int)$solicitudId, $userId);
                    if ($doc === null) {
                        return redirect()->back()->withInput()->with('error', 'Error al subir los documentos de capacidad legal.');
                    }
                }
            }
        }

        foreach ($archivosCondicionales as $campo => $nombre) {
            $f = $request->getFile($campo);
            if ($f !== null && $f->isValid()) {
                $doc = $this->uploader->subir($f, $campo, (int)$solicitudId, $userId);
                if ($doc === null) {
                    return redirect()->back()->withInput()->with('error', 'Error al subir el documento: ' . $nombre . '.');
                }
            }
        }

        $this->estadoService->cambiarEstatus((int)$solicitudId, 'En revisión documental', null, 'Sistema: paso automático después de registro');
        $this->auditoriaModel->registrar('solicitudes', $solicitudId, 'creada_t06', $userId, [
            'folio'   => $folio,
            'tramite' => 'UR-TT-T-06',
        ]);

        return redirect()->to('/portal/mis-solicitudes')->with('success', 'Solicitud ' . $folio . ' creada exitosamente. Estatus: En revisión documental. Te notificaremos cuando haya cambios.');
    }
}
