<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Validación rápida de UR-05 (cierre de calle): autorizar el pago y rechazar
 * son acciones separadas. Antes, presionar "Validar y autorizar pago" sin marcar
 * los tres criterios rechazaba la solicitud en silencio.
 */
class ValidacionUr05Test extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $solicitudId;
    private string $folio = 'FOLIOUR05PRUEBA00001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insert(['nombre' => 'administrador', 'descripcion' => 'admin']);
        $rolId = (int) $this->db->insertID();
        $this->db->table('users')->insert([
            'username' => 'admin_ur05', 'email' => 'admin_ur05@example.com',
            'password_hash' => password_hash('secreto123', PASSWORD_DEFAULT), 'activo' => 1,
        ]);
        $this->adminId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $this->adminId, 'role_id' => $rolId]);

        $this->db->table('solicitudes')->insert([
            'folio' => $this->folio, 'tramite' => 'UR-TT-T-05', 'ciudadano_id' => $this->adminId,
            'estatus' => 'Recibido', 'monto' => 287, 'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        $this->solicitudId = (int) $this->db->insertID();
    }

    private function validar(array $datos)
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'admin_ur05', 'roles' => ['administrador']])
            ->post('admin/solicitudes/ur05/validacion/' . $this->solicitudId, $datos);
    }

    private function estatus(): string
    {
        return (string) $this->db->table('solicitudes')->where('id', $this->solicitudId)->get()->getRow()->estatus;
    }

    private const CRITERIOS = ['afluencia_baja' => '1', 'sin_transporte_publico' => '1', 'horario_no_entorpece' => '1'];

    public function testAutorizarConLosTresCriteriosPasaAPagoPendiente(): void
    {
        $this->validar(['accion' => 'autorizar'] + self::CRITERIOS)
            ->assertRedirectTo(site_url('admin/solicitudes/' . $this->folio));

        $this->assertSame('Pago pendiente', $this->estatus());
    }

    public function testAutorizarSinTodosLosCriteriosNoRechaza(): void
    {
        $this->validar(['accion' => 'autorizar', 'afluencia_baja' => '1']);

        $this->assertSame('Recibido', $this->estatus());
        $this->assertStringContainsString('3 criterios', (string) session('error'));
    }

    public function testRechazarExigeMotivo(): void
    {
        $this->validar(['accion' => 'rechazar']);

        $this->assertSame('Recibido', $this->estatus());
    }

    public function testRechazarConMotivoPasaARechazado(): void
    {
        $this->validar(['accion' => 'rechazar', 'observaciones_validacion' => 'Calle con transporte público.']);

        $this->assertSame('Rechazado', $this->estatus());
    }

    public function testSinAccionNoCambiaElEstatus(): void
    {
        $this->validar(self::CRITERIOS);

        $this->assertSame('Recibido', $this->estatus());
    }
}
