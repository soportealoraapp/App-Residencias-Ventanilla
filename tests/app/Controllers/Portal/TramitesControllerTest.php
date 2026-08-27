<?php declare(strict_types=1);

namespace Tests\Controllers\Portal;

use App\Models\HistorialEstatusModel;
use App\Models\SolicitudDatoModel;
use App\Models\SolicitudModel;
use App\Models\VerificacionFisicaModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class TramitesControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;
    private int $operadorId;
    private int $convocatoriaId;
    private int $concesionId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCatalogos();
    }

    private function seedCatalogos(): void
    {
        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);
        $roles = $this->db->table('roles')->get()->getResult();
        $roleIds = [];
        foreach ($roles as $rol) {
            $roleIds[$rol->nombre] = (int) $rol->id;
        }

        $this->ciudadanoId = $this->insertUser('ciudadano1', 'ciudadano@example.com', 'Ciudadano Prueba');
        $this->operadorId = $this->insertUser('operador1', 'operador1@uriangato.gob.mx', 'Operador Prueba');
        $this->db->table('user_roles')->insertBatch([
            ['user_id' => $this->ciudadanoId, 'role_id' => $roleIds['ciudadano']],
            ['user_id' => $this->operadorId, 'role_id' => $roleIds['operador_ventanilla']],
        ]);

        $this->db->table('tarifas')->insertBatch([
            $this->tarifa('UR-TT-T-01', 9055.20),
            $this->tarifa('UR-TT-T-02', 64.90),
            $this->tarifa('UR-TT-T-03', 50.00),
        ]);

        $this->db->table('convocatorias')->insert([
            'fecha_publicacion' => date('Y-m-d', strtotime('-7 days')),
            'periodo_registro_inicio' => date('Y-m-d', strtotime('-2 days')),
            'periodo_registro_fin' => date('Y-m-d', strtotime('+30 days')),
            'bases' => 'Convocatoria de prueba UR-01',
            'estatus' => 'Vigente',
        ]);
        $this->convocatoriaId = (int) $this->db->insertID();

        $this->db->table('concesiones')->insert([
            'numero_titulo' => 'CONC-URI-2024-0001',
            'titular_actual' => 'María González López',
            'vehiculo_placas' => 'GTO-123-45',
            'vehiculo_num_serie' => '3VWSK7AN1RM000001',
            'tipo_persona' => 'fisica',
            'vigencia_inicio' => '2024-01-15',
            'vigencia_fin' => '2029-01-15',
            'estatus' => 'vigente',
        ]);
        $this->concesionId = (int) $this->db->insertID();
    }

    private function insertUser(string $username, string $email, string $nombre): int
    {
        $this->db->table('users')->insert([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash('12345678', PASSWORD_DEFAULT),
            'nombre_completo' => $nombre,
            'activo' => 1,
        ]);

        return (int) $this->db->insertID();
    }

    private function tarifa(string $tramite, float $monto): array
    {
        return [
            'tramite' => $tramite,
            'criterio' => 'base',
            'monto' => $monto,
            'vigente_desde' => date('Y-m-d'),
            'placeholder_oficial' => 1,
            'descripcion' => 'editable en catalogo',
        ];
    }

    private function sessionCiudadano(): self
    {
        return $this->withSession([
            'user_id' => $this->ciudadanoId,
            'username' => 'ciudadano1',
            'nombre_completo' => 'Ciudadano Prueba',
            'roles' => ['ciudadano'],
        ]);
    }

    private function sessionOperador(): self
    {
        return $this->withSession([
            'user_id' => $this->operadorId,
            'username' => 'operador1',
            'nombre_completo' => 'Operador Prueba',
            'roles' => ['operador_ventanilla'],
        ]);
    }

    private function postJson(string $path, array $payload)
    {
        return $this->withBodyFormat('json')->post($path, $payload);
    }

    public function testFlujosCompletosUr01Ur02Ur03(): void
    {
        $ur01a = $this->sessionCiudadano()->postJson('portal/tramites/solicitudes', [
            'tramite' => 'UR-TT-T-01',
            'convocatoria_id' => $this->convocatoriaId,
            'datos' => ['solicitante' => 'Juan Perez', 'tipo_persona' => 'fisica'],
        ]);
        $this->assertSame(201, $ur01a->response()->getStatusCode(), (string) $ur01a->response()->getBody());
        $body01a = json_decode((string) $ur01a->response()->getBody(), true);
        $solA = $body01a['solicitud'];

        $ur01b = $this->sessionCiudadano()->postJson('portal/tramites/solicitudes', [
            'tramite' => 'UR-TT-T-01',
            'convocatoria_id' => $this->convocatoriaId,
            'datos' => ['solicitante' => 'Ana Lopez', 'tipo_persona' => 'fisica'],
        ]);
        $this->assertSame(201, $ur01b->response()->getStatusCode(), (string) $ur01b->response()->getBody());
        $body01b = json_decode((string) $ur01b->response()->getBody(), true);
        $solB = $body01b['solicitud'];

        $this->assertSame('9055.20', number_format((float) $solA['monto'], 2, '.', ''));

        $consulta01 = $this->sessionCiudadano()->get('portal/tramites/solicitudes/' . $solA['folio']);
        $this->assertSame(200, $consulta01->response()->getStatusCode(), (string) $consulta01->response()->getBody());
        $bodyConsulta01 = json_decode((string) $consulta01->response()->getBody(), true);

        $listado = $this->sessionOperador()->get('portal/tramites/ur-01/convocatorias/' . $this->convocatoriaId . '/solicitudes');
        $this->assertSame(200, $listado->response()->getStatusCode(), (string) $listado->response()->getBody());
        $bodyListado = json_decode((string) $listado->response()->getBody(), true);
        $this->assertCount(2, $bodyListado['solicitudes']);

        $seleccion = $this->sessionOperador()->postJson(
            'portal/tramites/ur-01/convocatorias/' . $this->convocatoriaId . '/seleccionar',
            ['solicitud_id' => (int) $solA['id']]
        );
        $this->assertSame(200, $seleccion->response()->getStatusCode(), (string) $seleccion->response()->getBody());
        $bodySeleccion = json_decode((string) $seleccion->response()->getBody(), true);

        $historialB = (new HistorialEstatusModel())->porSolicitud((int) $solB['id']);
        $estatusHistorial = array_map(static fn ($row) => $row->estatus_nuevo, $historialB);
        $this->assertContains('No seleccionado', $estatusHistorial);

        $ur02 = $this->sessionCiudadano()->postJson('portal/tramites/solicitudes', [
            'tramite' => 'UR-TT-T-02',
            'concesion_id' => $this->concesionId,
            'datos' => ['motivo' => 'Verificacion periodica'],
        ]);
        $this->assertSame(201, $ur02->response()->getStatusCode(), (string) $ur02->response()->getBody());
        $body02 = json_decode((string) $ur02->response()->getBody(), true);
        $sol02 = $body02['solicitud'];
        $this->assertSame('64.90', number_format((float) $sol02['monto'], 2, '.', ''));

        $cita = $this->sessionCiudadano()->postJson(
            'portal/tramites/ur-02/solicitudes/' . $sol02['id'] . '/cita',
            ['fecha_cita' => date('Y-m-d H:i:s', strtotime('+3 days'))]
        );
        $this->assertSame(201, $cita->response()->getStatusCode(), (string) $cita->response()->getBody());
        $bodyCita = json_decode((string) $cita->response()->getBody(), true);

        $resultado = $this->sessionOperador()->postJson(
            'portal/tramites/ur-02/solicitudes/' . $sol02['id'] . '/resultado',
            ['resultado' => 'aprobado', 'observaciones' => 'Unidad en condiciones de circular.']
        );
        $this->assertSame(200, $resultado->response()->getStatusCode(), (string) $resultado->getBody());
        $bodyResultado = json_decode((string) $resultado->response()->getBody(), true);

        $consulta02 = $this->sessionOperador()->get('portal/tramites/solicitudes/' . $sol02['folio']);
        $this->assertSame(200, $consulta02->response()->getStatusCode(), (string) $consulta02->response()->getBody());
        $bodyConsulta02 = json_decode((string) $consulta02->response()->getBody(), true);
        $this->assertSame('Verificado', $bodyConsulta02['solicitud']['estatus']);
        $this->assertNotEmpty($bodyConsulta02['verificaciones']);

        $ur03 = $this->sessionCiudadano()->postJson('portal/tramites/solicitudes', [
            'tramite' => 'UR-TT-T-03',
            'concesion_id' => $this->concesionId,
            'datos' => ['tipo_servicio' => 'revalidacion'],
        ]);
        $this->assertSame(201, $ur03->response()->getStatusCode(), (string) $ur03->response()->getBody());
        $body03 = json_decode((string) $ur03->response()->getBody(), true);
        $sol03 = $body03['solicitud'];
        $this->assertSame('50.00', number_format((float) $sol03['monto'], 2, '.', ''));

        $rev = $this->sessionOperador()->post(
            'admin/solicitudes/cambiar-estatus/' . $sol03['id'],
            ['nuevo_estatus' => 'En revisión']
        );
        $this->assertTrue($rev->isRedirect(), (string) $rev->response()->getBody());
        $apr = $this->sessionOperador()->post(
            'admin/solicitudes/cambiar-estatus/' . $sol03['id'],
            ['nuevo_estatus' => 'Aprobado']
        );
        $this->assertTrue($apr->isRedirect(), (string) $apr->response()->getBody());

        $consulta03 = $this->sessionCiudadano()->get('portal/tramites/solicitudes/' . $sol03['folio']);
        $this->assertSame(200, $consulta03->response()->getStatusCode(), (string) $consulta03->response()->getBody());
        $bodyConsulta03 = json_decode((string) $consulta03->response()->getBody(), true);
        $this->assertSame('Aprobado', $bodyConsulta03['solicitud']['estatus']);
        $this->assertSame([], $bodyConsulta03['verificaciones']);

        $desdeDb = [
            'ur01' => (new SolicitudModel())->find((int) $solA['id']),
            'ur01_no_seleccionada' => (new SolicitudModel())->find((int) $solB['id']),
            'ur01_datos' => (new SolicitudDatoModel())->porSolicitudAgrupado((int) $solA['id']),
            'ur02' => (new SolicitudModel())->find((int) $sol02['id']),
            'ur02_verificacion' => (new VerificacionFisicaModel())->porSolicitud((int) $sol02['id']),
            'ur03' => (new SolicitudModel())->find((int) $sol03['id']),
            'ur03_datos' => (new SolicitudDatoModel())->porSolicitudAgrupado((int) $sol03['id']),
        ];
        $this->assertSame('Seleccionado', $desdeDb['ur01']->estatus);
        $this->assertSame('No seleccionado', $desdeDb['ur01_no_seleccionada']->estatus);
        $this->assertSame('Juan Perez', $desdeDb['ur01_datos']['solicitante']);
        $this->assertSame('Verificado', $desdeDb['ur02']->estatus);
        $this->assertSame('aprobado', $desdeDb['ur02_verificacion'][0]->resultado);
        $this->assertSame((string) $this->concesionId, (string) $desdeDb['ur02']->concesion_id);
        $this->assertSame('revalidacion', $desdeDb['ur03_datos']['tipo_servicio']);

        $evidencia = [
            'ur01_crear_a' => $body01a,
            'ur01_crear_b' => $body01b,
            'ur01_consultar' => $bodyConsulta01,
            'ur01_listado' => $bodyListado,
            'ur01_seleccion' => $bodySeleccion,
            'ur02_crear' => $body02,
            'ur02_cita' => $bodyCita,
            'ur02_resultado' => $bodyResultado,
            'ur02_consultar' => $bodyConsulta02,
            'ur03_crear' => $body03,
            'ur03_consultar' => $bodyConsulta03,
        ];
        $dir = WRITEPATH . 'logs';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/ur-tramites-evidencia.json', json_encode($evidencia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
