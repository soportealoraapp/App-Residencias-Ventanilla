<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\CuentaCiudadanoService;
use App\Models\UserModel;
use Tests\Support\DatabaseTestCase;

/**
 * COB-08: el ciudadano que llega a ventanilla puede registrarse con solo su
 * nombre. La cuenta existe, pero sin usuario ni contraseña hasta que la reclama
 * desde el portal con su CURP.
 */
class CuentaCiudadanoServiceTest extends DatabaseTestCase
{
    private const CURP_VALIDA = 'LOPR850315MGTPRS04';

    private CuentaCiudadanoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new CuentaCiudadanoService();
        if ($this->db->table('roles')->where('nombre', 'ciudadano')->countAllResults() === 0) {
            $this->db->table('roles')->insert(['nombre' => 'ciudadano', 'descripcion' => 'Ciudadano']);
        }
    }

    public function testElAltaSinAccesoNoDejaUsuarioNiContrasena(): void
    {
        $id = $this->servicio->crearSinAcceso(['nombre' => 'Rosa Elena', 'apellido' => 'Márquez Soto']);

        $usuario = (new UserModel())->find($id);
        $this->assertNotNull($usuario);
        $this->assertNull($usuario->username);
        $this->assertNull($usuario->password_hash);
        $this->assertNull($usuario->curp);
        $this->assertSame('Rosa Elena Márquez Soto', $usuario->nombre_completo);
        $this->assertSame(1, (int) $usuario->activo);
        $this->assertTrue((new UserModel())->tieneRol($id, 'ciudadano'));
    }

    public function testLaCurpSeGuardaNormalizadaCuandoSeCaptura(): void
    {
        $id = $this->servicio->crearSinAcceso([
            'nombre' => 'Laura', 'apellido' => 'Pérez Ríos', 'curp' => ' lopr850315mgtprs04 ',
        ]);

        $this->assertSame(self::CURP_VALIDA, (new UserModel())->find($id)->curp);
        // Aun con CURP, el alta presencial sin acceso no crea credenciales.
        $this->assertNull((new UserModel())->find($id)->password_hash);
    }

    public function testLaCurpConFormatoInvalidoSeRechaza(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La CURP no tiene el formato oficial');

        $this->servicio->crearSinAcceso(['nombre' => 'Laura', 'apellido' => 'Pérez', 'curp' => 'LOPR850315']);
    }

    public function testNombreSimilarIgnoraAcentosMayusculasYEspaciosRepetidos(): void
    {
        $id = $this->servicio->crearSinAcceso(['nombre' => 'José', 'apellido' => 'Pérez Núñez']);

        $similar = $this->servicio->nombreSimilar('JOSE   PEREZ NUNEZ');
        $this->assertNotNull($similar);
        $this->assertSame($id, (int) $similar->id);

        $this->assertNull($this->servicio->nombreSimilar('Josefa Peralta'));
        $this->assertNull($this->servicio->nombreSimilar('   '));
    }

    public function testHabilitarAccesoPermiteEntrarSinDuplicarLaCuenta(): void
    {
        $id  = $this->servicio->crearSinAcceso(['nombre' => 'José', 'apellido' => 'Pérez Núñez']);
        $antes = $this->db->table('users')->countAllResults();

        $habilitada = $this->servicio->habilitarAcceso($id, [
            'username'      => 'joseperez',
            'password_hash' => password_hash('Secreto123', PASSWORD_DEFAULT),
            'email'         => 'jose@example.com',
        ]);

        $this->assertTrue($habilitada);
        $this->assertSame($antes, $this->db->table('users')->countAllResults());

        $usuario = (new UserModel())->find($id);
        $this->assertSame('joseperez', $usuario->username);
        $this->assertTrue(password_verify('Secreto123', (string) $usuario->password_hash));
        $this->assertTrue((new UserModel())->tieneRol($id, 'ciudadano'));
    }

    public function testHabilitarAccesoSeNiegaEnUnaCuentaQueYaTieneContrasena(): void
    {
        $this->db->table('users')->insert([
            'username' => 'yaralopez', 'email' => 'yara@example.com',
            'password_hash' => password_hash('Original123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Yara López', 'activo' => 1,
        ]);
        $id = (int) $this->db->insertID();

        $this->assertFalse($this->servicio->habilitarAcceso($id, ['username' => 'otro']));
        $this->assertSame('yaralopez', (new UserModel())->find($id)->username);
    }
}
