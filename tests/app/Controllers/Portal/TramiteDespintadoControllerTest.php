<?php declare(strict_types=1);

namespace Tests\Controllers\Portal;

use App\Controllers\Admin\AdminController;
use App\Controllers\Portal\TramiteDespintadoController;
use App\Models\DocumentoModel;
use App\Models\HistorialEstatusModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use App\Models\VerificacionFisicaModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\FeatureTestTrait;
use Config\App;
use Config\Services;
use Tests\Support\DatabaseTestCase;

class TramiteDespintadoControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;
    private int $operadorId;
    private int $concesionId;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_FILES'] = [];
        $GLOBALS['_POST'] = [];
        $_FILES = [];
        $_POST = [];
        $this->request = null;
        Services::resetSingle('request');
        Services::resetSingle('validation');
        $this->seedData();
    }

    protected function tearDown(): void
    {
        $GLOBALS['_FILES'] = [];
        $GLOBALS['_POST'] = [];
        $_FILES = [];
        $_POST = [];
        $this->request = null;
        Services::resetSingle('request');
        Services::resetSingle('validation');
        parent::tearDown();
    }

    private function seedData(): void
    {
        $this->db->table('roles')->insertBatch([
            ['id' => 1, 'nombre' => 'administrador', 'descripcion' => 'admin'],
            ['id' => 2, 'nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['id' => 3, 'nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);

        $this->db->table('users')->insert([
            'username'        => 'ciudadano_ur02',
            'email'           => 'ciudadano_ur02@example.com',
            'password_hash'   => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => 'María Fernández Despintado',
            'activo'          => 1,
        ]);
        $this->ciudadanoId = (int) $this->db->insertID();

        $this->db->table('user_roles')->insert([
            'user_id' => $this->ciudadanoId,
            'role_id' => 3,
        ]);

        $this->db->table('users')->insert([
            'username'        => 'operador_ur02',
            'email'           => 'operador_ur02@example.com',
            'password_hash'   => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => 'Inspector Carlos Vaca',
            'activo'          => 1,
        ]);
        $this->operadorId = (int) $this->db->insertID();

        $this->db->table('user_roles')->insert([
            'user_id' => $this->operadorId,
            'role_id' => 2,
        ]);

        $this->db->table('tarifas')->insert([
            'tramite'             => 'UR-TT-T-02',
            'criterio'            => 'base',
            'monto'               => 64.90,
            'vigente_desde'       => date('Y-m-d'),
            'placeholder_oficial' => 1,
            'descripcion'         => 'Tarifa Constancia de Despintado',
        ]);

        $this->db->table('concesiones')->insert([
            'numero_titulo'      => 'CONC-URI-2024-0002',
            'titular_actual'     => 'María Fernández Despintado',
            'vehiculo_placas'    => 'GTO-543-A',
            'vehiculo_num_serie' => '3VWSK7AN1RM000002',
            'tipo_persona'       => 'fisica',
            'vigencia_inicio'    => '2024-01-15',
            'vigencia_fin'       => '2029-01-15',
            'estatus'            => 'vigente',
        ]);
        $this->concesionId = (int) $this->db->insertID();
    }

    private function sessionCiudadano(): self
    {
        return $this->withSession([
            'user_id'         => $this->ciudadanoId,
            'username'        => 'ciudadano_ur02',
            'nombre_completo' => 'María Fernández Despintado',
            'roles'           => ['ciudadano'],
        ]);
    }

    public function testFormularioGetRenders200(): void
    {
        $_FILES = [];
        $_POST = [];
        Services::resetSingle('request');
        Services::resetSingle('validation');

        $response = $this->sessionCiudadano()->get('portal/tramites/constancia-despintado');
        $this->assertSame(200, $response->response()->getStatusCode());
        $this->assertStringContainsString('Constancia de Despintado', (string) $response->response()->getBody());
        $this->assertStringContainsString('64.90', (string) $response->response()->getBody());
    }

    public function testGuardarSolicitudYAgendarCita(): void
    {
        $tmpDir = WRITEPATH . 'test_tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $dummyPdf = $tmpDir . '/dummy_ur02.pdf';
        file_put_contents($dummyPdf, '%PDF-1.4 dummy UR02 content');

        $_FILES = [
            'doc_identificacion' => [
                'name'     => 'ine_ur02.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
            'doc_factura' => [
                'name'     => 'factura_ur02.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
        ];

        $_POST = [
            'numero_titulo_concesion' => 'CONC-URI-2024-0002',
            'nombre_titular'          => 'María Fernández Despintado',
            'vehiculo_placas'         => 'GTO-543-A',
            'vehiculo_num_serie'      => '3VWSK7AN1RM000002',
            'motivo_despintado'       => 'Sustitución de unidad por modelo 2026',
        ];

        $_SESSION = [
            'user_id'         => $this->ciudadanoId,
            'username'        => 'ciudadano_ur02',
            'nombre_completo' => 'María Fernández Despintado',
            'roles'           => ['ciudadano'],
        ];

        $appConfig = new App();
        $uri = new SiteURI($appConfig, 'portal/tramites/constancia-despintado/guardar');
        $userAgent = new UserAgent();
        $request = new IncomingRequest($appConfig, $uri, 'php://input', $userAgent);
        $request->setMethod('POST');
        $request->setGlobal('post', $_POST);
        $request->setGlobal('request', $_POST);
        $request->setGlobal('files', $_FILES);

        Services::injectMock('request', $request);
        Services::injectMock('validation', Services::validation(null, false));
        Services::session()->set($_SESSION);

        $controller = new TramiteDespintadoController();
        $controller->initController($request, new Response($appConfig), Services::logger());

        $result = $controller->guardar();
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $result);

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->where('tramite', 'UR-TT-T-02')->where('ciudadano_id', $this->ciudadanoId)->first();
        $this->assertNotNull($solicitud);
        $this->assertSame('Recibido', $solicitud->estatus);
        $this->assertSame('64.90', number_format((float) $solicitud->monto, 2, '.', ''));

        // Agendar Cita
        $fechaCita = date('Y-m-d\TH:i', strtotime('+2 days 10:00'));
        $_POST = ['fecha_cita' => $fechaCita];

        $uriCita = new SiteURI($appConfig, 'portal/tramites/ur-02/solicitud/' . $solicitud->folio . '/cita/guardar');
        $requestCita = new IncomingRequest($appConfig, $uriCita, 'php://input', $userAgent);
        $requestCita->setMethod('POST');
        $requestCita->setGlobal('post', $_POST);
        $requestCita->setGlobal('request', $_POST);

        Services::injectMock('request', $requestCita);
        Services::injectMock('validation', Services::validation(null, false));

        $controller->initController($requestCita, new Response($appConfig), Services::logger());
        $resCita = $controller->guardarCita($solicitud->folio);
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $resCita);

        $solicitudActualizada = $solicitudModel->find($solicitud->id);
        $this->assertSame('Cita agendada', $solicitudActualizada->estatus);

        $verificacion = (new VerificacionFisicaModel())->primerPorSolicitud((int) $solicitud->id);
        $this->assertNotNull($verificacion);
        $this->assertNotEmpty($verificacion->fecha_cita);

        // Operador/Admin registra dictamen Aprobado
        $_POST = [
            'resultado'     => 'aprobado',
            'observaciones' => 'Unidad desincorporada con despintado completo verificado conforme a la norma.',
        ];
        $_SESSION['user_id'] = $this->operadorId;
        $_SESSION['roles'] = ['operador_ventanilla'];

        $uriDictamen = new SiteURI($appConfig, 'admin/solicitudes/dictamen-ur02/' . $solicitud->id);
        $requestDictamen = new IncomingRequest($appConfig, $uriDictamen, 'php://input', $userAgent);
        $requestDictamen->setMethod('POST');
        $requestDictamen->setGlobal('post', $_POST);
        $requestDictamen->setGlobal('request', $_POST);

        Services::injectMock('request', $requestDictamen);
        Services::injectMock('validation', Services::validation(null, false));
        Services::session()->set($_SESSION);

        $adminController = new AdminController();
        $adminController->initController($requestDictamen, new Response($appConfig), Services::logger());
        $resDict = $adminController->registrarDictamenUr02((int) $solicitud->id);
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $resDict);

        $solicitudFinal = $solicitudModel->find($solicitud->id);
        $this->assertSame('Verificado', $solicitudFinal->estatus);

        $verificacionFinal = (new VerificacionFisicaModel())->primerPorSolicitud((int) $solicitud->id);
        $this->assertSame('aprobado', $verificacionFinal->resultado);
        $this->assertStringContainsString('despintado completo', $verificacionFinal->observaciones);

        $GLOBALS['_FILES'] = [];
        $GLOBALS['_POST'] = [];
        $_FILES = [];
        $_POST = [];
        Services::injectMock('request', null);
        Services::injectMock('validation', null);
        Services::resetSingle('request');
        Services::resetSingle('validation');
    }
}
