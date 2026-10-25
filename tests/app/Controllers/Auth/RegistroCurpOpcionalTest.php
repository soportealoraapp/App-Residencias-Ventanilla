<?php declare(strict_types=1);

namespace Tests\Controllers\Auth;

use App\Libraries\CuentaCiudadanoService;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * COB-08: CURP opcional en el registro público y reclamo de la cuenta que
 * ventanilla dio de alta sin acceso.
 *
 * La persona que ya fue registrada presencialmente solo con su nombre puede
 * llegar al portal, registrarse con su CURP y quedarse con los trámites que ya
 * tenía; nunca se crea una segunda cuenta.
 */
class RegistroCurpOpcionalTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private const CURP = 'NUNC892145MGTRRN04';

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->db->table('roles')->where('nombre', 'ciudadano')->countAllResults() === 0) {
            $this->db->table('roles')->insert(['nombre' => 'ciudadano', 'descripcion' => 'Ciudadano']);
        }
    }

    /** @param array<string, string> $extra */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'nombre'           => 'Ana Sofía',
            'apellido'         => 'Núñez Castro',
            'telefono'         => '4451234567',
            'estado'           => 'Guanajuato',
            'ciudad'           => 'Uriangato',
            'domicilio'        => 'Calle Juárez 12, Centro',
            'email'            => 'ana@example.com',
            'username'         => 'anasofia',
            'password'         => 'Secreto123',
            'password_confirm' => 'Secreto123',
            'acepto_terminos'  => '1',
        ], $extra);
    }

    private function contarUsuarios(): int
    {
        return $this->db->table('users')->countAllResults();
    }

    private function usuario(string $columna, string $valor): ?object
    {
        return $this->db->table('users')->where($columna, $valor)->get()->getRow();
    }

    public function testSinCurpSeCreaLaCuentaIgual(): void
    {
        $respuesta = $this->post('/auth/attempt-register', $this->datos());
        $respuesta->assertRedirectTo(site_url('auth/login'));

        $usuario = $this->usuario('username', 'anasofia');
        $this->assertNotNull($usuario);
        $this->assertNull($usuario->curp);
        $this->assertTrue(password_verify('Secreto123', (string) $usuario->password_hash));
    }

    public function testConCurpValidaLaCuentaGuardaLosDieciochoCaracteres(): void
    {
        $this->post('/auth/attempt-register', $this->datos(['curp' => strtolower(self::CURP)]))
            ->assertRedirectTo(site_url('auth/login'));

        $this->assertSame(self::CURP, $this->usuario('username', 'anasofia')->curp);
    }

    public function testConLaCurpDeVentanillaSeReclamaLaCuentaSinDuplicarla(): void
    {
        $servicio = new CuentaCiudadanoService();
        $id = $servicio->crearSinAcceso([
            'nombre' => 'Ana Sofía', 'apellido' => 'Núñez Castro',
            'curp' => self::CURP, 'email' => 'ana@example.com',
        ]);
        $antes = $this->contarUsuarios();

        $respuesta = $this->post('/auth/attempt-register', $this->datos(['curp' => self::CURP]));

        $respuesta->assertRedirectTo(site_url('auth/login'));
        $this->assertSame($antes, $this->contarUsuarios());
        $this->assertStringContainsString('Vinculamos los trámites', (string) session('message'));

        $usuario = $this->usuario('id', (string) $id);
        $this->assertSame('anasofia', $usuario->username);
        $this->assertTrue(password_verify('Secreto123', (string) $usuario->password_hash));
        // El nombre capturado en ventanilla se respeta: la persona conserva su historial.
        $this->assertSame('Ana Sofía Núñez Castro', $usuario->nombre_completo);
    }

    public function testUnaCurpConCuentaActivaSeRechazaYNoCreaFilas(): void
    {
        $this->db->table('users')->insert([
            'username' => 'ana_ventanilla', 'email' => 'otra@example.com',
            'password_hash' => password_hash('Original123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Ana Sofía Núñez Castro', 'curp' => self::CURP, 'activo' => 1,
        ]);
        $antes = $this->contarUsuarios();

        $this->post('/auth/attempt-register', $this->datos(['curp' => self::CURP]));

        $errores = (array) session('errors');
        $this->assertArrayHasKey('curp', $errores);
        $this->assertStringContainsString('Ya existe una cuenta con esta CURP', (string) $errores['curp']);
        $this->assertSame($antes, $this->contarUsuarios());
        $this->assertNull($this->usuario('username', 'anasofia'));
    }

    public function testUnaCurpDeCuentaDesactivadaSeRechaza(): void
    {
        $this->db->table('users')->insert([
            'username' => null, 'email' => null, 'password_hash' => null,
            'nombre_completo' => 'Ana Sofía Núñez Castro', 'curp' => self::CURP, 'activo' => 0,
        ]);
        $antes = $this->contarUsuarios();

        $this->post('/auth/attempt-register', $this->datos(['curp' => self::CURP]));

        $errores = (array) session('errors');
        $this->assertArrayHasKey('curp', $errores);
        $this->assertStringContainsString('desactivada', (string) $errores['curp']);
        $this->assertSame($antes, $this->contarUsuarios());
    }
}
