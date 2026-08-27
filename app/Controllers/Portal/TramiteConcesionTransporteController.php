<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\ConvocatoriaModel;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\DocumentoUploader;
use Config\Services;
use DateTime;

class TramiteConcesionTransporteController extends Controller
{
    protected SolicitudModel $solicitudModel;
    protected SolicitudDatoModel $solicitudDatoModel;
    protected ConvocatoriaModel $convocatoriaModel;
    protected TarifarioService $tarifarioService;
    protected EstadoSolicitudService $estadoService;
    protected DocumentoUploader $uploader;

    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
        $this->solicitudModel = new SolicitudModel();
        $this->solicitudDatoModel = new SolicitudDatoModel();
        $this->convocatoriaModel = new ConvocatoriaModel();
        $this->tarifarioService = new TarifarioService();
        $this->estadoService = new EstadoSolicitudService();
        $this->uploader = new DocumentoUploader();
    }

    public function formulario()
    {
        $convocatoriaVigente = $this->convocatoriaModel->primeraVigente();
        $tarifaMonto = $this->tarifarioService->calcularMonto('UR-TT-T-01', 'base') ?? 9055.20;
        $esPlaceholder = $this->tarifarioService->esPlaceholder('UR-TT-T-01', 'base');

        return view('portal/tramites/concesion_transporte_form', [
            'convocatoria'  => $convocatoriaVigente,
            'tarifaMonto'   => $tarifaMonto,
            'esPlaceholder' => $esPlaceholder,
        ]);
    }

    public function guardar()
    {
        $request = Services::request();
        $session = Services::session();
        $userId = (int) $session->get('user_id');

        $convocatoriaId = (int) $request->getPost('convocatoria_id');
        $convocatoria = $this->convocatoriaModel->vigente($convocatoriaId);

        if ($convocatoria === null) {
            return redirect()->back()->withInput()->with('error', 'No fue posible registrar la solicitud: la convocatoria especificada no está vigente o ha expirado.');
        }

        $rules = [
            'convocatoria_id'   => 'required|is_natural_no_zero',
            'solicitante_nombre' => 'required|min_length[3]|max_length[180]',
            'tipo_persona'       => 'required|in_list[fisica,moral]',
            'rfc'                => 'required|regex_match[/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/]',
            'domicilio'          => 'required|min_length[5]|max_length[250]',
            'tipo_servicio'      => 'required|min_length[3]|max_length[100]',
            'num_vehiculos'      => 'required|is_natural_no_zero|less_than_equal_to[50]',
        ];

        $docRules = [
            'doc_acta' => [
                'rules' => 'uploaded[doc_acta]|max_size[doc_acta,10240]|mime_in[doc_acta,application/pdf,image/jpeg,image/png]',
                'label' => 'Acta de nacimiento o Acta constitutiva',
            ],
            'doc_rfc' => [
                'rules' => 'uploaded[doc_rfc]|max_size[doc_rfc,10240]|mime_in[doc_rfc,application/pdf,image/jpeg,image/png]',
                'label' => 'Constancia de Situación Fiscal (RFC)',
            ],
            'doc_escritura' => [
                'rules' => 'uploaded[doc_escritura]|max_size[doc_escritura,10240]|mime_in[doc_escritura,application/pdf,image/jpeg,image/png]',
                'label' => 'Acreditación de capacidad financiera y legal',
            ],
            'doc_residencia' => [
                'rules' => 'uploaded[doc_residencia]|max_size[doc_residencia,10240]|mime_in[doc_residencia,application/pdf,image/jpeg,image/png]',
                'label' => 'Constancia de residencia municipal',
            ],
            'doc_antecedentes' => [
                'rules' => 'uploaded[doc_antecedentes]|max_size[doc_antecedentes,10240]|mime_in[doc_antecedentes,application/pdf,image/jpeg,image/png]',
                'label' => 'Constancia de no antecedentes penales',
            ],
            'doc_proyecto' => [
                'rules' => 'uploaded[doc_proyecto]|max_size[doc_proyecto,10240]|mime_in[doc_proyecto,application/pdf,image/jpeg,image/png]',
                'label' => 'Proyecto técnico de horarios y rutas',
            ],
            'doc_vehiculos' => [
                'rules' => 'uploaded[doc_vehiculos]|max_size[doc_vehiculos,10240]|mime_in[doc_vehiculos,application/pdf,image/jpeg,image/png]',
                'label' => 'Documentación técnica de los vehículos propuestos',
            ],
        ];

        $allRules = array_merge($rules, $docRules);

        if (! $this->validate($allRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $monto = $this->tarifarioService->calcularMonto('UR-TT-T-01', 'base') ?? 9055.20;
        $folio = FolioGenerator::generar();

        $solicitudId = $this->solicitudModel->insert([
            'folio'           => $folio,
            'tramite'         => 'UR-TT-T-01',
            'ciudadano_id'    => $userId,
            'concesion_id'    => null,
            'convocatoria_id' => $convocatoriaId,
            'estatus'         => 'Recibido',
            'monto'           => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if (! $solicitudId) {
            return redirect()->back()->withInput()->with('error', 'Error al registrar la solicitud de concesión.');
        }

        $datosGuardar = [
            'solicitante_nombre' => (string) $request->getPost('solicitante_nombre'),
            'tipo_persona'       => (string) $request->getPost('tipo_persona'),
            'rfc'                => strtoupper((string) $request->getPost('rfc')),
            'domicilio'          => (string) $request->getPost('domicilio'),
            'tipo_servicio'      => (string) $request->getPost('tipo_servicio'),
            'num_vehiculos'      => (string) $request->getPost('num_vehiculos'),
            'convocatoria_bases' => $convocatoria->bases ?? 'Convocatoria Oficial UR-01',
            'tramite_concepto'   => 'Otorgamiento de Concesión de Transporte Público',
        ];
        $this->solicitudDatoModel->guardarDatos((int) $solicitudId, $datosGuardar);

        $documentosKeys = [
            'doc_acta'         => 'acta_nacimiento_o_constitutiva',
            'doc_rfc'          => 'constancia_situacion_fiscal',
            'doc_escritura'    => 'acreditacion_capacidad_financiera',
            'doc_residencia'   => 'constancia_residencia',
            'doc_antecedentes' => 'constancia_antecedentes_penales',
            'doc_proyecto'     => 'proyecto_tecnico_horarios_rutas',
            'doc_vehiculos'    => 'documentacion_tecnica_vehiculos',
        ];

        foreach ($documentosKeys as $inputKey => $tipoDoc) {
            $file = $request->getFile($inputKey);
            if ($file && ($file->isValid() || ENVIRONMENT === 'testing') && ! $file->hasMoved() && $file->getError() === UPLOAD_ERR_OK) {
                $docObj = $this->uploader->subir($file, $tipoDoc, (int) $solicitudId, $userId);
                if ($docObj === null) {
                    return redirect()->back()->withInput()->with('error', "Error al procesar el archivo: {$inputKey}");
                }
            }
        }

        $historialModel = new \App\Models\HistorialEstatusModel();
        $historialModel->insert([
            'solicitud_id'     => (int) $solicitudId,
            'estatus_anterior' => null,
            'estatus_nuevo'    => 'Recibido',
            'usuario_id'       => $userId,
            'fecha'            => date('Y-m-d H:i:s'),
            'comentario'       => 'Solicitud inicial para Convocatoria #' . $convocatoriaId . ' de Concesión de Transporte.',
        ]);

        return redirect()->to('/portal/solicitud/' . $folio)
            ->with('message', '¡Expediente enviado exitosamente a la Convocatoria de Concesión de Transporte!');
    }
}
