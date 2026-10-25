<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Libraries\FolioGenerator;
use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Verifica la creación de solicitudes presenciales desde el panel de admin/operador.
 * Cubre los casos de uso principales de cada trámite y las restricciones de acceso.
 */
class SolicitudPresencialTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $operadorId;
    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles
        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);
        $roles = [];
        foreach ($this->db->table('roles')->get()->getResult() as $rol) {
            $roles[$rol->nombre] = (int) $rol->id;
        }

        // Crear usuarios
        $this->adminId = $this->crearUsuario('admin_pres', $roles['administrador']);
        $this->operadorId = $this->crearUsuario('op_pres', $roles['operador_ventanilla']);
        $this->ciudadanoId = $this->crearUsuario('ciudadano_pres', $roles['ciudadano']);

        // Crear tarifas vigentes para todos los trámites
        $tramites = [
            'UR-TT-T-01' => 9055.20,
            'UR-TT-T-02' => 64.90,
            'UR-TT-T-03' => 50.00,
            'UR-TT-T-04' => 156.94,
            'UR-TT-T-05' => 287.00,
            'UR-TT-T-06' => 4500.00,
            'UR-TT-T-07' => 100.00,
        ];
        foreach ($tramites as $tramite => $monto) {
            $criterio = $tramite === 'UR-TT-T-06' ? 'cesion_concesion_base' : 'base';
            $this->db->table('tarifas')->insert([
                'tramite'   => $tramite,
                'criterio'  => $criterio,
                'monto'     => $monto,
                'vigente_desde' => date('Y-m-d', strtotime('-1 year')),
                'vigente_hasta' => date('Y-m-d', strtotime('+1 year')),
                'placeholder_oficial' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        // Tarifas especiales para UR-07 (particular_dia, empresa_mes, etc.)
        foreach (['particular', 'empresa'] as $tipo) {
            foreach (['dia', 'mes', 'semestre', 'anio'] as $periodo) {
                $this->db->table('tarifas')->insert([
                    'tramite'   => 'UR-TT-T-07',
                    'criterio'  => $tipo . '_' . $periodo,
                    'monto'     => 100.00,
                    'vigente_desde' => date('Y-m-d', strtotime('-1 year')),
                    'vigente_hasta' => date('Y-m-d', strtotime('+1 year')),
                    'placeholder_oficial' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
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

    private function comoAdmin(): self
    {
        return $this->withSession([
            'user_id' => $this->adminId,
            'username' => 'admin_pres',
            'roles' => ['administrador'],
        ]);
    }

    private function comoOperador(): self
    {
        return $this->withSession([
            'user_id' => $this->operadorId,
            'username' => 'op_pres',
            'roles' => ['operador_ventanilla'],
        ]);
    }

    // ─── ACCESS CONTROL ────────────────────────────────────────────

    public function testIndexEsAccesiblePorAdmin(): void
    {
        $this->comoAdmin()->get('/admin/solicitudes/nueva')->assertStatus(200);
    }

    public function testIndexEsAccesiblePorOperador(): void
    {
        $this->comoOperador()->get('/admin/solicitudes/nueva')->assertStatus(200);
    }

    public function testCiudadanoNoAccedeA_solicitudesNueva(): void
    {
        $result = $this->withSession([
            'user_id' => $this->ciudadanoId,
            'username' => 'ciudadano_pres',
            'roles' => ['ciudadano'],
        ])->get('/admin/solicitudes/nueva');
        // Role filter denies: either redirect or 403
        $this->assertTrue(
            $result->response()->getStatusCode() === 302 || $result->response()->getStatusCode() === 403
        );
    }

    // ─── FORMULARIO POR TRÁMITE ─────────────────────────────────────

    public function testFormularioMuestraCamposUr02(): void
    {
        $result = $this->comoAdmin()->get('/admin/solicitudes/nueva/UR-TT-T-02?ciudadano_id=' . $this->ciudadanoId);
        $result->assertStatus(200);
        $body = $result->response()->getBody();
        $this->assertStringContainsString('numero_titulo_concesion', $body);
        $this->assertStringContainsString('nombre_titular', $body);
        $this->assertStringContainsString('vehiculo_placas', $body);
        $this->assertStringContainsString('motivo_despintado', $body);
    }

    public function testFormularioMuestraCamposUr05(): void
    {
        $result = $this->comoAdmin()->get('/admin/solicitudes/nueva/UR-TT-T-05?ciudadano_id=' . $this->ciudadanoId);
        $result->assertStatus(200);
        $body = $result->response()->getBody();
        $this->assertStringContainsString('nombre_solicitante', $body);
        $this->assertStringContainsString('fecha_cierre', $body);
        $this->assertStringContainsString('hora_inicio', $body);
        $this->assertStringContainsString('tipo_cierre', $body);
    }

    public function testFormularioTramiteInvalido404(): void
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->comoAdmin()->get('/admin/solicitudes/nueva/UR-TT-T-99');
    }

    // ─── CREACIÓN DE SOLICITUD ──────────────────────────────────────

    public function testCrearSolicitudUr03Presencial(): void
    {
        $postData = [
            'tramite' => 'UR-TT-T-03',
            'ciudadano_id' => $this->ciudadanoId,
            'numero_titulo_concesion' => 'TIT-2024-001',
            'nombre_concesionario' => 'Juan Pérez García',
            'tipo_persona' => 'fisica',
            'numero_factura' => 'FAC-9999',
            'vehiculo_placas' => 'ABC-123-DEF',
            'vehiculo_num_serie' => 'VNK123456789',
        ];

        $result = $this->comoAdmin()->post('/admin/solicitudes/nueva/guardar', $postData);
        $result->assertRedirect();

        // Verificar que la solicitud se creó
        $solicitud = (new SolicitudModel())->where('tramite', 'UR-TT-T-03')->orderBy('id', 'DESC')->first();
        $this->assertNotNull($solicitud);
        $this->assertEquals('Recibido', $solicitud->estatus);
        $this->assertEquals($this->ciudadanoId, (int) $solicitud->ciudadano_id);
        $this->assertEquals(50.00, (float) $solicitud->monto);

        // Verificar datos
        $datos = (new SolicitudDatoModel())->porSolicitudAgrupado((int) $solicitud->id);
        $this->assertEquals('presencial', $datos['origen']);
        $this->assertEquals('TIT-2024-001', $datos['numero_titulo_concesion']);
        $this->assertEquals('Juan Pérez García', $datos['nombre_concesionario']);
        $this->assertEquals('Orden de Plaqueo', $datos['tramite_concepto']);
    }

    public function testCrearSolicitudSinCiudadanoFallar(): void
    {
        $postData = [
            'tramite' => 'UR-TT-T-05',
            'ciudadano_id' => 0,
            'nombre_solicitante' => 'María López',
            'domicilio' => 'Calle Principal 123, Uriangato',
            'fecha_cierre' => '2026-12-25',
            'hora_inicio' => '08:00',
            'hora_fin' => '14:00',
            'calle_tramo' => 'Av. Hidalgo entre Juárez y Morelos',
            'colonia' => 'Centro',
            'tipo_cierre' => 'parcial',
            'motivo_evento' => 'Fiesta navideña',
            'descripcion_evento' => 'Evento comunitario de temporada navideña 2026',
        ];

        $result = $this->comoOperador()->post('/admin/solicitudes/nueva/guardar', $postData);
        $result->assertRedirect();

        // No se creó la solicitud porque no hay ciudadano vinculado
        $count = (new SolicitudModel())->where('tramite', 'UR-TT-T-05')->countAllResults();
        $this->assertEquals(0, $count);
    }

    public function testCrearSolicitudUr07ConMontoDinamico(): void
    {
        $postData = [
            'tramite' => 'UR-TT-T-07',
            'ciudadano_id' => $this->ciudadanoId,
            'tipo_solicitante' => 'empresa',
            'razon_social_o_nombre' => 'Transportes Uriangato SA de CV',
            'rfc' => 'TUE230101AB1',
            'domicilio_negocio' => 'Av. Industrial 456',
            'direccion_carga_descarga' => 'Calle Mercado 789',
            'periodo' => 'mes',
            'num_camiones' => '3',
            'horario_inicio' => '06:00',
            'horario_fin' => '10:00',
            'es_mudanza' => '0',
        ];

        $result = $this->comoAdmin()->post('/admin/solicitudes/nueva/guardar', $postData);
        $result->assertRedirect();

        $solicitud = (new SolicitudModel())->where('tramite', 'UR-TT-T-07')->orderBy('id', 'DESC')->first();
        $this->assertNotNull($solicitud);
        // 100 * 3 camiones = 300
        $this->assertEquals(300.00, (float) $solicitud->monto);
    }

    public function testFallaValidacionCamposRequeridos(): void
    {
        $postData = [
            'tramite' => 'UR-TT-T-05',
            'ciudadano_id' => 0,
            'nombre_solicitante' => '',
            'domicilio' => '',
            'fecha_cierre' => '',
            'hora_inicio' => '',
            'hora_fin' => '',
            'calle_tramo' => '',
            'colonia' => '',
            'tipo_cierre' => '',
            'motivo_evento' => '',
            'descripcion_evento' => '',
        ];

        $result = $this->comoAdmin()->post('/admin/solicitudes/nueva/guardar', $postData);
        // Should redirect back with errors (validation failure)
        $result->assertRedirect();
    }

    public function testTramiteInvalidoNoCreaSolicitud(): void
    {
        $result = $this->comoAdmin()->post('/admin/solicitudes/nueva/guardar', [
            'tramite' => 'INVALID',
            'ciudadano_id' => 0,
        ]);
        $result->assertRedirect();

        // No should exist any solicitud with tramite INVALID
        $count = (new SolicitudModel())->where('tramite', 'INVALID')->countAllResults();
        $this->assertEquals(0, $count);
    }

    // ─── AUDITORÍA E HISTORIAL ──────────────────────────────────────

    public function testCreaRegistroDeAuditoria(): void
    {
        $postData = [
            'tramite' => 'UR-TT-T-02',
            'ciudadano_id' => $this->ciudadanoId,
            'numero_titulo_concesion' => 'TIT-AUD-001',
            'nombre_titular' => 'Auditoria Test',
            'vehiculo_placas' => 'AUD-001',
            'vehiculo_num_serie' => 'SERIEAUDIT001',
            'motivo_despintado' => 'Prueba de auditoria de cambio de unidad',
        ];

        $this->comoAdmin()->post('/admin/solicitudes/nueva/guardar', $postData);

        $auditoria = $this->db->table('auditoria')
            ->where('entidad', 'solicitudes')
            ->where('accion', 'creada_presencial')
            ->get()->getResult();
        $this->assertGreaterThanOrEqual(1, count($auditoria));
    }

    public function testCreaHistorialEstatus(): void
    {
        $postData = [
            'tramite' => 'UR-TT-T-03',
            'ciudadano_id' => $this->ciudadanoId,
            'numero_titulo_concesion' => 'TIT-HIST-001',
            'nombre_concesionario' => 'Historial Test',
            'tipo_persona' => 'fisica',
            'numero_factura' => 'FAC-HIST',
            'vehiculo_placas' => '',
            'vehiculo_num_serie' => '',
        ];

        $this->comoAdmin()->post('/admin/solicitudes/nueva/guardar', $postData);

        $solicitud = (new SolicitudModel())->where('tramite', 'UR-TT-T-03')->orderBy('id', 'DESC')->first();
        $historial = $this->db->table('historial_estatus')
            ->where('solicitud_id', $solicitud->id)
            ->where('estatus_nuevo', 'Recibido')
            ->get()->getResult();
        $this->assertCount(1, $historial);
        $this->assertStringContainsString('presencial', $historial[0]->comentario);
    }

    // ─── IDENTIFICACIÓN POR CURP Y FLUJO GUIADO ─────────────────────

    private const CURP_PRUEBA = 'PEPJ850315HGTRRN04';

    public function testFormularioSinCiudadanoRegresaAlPaso1(): void
    {
        $this->comoOperador()->get('/admin/solicitudes/nueva/UR-TT-T-04')
            ->assertRedirectTo(site_url('admin/solicitudes/nueva'));
    }

    public function testCurpExistenteSeleccionaAlCiudadano(): void
    {
        $this->db->table('users')->where('id', $this->ciudadanoId)->update(['curp' => self::CURP_PRUEBA]);

        $this->comoOperador()->get('/admin/solicitudes/nueva?curp=' . strtolower(self::CURP_PRUEBA))
            ->assertRedirectTo(site_url('admin/solicitudes/nueva?ciudadano_id=' . $this->ciudadanoId));
    }

    public function testCurpInexistenteMuestraAltaEnLinea(): void
    {
        $result = $this->comoOperador()->get('/admin/solicitudes/nueva?curp=' . self::CURP_PRUEBA);
        $result->assertStatus(200);
        $this->assertStringContainsString('registrar-ciudadano', $result->response()->getBody());
    }

    public function testOperadorCreaCiudadanoPorCurpYQuedaSeleccionado(): void
    {
        $result = $this->comoOperador()->post('/admin/solicitudes/nueva/registrar-ciudadano', [
            'curp'      => strtolower(self::CURP_PRUEBA),
            'nombre'    => 'José',
            'apellido'  => 'Pérez Prueba',
            'telefono'  => '4451234567',
            'domicilio' => 'Calle Morelos 45',
        ]);

        $usuario = $this->db->table('users')->where('curp', self::CURP_PRUEBA)->get()->getRow();
        $this->assertNotNull($usuario);
        $this->assertSame(strtolower(self::CURP_PRUEBA), $usuario->username);
        $this->assertTrue(model(\App\Models\UserModel::class)->tieneRol((int) $usuario->id, 'ciudadano'));
        $result->assertRedirectTo(site_url('admin/solicitudes/nueva?ciudadano_id=' . $usuario->id));
        $this->assertStringContainsString('Contraseña temporal', (string) session('message'));
    }

    public function testNoSeDuplicaUnaCurpExistente(): void
    {
        $this->db->table('users')->where('id', $this->ciudadanoId)->update(['curp' => self::CURP_PRUEBA]);

        $this->comoOperador()->post('/admin/solicitudes/nueva/registrar-ciudadano', [
            'curp' => self::CURP_PRUEBA, 'nombre' => 'Otra', 'apellido' => 'Persona', 'telefono' => '4450000000',
        ]);

        $this->assertSame(1, $this->db->table('users')->where('curp', self::CURP_PRUEBA)->countAllResults());
    }

    // ─── ALTA SIN CURP Y AVISO DE HOMÓNIMO (COB-08) ─────────────────

    private function altaSinCurp(array $extra = []): \CodeIgniter\Test\TestResponse
    {
        return $this->comoOperador()->post('/admin/solicitudes/nueva/registrar-ciudadano', array_merge([
            'nombre'   => 'Rosa Elena',
            'apellido' => 'Márquez Soto',
            'telefono' => '4459876543',
        ], $extra));
    }

    public function testAltaSinCurpRegistraAlCiudadanoSinCuentaDeAcceso(): void
    {
        $result = $this->altaSinCurp();

        $usuario = $this->db->table('users')->where('nombre_completo', 'Rosa Elena Márquez Soto')->get()->getRow();
        $this->assertNotNull($usuario);
        $this->assertNull($usuario->username);
        $this->assertNull($usuario->password_hash);
        $this->assertNull($usuario->curp);
        $this->assertTrue(model(\App\Models\UserModel::class)->tieneRol((int) $usuario->id, 'ciudadano'));
        $result->assertRedirectTo(site_url('admin/solicitudes/nueva?ciudadano_id=' . $usuario->id));
        $this->assertStringContainsString('sin cuenta de acceso', (string) session('message'));
    }

    public function testUnNombreRepetidoPideConfirmacionAntesDeDuplicar(): void
    {
        $this->db->table('users')->where('id', $this->ciudadanoId)
            ->update(['nombre_completo' => 'Rosa Elena Márquez Soto']);

        $result = $this->altaSinCurp(['nombre' => 'ROSA  ELENA', 'apellido' => 'marquez soto']);

        $result->assertRedirect();
        $this->assertSame(1, $this->db->table('users')->where('nombre_completo', 'Rosa Elena Márquez Soto')->countAllResults());
        $this->assertStringContainsString('Ya existe una persona registrada', (string) session('warning'));
    }

    public function testConConfirmacionExpresaSeRegistraElHomonimo(): void
    {
        $this->db->table('users')->where('id', $this->ciudadanoId)
            ->update(['nombre_completo' => 'Rosa Elena Márquez Soto']);

        $result = $this->altaSinCurp(['confirmar_duplicado' => '1']);

        $result->assertRedirect();
        $this->assertSame(2, $this->db->table('users')->where('nombre_completo', 'Rosa Elena Márquez Soto')->countAllResults());
    }

    public function testElFormularioAvisaDelHomonimoAlBuscarPorNombre(): void
    {
        $this->db->table('users')->where('id', $this->ciudadanoId)
            ->update(['nombre_completo' => 'Rosa Elena Márquez Soto']);

        $result = $this->comoOperador()->get('/admin/solicitudes/nueva?q=ROSA%20ELENA%20MARQUEZ%20SOTO');
        $result->assertStatus(200);

        $body = $result->response()->getBody();
        $this->assertStringContainsString('Ya existe una persona registrada', $body);
        $this->assertStringContainsString('Registrar de todos modos', $body);
    }

    public function testFormularioSePrellenaConDatosDelCiudadano(): void
    {
        $this->db->table('users')->where('id', $this->ciudadanoId)->update([
            'nombre_completo' => 'María López', 'domicilio' => 'Calle Hidalgo 10', 'rfc' => 'LOMM900101AB1',
        ]);

        $body = $this->comoOperador()->get('/admin/solicitudes/nueva/UR-TT-T-04?ciudadano_id=' . $this->ciudadanoId)
            ->response()->getBody();

        $this->assertStringContainsString('value="María López"', $body);
        $this->assertStringContainsString('value="Calle Hidalgo 10"', $body);
        $this->assertStringContainsString('value="LOMM900101AB1"', $body);
    }

    public function testErrorDeValidacionRegresaAlMismoFormulario(): void
    {
        $result = $this->comoOperador()->post('/admin/solicitudes/nueva/guardar', [
            'tramite' => 'UR-TT-T-05', 'ciudadano_id' => $this->ciudadanoId, 'nombre_solicitante' => 'María',
        ]);

        $result->assertRedirectTo(site_url('admin/solicitudes/nueva/UR-TT-T-05?ciudadano_id=' . $this->ciudadanoId));
        $this->assertSame('María', old('nombre_solicitante'));
    }

    public function testMigracionSiembraTarifasBaseDeLosTramites(): void
    {
        // Las migraciones de la base de pruebas incluyen 2026-10-21-000025 (tarifas base faltantes).
        $sembradas = $this->db->table('tarifas')
            ->where('criterio', 'base')
            ->like('descripcion', 'Tarifa provisional del prototipo', 'after')
            ->get()->getResult();
        $this->assertEqualsCanonicalizing(
            ['UR-TT-T-01', 'UR-TT-T-02', 'UR-TT-T-03', 'UR-TT-T-04', 'UR-TT-T-05'],
            array_map(static fn ($t) => $t->tramite, $sembradas)
        );
    }

    // ─── BUSQUEDA AJAX ──────────────────────────────────────────────

    public function testBuscarCiudadanoAjax(): void
    {
        $result = $this->comoAdmin()->post('/admin/solicitudes/nueva/buscar-ciudadano', [
            'q' => 'ciudadano_pres',
        ]);
        $result->assertStatus(200);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertArrayHasKey('resultados', $json);
        $this->assertGreaterThanOrEqual(1, count($json['resultados']));
    }

    public function testBuscarCiudadanoQueryCorto(): void
    {
        $result = $this->comoAdmin()->post('/admin/solicitudes/nueva/buscar-ciudadano', [
            'q' => 'a',
        ]);
        $result->assertStatus(200);
        $json = json_decode($result->response()->getBody(), true);
        $this->assertEmpty($json['resultados']);
    }
}
