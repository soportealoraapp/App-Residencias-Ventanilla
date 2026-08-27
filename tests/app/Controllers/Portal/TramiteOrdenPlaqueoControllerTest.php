<?php declare(strict_types=1);

namespace Tests\Controllers\Portal;

use App\Controllers\Portal\TramiteOrdenPlaqueoController;
use App\Models\DocumentoModel;
use App\Models\HistorialEstatusModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\FeatureTestTrait;
use Config\App;
use Config\Services;
use Tests\Support\DatabaseTestCase;

class TramiteOrdenPlaqueoControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;
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
            'username'      => 'ciudadano_ur03',
            'email'         => 'ciudadano_ur03@example.com',
            'password_hash' => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => 'Roberto Gómez Plaqueo',
            'activo'        => 1,
        ]);
        $this->ciudadanoId = (int) $this->db->insertID();

        $this->db->table('user_roles')->insert([
            'user_id' => $this->ciudadanoId,
            'role_id' => 3,
        ]);

        $this->db->table('tarifas')->insert([
            'tramite'             => 'UR-TT-T-03',
            'criterio'            => 'base',
            'monto'               => 50.00,
            'vigente_desde'       => date('Y-m-d'),
            'placeholder_oficial' => 1,
            'descripcion'         => 'Tarifa Orden de Plaqueo',
        ]);

        $this->db->table('concesiones')->insert([
            'numero_titulo'      => 'CONC-URI-2024-0001',
            'titular_actual'     => 'Roberto Gómez Plaqueo',
            'vehiculo_placas'    => 'GTO-123-45',
            'vehiculo_num_serie' => '3VWSK7AN1RM000001',
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
            'username'        => 'ciudadano_ur03',
            'nombre_completo' => 'Roberto Gómez Plaqueo',
            'roles'           => ['ciudadano'],
        ]);
    }

    public function testFormularioGetRenders200(): void
    {
        $_FILES = [];
        $_POST = [];
        Services::resetSingle('request');
        Services::resetSingle('validation');

        $response = $this->sessionCiudadano()->get('portal/tramites/orden-plaqueo');
        $this->assertSame(200, $response->response()->getStatusCode());
        $this->assertStringContainsString('Orden de Plaqueo', (string) $response->response()->getBody());
        $this->assertStringContainsString('50.00', (string) $response->response()->getBody());
        $this->assertStringContainsString('UR-TT-T-03', (string) $response->response()->getBody());
    }

    public function testGuardarFailsOnMissingFields(): void
    {
        $GLOBALS['_FILES'] = [];
        $GLOBALS['_POST'] = [];
        $_FILES = [];
        $_POST = [];
        $this->request = null;
        Services::injectMock('request', null);
        Services::injectMock('validation', null);
        Services::resetSingle('request');
        Services::resetSingle('validation');
        Services::resetSingle('incomingRequest');

        $response = $this->sessionCiudadano()->post('portal/tramites/orden-plaqueo/guardar', [
            'numero_titulo_concesion' => '',
        ]);
        $this->assertTrue($response->isRedirect());
    }

    public function testGuardarControllerDirectExecutionWithFiles(): void
    {
        $tmpDir = WRITEPATH . 'test_tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $dummyPdf = $tmpDir . '/dummy.pdf';
        file_put_contents($dummyPdf, '%PDF-1.4 dummy test content for plaqueo');

        $_FILES = [
            'doc_solicitud' => [
                'name'     => 'solicitud.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
            'doc_titulo_concesion' => [
                'name'     => 'titulo.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
            'doc_identificacion' => [
                'name'     => 'ine.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
            'doc_factura' => [
                'name'     => 'factura.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
            'doc_revista' => [
                'name'     => 'revista.pdf',
                'type'     => 'application/pdf',
                'size'     => filesize($dummyPdf),
                'tmp_name' => $dummyPdf,
                'error'    => UPLOAD_ERR_OK,
            ],
        ];

        $_POST = [
            'numero_titulo_concesion' => 'CONC-URI-2024-0001',
            'nombre_concesionario'    => 'Roberto Gómez Plaqueo',
            'tipo_persona'            => 'fisica',
            'numero_factura'          => 'FAC-2026-9901',
            'vehiculo_placas'         => 'GTO-999-B',
            'vehiculo_num_serie'      => '3VWSK7AN1RM999999',
        ];

        $_SESSION = [
            'user_id'         => $this->ciudadanoId,
            'username'        => 'ciudadano_ur03',
            'nombre_completo' => 'Roberto Gómez Plaqueo',
            'roles'           => ['ciudadano'],
        ];

        $appConfig = new App();
        $uri = new SiteURI($appConfig, 'portal/tramites/orden-plaqueo/guardar');
        $userAgent = new UserAgent();
        $request = new IncomingRequest($appConfig, $uri, 'php://input', $userAgent);
        $request->setMethod('POST');
        $request->setGlobal('post', $_POST);
        $request->setGlobal('request', $_POST);
        $request->setGlobal('files', $_FILES);

        Services::injectMock('request', $request);
        Services::injectMock('validation', Services::validation(null, false));
        Services::session()->set($_SESSION);

        $controller = new TramiteOrdenPlaqueoController();
        $controller->initController($request, new Response($appConfig), Services::logger());

        $result = $controller->guardar();
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $result);

        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->where('tramite', 'UR-TT-T-03')->where('ciudadano_id', $this->ciudadanoId)->first();
        $this->assertNotNull($solicitud, "Solicitud was not found in DB.");
        $this->assertSame('Recibido', $solicitud->estatus);
        $this->assertSame((string) $this->concesionId, (string) $solicitud->concesion_id);
        $this->assertSame('50.00', number_format((float) $solicitud->monto, 2, '.', ''));

        $datos = (new SolicitudDatoModel())->porSolicitudAgrupado((int) $solicitud->id);
        $this->assertSame('CONC-URI-2024-0001', $datos['numero_titulo_concesion']);
        $this->assertSame('Roberto Gómez Plaqueo', $datos['nombre_concesionario']);
        $this->assertSame('FAC-2026-9901', $datos['numero_factura']);

        $documentos = (new DocumentoModel())->porSolicitud((int) $solicitud->id);
        $this->assertNotEmpty($documentos);

        $historial = (new HistorialEstatusModel())->porSolicitud((int) $solicitud->id);
        $this->assertNotEmpty($historial);
        $this->assertSame('Recibido', $historial[0]->estatus_nuevo);

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
