<?php declare(strict_types=1);

namespace Tests\Controllers\Juez;

use App\Database\Migrations\AddJuezCalificadorRole;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Tests\Support\DatabaseTestCase;

class JuezCalificadorAccessTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $roleIds = [];

    /** @var array<string, int> */
    private array $userIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'administrador'       => 'Administrador',
            'operador_ventanilla' => 'Operador',
            'ciudadano'           => 'Ciudadano',
            'juez_calificador'    => 'Juez',
        ] as $nombre => $descripcion) {
            if ($this->db->table('roles')->where('nombre', $nombre)->countAllResults() === 0) {
                $this->db->table('roles')->insert(['nombre' => $nombre, 'descripcion' => $descripcion]);
            }
            $rol = $this->db->table('roles')->where('nombre', $nombre)->get()->getRow();
            $this->roleIds[$nombre] = (int) $rol->id;
            $this->userIds[$nombre] = $this->crearUsuario($nombre . '_cob09', $nombre);
        }
    }

    private function crearUsuario(string $username, string $rol): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.test',
            'password_hash'   => password_hash('Prueba123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Prueba ' . $rol,
            'activo'          => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert([
            'user_id' => $id,
            'role_id' => $this->roleIds[$rol],
        ]);

        return $id;
    }

    private function como(string $rol): self
    {
        return $this->withSession([
            'user_id'         => $this->userIds[$rol],
            'username'        => $rol . '_cob09',
            'nombre_completo' => 'Prueba ' . $rol,
            'roles'           => [$rol],
        ]);
    }

    public function testMigracionYSeederCreanJuezSinDuplicarloNiBorrarAsignaciones(): void
    {
        $this->db->table('user_roles')->where('role_id', $this->roleIds['juez_calificador'])->delete();
        $this->db->table('roles')->where('id', $this->roleIds['juez_calificador'])->delete();

        $migration = new AddJuezCalificadorRole(Database::forge('tests'));
        $migration->up();
        $migration->up();
        $this->assertSame(1, $this->db->table('roles')->where('nombre', 'juez_calificador')->countAllResults());

        $rol = $this->db->table('roles')->where('nombre', 'juez_calificador')->get()->getRow();
        $this->db->table('user_roles')->insert([
            'user_id' => $this->userIds['juez_calificador'],
            'role_id' => (int) $rol->id,
        ]);
        $migration->down();
        $this->assertSame(1, $this->db->table('roles')->where('nombre', 'juez_calificador')->countAllResults());
        $this->assertSame(1, $this->db->table('user_roles')->where('role_id', $rol->id)->countAllResults());

        $this->db->table('user_roles')->where('role_id', $rol->id)->delete();
        $this->db->table('roles')->where('id', $rol->id)->delete();
        $seeder = Database::seeder('tests');
        $seeder->call('RolesSeeder');
        $seeder->call('RolesSeeder');
        $this->assertSame(1, $this->db->table('roles')->where('nombre', 'juez_calificador')->countAllResults());
    }

    public function testRolesSeederConservaLaReglaHistoricaDeIdONombre(): void
    {
        $this->db->table('user_roles')->where('role_id', $this->roleIds['administrador'])->delete();
        $this->db->table('roles')->where('id', $this->roleIds['administrador'])->delete();

        // La migración del juez ocupa el id histórico 1 en esta base aislada.
        // La lógica anterior considera suficiente esa colisión y no reasigna
        // automáticamente administrador a otro id.
        $this->assertSame(1, $this->db->table('roles')->where('id', 1)->countAllResults());
        Database::seeder('tests')->call('RolesSeeder');

        $this->assertSame(0, $this->db->table('roles')->where('nombre', 'administrador')->countAllResults());
        $this->assertSame(1, $this->db->table('roles')->where('nombre', 'juez_calificador')->countAllResults());
    }

    public function testLoginDeJuezUnicoRedirigeASuInicio(): void
    {
        $result = $this->post('/auth/attempt-login', [
            'username' => 'juez_calificador_cob09',
            'password' => 'Prueba123',
        ]);

        $result->assertRedirectTo('/juez');
        $this->assertSame(['juez_calificador'], session('roles'));
    }

    public function testPrioridadDeCuentaMultirrolConservaPanelAdministrativo(): void
    {
        $this->db->table('user_roles')->insert([
            'user_id' => $this->userIds['administrador'],
            'role_id' => $this->roleIds['juez_calificador'],
        ]);

        $this->post('/auth/attempt-login', [
            'username' => 'administrador_cob09',
            'password' => 'Prueba123',
        ])->assertRedirectTo('/admin/dashboard');
    }

    public function testSesionRestauradaDeJuezVuelveASuPanel(): void
    {
        $this->como('juez_calificador')->get('/')->assertRedirectTo('/juez');
        $this->como('juez_calificador')->get('/portal/dashboard')->assertRedirectTo('/juez');
    }

    public function testPersonaSinSesionNoAccedeAlPanelDelJuez(): void
    {
        $this->get('/juez')->assertRedirectTo('/auth/login');
    }

    public function testJuezAccedeASuInicioYListaDeSoloLectura(): void
    {
        $inicio = $this->como('juez_calificador')->get('/juez');
        $inicio->assertOK();
        $inicio->assertSee('Inicio del juez calificador');
        $inicio->assertSee('Criterio de calificación pendiente');

        $boletas = $this->como('juez_calificador')->get('/juez/boletas');
        $boletas->assertOK();
        $boletas->assertSee('No hay un criterio verificable');
        $boletas->assertDontSee('Editar');
        $boletas->assertDontSee('Eliminar');
    }

    public function testCiudadanoYOperadorNoAccedenARutasDelJuez(): void
    {
        $this->como('ciudadano')->get('/juez')->assertRedirectTo('/');
        $this->como('operador_ventanilla')->get('/juez/boletas')->assertRedirectTo('/');
    }

    public function testJuezNoAccedeAUsuariosNiCatalogosAdministrativos(): void
    {
        $this->como('juez_calificador')->get('/admin/usuarios')->assertRedirectTo('/');
        $this->como('juez_calificador')->get('/admin/app-transito/catalogo')->assertRedirectTo('/');
    }

    public function testAdministradorYOperadorConservanAccesoAlPanelActual(): void
    {
        $this->como('administrador')->get('/admin/dashboard')->assertOK();
        $this->como('operador_ventanilla')->get('/admin/dashboard')->assertOK();
    }

    public function testRutasDelJuezNoOfrecenMetodosDeEscrituraNiCambianBoletas(): void
    {
        $this->db->table('boletas_infracciones')->insert([
            'folio'             => 'COB09-LECTURA-001',
            'agente_placa'      => 'TEST-01',
            'fecha_infraccion'  => '2026-10-23',
            'hora_infraccion'   => '10:00:00',
            'lugar'             => 'Lugar de prueba',
            'estado'            => 'recibida',
        ]);
        $antes = $this->db->table('boletas_infracciones')->where('folio', 'COB09-LECTURA-001')->get()->getRowArray();

        $this->como('juez_calificador')->get('/juez');
        $this->como('juez_calificador')->get('/juez/boletas');
        $this->assertArrayNotHasKey('juez/boletas', service('routes')->getRoutes('POST'));

        $despues = $this->db->table('boletas_infracciones')->where('folio', 'COB09-LECTURA-001')->get()->getRowArray();
        $this->assertSame($antes, $despues);
    }

    public function testAdministradorPuedeCrearCuentaDeJuezYOperadorNoPuede(): void
    {
        $datos = [
            'nombre'   => 'Juez',
            'apellido' => 'Demostración',
            'username' => 'jueznuevocob09',
            'email'    => 'jueznuevocob09@example.test',
            'password' => 'Demo1234',
            'rol'      => 'juez_calificador',
            'activo'   => '1',
        ];

        $respuesta = $this->como('administrador')->post('/admin/usuarios/guardar', $datos);
        $this->assertEmpty((array) session('errors'), json_encode(session('errors'), JSON_UNESCAPED_UNICODE));
        $respuesta->assertRedirectTo('/admin/usuarios');

        $creado = $this->db->table('users')->where('username', 'jueznuevocob09')->get()->getRow();
        $this->assertNotNull($creado);
        $this->assertTrue(model(\App\Models\UserModel::class)->tieneRol((int) $creado->id, 'juez_calificador'));

        $datos['username'] = 'juezdenegadocob09';
        $datos['email'] = 'juezdenegadocob09@example.test';
        $this->como('operador_ventanilla')->post('/admin/usuarios/guardar', $datos);
        $this->assertNull($this->db->table('users')->where('username', 'juezdenegadocob09')->get()->getRow());
    }
    public function testLoginDeJuezReemplazaUnaSesionAdministrativaAnterior(): void
    {
        $this->withSession([
            'user_id' => $this->userIds['administrador'],
            'roles'   => ['administrador'],
        ])->post('/auth/attempt-login', [
            'username' => 'juez_calificador_cob09',
            'password' => 'Prueba123',
        ])->assertRedirectTo('/juez');

        $this->assertSame($this->userIds['juez_calificador'], (int) session('user_id'));
        $this->assertSame(['juez_calificador'], session('roles'));
    }

    public function testJuezNoPuedeInvocarLasAccionesDeUsuarios(): void
    {
        $targetId = $this->userIds['ciudadano'];
        $usuarioAntes = $this->db->table('users')->where('id', $targetId)->get()->getRowArray();
        $rolesAntes = $this->db->table('user_roles')->where('user_id', $targetId)->orderBy('role_id')->get()->getResultArray();

        $resultados = [
            $this->como('juez_calificador')->get('/admin/usuarios'),
            $this->como('juez_calificador')->get('/admin/usuarios/nuevo'),
            $this->como('juez_calificador')->post('/admin/usuarios/guardar', ['rol' => 'administrador']),
            $this->como('juez_calificador')->get('/admin/usuarios/editar/' . $targetId),
            $this->como('juez_calificador')->post('/admin/usuarios/actualizar/' . $targetId, ['rol' => 'administrador']),
            $this->como('juez_calificador')->post('/admin/usuarios/toggle/' . $targetId),
            $this->como('juez_calificador')->post('/admin/usuarios/eliminar/' . $targetId),
        ];

        foreach ($resultados as $resultado) {
            $resultado->assertRedirectTo('/');
            $resultado->assertDontSee('Gestión de usuarios');
        }

        $this->assertSame($usuarioAntes, $this->db->table('users')->where('id', $targetId)->get()->getRowArray());
        $this->assertSame($rolesAntes, $this->db->table('user_roles')->where('user_id', $targetId)->orderBy('role_id')->get()->getResultArray());
    }

    public function testOperadorConRolAdministrativoObsoletoEnSesionNoAsignaJuez(): void
    {
        $datos = [
            'nombre'   => 'Juez',
            'apellido' => 'Denegado',
            'username' => 'juezsesionobsoleta',
            'email'    => 'juezsesionobsoleta@example.test',
            'password' => 'Demo1234',
            'rol'      => 'juez_calificador',
            'activo'   => '1',
        ];

        $this->withSession([
            'user_id'         => $this->userIds['operador_ventanilla'],
            'username'        => 'operador_ventanilla_cob09',
            'nombre_completo' => 'Prueba operador',
            'roles'           => ['administrador'],
        ])->post('/admin/usuarios/guardar', $datos)->assertRedirect();

        $this->assertNull($this->db->table('users')->where('username', 'juezsesionobsoleta')->get()->getRow());
        $this->assertSame('No tienes permiso para asignar ese rol.', session('errors')['rol'] ?? null);
    }

    public function testRegistroPublicoIgnoraRolManipuladoYAsignaCiudadano(): void
    {
        $respuesta = $this->post('/auth/attempt-register', [
            'curp'             => 'ABCD001231HGTXYZ09',
            'nombre'           => 'Persona',
            'apellido'         => 'Pública',
            'telefono'         => '4451234567',
            'estado'           => 'Guanajuato',
            'ciudad'           => 'Uriangato',
            'domicilio'        => 'Domicilio de prueba',
            'email'            => 'registropublicocob09@example.test',
            'username'         => 'registropublicocob09',
            'password'         => 'Demo1234',
            'password_confirm' => 'Demo1234',
            'rfc'              => '',
            'acepto_terminos'  => '1',
            'rol'              => 'juez_calificador',
        ]);

        $respuesta->assertRedirectTo('/auth/login');
        $usuario = $this->db->table('users')->where('username', 'registropublicocob09')->get()->getRow();
        $this->assertNotNull($usuario);
        $this->assertSame(['ciudadano'], model(\App\Models\UserModel::class)->conRoles((int) $usuario->id));
    }
}
