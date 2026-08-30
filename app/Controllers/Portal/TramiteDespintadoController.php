<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\ConcesionModel;
use App\Models\VerificacionFisicaModel;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\DocumentoUploader;
use Config\Services;
use DateTime;

class TramiteDespintadoController extends Controller
{
    protected SolicitudModel $solicitudModel;
    protected SolicitudDatoModel $solicitudDatoModel;
    protected ConcesionModel $concesionModel;
    protected VerificacionFisicaModel $verificacionModel;
    protected TarifarioService $tarifarioService;
    protected EstadoSolicitudService $estadoService;
    protected DocumentoUploader $uploader;

    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
        $this->solicitudModel = new SolicitudModel();
        $this->solicitudDatoModel = new SolicitudDatoModel();
        $this->concesionModel = new ConcesionModel();
        $this->verificacionModel = new VerificacionFisicaModel();
        $this->tarifarioService = new TarifarioService();
        $this->estadoService = new EstadoSolicitudService();
        $this->uploader = new DocumentoUploader();
    }

    public function formulario()
    {
        $tarifaMonto = $this->tarifarioService->calcularMonto('UR-TT-T-02', 'base') ?? 64.90;
        $esPlaceholder = $this->tarifarioService->esPlaceholder('UR-TT-T-02', 'base');

        return view('portal/tramites/constancia_despintado_form', [
            'tarifaMonto'   => $tarifaMonto,
            'esPlaceholder' => $esPlaceholder,
        ]);
    }

    public function guardar()
    {
        $request = Services::request();
        $session = Services::session();
        $userId = (int) $session->get('user_id');

        $allRules = [
            'numero_titulo_concesion' => [
                'rules' => 'required|min_length[3]|max_length[50]',
                'label' => 'Número de título de concesión',
            ],
            'nombre_titular' => [
                'rules' => 'required|min_length[3]|max_length[180]',
                'label' => 'Nombre completo del titular / concesionario',
            ],
            'vehiculo_placas' => [
                'rules' => 'required|min_length[3]|max_length[20]',
                'label' => 'Placas actuales del vehículo',
            ],
            'vehiculo_num_serie' => [
                'rules' => 'required|min_length[5]|max_length[30]',
                'label' => 'Número de Serie (VIN)',
            ],
            'motivo_despintado' => [
                'rules' => 'required|min_length[5]|max_length[250]',
                'label' => 'Motivo de desincorporación / despintado',
            ],
            'doc_identificacion' => [
                'rules' => 'uploaded[doc_identificacion]|max_size[doc_identificacion,10240]|mime_in[doc_identificacion,application/pdf,image/jpeg,image/png]',
                'label' => 'Identificación oficial del titular',
            ],
            'doc_factura' => [
                'rules' => 'uploaded[doc_factura]|max_size[doc_factura,10240]|mime_in[doc_factura,application/pdf,image/jpeg,image/png]',
                'label' => 'Factura o documento de propiedad del vehículo',
            ],
        ];

        if (! $this->validate($allRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $numTitulo = trim((string) $request->getPost('numero_titulo_concesion'));
        $concesion = $this->concesionModel->findByNumeroTitulo($numTitulo);
        $concesionId = $concesion ? (int) $concesion->id : null;

        $monto = $this->tarifarioService->calcularMonto('UR-TT-T-02', 'base') ?? 64.90;
        $folio = FolioGenerator::generar();

        $solicitudId = $this->solicitudModel->insert([
            'folio'           => $folio,
            'tramite'         => 'UR-TT-T-02',
            'ciudadano_id'    => $userId,
            'concesion_id'    => $concesionId,
            'convocatoria_id' => null,
            'estatus'         => 'Recibido',
            'monto'           => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if (! $solicitudId) {
            return redirect()->back()->withInput()->with('error', 'No fue posible registrar la solicitud de constancia de despintado.');
        }

        $datosGuardar = [
            'numero_titulo_concesion' => $numTitulo,
            'nombre_titular'          => (string) $request->getPost('nombre_titular'),
            'vehiculo_placas'         => (string) $request->getPost('vehiculo_placas'),
            'vehiculo_num_serie'      => (string) $request->getPost('vehiculo_num_serie'),
            'motivo_despintado'       => (string) $request->getPost('motivo_despintado'),
            'tramite_concepto'        => 'Constancia de Despintado',
        ];
        $this->solicitudDatoModel->guardarDatos((int) $solicitudId, $datosGuardar);

        $documentosKeys = [
            'doc_identificacion' => 'identificacion_oficial',
            'doc_factura'        => 'factura_vehiculo',
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
            'comentario'       => 'Solicitud de Constancia de Despintado registrada por el usuario.',
        ]);

        return redirect()->to('/portal/tramites/ur-02/solicitud/' . $folio . '/cita')
            ->with('message', 'Solicitud registrada. Por favor agenda tu fecha de verificación física de la unidad.');
    }

    public function agendarCitaForm(string $folio)
    {
        $solicitud = $this->solicitudModel->findByFolio($folio);
        if ($solicitud === null) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada.');
        }

        $verificacion = $this->verificacionModel->primerPorSolicitud((int) $solicitud->id);

        return view('portal/tramites/ur02_cita_form', [
            'solicitud'    => $solicitud,
            'verificacion' => $verificacion,
        ]);
    }

    public function guardarCita(string $folio)
    {
        $request = Services::request();
        $session = Services::session();
        $userId = (int) $session->get('user_id');

        $solicitud = $this->solicitudModel->findByFolio($folio);
        if ($solicitud === null) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada.');
        }

        $rules = [
            'fecha_cita' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $fechaCita = (string) $request->getPost('fecha_cita');
        // Standardize format
        $fechaTimestamp = strtotime($fechaCita);
        if ($fechaTimestamp === false) {
            return redirect()->back()->withInput()->with('error', 'Fecha de cita inválida.');
        }
        $fechaSql = date('Y-m-d H:i:s', $fechaTimestamp);

        $solicitudId = (int) $solicitud->id;
        $this->verificacionModel->where('solicitud_id', $solicitudId)->delete();
        $this->verificacionModel->insert([
            'solicitud_id' => $solicitudId,
            'fecha_cita'   => $fechaSql,
            'resultado'    => null,
            'observaciones' => null,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->estadoService->cambiarEstatus(
            $solicitudId,
            'Cita agendada',
            $userId,
            'Cita de inspección física agendada para: ' . date('d/m/Y H:i', $fechaTimestamp)
        );

        return redirect()->to('/portal/solicitud/' . $folio)
            ->with('message', '¡Cita de verificación física agendada con éxito para el ' . date('d/m/Y H:i', $fechaTimestamp) . '!');
    }
}
