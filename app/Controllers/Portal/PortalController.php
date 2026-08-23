<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\DocumentoModel;
use App\Libraries\FeatureFlags;
use Config\Services;

class PortalController extends Controller
{
    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
    }

    public function dashboard()
    {
        $session = Services::session();
        $roles = $session->get('roles') ?? [];
        $rolesAdmin = array_intersect(['admin', 'operador'], $roles);
        if (!empty($rolesAdmin)) {
            return redirect()->to('/admin/dashboard');
        }

        $userId = (int)$session->get('user_id');
        $solicitudModel = new SolicitudModel();
        $ultimasSolicitudes = $solicitudModel->where('ciudadano_id', $userId)
            ->orderBy('fecha_solicitud', 'DESC')
            ->limit(5)
            ->findAll();

        $habilitaT06 = FeatureFlags::habilitarUrTtT06();

        return view('portal/dashboard', [
            'ultimasSolicitudes' => $ultimasSolicitudes,
            'habilitaT06' => $habilitaT06,
        ]);
    }

    public function tramites()
    {
        $habilitaT06 = FeatureFlags::habilitarUrTtT06();
        return view('portal/tramites', [
            'habilitaT06' => $habilitaT06,
        ]);
    }

    public function misSolicitudes()
    {
        $session = Services::session();
        $userId = (int)$session->get('user_id');
        $solicitudModel = new SolicitudModel();
        $solicitudes = $solicitudModel->porCiudadano($userId);

        return view('portal/mis_solicitudes', [
            'solicitudes' => $solicitudes,
        ]);
    }

    public function verSolicitud(string $folio)
    {
        $session = Services::session();
        $userId = (int)$session->get('user_id');

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->findByFolio($folio);

        if ($solicitud === null || (int)$solicitud->ciudadano_id !== $userId) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Solicitud no encontrada o acceso denegado.');
        }

        $solicitudDatoModel = new SolicitudDatoModel();
        $datos = $solicitudDatoModel->porSolicitudAgrupado((int)$solicitud->id);

        $documentoModel = new DocumentoModel();
        $documentos = $documentoModel->porSolicitud((int)$solicitud->id);

        $historialModel = new \App\Models\HistorialEstatusModel();
        $historial = $historialModel->porSolicitud((int)$solicitud->id);

        return view('portal/ver_solicitud', [
            'solicitud'  => $solicitud,
            'datos'      => $datos,
            'documentos' => $documentos,
            'historial'  => $historial,
        ]);
    }
}
