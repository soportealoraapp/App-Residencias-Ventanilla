<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\ConcesionModel;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\DocumentoUploader;
use Config\Services;
use DateTime;

class TramiteOrdenPlaqueoController extends Controller
{
    protected SolicitudModel $solicitudModel;
    protected SolicitudDatoModel $solicitudDatoModel;
    protected ConcesionModel $concesionModel;
    protected TarifarioService $tarifarioService;
    protected EstadoSolicitudService $estadoService;
    protected DocumentoUploader $uploader;

    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
        $this->solicitudModel = new SolicitudModel();
        $this->solicitudDatoModel = new SolicitudDatoModel();
        $this->concesionModel = new ConcesionModel();
        $this->tarifarioService = new TarifarioService();
        $this->estadoService = new EstadoSolicitudService();
        $this->uploader = new DocumentoUploader();
    }

    public function formulario()
    {
        $tarifaMonto = $this->tarifarioService->calcularMonto('UR-TT-T-03', 'base') ?? 50.00;
        $esPlaceholder = $this->tarifarioService->esPlaceholder('UR-TT-T-03', 'base');

        return view('portal/tramites/orden_plaqueo_form', [
            'tarifaMonto'   => $tarifaMonto,
            'esPlaceholder' => $esPlaceholder,
        ]);
    }

    public function guardar()
    {
        $request = Services::request();
        $session = Services::session();
        $userId = (int) $session->get('user_id');
        $tipoPersona = $request->getPost('tipo_persona') ?? 'fisica';

        $rules = [
            'numero_titulo_concesion' => 'required|min_length[3]|max_length[50]',
            'nombre_concesionario'    => 'required|min_length[3]|max_length[180]',
            'tipo_persona'            => 'required|in_list[fisica,moral]',
            'numero_factura'          => 'required|min_length[2]|max_length[60]',
            'vehiculo_placas'         => 'permit_empty|max_length[20]',
            'vehiculo_num_serie'      => 'permit_empty|max_length[30]',
        ];

        $docRules = [
            'doc_solicitud' => [
                'rules' => 'uploaded[doc_solicitud]|max_size[doc_solicitud,10240]|mime_in[doc_solicitud,application/pdf,image/jpeg,image/png]',
                'label' => 'Solicitud oficial firmada',
            ],
            'doc_titulo_concesion' => [
                'rules' => 'uploaded[doc_titulo_concesion]|max_size[doc_titulo_concesion,10240]|mime_in[doc_titulo_concesion,application/pdf,image/jpeg,image/png]',
                'label' => 'Título de concesión',
            ],
            'doc_identificacion' => [
                'rules' => 'uploaded[doc_identificacion]|max_size[doc_identificacion,10240]|mime_in[doc_identificacion,application/pdf,image/jpeg,image/png]',
                'label' => 'Identificación oficial vigente',
            ],
            'doc_factura' => [
                'rules' => 'uploaded[doc_factura]|max_size[doc_factura,10240]|mime_in[doc_factura,application/pdf,image/jpeg,image/png]',
                'label' => 'Factura o carta factura del vehículo',
            ],
            'doc_revista' => [
                'rules' => 'uploaded[doc_revista]|max_size[doc_revista,10240]|mime_in[doc_revista,application/pdf,image/jpeg,image/png]',
                'label' => 'Comprobante de revista físico-mecánica',
            ],
        ];

        if ($tipoPersona === 'moral') {
            $docRules['doc_acta_constitutiva'] = [
                'rules' => 'uploaded[doc_acta_constitutiva]|max_size[doc_acta_constitutiva,10240]|mime_in[doc_acta_constitutiva,application/pdf,image/jpeg,image/png]',
                'label' => 'Acta constitutiva y poder notarial',
            ];
        }

        $allRules = array_merge($rules, $docRules);

        if (! $this->validate($allRules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $numTitulo = trim((string) $request->getPost('numero_titulo_concesion'));
        $concesion = $this->concesionModel->findByNumeroTitulo($numTitulo);
        $concesionId = $concesion ? (int) $concesion->id : null;

        $monto = $this->tarifarioService->calcularMonto('UR-TT-T-03', 'base') ?? 50.00;
        $folio = FolioGenerator::generar();

        $solicitudId = $this->solicitudModel->insert([
            'folio'           => $folio,
            'tramite'         => 'UR-TT-T-03',
            'ciudadano_id'    => $userId,
            'concesion_id'    => $concesionId,
            'convocatoria_id' => null,
            'estatus'         => 'Recibido',
            'monto'           => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if (! $solicitudId) {
            return redirect()->back()->withInput()->with('error', 'No fue posible registrar la solicitud en la base de datos.');
        }

        $datosGuardar = [
            'numero_titulo_concesion' => $numTitulo,
            'nombre_concesionario'    => (string) $request->getPost('nombre_concesionario'),
            'tipo_persona'            => $tipoPersona,
            'numero_factura'          => (string) $request->getPost('numero_factura'),
            'vehiculo_placas'         => (string) ($request->getPost('vehiculo_placas') ?? ''),
            'vehiculo_num_serie'      => (string) ($request->getPost('vehiculo_num_serie') ?? ''),
            'tramite_concepto'        => 'Orden de Plaqueo',
        ];
        $this->solicitudDatoModel->guardarDatos((int) $solicitudId, $datosGuardar);

        $documentosKeys = [
            'doc_solicitud'        => 'solicitud_plaqueo',
            'doc_titulo_concesion' => 'titulo_concesion',
            'doc_identificacion'   => 'identificacion_oficial',
            'doc_factura'          => 'factura_vehiculo',
            'doc_revista'          => 'revista_fisicomecanica',
        ];
        if ($tipoPersona === 'moral') {
            $documentosKeys['doc_acta_constitutiva'] = 'acta_constitutiva';
        }

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
            'comentario'       => 'Solicitud inicial de Orden de Plaqueo registrada por el ciudadano.',
        ]);

        return redirect()->to('/portal/solicitud/' . $folio)->with('message', '¡Tu solicitud de Orden de Plaqueo ha sido registrada exitosamente!');
    }
}
