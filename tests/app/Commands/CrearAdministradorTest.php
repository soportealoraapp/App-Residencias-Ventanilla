<?php declare(strict_types=1);

namespace Tests\Commands;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\DatabaseTestCase;

/**
 * php spark usuarios:crear-admin: la única forma de entrar al panel en una
 * instalación limpia (ensayo de instalación, GUIA_MIGRACION.md sección 10).
 */
class CrearAdministradorTest extends DatabaseTestCase
{
    use FeatureTestTrait;
    use StreamFilterTrait;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->db->table('roles')->where('nombre', 'administrador')->countAllResults() === 0) {
            $this->db->table('roles')->insert(['nombre' => 'administrador', 'descripcion' => 'admin']);
        }
    }

    public function testCreaUnAdministradorQuePuedeEntrar(): void
    {
        command('usuarios:crear-admin --usuario admintest01 --correo ti@municipio.test --nombre "Persona de Sistemas"');

        preg_match('/no se vuelve a mostrar\): ([A-Za-z0-9]{16})/', $this->getStreamFilterBuffer(), $m);
        $this->assertNotEmpty($m[1] ?? null);
        $this->assertSame(16, strlen($m[1]));

        $usuario = $this->db->table('users')->where('username', 'admintest01')->get()->getRow();
        $this->assertTrue(password_verify($m[1], $usuario->password_hash));
        $this->assertSame(1, $this->db->table('user_roles ur')->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $usuario->id)->where('r.nombre', 'administrador')->countAllResults());

        $this->post('auth/attempt-login', ['username' => 'admintest01', 'password' => $m[1]])
            ->assertRedirectTo('/admin/dashboard');
    }

    public function testNoDuplicaNiAceptaDatosInvalidos(): void
    {
        command('usuarios:crear-admin --usuario admintest01 --correo ti@municipio.test --nombre "Persona de Sistemas"');
        command('usuarios:crear-admin --usuario otro01 --correo ti@municipio.test --nombre "Otra Persona"');
        command('usuarios:crear-admin --usuario ab --correo malo --nombre "X"');

        $this->assertSame(1, $this->db->table('users')->countAllResults());
        $this->assertStringContainsString('Ya existe una cuenta', $this->getStreamFilterBuffer());
    }
}
