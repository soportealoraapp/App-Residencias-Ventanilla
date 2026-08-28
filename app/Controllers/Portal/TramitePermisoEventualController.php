<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Libraries\DocumentoUploader;
use App\Libraries\BanbajioMockGateway;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Models\DocumentoModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use CodeIgniter\Controller;
use Config\Services;
use DateTime;

class TramitePermisoEventualController extends Controller
{
    private const TRAMITE = 'UR-TT-T-04';

    protected SolicitudModel $solicitudModel;
    protected SolicitudDatoModel $solicitudDatoModel;
    protected DocumentoModel $documentoModel;
    protected TarifarioService $tarifarioService;
    protected DocumentoUploader $uploader;
    protected BanbajioMockGateway $gateway;

    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
        $this->solicitudModel = new SolicitudModel();
        $this->solicitudDatoModel = new SolicitudDatoModel();
        $this->documentoModel = new DocumentoModel();
        $this->tarifarioService = new TarifarioService();
        $this->uploader = new DocumentoUploader();
        $this->gateway = new BanbajioMockGateway();
    }

    public function formulario()
    {
        return view('portal/tramites/permiso_eventual_form', [
            'tarifaMonto' => $this->tarifarioService->calcularMonto(self::TRAMITE, 'base') ?? 156.94,
        ]);
    }

    public function guardar()
    {
        $request = Services::request();
        $session = Services::session();
        $userId = (int) $session->get('user_id');
        $tipoPersona = (string) ($request->getPost('tipo_persona') ?? '');

        $rules = [
            'nombre_razon_social' => 'required|min_length[3]|max_length[180]',
            'tipo_persona' => 'required|in_list[fisica,moral]',
            'rfc' => 'required|regex_match[/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/]',
            'domicilio' => 'required|min_length[5]|max_length[250]',
            'tipo_servicio' => 'required|max_length[100]',
            'tipo_unidad' => 'required|max_length[100]',
            'cantidad_unidades' => 'required|is_natural_no_zero|less_than_equal_to[50]',
            'lugar_servicio' => 'required|max_length[250]',
            'zona_servicio' => 'required|max_length[180]',
            'motivo_necesidad' => 'required|in_list[descompostura,falta_unidades,otra_necesidad]',
            'descripcion_necesidad' => 'required|min_length[10]|max_length[500]',
            'vigencia_observacion' => 'required|max_length[250]',
        ];

        $documentos = [
            'solicitud_escrita' => 'Solicitud por escrito',
            'proyecto_vehiculos' => 'Proyecto de cantidad de vehículos',
            'frecuencia_servicios' => 'Frecuencia de servicios',
            'documento_identidad' => $tipoPersona === 'moral' ? 'Acta constitutiva' : 'Acta de nacimiento',
            'poliza_seguro' => 'Fondo de garantía o póliza de seguro',
        ];

        foreach ($documentos as $campo => $label) {
            $rules[$campo] = [
                'label' => $label,
                'rules' => 'uploaded[' . $campo . ']|max_size[' . $campo . ',10240]|mime_in[' . $campo . ',application/pdf,image/jpeg,image/png]',
            ];
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $monto = $this->tarifarioService->calcularMonto(self::TRAMITE, 'base') ?? 156.94;
        $folio = FolioGenerator::generar();
        $solicitudId = $this->solicitudModel->insert([
            'folio' => $folio,
            'tramite' => self::TRAMITE,
            'ciudadano_id' => $userId,
            'estatus' => 'Recibido',
            'monto' => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if ($solicitudId === false) {
            return redirect()->back()->withInput()->with('error', 'No fue posible crear la solicitud.');
        }

        $this->solicitudDatoModel->guardarDatos((int) $solicitudId, [
            'nombre_razon_social' => $request->getPost('nombre_razon_social'),
            'tipo_persona' => $tipoPersona,
            'rfc' => strtoupper((string) $request->getPost('rfc')),
            'domicilio' => $request->getPost('domicilio'),
            'tipo_servicio' => $request->getPost('tipo_servicio'),
            'tipo_unidad' => $request->getPost('tipo_unidad'),
            'cantidad_unidades' => $request->getPost('cantidad_unidades'),
            'lugar_servicio' => $request->getPost('lugar_servicio'),
            'zona_servicio' => $request->getPost('zona_servicio'),
            'motivo_necesidad' => $request->getPost('motivo_necesidad'),
            'descripcion_necesidad' => $request->getPost('descripcion_necesidad'),
            'vigencia_observacion' => $request->getPost('vigencia_observacion'),
        ]);

        foreach ($documentos as $campo => $label) {
            $file = $request->getFile($campo);
            if ($file === null || ! $file->isValid() || $this->uploader->subir($file, $campo, (int) $solicitudId, $userId) === null) {
                return redirect()->back()->withInput()->with('error', "No fue posible guardar: {$label}.");
            }
        }

        return redirect()->to('/portal/tramites/permiso-eventual/resumen/' . $folio);
    }

    public function resumen(string $folio)
    {
        $userId = (int) Services::session()->get('user_id');
        $solicitud = $this->solicitudModel->findByFolio($folio);

        if ($solicitud === null || (int) $solicitud->ciudadano_id !== $userId || $solicitud->tramite !== self::TRAMITE) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada o acceso denegado.');
        }

        return view('portal/tramites/permiso_eventual_resumen', [
            'solicitud' => $solicitud,
            'datos' => $this->solicitudDatoModel->porSolicitudAgrupado((int) $solicitud->id),
            'documentos' => $this->documentoModel->porSolicitud((int) $solicitud->id),
        ]);
    }

    public function pagar(int $solicitudId)
    {
        $userId = (int) Services::session()->get('user_id');
        $solicitud = $this->solicitudModel->find($solicitudId);

        if ($solicitud === null || (int) $solicitud->ciudadano_id !== $userId || $solicitud->tramite !== self::TRAMITE) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada o acceso denegado.');
        }
        if ($solicitud->estatus !== 'Pago pendiente') {
            return redirect()->back()->with('error', 'La solicitud aún no está autorizada para pago.');
        }

        $resultado = $this->gateway->crearCargo($solicitud->folio, (float) $solicitud->monto, 'UR-TT-T-04 Permiso Eventual de Transporte');
        if (empty($resultado['success'])) {
            return redirect()->back()->with('error', 'No fue posible procesar el pago simulado.');
        }

        $referencia = (string) ($resultado['referencia'] ?? 'MOCK-REF');
        $fechaPago = (new DateTime())->format('Y-m-d H:i:s');
        $this->solicitudModel->update($solicitudId, ['fecha_pago' => $fechaPago]);
        $this->solicitudDatoModel->guardarDatos($solicitudId, array_merge(
            $this->solicitudDatoModel->porSolicitudAgrupado($solicitudId),
            ['referencia_pago' => $referencia]
        ));

        $estadoService = new \App\Libraries\EstadoSolicitudService();
        $estadoService->cambiarEstatus($solicitudId, 'Pagado', null, 'Pago procesado vía mock BanBajío: ' . $referencia);
        $estadoService->cambiarEstatus($solicitudId, 'Permiso emitido', null, 'Permiso emitido después del pago');

        return redirect()->to('/portal/tramites/permiso-eventual/resumen/' . $solicitud->folio)
            ->with('success', 'Pago simulado exitosamente. Permiso emitido.');
    }
}