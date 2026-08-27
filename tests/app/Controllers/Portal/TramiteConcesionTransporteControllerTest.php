<?php declare(strict_types=1);

namespace Tests\Controllers\Portal;

use App\Controllers\Admin\AdminController;
use App\Controllers\Portal\TramiteConcesionTransporteController;
use App\Models\ConvocatoriaModel;
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

class TramiteConcesionTransporteControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadano1Id;
    private int $ciudadano2Id;
    private int $operadorId;
    private int $convocatoriaId;

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
            'username'        => 'postulante1_ur01',
            'email'           => 'postulante1_ur01@example.com',
            'password_hash'   => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => 'Transportes Uriangato S.A. de C.V.',
            'activo'          => 1,
        ]);
        $this->ciudadano1Id = (int) $this->db->insertID();

        $this->db->table('user_roles')->insert([
            'user_id' => $this->ciudadano1Id,
            'role_id' => 3,
        ]);

        $this->db->table('users')->insert([
            'username'        => 'postulante2_ur01',
            'email'           => 'postulante2_ur01@example.com',
            'password_hash'   => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => 'Juan Pérez Concesiones',
            'activo'          => 1,
        ]);
        $this->ciudadano2Id = (int) $this->db->insertID();

        $this->db->table('user_roles')->insert([
            'user_id' => $this->ciudadano2Id,
            'role_id' => 3,
        ]);

        $this->db->table('users')->insert([
            'username'        => 'operador_ur01',
            'email'           => 'operador_ur01@example.com',
            'password_hash'   => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => 'Lic. Fernando Ventanilla',
            'activo'          => 1,
        ]);
        $this->operadorId = (int) $this->db->insertID();

        $this->db->table('user_roles')->insert([
            'user_id' => $this->operadorId,
            'role_id' => 2,
        ]);

        $this->db->table('tarifas')->insert([
            'tramite'             => 'UR-TT-T-01',
            'criterio'            => 'base',
            'monto'               => 9055.20,
            'vigente_desde'       => date('Y-m-d'),
            'placeholder_oficial' => 1,
            'descripcion'         => 'Tarifa Otorgamiento de Concesión',
        ]);

        $this->db->table('convocatorias')->insert([
            'fecha_publicacion'       => date('Y-m-d', strtotime('-5 days')),
            'periodo_registro_inicio' => date('Y-m-d', strtotime('-2 days')),
            'periodo_registro_fin'    => date('Y-m-d', strtotime('+20 days')),
            'bases'                   => 'Convocatoria Pública Oficial 2026 para Otorgamiento de 5 Concesiones de Transporte Urbano en Uriangato, Gto.',
            'estatus'                 => 'Vigente',
            'created_at'              => date('Y-m-d H:i:s'),
            'updated_at'              => date('Y-m-d H:i:s'),
        ]);
        $this->convocatoriaId = (int) $this->db->insertID();
    }

    private function sessionCiudadano(): self
    {
        return $this->withSession([
            'user_id'         => $this->ciudadano1Id,
            'username'        => 'postulante1_ur01',
            'nombre_completo' => 'Transportes Uriangato S.A. de C.V.',
            'roles'           => ['ciudadano'],
        ]);
    }

    public function testFormularioGetRenders200WithActiveConvocatoria(): void
    {
        $_FILES = [];
        $_POST = [];
        Services::resetSingle('request');
        Services::resetSingle('validation');

        $response = $this->sessionCiudadano()->get('portal/tramites/concesion-transporte');
        $this->assertSame(200, $response->response()->getStatusCode());
        $this->assertStringContainsString('UR-TT-T-01', (string) $response->response()->getBody());
        $this->assertStringContainsString('9,055.20', (string) $response->response()->getBody());
        $this->assertStringContainsString('CONVOCATORIA VIGENTE', (string) $response->response()->getBody());
    }

    public function testFormularioGetRendersBlockingBannerWhenNoConvocatoria(): void
    {
        $_FILES = [];
        $_POST = [];
        Services::resetSingle('request');
        Services::resetSingle('validation');

        $this->db->table('convocatorias')->emptyTable();

        $response = $this->sessionCiudadano()->get('portal/tramites/concesion-transporte');
        $this->assertSame(200, $response->response()->getStatusCode());
        $this->assertStringContainsString('No hay Convocatoria Vigente Abierta', (string) $response->response()->getBody());
    }

    public function testPostulacionYSeleccionComparativaConvocatoria(): void
    {
        $tmpDir = WRITEPATH . 'test_tmp';
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        $dummyPdf = $tmpDir . '/expediente_ur01.pdf';
        file_put_contents($dummyPdf, '%PDF-1.4 dummy UR01 expediente content');

        $_FILES = [
            'doc_acta'         => ['name' => 'acta.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
            'doc_rfc'          => ['name' => 'rfc.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
            'doc_escritura'    => ['name' => 'escritura.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
            'doc_residencia'   => ['name' => 'residencia.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
            'doc_antecedentes' => ['name' => 'antecedentes.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
            'doc_proyecto'     => ['name' => 'proyecto.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
            'doc_vehiculos'    => ['name' => 'vehiculos.pdf', 'type' => 'application/pdf', 'size' => filesize($dummyPdf), 'tmp_name' => $dummyPdf, 'error' => UPLOAD_ERR_OK],
        ];

        $_POST = [
            'convocatoria_id'    => $this->convocatoriaId,
            'solicitante_nombre' => 'Transportes Uriangato S.A. de C.V.',
            'tipo_persona'       => 'moral',
            'rfc'                => 'TUR010101XYZ',
            'domicilio'          => 'Av. Hidalgo 100, Uriangato, Gto.',
            'tipo_servicio'      => 'Colectivo Urbano Ruta 1',
            'num_vehiculos'      => '4',
        ];

        $_SESSION = [
            'user_id'         => $this->ciudadano1Id,
            'username'        => 'postulante1_ur01',
            'nombre_completo' => 'Transportes Uriangato S.A. de C.V.',
            'roles'           => ['ciudadano'],
        ];

        $appConfig = new App();
        $uri = new SiteURI($appConfig, 'portal/tramites/concesion-transporte/guardar');
        $userAgent = new UserAgent();
        $request = new IncomingRequest($appConfig, $uri, 'php://input', $userAgent);
        $request->setMethod('POST');
        $request->setGlobal('post', $_POST);
        $request->setGlobal('request', $_POST);
        $request->setGlobal('files', $_FILES);

        Services::injectMock('request', $request);
        Services::injectMock('validation', Services::validation(null, false));
        Services::session()->set($_SESSION);

        $controller = new TramiteConcesionTransporteController();
        $controller->initController($request, new Response($appConfig), Services::logger());

        $res1 = $controller->guardar();
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $res1);

        $sol1 = (new SolicitudModel())->where('tramite', 'UR-TT-T-01')->where('ciudadano_id', $this->ciudadano1Id)->first();
        $this->assertNotNull($sol1);
        $this->assertSame((string)$this->convocatoriaId, (string)$sol1->convocatoria_id);

        // Crear segunda solicitud competidora para el mismo concurso
        $sol2Id = (new SolicitudModel())->insert([
            'folio'           => \App\Libraries\FolioGenerator::generar(),
            'tramite'         => 'UR-TT-T-01',
            'ciudadano_id'    => $this->ciudadano2Id,
            'convocatoria_id' => $this->convocatoriaId,
            'estatus'         => 'Recibido',
            'monto'           => 9055.20,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        (new SolicitudDatoModel())->guardarDatos((int)$sol2Id, [
            'solicitante_nombre' => 'Juan Pérez Concesiones',
            'tipo_persona'       => 'fisica',
            'rfc'                => 'PEJU800101ABC',
            'tipo_servicio'      => 'Colectivo Urbano Ruta 1',
            'num_vehiculos'      => '2',
        ]);

        // Operador accede a evaluación comparativa
        $_SESSION['user_id'] = $this->operadorId;
        $_SESSION['roles'] = ['operador_ventanilla'];

        $uriEval = new SiteURI($appConfig, 'admin/convocatorias/' . $this->convocatoriaId . '/evaluacion');
        $reqEval = new IncomingRequest($appConfig, $uriEval, 'php://input', $userAgent);

        Services::injectMock('request', $reqEval);
        Services::session()->set($_SESSION);

        $adminController = new AdminController();
        $adminController->initController($reqEval, new Response($appConfig), Services::logger());

        $viewRes = $adminController->evaluacionConvocatoria($this->convocatoriaId);
        $this->assertStringContainsString('Cuadro Comparativo de Solicitantes', (string)$viewRes);

        // Operador Selecciona al Ganador (sol1)
        $_POST = ['solicitud_id' => $sol1->id];
        $uriSel = new SiteURI($appConfig, 'admin/convocatorias/' . $this->convocatoriaId . '/seleccionar');
        $reqSel = new IncomingRequest($appConfig, $uriSel, 'php://input', $userAgent);
        $reqSel->setMethod('POST');
        $reqSel->setGlobal('post', $_POST);
        $reqSel->setGlobal('request', $_POST);

        Services::injectMock('request', $reqSel);
        Services::session()->set($_SESSION);

        $adminController->initController($reqSel, new Response($appConfig), Services::logger());
        $resSel = $adminController->seleccionarGanadorConvocatoria($this->convocatoriaId);
        $this->assertInstanceOf(\CodeIgniter\HTTP\RedirectResponse::class, $resSel);

        // Verificación de selección comparativa en BD
        $solModel = new SolicitudModel();
        $sol1Final = $solModel->find($sol1->id);
        $sol2Final = $solModel->find($sol2Id);

        $this->assertSame('Seleccionado', $sol1Final->estatus);
        $this->assertSame('No seleccionado', $sol2Final->estatus);

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
