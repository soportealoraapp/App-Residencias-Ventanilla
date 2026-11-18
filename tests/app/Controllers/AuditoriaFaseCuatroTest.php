<?php declare(strict_types=1);

namespace Tests\Controllers;

use App\Models\AgenteTransitoModel;
use App\Models\ConcesionModel;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Cors;
use Tests\Support\DatabaseTestCase;

/**
 * Auditoría de Félix, Fase 4: B1 (CORS con lista fija), B2 (logout de agentes),
 * B3 (validar-concesion sin datos personales) y B5 (límite y vigencia de la
 * recuperación de contraseña).
 */
class AuditoriaFaseCuatroTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->db->table('roles')->where('nombre', 'ciudadano')->countAllResults() === 0) {
            $this->db->table('roles')->insert(['nombre' => 'ciudadano', 'descripcion' => 'ciudadano']);
        }
        $rolId = (int) $this->db->table('roles')->where('nombre', 'ciudadano')->get()->getRow()->id;
        $this->db->table('users')->insert([
            'username' => 'ciudadano_f4', 'email' => 'ciudadano@f4.test', 'password_hash' => password_hash('x', PASSWORD_DEFAULT),
            'nombre_completo' => 'Prueba F4', 'activo' => 1,
        ]);
        $this->ciudadanoId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $this->ciudadanoId, 'role_id' => $rolId]);

        (new AgenteTransitoModel())->insert([
            'placa' => 'AGT-F4', 'nombre_completo' => 'Agente F4', 'password_hash' => password_hash('secreto123', PASSWORD_BCRYPT),
            'rol' => 'operativo', 'sector' => 'Pruebas', 'activo' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('APP_CORS_ORIGINS');
        putenv('APP_ENABLE_UR_TT_T_06');
        parent::tearDown();
    }

    // ─── B1 · CORS ────────────────────────────────────────────────────────

    public function testCorsSoloAceptaOrigenesDeLaLista(): void
    {
        $origenes = (new Cors())->default['allowedOrigins'];

        $this->assertSame([], (new Cors())->default['allowedOriginsPatterns']);
        $this->assertContains('http://localhost:8081', $origenes);
        $this->assertNotContains('https://evil.vercel.app', $origenes);

        putenv('APP_CORS_ORIGINS=https://movilidad.uriangato.gob.mx, javascript:alert(1), https://otro.test/');
        $origenes = (new Cors())->default['allowedOrigins'];
        $this->assertContains('https://movilidad.uriangato.gob.mx', $origenes);
        $this->assertContains('https://otro.test', $origenes);
        $this->assertNotContains('javascript:alert(1)', $origenes);
    }

    public function testLaApiNoReflejaUnOrigenAjeno(): void
    {
        $respuesta = $this->withHeaders(['Origin' => 'https://evil.vercel.app'])->get('api/parametros');

        $respuesta->assertStatus(200);
        $this->assertNotSame('https://evil.vercel.app', $respuesta->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testLaApiAceptaUnOrigenDeLaLista(): void
    {
        $respuesta = $this->withHeaders(['Origin' => 'http://localhost:8081'])->get('api/parametros');

        $this->assertSame('http://localhost:8081', $respuesta->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    // ─── B2 · Logout de agentes ───────────────────────────────────────────

    public function testElLogoutRevocaElTokenEnElServidor(): void
    {
        $token = json_decode($this->post('api/agentes/login', ['placa' => 'AGT-F4', 'password' => 'secreto123'])->getJSON(), true)['token'];
        $auth  = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($auth)->post('api/agentes/logout')->assertStatus(200);

        $this->assertNotNull($this->db->table('agentes_transito_tokens')->get()->getRow()->revocado_en);
        $this->withHeaders($auth)->withBodyFormat('json')
            ->post('api/infracciones/sincronizar', ['folio' => 'X'])
            ->assertStatus(401);
        $this->assertSame(1, $this->db->table('auditoria')->where('accion', 'logout_agente')->countAllResults());
    }

    public function testElLogoutSinTokenNoFalla(): void
    {
        $this->post('api/agentes/logout')->assertStatus(200);
        $this->assertSame(0, $this->db->table('auditoria')->where('accion', 'logout_agente')->countAllResults());
    }

    // ─── B3 · validar-concesion ───────────────────────────────────────────

    public function testValidarConcesionNoDevuelveDatosDelTitular(): void
    {
        putenv('APP_ENABLE_UR_TT_T_06=true');
        (new ConcesionModel())->insert([
            'numero_titulo' => 'CONC-F4-001', 'titular_actual' => 'Titular Privado F4', 'vehiculo_placas' => 'GTO-F4-01',
            'vehiculo_num_serie' => 'SERIEF4', 'vigencia_inicio' => date('Y-m-d', strtotime('-1 year')),
            'vigencia_fin' => date('Y-m-d', strtotime('+1 year')), 'estatus' => 'vigente',
        ]);
        $sesion = ['user_id' => $this->ciudadanoId, 'username' => 'ciudadano_f4', 'roles' => ['ciudadano']];

        $json = json_decode($this->withSession($sesion)->get('portal/tramites/cesion-concesion/validar-concesion/CONC-F4-001')->getJSON(), true);
        $this->assertTrue($json['vigente']);
        $this->assertSame(['success', 'vigente', 'mensaje'], array_keys($json));

        $json = json_decode($this->withSession($sesion)->get('portal/tramites/cesion-concesion/validar-concesion/NO-EXISTE')->getJSON(), true);
        $this->assertFalse($json['vigente']);
    }

    // ─── B5 · Recuperar contraseña ────────────────────────────────────────

    public function testElEnlaceDeRecuperacionValeUnaHora(): void
    {
        $this->post('auth/attempt-forgot', ['email' => 'CIUDADANO@f4.test']);

        $expira = strtotime((string) $this->db->table('users')->where('id', $this->ciudadanoId)->get()->getRow()->reset_expira);
        $this->assertEqualsWithDelta(time() + 3600, $expira, 120);
    }

    public function testLaRecuperacionTieneLimitePorCorreo(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('auth/attempt-forgot', ['email' => 'ciudadano@f4.test']);
        }
        $this->db->table('users')->where('id', $this->ciudadanoId)->update(['reset_token' => null, 'reset_expira' => null]);

        $respuesta = $this->post('auth/attempt-forgot', ['email' => 'ciudadano@f4.test']);

        // Mismo mensaje de siempre, pero ya no se genera enlace.
        $respuesta->assertRedirect();
        $this->assertNull($this->db->table('users')->where('id', $this->ciudadanoId)->get()->getRow()->reset_token);
        $this->assertSame(1, $this->db->table('auditoria')->where('accion', 'recuperacion_limitada')->countAllResults());
        $this->assertStringNotContainsString('ciudadano@f4.test', (string) $this->db->table('auditoria')->where('accion', 'recuperacion_pedida')->get()->getRow()->detalle);
    }

    public function testLaRecuperacionTieneLimitePorIp(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('auth/attempt-forgot', ['email' => 'otro' . $i . '@f4.test']);
        }

        $this->post('auth/attempt-forgot', ['email' => 'ciudadano@f4.test']);

        $this->assertNull($this->db->table('users')->where('id', $this->ciudadanoId)->get()->getRow()->reset_token);
    }
}
