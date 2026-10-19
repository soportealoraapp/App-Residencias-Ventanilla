<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Libraries\DocumentoUploader;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\FeatureFlags;
use App\Libraries\FolioGenerator;
use App\Libraries\TarifarioService;
use App\Models\AuditoriaModel;
use App\Models\ConcesionModel;
use App\Models\ConvocatoriaModel;
use App\Models\HistorialEstatusModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use App\Models\UserModel;
use Config\Services;
use DateTime;

/**
 * Permite al administrador / operador de ventanilla registrar solicitudes de
 * trámite presenciales por ciudadanos que acuden físicamente a la dependencia
 * y no utilizan el portal web.
 *
 * Soporta los 7 trámites (UR-TT-T-01 … UR-TT-T-07). Los documentos son
 * opcionales en el flujo presencial porque el ciudadano puede entregarlos
 * después, o el operador puede escanearlos en el momento.
 */
class SolicitudPresencialController extends Controller
{
    /**
     * Catálogo de campos por trámite. Cada campo tiene:
     *   label, type (text|select|date|time|number|textarea), required, options (select)
     */
    private const CAMPOS_TRAMITE = [
        'UR-TT-T-01' => [
            ['name' => 'solicitante_nombre', 'label' => 'Nombre o razón social del solicitante', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'tipo_persona', 'label' => 'Tipo de persona', 'type' => 'select', 'required' => true, 'options' => ['fisica' => 'Persona Física', 'moral' => 'Persona Moral']],
            ['name' => 'rfc', 'label' => 'RFC', 'type' => 'text', 'required' => true, 'max_length' => 13],
            ['name' => 'domicilio', 'label' => 'Domicilio', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'tipo_servicio', 'label' => 'Tipo de servicio', 'type' => 'text', 'required' => true, 'max_length' => 100],
            ['name' => 'num_vehiculos', 'label' => 'Número de vehículos', 'type' => 'number', 'required' => true],
        ],
        'UR-TT-T-02' => [
            ['name' => 'numero_titulo_concesion', 'label' => 'Número de título de concesión', 'type' => 'text', 'required' => true, 'max_length' => 50],
            ['name' => 'nombre_titular', 'label' => 'Nombre del titular / concesionario', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'vehiculo_placas', 'label' => 'Placas del vehículo', 'type' => 'text', 'required' => true, 'max_length' => 20],
            ['name' => 'vehiculo_num_serie', 'label' => 'Número de serie (VIN)', 'type' => 'text', 'required' => true, 'max_length' => 30],
            ['name' => 'motivo_despintado', 'label' => 'Motivo de despintado', 'type' => 'textarea', 'required' => true, 'max_length' => 250],
        ],
        'UR-TT-T-03' => [
            ['name' => 'numero_titulo_concesion', 'label' => 'Número de título de concesión', 'type' => 'text', 'required' => true, 'max_length' => 50],
            ['name' => 'nombre_concesionario', 'label' => 'Nombre del concesionario', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'tipo_persona', 'label' => 'Tipo de persona', 'type' => 'select', 'required' => true, 'options' => ['fisica' => 'Persona Física', 'moral' => 'Persona Moral']],
            ['name' => 'numero_factura', 'label' => 'Número de factura', 'type' => 'text', 'required' => true, 'max_length' => 60],
            ['name' => 'vehiculo_placas', 'label' => 'Placas actuales', 'type' => 'text', 'required' => false, 'max_length' => 20],
            ['name' => 'vehiculo_num_serie', 'label' => 'Número de serie (VIN)', 'type' => 'text', 'required' => false, 'max_length' => 30],
        ],
        'UR-TT-T-04' => [
            ['name' => 'nombre_razon_social', 'label' => 'Nombre o razón social', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'tipo_persona', 'label' => 'Tipo de persona', 'type' => 'select', 'required' => true, 'options' => ['fisica' => 'Persona Física', 'moral' => 'Persona Moral']],
            ['name' => 'rfc', 'label' => 'RFC', 'type' => 'text', 'required' => true, 'max_length' => 13],
            ['name' => 'domicilio', 'label' => 'Domicilio', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'tipo_servicio', 'label' => 'Tipo de servicio', 'type' => 'text', 'required' => true, 'max_length' => 100],
            ['name' => 'tipo_unidad', 'label' => 'Tipo de unidad', 'type' => 'text', 'required' => true, 'max_length' => 100],
            ['name' => 'cantidad_unidades', 'label' => 'Cantidad de unidades', 'type' => 'number', 'required' => true],
            ['name' => 'lugar_servicio', 'label' => 'Lugar del servicio', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'zona_servicio', 'label' => 'Zona del servicio', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'motivo_necesidad', 'label' => 'Motivo de la necesidad', 'type' => 'select', 'required' => true, 'options' => ['descompostura' => 'Descompostura', 'falta_unidades' => 'Falta de unidades', 'otra_necesidad' => 'Otra necesidad']],
            ['name' => 'descripcion_necesidad', 'label' => 'Descripción de la necesidad', 'type' => 'textarea', 'required' => true, 'max_length' => 500],
            ['name' => 'vigencia_observacion', 'label' => 'Vigencia u observación', 'type' => 'text', 'required' => true, 'max_length' => 250],
        ],
        'UR-TT-T-05' => [
            ['name' => 'nombre_solicitante', 'label' => 'Nombre del solicitante', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'domicilio', 'label' => 'Domicilio', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'fecha_cierre', 'label' => 'Fecha del cierre', 'type' => 'date', 'required' => true],
            ['name' => 'hora_inicio', 'label' => 'Hora de inicio', 'type' => 'time', 'required' => true],
            ['name' => 'hora_fin', 'label' => 'Hora de término', 'type' => 'time', 'required' => true],
            ['name' => 'calle_tramo', 'label' => 'Calle y tramo a cerrar', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'colonia', 'label' => 'Colonia', 'type' => 'text', 'required' => true, 'max_length' => 120],
            ['name' => 'tipo_cierre', 'label' => 'Tipo de cierre', 'type' => 'select', 'required' => true, 'options' => ['parcial' => 'Parcial', 'total' => 'Total']],
            ['name' => 'motivo_evento', 'label' => 'Motivo del evento', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'descripcion_evento', 'label' => 'Descripción del evento', 'type' => 'textarea', 'required' => true, 'max_length' => 500],
        ],
        'UR-TT-T-06' => [
            ['name' => 'solicitante_nombre', 'label' => 'Nombre del solicitante', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'solicitante_domicilio', 'label' => 'Domicilio del solicitante', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'tipo_cesion', 'label' => 'Tipo de cesión', 'type' => 'select', 'required' => true, 'options' => ['muerte_incapacidad' => 'Muerte o Incapacidad', 'cesion_derechos' => 'Cesión de Derechos', 'mandamiento_judicial' => 'Mandamiento Judicial']],
            ['name' => 'numero_titulo_concesion', 'label' => 'Número de título de concesión', 'type' => 'text', 'required' => true, 'max_length' => 50],
            ['name' => 'vehiculo_placas', 'label' => 'Placas del vehículo', 'type' => 'text', 'required' => true, 'max_length' => 10],
            ['name' => 'vehiculo_num_serie', 'label' => 'Número de serie (VIN)', 'type' => 'text', 'required' => true, 'max_length' => 20],
        ],
        'UR-TT-T-07' => [
            ['name' => 'tipo_solicitante', 'label' => 'Tipo de solicitante', 'type' => 'select', 'required' => true, 'options' => ['particular' => 'Particular', 'empresa' => 'Empresa']],
            ['name' => 'razon_social_o_nombre', 'label' => 'Nombre o razón social', 'type' => 'text', 'required' => true, 'max_length' => 180],
            ['name' => 'rfc', 'label' => 'RFC', 'type' => 'text', 'required' => true, 'max_length' => 13],
            ['name' => 'domicilio_negocio', 'label' => 'Domicilio del negocio o particular', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'direccion_carga_descarga', 'label' => 'Dirección de carga/descarga', 'type' => 'text', 'required' => true, 'max_length' => 250],
            ['name' => 'periodo', 'label' => 'Periodo de vigencia', 'type' => 'select', 'required' => true, 'options' => ['dia' => 'Día', 'mes' => 'Mes', 'semestre' => 'Semestre', 'anio' => 'Año']],
            ['name' => 'num_camiones', 'label' => 'Número de camiones (solo empresa)', 'type' => 'number', 'required' => false],
            ['name' => 'horario_inicio', 'label' => 'Horario de inicio', 'type' => 'time', 'required' => true],
            ['name' => 'horario_fin', 'label' => 'Horario de término', 'type' => 'time', 'required' => true],
            ['name' => 'es_mudanza', 'label' => '¿Es mudanza?', 'type' => 'select', 'required' => false, 'options' => ['0' => 'No', '1' => 'Sí']],
        ],
    ];

    private const TRAMITES = [
        'UR-TT-T-01' => ['nombre' => 'Concesión de Transporte', 'icono' => 'bi-award'],
        'UR-TT-T-02' => ['nombre' => 'Constancia de Despintado', 'icono' => 'bi-paint-bucket'],
        'UR-TT-T-03' => ['nombre' => 'Orden de Plaqueo', 'icono' => 'bi-card-heading'],
        'UR-TT-T-04' => ['nombre' => 'Permiso Eventual de Transporte', 'icono' => 'bi-bus-front'],
        'UR-TT-T-05' => ['nombre' => 'Permiso para Cierre de Calle', 'icono' => 'bi-sign-stop'],
        'UR-TT-T-06' => ['nombre' => 'Cesión de Concesión', 'icono' => 'bi-arrow-left-right'],
        'UR-TT-T-07' => ['nombre' => 'Permiso de Carga y Descarga', 'icono' => 'bi-truck'],
    ];

    public function __construct()
    {
        helper(['url', 'form', 'url_helper_custom']);
    }

    /**
     * Paso 1: Selección de trámite + búsqueda de ciudadano.
     */
    public function index()
    {
        $q = trim((string) ($this->request->getGet('q') ?? ''));
        $ciudadanos = [];

        if ($q !== '') {
            $userModel = new UserModel();
            $ciudadanos = $userModel
                ->groupStart()
                    ->like('nombre_completo', $q)
                    ->orLike('username', $q)
                    ->orLike('email', $q)
                ->groupEnd()
                ->where('activo', 1)
                ->orderBy('nombre_completo', 'ASC')
                ->limit(20)
                ->findAll();
        }

        $tramites = self::TRAMITES;
        if (!FeatureFlags::habilitarUrTtT06()) {
            unset($tramites['UR-TT-T-06']);
        }

        return view('admin/solicitudes_presenciales/index', [
            'tramites'   => $tramites,
            'ciudadanos' => $ciudadanos,
            'q'          => $q,
        ]);
    }

    /**
     * Paso 2: Formulario dinámico por tipo de trámite.
     */
    public function formulario(string $tramite)
    {
        if (! isset(self::CAMPOS_TRAMITE[$tramite])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if ($tramite === 'UR-TT-T-06' && ! FeatureFlags::habilitarUrTtT06()) {
            return redirect()->to('/admin/solicitudes/nueva')->with('error', 'UR-TT-T-06 está deshabilitado temporalmente.');
        }

        $ciudadanoId = (int) $this->request->getGet('ciudadano_id');
        $ciudadano = $ciudadanoId > 0 ? (new UserModel())->find($ciudadanoId) : null;

        // Datos extra según trámite (convocatorias para UR-01, tarifas para mostrar monto)
        $extras = [];
        if ($tramite === 'UR-TT-T-01') {
            $convocatoriaModel = new ConvocatoriaModel();
            // abiertasHoy() compara el estatus con el texto exacto ('Vigente') y
            // además exige que hoy caiga dentro del periodo de registro. Antes se
            // filtraba por 'vigente' en minúsculas, y como Postgres distingue
            // mayúsculas la lista salía vacía aunque hubiera convocatoria.
            try {
                $extras['convocatorias'] = $convocatoriaModel->abiertasHoy();
            } catch (\Throwable $e) {
                log_message('error', 'SolicitudPresencialController::formulario no pudo leer convocatorias: ' . $e->getMessage());
                $extras['convocatorias'] = [];
            }
        }

        $tarifaMonto = $this->obtenerMontoTarifa($tramite);

        return view('admin/solicitudes_presenciales/formulario', [
            'tramite'     => $tramite,
            'tramiteInfo' => self::TRAMITES[$tramite],
            'campos'      => self::CAMPOS_TRAMITE[$tramite],
            'ciudadano'   => $ciudadano,
            'ciudadanoId' => $ciudadanoId,
            'extras'      => $extras,
            'tarifaMonto' => $tarifaMonto,
        ]);
    }

    /**
     * Guarda la solicitud presencial.
     */
    public function guardar()
    {
        $request = Services::request();
        $session = Services::session();
        $operadorId = (int) $session->get('user_id');

        $tramite = (string) $request->getPost('tramite');
        $ciudadanoId = (int) $request->getPost('ciudadano_id');

        if (! isset(self::CAMPOS_TRAMITE[$tramite])) {
            return redirect()->back()->with('error', 'Trámite no válido.');
        }
        if ($tramite === 'UR-TT-T-06' && ! FeatureFlags::habilitarUrTtT06()) {
            return redirect()->back()->with('error', 'UR-TT-T-06 está deshabilitado.');
        }

        // Validar campos requeridos del trámite
        $rules = [];
        foreach (self::CAMPOS_TRAMITE[$tramite] as $campo) {
            $ruleStr = $campo['required'] ? 'required' : 'permit_empty';
            if (isset($campo['max_length'])) {
                $ruleStr .= '|max_length[' . $campo['max_length'] . ']';
            }
            if ($campo['type'] === 'select' && isset($campo['options'])) {
                $ruleStr .= '|in_list[' . implode(',', array_keys($campo['options'])) . ']';
            }
            if ($campo['type'] === 'date') {
                $ruleStr .= '|valid_date[Y-m-d]';
            }
            if ($campo['type'] === 'number' && $campo['required']) {
                $ruleStr .= '|is_natural_no_zero';
            }
            $rules[$campo['name']] = ['rules' => $ruleStr, 'label' => $campo['label']];
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Validar ciudadano (se requiere cuenta del portal)
        if ($ciudadanoId <= 0) {
            return redirect()->back()->withInput()->with('error', 'Debe seleccionar un ciudadano. Si no tiene cuenta, créela primero en Usuarios y ciudadanos.');
        }
        $userModel = new UserModel();
        if ($userModel->find($ciudadanoId) === null) {
            return redirect()->back()->withInput()->with('error', 'El ciudadano seleccionado no existe.');
        }

        // Convocatoria para UR-01
        $convocatoriaId = null;
        if ($tramite === 'UR-TT-T-01') {
            $convocatoriaId = (int) $request->getPost('convocatoria_id');
            if ($convocatoriaId <= 0) {
                return redirect()->back()->withInput()->with('error', 'Debe seleccionar una convocatoria vigente.');
            }
            $convocatoria = (new ConvocatoriaModel())->vigente($convocatoriaId);
            if ($convocatoria === null) {
                return redirect()->back()->withInput()->with('error', 'La convocatoria seleccionada no está vigente.');
            }
        }

        // Monto de la tarifa
        $monto = $this->obtenerMontoTarifa($tramite, $request);
        if ($monto === null) {
            return redirect()->back()->withInput()->with('error', 'No existe tarifa vigente para este trámite. Configúrala primero en Tarifario.');
        }

        // Concesión para UR-02/03/06
        $concesionId = null;
        if (in_array($tramite, ['UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-06'], true)) {
            $numTitulo = trim((string) $request->getPost('numero_titulo_concesion'));
            if ($numTitulo !== '') {
                $concesion = (new ConcesionModel())->findByNumeroTitulo($numTitulo);
                $concesionId = $concesion ? (int) $concesion->id : null;
            }
        }

        // Crear la solicitud
        $solicitudModel = new SolicitudModel();
        $db = $solicitudModel->db;
        $db->transStart();

        $folio = FolioGenerator::generar();
        $solicitudId = $solicitudModel->insert([
            'folio'           => $folio,
            'tramite'         => $tramite,
            'ciudadano_id'    => $ciudadanoId,
            'concesion_id'    => $concesionId,
            'convocatoria_id' => $convocatoriaId,
            'estatus'         => 'Recibido',
            'monto'           => $monto,
            'fecha_solicitud' => (new DateTime())->format('Y-m-d H:i:s'),
        ]);

        if ($solicitudId === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'No fue posible crear la solicitud.');
        }

        // Guardar datos específicos del trámite
        $datosGuardar = ['origen' => 'presencial'];
        foreach (self::CAMPOS_TRAMITE[$tramite] as $campo) {
            $datosGuardar[$campo['name']] = (string) $request->getPost($campo['name']);
        }
        // Campos extra por trámite
        if ($tramite === 'UR-TT-T-01') {
            $datosGuardar['convocatoria_bases'] = 'Convocatoria oficial (captura presencial)';
            $datosGuardar['tramite_concepto'] = 'Otorgamiento de Concesión de Transporte Público';
        } elseif ($tramite === 'UR-TT-T-02') {
            $datosGuardar['tramite_concepto'] = 'Constancia de Despintado';
        } elseif ($tramite === 'UR-TT-T-03') {
            $datosGuardar['tramite_concepto'] = 'Orden de Plaqueo';
        } elseif ($tramite === 'UR-TT-T-04') {
            $datosGuardar['tramite_concepto'] = 'Permiso Eventual de Transporte';
        } elseif ($tramite === 'UR-TT-T-05') {
            $datosGuardar['tramite_concepto'] = 'Permiso para Cierre de Calle';
        } elseif ($tramite === 'UR-TT-T-06') {
            $datosGuardar['tramite_concepto'] = 'Cesión de Concesión';
            $concesion = $concesionId ? (new ConcesionModel())->find($concesionId) : null;
            $datosGuardar['numero_titulo_original_titular'] = $concesion ? ($concesion->titular_actual ?? '') : '';
        } elseif ($tramite === 'UR-TT-T-07') {
            $datosGuardar['tramite_concepto'] = 'Permiso de Carga y Descarga';
        }

        (new SolicitudDatoModel())->guardarDatos((int) $solicitudId, $datosGuardar);

        // Historial de estatus inicial
        (new HistorialEstatusModel())->insert([
            'solicitud_id'     => (int) $solicitudId,
            'estatus_anterior' => null,
            'estatus_nuevo'    => 'Recibido',
            'usuario_id'       => $operadorId,
            'fecha'            => date('Y-m-d H:i:s'),
            'comentario'       => 'Solicitud registrada presencialmente por ventanilla.',
        ]);

        // Subir documentos opcionales si se adjuntan
        $documentosSubidos = $this->manejarDocumentosOpcionales($request, (int) $solicitudId, $operadorId);

        $db->transComplete();
        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Error al procesar la solicitud. Intente de nuevo.');
        }

        // Auditoría
        (new AuditoriaModel())->registrar('solicitudes', (int) $solicitudId, 'creada_presencial', $operadorId, [
            'folio'   => $folio,
            'tramite' => $tramite,
            'ciudadano_id' => $ciudadanoId ?: null,
            'documentos' => $documentosSubidos,
        ]);

        return redirect()->to('/admin/solicitudes/' . $folio)
            ->with('message', '¡Solicitud ' . $folio . ' registrada correctamente (atención presencial)!');
    }

    /**
     * Busca un ciudadano vía AJAX para el formulario dinámico.
     */
    public function buscarCiudadano()
    {
        $q = trim((string) ($this->request->getPost('q') ?? ''));
        if ($q === '' || mb_strlen($q) < 2) {
            return $this->response->setJSON(['resultados' => []]);
        }

        $userModel = new UserModel();
        $usuarios = $userModel
            ->select('id, nombre_completo, username, email, rfc, curp')
            ->groupStart()
                ->like('nombre_completo', $q)
                ->orLike('username', $q)
                ->orLike('email', $q)
                ->orLike('rfc', $q)
                ->orLike('curp', $q)
            ->groupEnd()
            ->where('activo', 1)
            ->limit(10)
            ->findAll();

        $resultados = array_map(static fn ($u) => [
            'id'      => (int) $u->id,
            'nombre'  => $u->nombre_completo ?? $u->username,
            'email'   => $u->email ?? '',
            'rfc'     => $u->rfc ?? '',
        ], $usuarios);

        return $this->response->setJSON(['resultados' => $resultados]);
    }

    /**
     * Obtiene el monto de tarifa considerando parámetros dinámicos de UR-07.
     */
    private function obtenerMontoTarifa(string $tramite, ?object $request = null): ?float
    {
        $tarifario = new TarifarioService();

        if ($tramite === 'UR-TT-T-07' && $request !== null) {
            $tipoSolicitante = (string) ($request->getPost('tipo_solicitante') ?? 'particular');
            $periodo = (string) ($request->getPost('periodo') ?? 'dia');
            $numCamiones = max(1, (int) ($request->getPost('num_camiones') ?? 1));
            return $tarifario->calcularMontoUrTtT07($tipoSolicitante, $periodo, $numCamiones);
        }

        if ($tramite === 'UR-TT-T-06') {
            return $tarifario->calcularMontoUrTtT06();
        }

        return $tarifario->calcularMonto($tramite, 'base');
    }

    /**
     * Maneja documentos opcionales subidos por el operador.
     * @return list<string> Nombres de documentos subidos.
     */
    private function manejarDocumentosOpcionales(object $request, int $solicitudId, int $usuarioId): array
    {
        $subidos = [];
        $files = $request->getFiles();

        foreach ($files as $campo => $file) {
            if (! is_string($campo) || ! str_starts_with($campo, 'doc_')) {
                continue;
            }
            if ($file === null || ! ($file instanceof \CodeIgniter\HTTP\Files\UploadedFile)) {
                continue;
            }
            if (! $file->isValid() || $file->hasMoved()) {
                continue;
            }

            $tipoDoc = substr($campo, 4); // Remove 'doc_' prefix
            $uploader = new DocumentoUploader();
            $doc = $uploader->subir($file, $tipoDoc, $solicitudId, $usuarioId);
            if ($doc !== null) {
                $subidos[] = $tipoDoc;
            }
        }

        return $subidos;
    }
}
