<?php declare(strict_types=1);

namespace Tests\Controllers;

use App\Models\AgenteTransitoModel;
use CodeIgniter\Test\FeatureTestTrait;
use Config\App;
use Tests\Support\DatabaseTestCase;

/**
 * Auditoría de Félix, Fase 3: M3 (sesión de una cuenta desactivada), M6
 * (límite y bitácora en el login de agentes) y M7 (proxy de confianza).
 */
class AuditoriaFaseTresTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;
    private int $adminId;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['ciudadano' => 'ciudadanoId', 'administrador' => 'adminId'] as $rol => $prop) {
            if ($this->db->table('roles')->where('nombre', $rol)->countAllResults() === 0) {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
            }
            $rolId = (int) $this->db->table('roles')->where('nombre', $rol)->get()->getRow()->id;
            $this->db->table('users')->insert([
                'username' => $rol . '_f3', 'email' => $rol . '@f3.test', 'password_hash' => 'x',
                'nombre_completo' => 'Prueba ' . $rol, 'activo' => 1,
            ]);
            $this->{$prop} = (int) $this->db->insertID();
            $this->db->table('user_roles')->insert(['user_id' => $this->{$prop}, 'role_id' => $rolId]);
        }

        $agentes = new AgenteTransitoModel();
        foreach (['AGT-F3' => 1, 'AGT-F3-BAJA' => 0] as $placa => $activo) {
            $agentes->insert([
                'placa' => $placa, 'nombre_completo' => 'Agente ' . $placa, 'password_hash' => password_hash('secreto123', PASSWORD_BCRYPT),
                'rol' => 'operativo', 'sector' => 'Pruebas', 'activo' => $activo,
            ]);
        }
    }

    protected function tearDown(): void
    {
        putenv('VERCEL');
        putenv('APP_PROXY_IPS');
        parent::tearDown();
    }

    // ─── M3 · Cuenta desactivada con sesión abierta ───────────────────────

    public function testUnaCuentaDesactivadaPierdeLaSesionEnLaSiguientePeticion(): void
    {
        $sesion = ['user_id' => $this->ciudadanoId, 'username' => 'ciudadano_f3', 'roles' => ['ciudadano']];
        $this->withSession($sesion)->get('/portal/dashboard')->assertStatus(200);

        $this->db->table('users')->where('id', $this->ciudadanoId)->update(['activo' => 0]);

        $respuesta = $this->withSession($sesion)->get('/portal/dashboard');
        $respuesta->assertRedirectTo('/auth/login');
        $this->assertNull(session('user_id'));
        $this->assertStringContainsString('desactivada', (string) session()->getFlashdata('error'));
        $this->assertSame(1, $this->db->table('auditoria')->where('accion', 'sesion_cerrada_cuenta_inactiva')->countAllResults());
    }

    public function testElFiltroDeRolTambienRevisaQueLaCuentaSigaActiva(): void
    {
        $this->db->table('users')->where('id', $this->adminId)->update(['activo' => 0]);

        // Ruta protegida solo con el filtro de rol (sin el de sesión).
        $respuesta = $this->withSession(['user_id' => $this->adminId, 'username' => 'administrador_f3', 'roles' => ['administrador']])
            ->get('/evidencias/F1/placa_' . str_repeat('a', 32) . '.png');

        $respuesta->assertRedirectTo('/auth/login');
        $this->assertNull(session('user_id'));
    }

    public function testUnaCuentaBorradaTambienPierdeLaSesion(): void
    {
        $this->withSession(['user_id' => 999999, 'username' => 'fantasma', 'roles' => ['administrador']])
            ->get('/admin/dashboard')
            ->assertRedirectTo('/auth/login');
    }

    // ─── M6 · Login de agentes ────────────────────────────────────────────

    private function loginAgente(string $placa, string $password)
    {
        return $this->post('api/agentes/login', ['placa' => $placa, 'password' => $password]);
    }

    public function testElLoginDeAgentesDaElMismoMensajeEnTodosLosFallos(): void
    {
        $mensajes = [];
        foreach ([['NO-EXISTE', 'secreto123'], ['AGT-F3', 'incorrecta'], ['AGT-F3-BAJA', 'secreto123']] as [$placa, $clave]) {
            $respuesta = $this->loginAgente($placa, $clave);
            $respuesta->assertStatus(401);
            $mensajes[] = json_decode($respuesta->getJSON(), true)['message'];
        }

        $this->assertSame(['Placa o contraseña incorrectas.'], array_values(array_unique($mensajes)));
        $motivos = array_map(
            static fn ($f) => json_decode($f->detalle, true)['motivo'],
            $this->db->table('auditoria')->where('accion', 'login_agente_fallido')->orderBy('id')->get()->getResult()
        );
        $this->assertSame(['placa_inexistente', 'contrasena_incorrecta', 'agente_inactivo'], $motivos);
    }

    public function testCincoFallosBloqueanLaPlacaAunqueLaSiguienteClaveSeaCorrecta(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->loginAgente('AGT-F3', 'incorrecta' . $i)->assertStatus(401);
        }

        $respuesta = $this->loginAgente('AGT-F3', 'secreto123');
        $respuesta->assertStatus(429);
        $this->assertArrayNotHasKey('token', json_decode($respuesta->getJSON(), true));
        $this->assertSame(1, $this->db->table('auditoria')->where('accion', 'login_agente_bloqueado')->countAllResults());
    }

    public function testElLimitePorIpFrenaProbarMuchasPlacas(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->loginAgente('PLACA-' . $i, 'x')->assertStatus(401);
        }

        $this->loginAgente('AGT-F3', 'secreto123')->assertStatus(429);
    }

    public function testUnLoginCorrectoQuedaEnLaBitacora(): void
    {
        $this->loginAgente('agt-f3', 'secreto123')->assertStatus(200);

        $fila = $this->db->table('auditoria')->where('accion', 'login_agente_exitoso')->get()->getRow();
        $this->assertNotNull($fila);
        $this->assertSame('AGT-F3', json_decode($fila->detalle, true)['placa']);
    }

    // ─── M7 · Proxy de confianza ──────────────────────────────────────────

    public function testFueraDeVercelNoSeConfiaEnCualquierProxy(): void
    {
        putenv('VERCEL');
        putenv('APP_PROXY_IPS');
        $this->assertSame([], (new App())->proxyIPs);

        putenv('APP_PROXY_IPS=10.0.0.5, 10.1.0.0/16');
        $this->assertSame(['10.0.0.5' => 'X-Forwarded-For', '10.1.0.0/16' => 'X-Forwarded-For'], (new App())->proxyIPs);
    }

    public function testEnVercelSeConfiaEnSuEdge(): void
    {
        putenv('VERCEL=1');
        $this->assertSame(['0.0.0.0/0' => 'X-Forwarded-For'], (new App())->proxyIPs);
    }
}
