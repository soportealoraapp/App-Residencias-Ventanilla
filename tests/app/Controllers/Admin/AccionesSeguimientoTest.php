<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Libraries\AccionesSeguimientoSolicitud;
use App\Libraries\PermisosOperador;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

final class AccionesSeguimientoTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $operadorId;
    private int $ciudadanoId;
    private int $secuencia = 0;

    protected function setUp(): void
    {
        parent::setUp();
        PermisosOperador::reiniciarCache();
        $this->adminId = $this->usuario('admin_acciones', 'administrador');
        $this->operadorId = $this->usuario('operador_acciones', 'operador_ventanilla');
        $this->ciudadanoId = $this->usuario('ciudadano_acciones', 'ciudadano');
    }

    protected function tearDown(): void
    {
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function usuario(string $username, string $rol): int
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
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $rolId]);

        return $id;
    }

    /** @return array{id: int, folio: string} */
    private function solicitud(string $tramite, string $estatus, float $monto = 400.0, ?int $convocatoriaId = null): array
    {
        $this->secuencia++;
        $folio = 'ACCIONES' . str_pad((string) $this->secuencia, 12, '0', STR_PAD_LEFT);
        $this->db->table('solicitudes')->insert([
            'folio' => $folio,
            'tramite' => $tramite,
            'ciudadano_id' => $this->ciudadanoId,
            'convocatoria_id' => $convocatoriaId,
            'estatus' => $estatus,
            'monto' => $monto,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);

        return ['id' => (int) $this->db->insertID(), 'folio' => $folio];
    }

    private function comoAdmin(): self
    {
        return $this->withSession([
            'user_id' => $this->adminId,
            'username' => 'admin_acciones',
            'roles' => ['administrador'],
        ]);
    }

    private function cambiar(int $id, string $destino, ?string $comentario = null)
    {
        $datos = ['nuevo_estatus' => $destino];
        if ($comentario !== null) {
            $datos['comentario'] = $comentario;
        }

        return $this->comoAdmin()->post('/admin/solicitudes/cambiar-estatus/' . $id, $datos);
    }

    private function estatus(int $id): string
    {
        return (string) $this->db->table('solicitudes')->where('id', $id)->get()->getRow()->estatus;
    }

    private function comoOperador(): self
    {
        return $this->withSession([
            'user_id' => $this->operadorId,
            'username' => 'operador_acciones',
            'roles' => ['operador_ventanilla'],
        ]);
    }

    public function testOperadorSinPermisoDeSolicitudesNoVeLasAcciones(): void
    {
        $solicitud = $this->solicitud('UR-TT-T-03', 'Recibido');
        $permisos = PermisosOperador::todos();
        $permisos['solicitudes'] = false;
        PermisosOperador::guardar($permisos);

        $this->comoOperador()->get('/admin/solicitudes/' . $solicitud['folio'])
            ->assertRedirectTo('/admin/dashboard');
    }
    public function testDescriptoresSeDerivanDeLasTransicionesYExcluyenFlujosEspeciales(): void
    {
        $ur04 = array_column(AccionesSeguimientoSolicitud::genericas('UR-TT-T-04', 'En revisión documental'), 'etiqueta');
        $this->assertSame(['Pedir documentos', 'Confirmar documentos completos', 'Rechazar solicitud'], $ur04);

        $ur01 = array_column(AccionesSeguimientoSolicitud::genericas('UR-TT-T-01', 'Evaluación comparativa'), 'etiqueta');
        $this->assertSame(['Rechazar solicitud'], $ur01);
        $this->assertFalse(AccionesSeguimientoSolicitud::permiteEndpointGenerico('UR-TT-T-02', 'Recibido', 'Cita agendada'));
        $this->assertFalse(AccionesSeguimientoSolicitud::permiteEndpointGenerico('UR-TT-T-07', 'Pago pendiente', 'Pagado'));
    }

    public function testLasPantallasDeLosDemasTramitesMuestranAccionesSinSelector(): void
    {
        $casos = [
            ['UR-TT-T-01', 'Recibido', 'Iniciar revisi&oacute;n'],
            ['UR-TT-T-02', 'Recibido', 'La cita requiere capturar una fecha'],
            ['UR-TT-T-03', 'Recibido', 'Iniciar revisi&oacute;n'],
            ['UR-TT-T-04', 'Recibido', 'Iniciar revisi&oacute;n documental'],
            ['UR-TT-T-06', 'Recibido', 'Iniciar revisi&oacute;n documental'],
            ['UR-TT-T-07', 'Recibido', 'Autorizar pago'],
        ];

        foreach ($casos as [$tramite, $estatus, $texto]) {
            $solicitud = $this->solicitud($tramite, $estatus);
            $body = $this->comoAdmin()->get('/admin/solicitudes/' . $solicitud['folio'])->getBody();
            $this->assertStringContainsString('Acciones de seguimiento', $body, $tramite);
            $this->assertStringContainsString($texto, $body, $tramite);
            $this->assertStringNotContainsString('<select name="nuevo_estatus"', $body, $tramite);
            $this->assertStringNotContainsString('Cambio de estatus', $body, $tramite);
        }
    }

    public function testUr01NoPermiteSeleccionIndividualPorElEndpointGenerico(): void
    {
        $solicitud = $this->solicitud('UR-TT-T-01', 'Evaluación comparativa');
        $this->cambiar($solicitud['id'], 'Seleccionado');

        $this->assertSame('Evaluación comparativa', $this->estatus($solicitud['id']));
        $this->assertSame(0, $this->db->table('historial_estatus')->where('solicitud_id', $solicitud['id'])->countAllResults());
    }

    public function testUr02NoPermiteAgendarSinFechaNiDictaminarPorElEndpointGenerico(): void
    {
        $solicitud = $this->solicitud('UR-TT-T-02', 'Recibido');
        $this->cambiar($solicitud['id'], 'Cita agendada');
        $this->assertSame('Recibido', $this->estatus($solicitud['id']));

        $this->db->table('solicitudes')->where('id', $solicitud['id'])->update(['estatus' => 'Cita agendada']);
        $this->cambiar($solicitud['id'], 'Verificado');
        $this->assertSame('Cita agendada', $this->estatus($solicitud['id']));
        $this->assertSame(0, $this->db->table('verificaciones_fisicas')->where('solicitud_id', $solicitud['id'])->countAllResults());
    }

    public function testDictamenUr02ExigeCitaYGuardaResultadoEHistorialAtomicamente(): void
    {
        $sinCita = $this->solicitud('UR-TT-T-02', 'Recibido');
        $this->comoAdmin()->post('/admin/solicitudes/dictamen-ur02/' . $sinCita['id'], [
            'resultado' => 'aprobado', 'observaciones' => 'Unidad correctamente despintada.',
        ]);
        $this->assertSame('Recibido', $this->estatus($sinCita['id']));
        $this->assertSame(0, $this->db->table('verificaciones_fisicas')->where('solicitud_id', $sinCita['id'])->countAllResults());

        $conCita = $this->solicitud('UR-TT-T-02', 'Cita agendada');
        $this->comoAdmin()->post('/admin/solicitudes/dictamen-ur02/' . $conCita['id'], [
            'resultado' => 'aprobado', 'observaciones' => 'Unidad correctamente despintada.',
        ]);
        $this->assertSame('Verificado', $this->estatus($conCita['id']));
        $this->assertSame(1, $this->db->table('verificaciones_fisicas')->where('solicitud_id', $conCita['id'])->where('resultado', 'aprobado')->countAllResults());
        $this->assertSame(1, $this->db->table('historial_estatus')->where('solicitud_id', $conCita['id'])->where('estatus_nuevo', 'Verificado')->countAllResults());
        $this->assertSame(1, $this->db->table('auditoria')->where('entidad', 'solicitudes')->where('entidad_id', $conCita['id'])->countAllResults());
    }

    public function testPagoNoPuedeRegistrarseComoCambioGenerico(): void
    {
        $ur07 = $this->solicitud('UR-TT-T-07', 'Recibido');
        $this->cambiar($ur07['id'], 'Pago pendiente');
        $this->assertSame('Pago pendiente', $this->estatus($ur07['id']));
        $cobro = $this->db->table('cobros')->where('solicitud_id', $ur07['id'])->get()->getRow();
        $this->assertNotNull($cobro);

        $this->cambiar($ur07['id'], 'Pagado');
        $this->assertSame('Pago pendiente', $this->estatus($ur07['id']));
        $this->assertSame('pendiente', $this->db->table('cobros')->where('id', $cobro->id)->get()->getRow()->estatus);
        $this->assertSame(0, $this->db->table('cobro_movimientos')->where('cobro_id', $cobro->id)->where('accion', 'cobrar')->countAllResults());

        $ur06 = $this->solicitud('UR-TT-T-06', 'Pago pendiente');
        $this->cambiar($ur06['id'], 'Concluido');
        $this->assertSame('Pago pendiente', $this->estatus($ur06['id']));
    }

    public function testAutorizarPagoGeneraUnSoloCobroConHistorialYAuditoria(): void
    {
        $solicitud = $this->solicitud('UR-TT-T-07', 'Recibido', 450.0);
        $this->cambiar($solicitud['id'], 'Pago pendiente');
        $this->cambiar($solicitud['id'], 'Pago pendiente');

        $this->assertSame('Pago pendiente', $this->estatus($solicitud['id']));
        $this->assertSame(1, $this->db->table('historial_estatus')->where('solicitud_id', $solicitud['id'])->countAllResults());
        $this->assertSame(1, $this->db->table('auditoria')->where('entidad', 'solicitudes')->where('entidad_id', $solicitud['id'])->countAllResults());
        $this->assertSame(1, $this->db->table('cobros')->where('solicitud_id', $solicitud['id'])->where('estatus', 'pendiente')->countAllResults());
        $cobroId = (int) $this->db->table('cobros')->where('solicitud_id', $solicitud['id'])->get()->getRow()->id;
        $this->assertSame(1, $this->db->table('cobro_movimientos')->where('cobro_id', $cobroId)->where('accion', 'crear')->countAllResults());
    }

    public function testPrevencionYRechazoExigenMotivoYConservanLaEntrada(): void
    {
        $solicitud = $this->solicitud('UR-TT-T-04', 'En revisión documental');
        $this->cambiar($solicitud['id'], 'Prevención');
        $this->assertSame('En revisión documental', $this->estatus($solicitud['id']));

        $this->cambiar($solicitud['id'], 'Pago pendiente', 'Texto capturado que debe conservarse.');
        $entrada = session('_ci_old_input');
        $this->assertSame('Texto capturado que debe conservarse.', $entrada['post']['comentario'] ?? null);
        $this->assertSame('En revisión documental', $this->estatus($solicitud['id']));
    }

    public function testEstadoTerminalNoMuestraAccionesDeAvance(): void
    {
        $solicitud = $this->solicitud('UR-TT-T-03', 'Aprobado');
        $body = $this->comoAdmin()->get('/admin/solicitudes/' . $solicitud['folio'])->getBody();
        $this->assertStringContainsString('No hay acciones disponibles', $body);
        $this->assertStringNotContainsString('class="formAccionSeguimiento"', $body);
    }
}
