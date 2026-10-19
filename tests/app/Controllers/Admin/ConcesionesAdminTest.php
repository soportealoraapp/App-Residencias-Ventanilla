<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Libraries\FolioGenerator;
use App\Models\AuditoriaModel;
use App\Models\SolicitudModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Borrado del padrón de concesiones.
 *
 * Antes la baja era unconditional y la FK de solicitudes.concesion_id estaba en
 * ON DELETE CASCADE: un clic en "Eliminar" se llevaba por delante los expedientes
 * UR-02/03/06 vinculados, sin aviso y sin forma de revertirlo. Aquí se fija que
 * el panel se niegue cuando existen solicitudes y que sí permita borrar una
 * concesión que nadie ha usado todavía.
 */
class ConcesionesAdminTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);
        $roles = [];
        foreach ($this->db->table('roles')->get()->getResult() as $rol) {
            $roles[$rol->nombre] = (int) $rol->id;
        }

        $this->adminId     = $this->crearUsuario('admin_conc', $roles['administrador']);
        $this->ciudadanoId = $this->crearUsuario('ciud_conc', $roles['ciudadano']);
    }

    private function crearUsuario(string $username, int $roleId): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Usuario ' . $username,
            'activo'          => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $roleId]);

        return $id;
    }

    private function sembrarConcesion(array $cambios = []): int
    {
        $fila = array_merge([
            'numero_titulo'  => 'TIT-' . random_int(1000, 9999),
            'titular_actual' => 'Transportes de Prueba S.A. de C.V.',
            'estatus'        => 'vigente',
        ], $cambios);

        $this->db->table('concesiones')->insert($fila);

        return (int) $this->db->insertID();
    }

    private function vincularSolicitud(int $concesionId, string $tramite = 'UR-TT-T-02'): int
    {
        $solicitudModel = new SolicitudModel();
        $solicitudModel->insert([
            'folio'           => FolioGenerator::generar(),
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->ciudadanoId,
            'concesion_id'    => $concesionId,
            'estatus'         => 'Recibido',
            'monto'           => 500.00,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);

        return (int) $solicitudModel->getInsertID();
    }

    private function comoAdmin(): self
    {
        return $this->withSession([
            'user_id'  => $this->adminId,
            'username' => 'admin_conc',
            'roles'    => ['administrador'],
        ]);
    }

    public function testNoSePuedeEliminarUnaConcesionConExpedientes(): void
    {
        $concesionId = $this->sembrarConcesion();
        $solicitudId = $this->vincularSolicitud($concesionId);

        $response = $this->comoAdmin()->post('/admin/concesiones/eliminar/' . $concesionId);
        $response->assertRedirect();

        $this->assertSame(1, $this->db->table('concesiones')->where('id', $concesionId)->countAllResults(),
            'Se eliminó una concesión que todavía tenía expedientes vinculados.');
        $this->assertSame(1, $this->db->table('solicitudes')->where('id', $solicitudId)->countAllResults(),
            'El expediente del ciudadano se perdió junto con la concesión.');
    }

    public function testLaNegativaNoDejaRegistroDeUnaEliminacion(): void
    {
        $concesionId = $this->sembrarConcesion();
        $this->vincularSolicitud($concesionId);

        $this->comoAdmin()->post('/admin/concesiones/eliminar/' . $concesionId);

        $auditoria = (new AuditoriaModel())
            ->where('entidad', 'concesiones')
            ->where('entidad_id', $concesionId)
            ->where('accion', 'eliminar')
            ->countAllResults();

        $this->assertSame(0, $auditoria, 'La auditoría registró una eliminación que nunca ocurrió.');
    }

    public function testSiSePuedeEliminarUnaConcesionSinExpedientes(): void
    {
        $concesionId = $this->sembrarConcesion(['numero_titulo' => 'TIT-SIN-USO']);

        $response = $this->comoAdmin()->post('/admin/concesiones/eliminar/' . $concesionId);
        $response->assertRedirect();

        $this->assertSame(0, $this->db->table('concesiones')->where('id', $concesionId)->countAllResults());
    }

    public function testVariasSolicitudesTambienBloqueanLaEliminacion(): void
    {
        $concesionId = $this->sembrarConcesion();
        $primerId    = $this->vincularSolicitud($concesionId, 'UR-TT-T-02');
        $segundoId   = $this->vincularSolicitud($concesionId, 'UR-TT-T-03');

        $this->comoAdmin()->post('/admin/concesiones/eliminar/' . $concesionId);

        $this->assertSame(2, $this->db->table('solicitudes')
            ->whereIn('id', [$primerId, $segundoId])->countAllResults());
        $this->assertSame(1, $this->db->table('concesiones')->where('id', $concesionId)->countAllResults());
    }

    public function testLaListaDeConcesionesSigueOperando(): void
    {
        $this->sembrarConcesion(['numero_titulo' => 'TIT-Listable-01']);

        $response = $this->comoAdmin()->get('/admin/concesiones');
        $response->assertStatus(200);
        $response->assertSee('TIT-Listable-01');
    }
}
