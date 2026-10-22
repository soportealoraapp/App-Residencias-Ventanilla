<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class AppTransitoAdminTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $boletaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insert(['nombre' => 'administrador', 'descripcion' => 'admin']);
        $rolId = (int) $this->db->insertID();
        $this->db->table('users')->insert([
            'username'      => 'admin01',
            'email'         => 'admin01@example.com',
            'password_hash' => password_hash('secreto123', PASSWORD_DEFAULT),
            'activo'        => 1,
        ]);
        $this->adminId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $this->adminId, 'role_id' => $rolId]);

        $this->db->table('agentes_transito')->insert([
            'placa'           => 'AGT-204',
            'nombre_completo' => "Oficial O'Brien",
            'password_hash'   => password_hash('1234', PASSWORD_BCRYPT),
            'rol'             => 'operativo',
            'sector'          => 'Centro',
            'activo'          => 0,
        ]);

        $this->db->table('boletas_infracciones')->insert([
            'folio'            => 'BOLETA-TEST-1',
            'agente_placa'     => 'AGT-204',
            'agente_nombre'    => 'Oficial',
            'fecha_infraccion' => '2026-10-01',
            'hora_infraccion'  => '10:15:00',
            'lugar'            => 'Centro',
            'vehiculo_tipo'    => 'particular',
            'estado'           => 'recibida',
        ]);
        $this->boletaId = (int) $this->db->insertID();
    }

    private function comoAdmin(): self
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'admin01', 'roles' => ['administrador']]);
    }

    public function testVistasDeAppTransitoRenderizan(): void
    {
        $this->comoAdmin()->get('admin/app-transito')->assertOK();
        $this->comoAdmin()->get('admin/app-transito/boletas')->assertOK();
        $this->comoAdmin()->get('admin/app-transito/boletas/editar/' . $this->boletaId)->assertOK();
    }

    public function testEditarBoletaActualizaEstadoYDatos(): void
    {
        $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, [
            'estado'           => 'pagada',
            'fecha_infraccion' => '2026-10-01',
            'hora_infraccion'  => '10:15',
            'lugar'            => 'Av. Hidalgo',
            'vehiculo_tipo'    => 'particular',
            'vehiculo_placas'  => 'gtc441e',
        ])->assertRedirectTo(site_url('admin/app-transito/boletas'));

        $boleta = $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow();
        $this->assertSame('pagada', $boleta->estado);
        $this->assertSame('Av. Hidalgo', $boleta->lugar);
        $this->assertSame('GTC441E', $boleta->vehiculo_placas);
    }

    public function testAgenteConBoletasNoSeElimina(): void
    {
        $agente = $this->db->table('agentes_transito')->where('placa', 'AGT-204')->get()->getRow();

        $this->comoAdmin()->post('admin/app-transito/agentes/eliminar/' . $agente->id);

        $this->assertNotNull($this->db->table('agentes_transito')->where('id', $agente->id)->get()->getRow());
    }

    public function testEditarAgenteConservaEstadoInactivo(): void
    {
        $agente = $this->db->table('agentes_transito')->where('placa', 'AGT-204')->get()->getRow();

        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'id'              => (string) $agente->id,
            'placa'           => 'AGT-204',
            'nombre_completo' => 'Oficial Editado',
            'sector'          => 'Norte',
            'rol'             => 'supervisor',
        ]);

        $actualizado = $this->db->table('agentes_transito')->where('id', $agente->id)->get()->getRow();
        $this->assertSame('Oficial Editado', $actualizado->nombre_completo);
        $this->assertSame(0, (int) $actualizado->activo);
    }

    public function testCrearAgenteNuevoGuardaPlacaEnMayusculasYPuedeIniciarSesion(): void
    {
        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'placa' => 'agt-901', 'nombre_completo' => 'Oficial Nuevo', 'sector' => 'Norte',
            'rol' => 'supervisor', 'password' => 'Transito2026',
        ])->assertRedirectTo(site_url('admin/app-transito'));

        $agente = $this->db->table('agentes_transito')->where('placa', 'AGT-901')->get()->getRow();
        $this->assertNotNull($agente);
        $this->assertSame(1, (int) $agente->activo);
        $this->assertTrue(password_verify('Transito2026', $agente->password_hash));
    }

    public function testPlacaDuplicadaEnMinusculasSeRechaza(): void
    {
        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'placa' => 'agt-204', 'nombre_completo' => 'Duplicado', 'rol' => 'operativo', 'password' => 'Transito2026',
        ]);

        $this->assertSame(1, $this->db->table('agentes_transito')->where('placa', 'AGT-204')->countAllResults());
        $this->assertArrayHasKey('placa', (array) session('errors'));
    }

    public function testErrorAlCrearAgenteReabreElModalConLoCapturado(): void
    {
        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'placa' => 'AGT-777', 'nombre_completo' => 'Oficial Corto', 'rol' => 'operativo', 'password' => 'abc',
        ])->assertRedirectTo(site_url('admin/app-transito'));

        $this->assertTrue((bool) session('reabrir_modal_agente'));
        $this->assertSame('AGT-777', old('placa'));
        $this->assertNull($this->db->table('agentes_transito')->where('placa', 'AGT-777')->get()->getRow());
    }

    public function testEliminarBoletaLaQuita(): void
    {
        $this->comoAdmin()->post('admin/app-transito/boletas/eliminar/' . $this->boletaId);

        $this->assertNull($this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow());
    }
}
