<?php declare(strict_types=1);

namespace App\Controllers\Portal;

use CodeIgniter\Controller;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\DocumentoModel;
use App\Models\FormatoTramiteModel;
use App\Models\UserModel;
use App\Models\AuditoriaModel;
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
        $roles = (array) $session->get('roles');
        foreach ($roles as $rol) {
            if (str_contains($rol, 'admin') || str_contains($rol, 'operador')) {
                return redirect()->to('/admin/dashboard');
            }
        }

        $userId = (int)$session->get('user_id');
        $solicitudModel = new SolicitudModel();
        $ultimasSolicitudes = $solicitudModel->where('ciudadano_id', $userId)
            ->orderBy('fecha_solicitud', 'DESC')
            ->limit(5)
            ->findAll();

        $stats = [
            'total'      => (new SolicitudModel())->where('ciudadano_id', $userId)->countAllResults(),
            'en_proceso' => (new SolicitudModel())->where('ciudadano_id', $userId)->whereIn('estatus', ['Recibido', 'En revisión', 'En revisión documental', 'Cita agendada', 'Evaluación comparativa'])->countAllResults(),
            'concluidos' => (new SolicitudModel())->where('ciudadano_id', $userId)->whereIn('estatus', ['Verificado', 'Permiso emitido', 'Vigente', 'Seleccionado', 'Concluido', 'Pagado'])->countAllResults(),
            'pendientes' => (new SolicitudModel())->where('ciudadano_id', $userId)->where('estatus', 'Pago pendiente')->countAllResults(),
        ];

        $habilitaT06 = FeatureFlags::habilitarUrTtT06();

        return view('portal/dashboard', [
            'ultimasSolicitudes' => $ultimasSolicitudes,
            'habilitaT06'        => $habilitaT06,
            'stats'              => $stats,
        ]);
    }

    public function tramites()
    {
        $habilitaT06 = FeatureFlags::habilitarUrTtT06();

        $formatoModel = new FormatoTramiteModel();
        $formatosMap = [];
        $todos = $formatoModel->where('activo', 1)->findAll();
        foreach ($todos as $f) {
            $formatosMap[$f->tramite] = $f;
        }

        return view('portal/tramites', [
            'habilitaT06' => $habilitaT06,
            'formatosMap' => $formatosMap,
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

        $verificacionModel = new \App\Models\VerificacionFisicaModel();
        $verificacion = $verificacionModel->primerPorSolicitud((int)$solicitud->id);

        return view('portal/ver_solicitud', [
            'solicitud'    => $solicitud,
            'datos'        => $datos,
            'documentos'   => $documentos,
            'historial'    => $historial,
            'verificacion' => $verificacion,
        ]);
    }

    public function descargarDocumento(string $folio, int $documentoId)
    {
        $session = Services::session();
        $userId = (int)$session->get('user_id');

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->findByFolio($folio);

        if ($solicitud === null || (int)$solicitud->ciudadano_id !== $userId) {
            return redirect()->to('/portal/mis-solicitudes')->with('error', 'Acceso denegado.');
        }

        $documentoModel = new DocumentoModel();
        $doc = $documentoModel->find($documentoId);
        if ($doc === null || (int)$doc->solicitud_id !== (int)$solicitud->id) {
            return redirect()->back()->with('error', 'Documento no encontrado.');
        }

        $rutaInterna = $doc->ruta_interna ?? '';
        if ($rutaInterna === '') {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }

        $storage = new \App\Libraries\SupabaseStorage();
        $url = $storage->urlFirmada('documentos', $rutaInterna);
        if ($url === null) {
            return redirect()->back()->with('error', 'Error al obtener el documento.');
        }

        return redirect()->to($url);
    }

    public function miPerfil()
    {
        $session = Services::session();
        $userId = (int) $session->get('user_id');
        $userModel = new UserModel();
        $usuario = $userModel->find($userId);

        if ($usuario === null) {
            return redirect()->to('/portal/dashboard')->with('error', 'Usuario no encontrado.');
        }

        return view('portal/mi_perfil', ['usuario' => $usuario]);
    }

    public function guardarPerfil()
    {
        $session = Services::session();
        $userId = (int) $session->get('user_id');

        $rules = [
            'nombre'    => 'required|min_length[2]',
            'apellido'  => 'required|min_length[2]',
            'email'     => 'required|valid_email',
            'telefono'  => 'required|min_length[7]',
            'estado'    => 'required|min_length[2]',
            'ciudad'    => 'required|min_length[2]',
            'domicilio' => 'required|min_length[5]',
            'rfc'       => 'permit_empty|exact_length[13]|regex_match[/^[A-ZÑ&]{3,4}\d{6}[A-Z\d]{3}$/]',
            'ine_frente' => 'permit_empty|uploaded|mime_in[ine_frente,image/jpeg,image/png]|max_size[ine_frente,5120]',
            'ine_reverso' => 'permit_empty|uploaded|mime_in[ine_reverso,image/jpeg,image/png]|max_size[ine_reverso,5120]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userModel = new UserModel();
        $actual = $userModel->find($userId);

        if ($actual === null) {
            return redirect()->to('/portal/dashboard')->with('error', 'Usuario no encontrado.');
        }

        $email = $this->request->getPost('email');
        if ($email !== $actual->email) {
            $existe = $userModel->where('email', $email)->where('id !=', $userId)->first();
            if ($existe !== null) {
                return redirect()->back()->withInput()->with('errors', ['email' => 'Este correo ya está registrado por otro usuario.']);
            }
        }

        $nombre  = trim((string) $this->request->getPost('nombre'));
        $apellido = trim((string) $this->request->getPost('apellido'));

        $data = [
            'nombre_completo' => $nombre . ' ' . $apellido,
            'apellido'        => $apellido,
            'email'           => $email,
            'rfc'             => $this->request->getPost('rfc') !== '' ? strtoupper((string) $this->request->getPost('rfc')) : null,
            'telefono'        => $this->request->getPost('telefono'),
            'estado'          => $this->request->getPost('estado'),
            'ciudad'          => $this->request->getPost('ciudad'),
            'domicilio'       => $this->request->getPost('domicilio'),
        ];

        // Procesar INE si se subieron archivos nuevos
        $frenteFile = $this->request->getFile('ine_frente');
        $reversoFile = $this->request->getFile('ine_reverso');

        if ($frenteFile->isValid() || $reversoFile->isValid()) {
            $storage = new \App\Libraries\SupabaseStorage();

            // Eliminar archivos anteriores si existen
            if (!empty($actual->ine_frente)) {
                $storage->eliminar('ine', [$actual->ine_frente]);
            }
            if (!empty($actual->ine_reverso)) {
                $storage->eliminar('ine', [$actual->ine_reverso]);
            }

            // Subir nuevos archivos
            if ($frenteFile->isValid()) {
                $frenteExt = $frenteFile->getExtension() === 'jpeg' ? 'jpg' : $frenteFile->getExtension();
                $frenteNombre = bin2hex(random_bytes(16)) . '.' . $frenteExt;
                $frenteRuta = $userId . '/' . $frenteNombre;
                $frenteContenido = file_get_contents($frenteFile->getTempName());
                $storage->subir('ine', $frenteRuta, $frenteContenido, $frenteFile->getMimeType());
                $data['ine_frente'] = $frenteRuta;
            }

            if ($reversoFile->isValid()) {
                $reversoExt = $reversoFile->getExtension() === 'jpeg' ? 'jpg' : $reversoFile->getExtension();
                $reversoNombre = bin2hex(random_bytes(16)) . '.' . $reversoExt;
                $reversoRuta = $userId . '/' . $reversoNombre;
                $reversoContenido = file_get_contents($reversoFile->getTempName());
                $storage->subir('ine', $reversoRuta, $reversoContenido, $reversoFile->getMimeType());
                $data['ine_reverso'] = $reversoRuta;
            }
        }

        $actualizado = $userModel->update($userId, $data);

        if ($actualizado) {
            $this->registrarAuditoriaPerfil($userId, array_keys($data));
        }

        $session->set('nombre_completo', $data['nombre_completo']);

        return redirect()->to('/portal/mi-perfil')->with('message', 'Perfil actualizado correctamente.');
    }

    private function registrarAuditoriaPerfil(int $userId, array $campos): void
    {
        (new AuditoriaModel())->registrar('users', $userId, 'perfil_actualizado', $userId, [
            'campos' => array_values(array_diff($campos, ['ine_frente', 'ine_reverso'])),
            'documentos_ine_actualizados' => in_array('ine_frente', $campos, true) || in_array('ine_reverso', $campos, true),
        ]);
    }

    public function descargarFormato(string $tramite)
    {
        $tramitesValidos = ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-04', 'UR-TT-T-05', 'UR-TT-T-06', 'UR-TT-T-07'];
        if (! in_array($tramite, $tramitesValidos, true)) {
            return redirect()->back()->with('error', 'Trámite no válido.');
        }

        $formatoModel = new FormatoTramiteModel();
        $formato = $formatoModel->where('tramite', $tramite)->where('activo', 1)->first();

        if ($formato === null) {
            return redirect()->back()->with('error', 'No hay formato disponible para este trámite.');
        }

        $storage = new \App\Libraries\SupabaseStorage();
        $url = $storage->urlFirmada('formatos', $formato->ruta_interna);
        if ($url === null) {
            return redirect()->back()->with('error', 'Error al obtener el formato.');
        }

        return redirect()->to($url);
    }
}
