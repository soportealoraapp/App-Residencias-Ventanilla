<?php declare(strict_types=1);

namespace Tests\Controllers\Ciudadano;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Formulario de carga y descarga.
 *
 * Las demas pruebas de tramites solo comprueban a donde se redirige, pero nadie
 * renderizaba este formulario: aqui se verifica que el controlador realmente le
 * entrega la lista de periodos al select. La vista lee esa lista con la
 * convencion de get_defined_vars(), de modo que un nombre torcido dejaria el
 * desplegado vacio y esta prueba lo atraparia.
 */
class TramiteCargaDescargaFormTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insert(['nombre' => 'ciudadano', 'descripcion' => 'ciudadano']);
        $rolId = (int) $this->db->insertID();
        $this->db->table('users')->insert([
            'username'      => 'carga_ciudadano',
            'email'         => 'carga@example.com',
            'password_hash' => password_hash('12345678', PASSWORD_DEFAULT),
            'activo'        => 1,
        ]);
        $this->ciudadanoId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $this->ciudadanoId, 'role_id' => $rolId]);
    }

    private function comoCiudadano(): self
    {
        return $this->withSession([
            'user_id'  => $this->ciudadanoId,
            'username' => 'carga_ciudadano',
            'roles'    => ['ciudadano'],
        ]);
    }

    public function testElSelectDePeriodoLlegaConLasCuatroOpciones(): void
    {
        $respuesta = $this->comoCiudadano()->get('/portal/tramites/carga-descarga/formulario');
        $respuesta->assertOK();
        $cuerpo = $respuesta->getBody();

        // Ningun aviso de PHP colado en la pagina.
        self::assertStringNotContainsString('Undefined variable', $cuerpo);
        self::assertStringNotContainsString('get_defined_vars', $cuerpo);

        $inicio = strpos($cuerpo, 'id="periodo"');
        self::assertNotFalse($inicio, 'El formulario perdio el desplegado de periodo.');

        $fin = strpos($cuerpo, '</select>', (int) $inicio);
        self::assertNotFalse($fin, 'El desplegado de periodo no cierra.');
        $trozo = substr($cuerpo, (int) $inicio, (int) $fin - (int) $inicio);

        self::assertSame(4, preg_match_all('/<option value="([a-z]+)"/', $trozo), 'El periodo no trae sus cuatro ventanas de vigencia.');
        foreach (['dia', 'mes', 'semestre', 'anio'] as $ventana) {
            self::assertStringContainsString('value="' . $ventana . '"', $trozo, 'Falta la ventana ' . $ventana . '.');
        }
    }

    public function testElFormularioApuntaAlGuardadoDeCargaYDescarga(): void
    {
        $cuerpo = $this->comoCiudadano()->get('/portal/tramites/carga-descarga/formulario')->getBody();

        self::assertStringContainsString('carga-descarga/guardar', $cuerpo);
        // El monto se recalcula sin recargar la pagina, segun el periodo elegido.
        self::assertStringContainsString('carga-descarga/calcular-monto', $cuerpo);
    }

    public function testElAnonimoNoRecibeElFormulario(): void
    {
        $this->get('/portal/tramites/carga-descarga/formulario')->assertRedirect();
    }
}
