<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use App\Libraries\BanbajioMockGateway;
use App\Libraries\DocumentoUploader;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Models\DocumentoModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use CodeIgniter\Controller;
use Config\Services;
use DateTime;

class TramiteCierreCalleController extends Controller
{
    private const TRAMITE = 'UR-TT-T-05';

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
        return view('portal/tramites/cierre_calle_form', [
            'tarifaMonto' => $this->tarifarioService->calcularMonto(self::TRAMITE, 'base') ?? 287.00,
        ]);
    }

    public function guardar()
    {
        $request = Services::request();
        $userId = (int) Services::session()->get('user_id');

        $rules = [
            'nombre_solicitante' => 'required|min_length[3]|max_length[180]',
            'domicilio' => 'required|min_length[5]|max_length[250]',
            'fecha_cierre' => 'required|valid_date[Y-m-d]',
            'hora_inicio' => 'required|regex_match[/^([01][0-9]|2[0-3]):[0-5][0-9]$/]',
            'hora_fin' => 'required|regex_match[/^([01][0-9]|2[0-3]):[0-5][0-9]$/]',
            'calle_tramo' => 'required|min_length[3]|max_length[250]',
            'colonia' => 'required|max_length[120]',
            'tipo_cierre' => 'required|in_list[parcial,total]',
            'motivo_evento' => 'required|min_length[3]|max_length[180]',
            'descripcion_evento' => 'required|min_length[10]|max_length[500]',
            'identificacion_oficial' => 'uploaded[identificacion_oficial]|max_size[identificacion_oficial,10240]|mime_in[identificacion_oficial,application/pdf,image/jpeg,image/png]',
            'solicitud_escrita' => 'uploaded[solicitud_escrita]|max_size[solicitud_escrita,10240]|mime_in[solicitud_escrita,application/pdf,image/jpeg,image/png]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $monto = $this->tarifarioService->calcularMonto(self::TRAMITE, 'base') ?? 287.00;
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
            'nombre_solicitante' => $request->getPost('nombre_solicitante'),
            'domicilio' => $request->getPost('domicilio'),
            'fecha_cierre' => $request->getPost('fecha_cierre'),
            'hora_inicio' => $request->getPost('hora_inicio'),
            'hora_fin' => $request->getPost('hora_fin'),
            'calle_tramo' => $request->getPost('calle_tramo'),
            'colonia' => $request->getPost('colonia'),
            'tipo_cierre' => $request->getPost('tipo_cierre'),
            'motivo_evento' => $request->getPost('motivo_evento'),
            'descripcion_evento' => $request->getPost('descripcion_evento'),
        ]);

        foreach (['identificacion_oficial', 'solicitud_escrita'] as $campo) {
            $file = $request->getFile($campo);
            if ($file === null || ! $file->isValid() || $this->uploader->subir($file, $campo, (int) $solicitudId, $userId) === null) {
                return redirect()->back()->withInput()->with('error', 'No fue posible guardar el documento requerido.');
            }
        }

        return redirect()->to('/portal/tramites/cierre-calle/resumen/' . $folio);
    }

    public function resumen(string $folio)
    {
        $userId = (int) Services::session()->get('user_id');
        $solicitud = $this->solicitudModel->findByFolio($folio);

        if ($solicitud === null || (int) $solicitud->ciudadano_id !== $userId || $solicitud->tramite !== self::TRAMITE) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada o acceso denegado.');
        }

        return view('portal/tramites/cierre_calle_resumen', [
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

        $resultado = $this->gateway->crearCargo($solicitud->folio, (float) $solicitud->monto, 'UR-TT-T-05 Permiso para Cierre de Calle');
        if (empty($resultado['success'])) {
            return redirect()->back()->with('error', 'No fue posible procesar el pago simulado.');
        }

        $datos = $this->solicitudDatoModel->porSolicitudAgrupado($solicitudId);
        $fechaCierre = new DateTime((string) ($datos['fecha_cierre'] ?? date('Y-m-d')));
        $fechaPago = (new DateTime())->format('Y-m-d H:i:s');
        $referencia = (string) ($resultado['referencia'] ?? 'MOCK-REF');
        $this->solicitudModel->update($solicitudId, [
            'fecha_pago' => $fechaPago,
            'fecha_vigencia_inicio' => $fechaCierre->format('Y-m-d'),
            'fecha_vigencia_fin' => $fechaCierre->format('Y-m-d'),
        ]);
        $this->solicitudDatoModel->guardarDatos($solicitudId, array_merge($datos, ['referencia_pago' => $referencia]));

        $estadoService = new EstadoSolicitudService();
        $estadoService->cambiarEstatus($solicitudId, 'Pagado', null, 'Pago procesado vía mock BanBajío: ' . $referencia);
        $estadoService->cambiarEstatus($solicitudId, 'Permiso emitido', null, 'Permiso emitido después del pago');

        return redirect()->to('/portal/tramites/cierre-calle/permiso/' . $solicitud->folio)
            ->with('success', 'Pago simulado exitosamente. Permiso emitido.');
    }

    public function permiso(string $folio)
    {
        $userId = (int) Services::session()->get('user_id');
        $solicitud = $this->solicitudModel->findByFolio($folio);

        if ($solicitud === null || (int) $solicitud->ciudadano_id !== $userId || $solicitud->tramite !== self::TRAMITE) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Permiso no encontrado o acceso denegado.');
        }
        if (! in_array($solicitud->estatus, ['Pagado', 'Permiso emitido'], true)) {
            return redirect()->to('/portal/tramites/cierre-calle/resumen/' . $folio);
        }

        return view('portal/tramites/cierre_calle_permiso', [
            'solicitud' => $solicitud,
            'datos' => $this->solicitudDatoModel->porSolicitudAgrupado((int) $solicitud->id),
        ]);
    }
}