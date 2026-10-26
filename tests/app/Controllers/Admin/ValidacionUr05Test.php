<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Filters\RoleFilter;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\PermisosOperador;
use App\Models\SolicitudModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class ValidacionUr05Test extends DatabaseTestCase
{
    use FeatureTestTrait;

    private const CRITERIOS = [
        'afluencia_baja' => '1',
        'sin_transporte_publico' => '1',
        'horario_no_entorpece' => '1',
    ];

    private int $adminId;
    private int $ciudadanoId;
    private int $solicitudId;
    private string $folio = 'FOLIOUR05PRUEBA00001';

    protected function setUp(): void
    {
        parent::setUp();
        PermisosOperador::reiniciarCache();

        $this->adminId = $this->crearUsuarioConRol('admin_ur05', 'administrador');
        $this->ciudadanoId = $this->crearUsuarioConRol('ciudadano_ur05', 'ciudadano');

        $this->db->table('solicitudes')->insert([
            'folio' => $this->folio,
            'tramite' => 'UR-TT-T-05',
            'ciudadano_id' => $this->ciudadanoId,
            'estatus' => 'Recibido',
            'monto' => 287,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        $this->solicitudId = (int) $this->db->insertID();
    }

    protected function tearDown(): void
    {
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function crearUsuarioConRol(string $username, string $rol): int
    {
        $filaRol = $this->db->table('roles')->where('nombre', $rol)->get()->getRow();
        if ($filaRol === null) {
            $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
            $rolId = (int) $this->db->insertID();
        } else {
            $rolId = (int) $filaRol->id;
        }

        $this->db->table('users')->insert([
            'username' => $username,
            'email' => $username . '@example.test',
            'password_hash' => password_hash('secreto123', PASSWORD_DEFAULT),
            'activo' => 1,
        ]);
        $userId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => $rolId]);

        return $userId;
    }

    private function comoAdmin(): self
    {
        return $this->withSession([
            'user_id' => $this->adminId,
            'username' => 'admin_ur05',
            'roles' => ['administrador'],
        ]);
    }

    private function autorizar(array $datos)
    {
        return $this->comoAdmin()->post('admin/solicitudes/ur05/validacion/' . $this->solicitudId, $datos);
    }

    private function cambiar(string $estatus, ?string $comentario = null)
    {
        $datos = ['nuevo_estatus' => $estatus];
        if ($comentario !== null) {
            $datos['comentario'] = $comentario;
        }

        return $this->comoAdmin()->post('admin/solicitudes/cambiar-estatus/' . $this->solicitudId, $datos);
    }

    private function estatus(): string
    {
        return (string) $this->db->table('solicitudes')->where('id', $this->solicitudId)->get()->getRow()->estatus;
    }

    public function testRecibidoMuestraAccionesPermitidasSinSelectorNiAutorizacion(): void
    {
        $body = $this->comoAdmin()->get('admin/solicitudes/' . $this->folio)->getBody();

        $this->assertStringContainsString('Acciones de cierre de calle', $body);
        $this->assertStringContainsString('Iniciar validaci&oacute;n', $body);
        $this->assertStringContainsString('Rechazar solicitud', $body);
        $this->assertStringNotContainsString('Autorizar pago', $body);
        $this->assertStringNotContainsString('<select name="nuevo_estatus"', $body);
        $this->assertStringNotContainsString('Validación rápida UR-05', $body);
    }

    public function testEnValidacionMuestraCriteriosAutorizarYRechazarUnaSolaVez(): void
    {
        $this->cambiar('En validación');
        $body = $this->comoAdmin()->get('admin/solicitudes/' . $this->folio)->getBody();

        $this->assertSame(1, substr_count($body, 'Autorizar pago'));
        $this->assertSame(1, substr_count($body, 'Rechazar solicitud'));
        $this->assertStringContainsString('name="afluencia_baja"', $body);
        $this->assertStringContainsString('name="sin_transporte_publico"', $body);
        $this->assertStringContainsString('name="horario_no_entorpece"', $body);
        $this->assertStringNotContainsString('Iniciar validaci&oacute;n', $body);
    }

    public function testEstadosPosterioresMuestranSoloSusAccionesValidas(): void
    {
        $casos = [
            'Pago pendiente' => ['presente' => ['Registrar pago', 'Rechazar solicitud'], 'ausente' => ['Autorizar pago', 'Iniciar validaci&oacute;n']],
            'Pagado' => ['presente' => ['Emitir permiso'], 'ausente' => ['Autorizar pago', 'Rechazar solicitud']],
            'Rechazado' => ['presente' => ['No hay acciones disponibles'], 'ausente' => ['Autorizar pago', 'Rechazar solicitud']],
        ];

        foreach ($casos as $estatus => $esperado) {
            $this->db->table('solicitudes')->where('id', $this->solicitudId)->update(['estatus' => $estatus]);
            $body = $this->comoAdmin()->get('admin/solicitudes/' . $this->folio)->getBody();
            foreach ($esperado['presente'] as $texto) {
                $this->assertStringContainsString($texto, $body, $estatus);
            }
            foreach ($esperado['ausente'] as $texto) {
                $this->assertStringNotContainsString($texto, $body, $estatus);
            }
        }
    }
    public function testUsuarioSinRolDePanelEsRechazadoPorElFiltroReal(): void
    {
        session()->set([
            'user_id' => $this->ciudadanoId,
            'username' => 'ciudadano_ur05',
            'roles' => ['ciudadano'],
        ]);

        $respuesta = (new RoleFilter())->before(service('request'), ['administrador,operador_ventanilla']);

        $this->assertNotNull($respuesta);
        $this->assertSame(302, $respuesta->getStatusCode());
        $this->assertSame(site_url('/'), $respuesta->getHeaderLine('Location'));
    }
    public function testAutorizarConLosTresCriteriosGeneraHistorialAuditoriaYCobroUnaSolaVez(): void
    {
        $this->cambiar('En validación');
        $this->autorizar(self::CRITERIOS + ['observaciones_validacion' => 'Revisión vial completada.'])
            ->assertRedirectTo(site_url('admin/solicitudes/' . $this->folio));

        $this->assertSame('Pago pendiente', $this->estatus());
        $this->assertSame(2, $this->db->table('historial_estatus')->where('solicitud_id', $this->solicitudId)->countAllResults());
        $this->assertSame(2, $this->db->table('auditoria')->where('entidad', 'solicitudes')->where('entidad_id', $this->solicitudId)->countAllResults());
        $this->assertSame(1, $this->db->table('cobros')->where('solicitud_id', $this->solicitudId)->where('estatus', 'pendiente')->countAllResults());
        $cobroId = (int) $this->db->table('cobros')->where('solicitud_id', $this->solicitudId)->get()->getRow()->id;
        $this->assertSame(1, $this->db->table('cobro_movimientos')->where('cobro_id', $cobroId)->where('accion', 'crear')->countAllResults());

        foreach (array_keys(self::CRITERIOS) as $campo) {
            $this->assertSame('1', $this->db->table('solicitud_datos')->where(['solicitud_id' => $this->solicitudId, 'clave' => $campo])->get()->getRow()->valor);
        }

        $this->autorizar(self::CRITERIOS);
        $this->assertSame(1, $this->db->table('cobros')->where('solicitud_id', $this->solicitudId)->countAllResults());
        $this->assertSame(2, $this->db->table('historial_estatus')->where('solicitud_id', $this->solicitudId)->countAllResults());
    }

    public function testAutorizarSinCualquieraDeLosCriteriosEsRechazadoSinCambios(): void
    {
        $this->cambiar('En validación');

        foreach (array_keys(self::CRITERIOS) as $faltante) {
            $datos = self::CRITERIOS;
            unset($datos[$faltante]);
            $this->autorizar($datos);

            $this->assertSame('En validación', $this->estatus(), $faltante);
            $this->assertSame(0, $this->db->table('cobros')->where('solicitud_id', $this->solicitudId)->countAllResults(), $faltante);
            $this->assertSame(0, $this->db->table('solicitud_datos')->where('solicitud_id', $this->solicitudId)->whereIn('clave', array_keys(self::CRITERIOS))->countAllResults(), $faltante);
            $this->assertStringContainsString('3 criterios', (string) session('error'), $faltante);
        }
    }
    public function testPostDirectoAlEndpointGenericoNoOmiteLosCriterios(): void
    {
        $this->cambiar('En validación');
        $this->cambiar('Pago pendiente');

        $this->assertSame('En validación', $this->estatus());
        $this->assertSame(0, $this->db->table('cobros')->where('solicitud_id', $this->solicitudId)->countAllResults());
    }

    public function testAutorizarDesdeEstadoNoPermitidoEsRechazado(): void
    {
        $this->autorizar(self::CRITERIOS);

        $this->assertSame('Recibido', $this->estatus());
        $this->assertSame(0, $this->db->table('cobros')->where('solicitud_id', $this->solicitudId)->countAllResults());
    }

    public function testRechazarSinMotivoEsRechazado(): void
    {
        $this->cambiar('Rechazado');

        $this->assertSame('Recibido', $this->estatus());
        $this->assertSame(0, $this->db->table('historial_estatus')->where('solicitud_id', $this->solicitudId)->countAllResults());
    }

    public function testRechazarConMotivoPasaARechazado(): void
    {
        $this->cambiar('Rechazado', 'La calle forma parte de una ruta de transporte público.');

        $this->assertSame('Rechazado', $this->estatus());
        $this->assertSame(1, $this->db->table('historial_estatus')->where('solicitud_id', $this->solicitudId)->where('estatus_nuevo', 'Rechazado')->countAllResults());
    }

    public function testOtrosTramitesConservanElCambioGenerico(): void
    {
        $id = (int) (new SolicitudModel())->insert([
            'folio' => 'FOLIOUR03PRUEBA00001',
            'tramite' => 'UR-TT-T-03',
            'ciudadano_id' => $this->ciudadanoId,
            'estatus' => 'Recibido',
            'monto' => 50,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);

        $this->assertTrue((new EstadoSolicitudService())->cambiarEstatus($id, 'En revisión', $this->adminId));
        $this->assertSame('En revisión', (new SolicitudModel())->find($id)->estatus);
    }
}
