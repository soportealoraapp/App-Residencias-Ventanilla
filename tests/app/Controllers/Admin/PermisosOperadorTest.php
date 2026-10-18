<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Libraries\PermisosOperador;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Permisos configurables del operador de ventanilla:
 * catálogo por defecto, pantalla exclusiva del administrador y aplicación
 * del filtro en cada sección del panel.
 */
class PermisosOperadorTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $roleIds = [];
    private int $adminId;
    private int $operadorId;

    protected function setUp(): void
    {
        parent::setUp();
        PermisosOperador::reiniciarCache();

        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
        ]);
        foreach ($this->db->table('roles')->get()->getResult() as $rol) {
            $this->roleIds[$rol->nombre] = (int) $rol->id;
        }

        $this->adminId    = $this->crearUsuario('admin_perm', 'administrador');
        $this->operadorId = $this->crearUsuario('op_perm', 'operador_ventanilla');
    }

    protected function tearDown(): void
    {
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function crearUsuario(string $username, string $rol): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Usuario ' . $username,
            'activo'          => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $this->roleIds[$rol]]);

        return $id;
    }

    private function como(int $userId, string $rol): self
    {
        return $this->withSession(['user_id' => $userId, 'username' => 'x', 'roles' => [$rol]]);
    }

    public function testSinConfigurarTodoEstaPermitido(): void
    {
        foreach (PermisosOperador::todos() as $clave => $permitido) {
            $this->assertTrue($permitido, "El permiso '{$clave}' debe estar permitido por defecto.");
        }
    }

    public function testMapetaDeRutasUsaElPrefijoMasEspecifico(): void
    {
        $this->assertNull(PermisosOperador::requeridoParaUri('admin/dashboard'));
        $this->assertSame('tarifas', PermisosOperador::requeridoParaUri('admin/tarifas/nuevo'));
        $this->assertSame('app_transito_boletas', PermisosOperador::requeridoParaUri('admin/app-transito/boletas'));
        // Las altas/edición de agentes caen en configuración (prefijo admin/app-transito).
        $this->assertSame('app_transito_config', PermisosOperador::requeridoParaUri('admin/app-transito/agentes/guardar'));
    }

    public function testGuardarSoloGuardaClavesConocidas(): void
    {
        // El mapa es completo: las claves omitidas quedan bloqueadas.
        PermisosOperador::guardar(['solicitudes' => true, 'tarifas' => false, 'auditoria' => false, 'no_existe' => true]);

        $todos = PermisosOperador::todos();
        $this->assertFalse($todos['tarifas']);
        $this->assertFalse($todos['auditoria']);
        $this->assertTrue($todos['solicitudes']);
        $this->assertFalse($todos['convocatorias']);
        $this->assertArrayNotHasKey('no_existe', $todos);
    }

    public function testVentanillaBloqueadaEsRedirigidaYVentanillaPermitidaNo(): void
    {
        PermisosOperador::guardar(['auditoria' => false, 'tarifas' => true]);

        // Sección bloqueada: redirigido al inicio del panel.
        $this->como($this->operadorId, 'operador_ventanilla')
            ->get('admin/auditoria')
            ->assertRedirectTo('/admin/dashboard');

        // Sección permitida: respuesta 200 real (no un 302 disfrazado;
        // en esta versión isOK() también aceptaría redirecciones).
        $this->como($this->operadorId, 'operador_ventanilla')
            ->get('admin/tarifas')
            ->assertStatus(200);
    }

    public function testAdministradorNuncaSeBloquea(): void
    {
        PermisosOperador::guardar(['solicitudes' => false]);

        $this->como($this->adminId, 'administrador')->get('admin/tarifas')->assertStatus(200);
        $this->como($this->adminId, 'administrador')->get('admin/solicitudes')->assertStatus(200);
    }

    public function testPantallaDePermisosEsSoloDelAdministrador(): void
    {
        $this->como($this->adminId, 'administrador')->get('admin/permisos-operador')->assertStatus(200);

        $this->como($this->operadorId, 'operador_ventanilla')
            ->get('admin/permisos-operador')
            ->assertRedirect();
    }

    public function testAdministradorGuardaPermisosPorHttp(): void
    {
        $result = $this->como($this->adminId, 'administrador')->post('admin/permisos-operador/guardar', [
            'permisos' => ['usuarios' => '1', 'auditoria' => '1'],
        ]);
        $result->assertRedirectTo('/admin/permisos-operador');

        PermisosOperador::reiniciarCache();
        $todos = PermisosOperador::todos();
        $this->assertTrue($todos['usuarios']);
        $this->assertTrue($todos['auditoria']);
        $this->assertFalse($todos['tarifas']);

        // Y queda registro en auditoría.
        $hayAuditoria = $this->db->table('auditoria')->where('accion', 'actualizar')->where('entidad', 'permisos_operador')->countAllResults();
        $this->assertSame(1, $hayAuditoria);
    }

    public function testVentanillaNoPuedeModificarPermisos(): void
    {
        $this->como($this->operadorId, 'operador_ventanilla')
            ->post('admin/permisos-operador/guardar', ['permisos' => ['tarifas' => '1']])
            ->assertRedirect();

        PermisosOperador::reiniciarCache();
        $this->assertTrue(PermisosOperador::todos()['convocatorias']);
    }
}
