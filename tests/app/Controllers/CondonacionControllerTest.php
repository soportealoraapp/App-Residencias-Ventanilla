<?php declare(strict_types=1);

namespace Tests\Controllers;

use App\Libraries\CobroService;
use App\Libraries\PermisosOperador;
use App\Models\CobroModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/** COB-11: pantallas, rutas y permisos de la condonación de recargos. */
class CondonacionControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $usuarios = [];
    private int $cobro;

    protected function setUp(): void
    {
        parent::setUp();
        PermisosOperador::reiniciarCache();

        foreach (['administrador', 'operador_ventanilla', 'ciudadano', 'juez_calificador'] as $rol) {
            if ($this->db->table('roles')->where('nombre', $rol)->countAllResults() === 0) {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
            }
            $rolId = (int) $this->db->table('roles')->where('nombre', $rol)->get()->getRow()->id;
            $this->db->table('users')->insert([
                'username' => $rol . '_cob11', 'email' => $rol . '@cob11.test', 'password_hash' => 'x',
                'nombre_completo' => 'Prueba ' . $rol, 'activo' => 1,
            ]);
            $this->usuarios[$rol] = (int) $this->db->insertID();
            $this->db->table('user_roles')->insert(['user_id' => $this->usuarios[$rol], 'role_id' => $rolId]);
        }

        $this->db->table('agentes_transito')->insert([
            'placa' => 'AGT-204', 'nombre_completo' => 'Agente de prueba', 'password_hash' => 'x',
            'rol' => 'operativo', 'sector' => 'Pruebas', 'activo' => 1,
        ]);
        $this->db->table('boletas_infracciones')->insert([
            'folio' => 'BOLETA-COB11-C1', 'agente_placa' => 'AGT-204', 'fecha_infraccion' => date('Y-m-d', strtotime('-3 months -2 days')),
            'hora_infraccion' => '09:15:00', 'lugar' => 'Calle Hidalgo', 'infractor_nombre' => 'Martín Aguilar Rosas',
            'vehiculo_placas' => 'GTA-123-B', 'estado' => 'calificada',
        ]);
        $this->cobro = (new CobroService())->crearDesdeBoleta((int) $this->db->insertID(), 1000.0, $this->usuarios['juez_calificador']);
    }

    protected function tearDown(): void
    {
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function como(string $rol): self
    {
        return $this->withSession(['user_id' => $this->usuarios[$rol], 'username' => $rol . '_cob11', 'roles' => [$rol]]);
    }

    private function version(): string
    {
        return (new CobroService())->vistaCondonacion($this->cobro)['version'];
    }

    public function testAdministradorYJuezVenElBotonYElFormulario(): void
    {
        foreach (['administrador' => '/admin/cobros/', 'juez_calificador' => '/juez/cobros/'] as $rol => $base) {
            $detalle = $this->como($rol)->get($base . $this->cobro);
            $detalle->assertOK();
            $this->assertStringContainsString('Condonar recargo', $detalle->response()->getBody());

            $form = $this->como($rol)->get($base . $this->cobro . '/condonar');
            $form->assertOK();
            $html = $form->response()->getBody();
            $this->assertStringContainsString('BOLETA-COB11-C1', $html);
            $this->assertStringContainsString('name="confirmar"', $html);
            $this->assertStringContainsString('name="version"', $html);
            $this->assertStringContainsString('recargo sigue creciendo', $html);
        }
    }

    public function testElOperadorNoVeElBotonNiPuedeUsarElFormularioNiElPost(): void
    {
        $detalle = $this->como('operador_ventanilla')->get('/admin/cobros/' . $this->cobro);
        $detalle->assertOK();
        $this->assertStringNotContainsString('Condonar recargo', $detalle->response()->getBody());

        $this->como('operador_ventanilla')->get('/admin/cobros/' . $this->cobro . '/condonar')->assertRedirectTo('/admin/cobros/' . $this->cobro);
        $this->como('operador_ventanilla')->get('/admin/cobros/condonaciones')->assertRedirectTo('/admin/cobros');
        $this->como('operador_ventanilla')->post('/admin/cobros/' . $this->cobro . '/condonar', [
            'importe' => '10', 'motivo' => 'x', 'confirmar' => '1', 'version' => $this->version(),
        ])->assertRedirectTo('/admin/cobros/' . $this->cobro);
        $this->assertEqualsWithDelta(0.0, (float) (new CobroModel())->find($this->cobro)->condonado, 0.001);
    }

    public function testCiudadanoYSinSesionNoAccedenALasRutas(): void
    {
        $this->como('ciudadano')->get('/admin/cobros/' . $this->cobro . '/condonar')->assertRedirect();
        $this->como('ciudadano')->get('/juez/cobros/condonaciones')->assertRedirect();
        $this->como('ciudadano')->post('/juez/cobros/' . $this->cobro . '/condonar', ['importe' => '10'])->assertRedirect();
        $this->get('/admin/cobros/condonaciones')->assertRedirect();
        $this->post('/admin/cobros/' . $this->cobro . '/condonar', ['importe' => '10'])->assertRedirect();
        $this->assertEqualsWithDelta(0.0, (float) (new CobroModel())->find($this->cobro)->condonado, 0.001);
    }

    public function testElJuezCondonaPorSuRutaYQuedaRegistrado(): void
    {
        $r = $this->como('juez_calificador')->post('/juez/cobros/' . $this->cobro . '/condonar', [
            'importe' => '75.25', 'motivo' => 'Apoyo', 'confirmar' => '1', 'version' => $this->version(),
        ]);

        $r->assertRedirectTo('/juez/cobros/' . $this->cobro);
        $this->assertStringContainsString('Condonación registrada', (string) session('message'));
        $this->assertEqualsWithDelta(75.25, (float) (new CobroModel())->find($this->cobro)->condonado, 0.001);
        $this->assertSame(1, $this->db->table('cobro_movimientos')->where('accion', 'condonar')->where('usuario_id', $this->usuarios['juez_calificador'])->countAllResults());
    }

    public function testSinConfirmacionOConDatosInvalidosNoGuardaYConservaLaCaptura(): void
    {
        $r = $this->como('administrador')->post('/admin/cobros/' . $this->cobro . '/condonar', [
            'importe' => '10', 'motivo' => 'x', 'version' => $this->version(),
        ]);
        $r->assertRedirectTo('/admin/cobros/' . $this->cobro . '/condonar');
        $this->assertStringContainsString('confirmación', (string) session('error'));

        $r = $this->como('administrador')->post('/admin/cobros/' . $this->cobro . '/condonar', [
            'importe' => '9999', 'motivo' => 'x', 'confirmar' => '1', 'version' => $this->version(),
        ]);
        $r->assertRedirectTo('/admin/cobros/' . $this->cobro . '/condonar');
        $this->assertEqualsWithDelta(0.0, (float) (new CobroModel())->find($this->cobro)->condonado, 0.001);
    }

    public function testElReporteLoVeAdministradorYJuezPeroNoElOperador(): void
    {
        $this->como('administrador')->post('/admin/cobros/' . $this->cobro . '/condonar', [
            'importe' => '20', 'motivo' => 'Motivo <b>x</b>', 'confirmar' => '1', 'version' => $this->version(),
        ]);

        foreach (['administrador' => '/admin/cobros/condonaciones', 'juez_calificador' => '/juez/cobros/condonaciones'] as $rol => $ruta) {
            $r = $this->como($rol)->get($ruta);
            $r->assertOK();
            $html = $r->response()->getBody();
            $this->assertStringContainsString('BOLETA-COB11-C1', $html);
            $this->assertStringContainsString('$20.00', $html);
            $this->assertStringNotContainsString('<b>x</b>', $html);
        }
    }

    public function testElMenuDelJuezTieneElAccesoFuncional(): void
    {
        $html = $this->como('juez_calificador')->get('/juez/cobros')->response()->getBody();
        $this->assertStringContainsString('/juez/cobros/condonaciones', $html);
        $this->assertStringNotContainsString('Condonaciones · pendiente', $html);
    }

    public function testTrasPagarYaNoSeOfreceCondonar(): void
    {
        (new CobroService())->marcarCobrado($this->cobro, 'juez_calificador', null, $this->usuarios['juez_calificador']);

        $this->assertStringNotContainsString('Condonar recargo', $this->como('administrador')->get('/admin/cobros/' . $this->cobro)->response()->getBody());
        $this->como('administrador')->get('/admin/cobros/' . $this->cobro . '/condonar')->assertRedirectTo('/admin/cobros/' . $this->cobro);
    }
}
