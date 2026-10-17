<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class UsuariosControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $roleIds = [];
    private int $adminId;
    private int $operadorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);
        foreach ($this->db->table('roles')->get()->getResult() as $rol) {
            $this->roleIds[$rol->nombre] = (int) $rol->id;
        }

        $this->adminId    = $this->crearUsuario('admin01', 'administrador');
        $this->operadorId = $this->crearUsuario('operador01', 'operador_ventanilla');
    }

    private function crearUsuario(string $username, string $rol, int $activo = 1): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Usuario ' . $username,
            'activo'          => $activo,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $this->roleIds[$rol]]);

        return $id;
    }

    private function como(int $userId, string $rol): self
    {
        return $this->withSession(['user_id' => $userId, 'username' => 'x', 'roles' => [$rol]]);
    }

    private function datosCiudadano(array $extra = []): array
    {
        return $extra + [
            'nombre'   => 'María',
            'apellido' => 'López García',
            'curp'     => 'logm900101mgtprr05',
            'rfc'      => 'logm900101ab1',
            'username' => 'mlopez90',
            'password' => 'clave123',
            'rol'      => 'ciudadano',
            'activo'   => '1',
        ];
    }

    public function testRegistroPublicoYaNoExiste(): void
    {
        $this->assertFalse(in_array('auth/register', array_keys(service('routes')->getRoutes('GET')), true));
        $this->assertFalse(in_array('auth/attempt-register', array_keys(service('routes')->getRoutes('POST')), true));
    }

    public function testOperadorCreaCiudadanoConCurpYRfcNormalizados(): void
    {
        $result = $this->como($this->operadorId, 'operador_ventanilla')
            ->post('admin/usuarios/guardar', $this->datosCiudadano());

        $result->assertRedirectTo('/admin/usuarios');
        $usuario = $this->db->table('users')->where('username', 'mlopez90')->get()->getRow();
        $this->assertNotNull($usuario);
        $this->assertSame('LOGM900101MGTPRR05', $usuario->curp);
        $this->assertSame('LOGM900101AB1', $usuario->rfc);
        $this->assertSame('María López García', $usuario->nombre_completo);
        $this->assertTrue(password_verify('clave123', $usuario->password_hash));
        $this->assertTrue(model(\App\Models\UserModel::class)->tieneRol((int) $usuario->id, 'ciudadano'));
    }

    public function testSinContrasenaSeGeneraUnaTemporal(): void
    {
        $result = $this->como($this->operadorId, 'operador_ventanilla')
            ->post('admin/usuarios/guardar', $this->datosCiudadano(['password' => '']));

        $result->assertRedirectTo('/admin/usuarios');
        $this->assertStringContainsString('Contraseña temporal', (string) session('message'));
    }

    public function testOperadorNoPuedeCrearAdministradores(): void
    {
        $this->como($this->operadorId, 'operador_ventanilla')
            ->post('admin/usuarios/guardar', $this->datosCiudadano(['rol' => 'administrador']));

        $this->assertNull($this->db->table('users')->where('username', 'mlopez90')->get()->getRow());
    }

    public function testOperadorNoPuedeEliminarCuentas(): void
    {
        $ciudadanoId = $this->crearUsuario('ciudadano01', 'ciudadano');

        $this->como($this->operadorId, 'operador_ventanilla')->post('admin/usuarios/eliminar/' . $ciudadanoId);

        $this->assertNotNull($this->db->table('users')->where('id', $ciudadanoId)->get()->getRow());
    }

    public function testAdministradorEliminaCuentaSinSolicitudes(): void
    {
        $ciudadanoId = $this->crearUsuario('ciudadano01', 'ciudadano');

        $this->como($this->adminId, 'administrador')->post('admin/usuarios/eliminar/' . $ciudadanoId)
            ->assertRedirectTo('/admin/usuarios');

        $this->assertNull($this->db->table('users')->where('id', $ciudadanoId)->get()->getRow());
        $this->assertSame(0, $this->db->table('user_roles')->where('user_id', $ciudadanoId)->countAllResults());
    }

    public function testListadoFiltraPorRol(): void
    {
        $this->crearUsuario('ciudadano01', 'ciudadano');

        $result = $this->como($this->adminId, 'administrador')->get('admin/usuarios?rol=ciudadano');

        $result->assertOK();
        $result->assertSee('ciudadano01');
        $result->assertDontSee('operador01');
    }

    public function testCuentaDesactivadaNoPuedeIniciarSesion(): void
    {
        $this->crearUsuario('inactivo01', 'ciudadano', 0);

        $this->post('auth/attempt-login', ['username' => 'inactivo01', 'password' => 'secreto123']);

        $this->assertNull(session('user_id'));
    }
}
