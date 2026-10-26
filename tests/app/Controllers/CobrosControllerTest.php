<?php declare(strict_types=1);

namespace Tests\Controllers;

use App\Libraries\CobroService;
use App\Libraries\EstadoSolicitudService;
use App\Libraries\PermisosOperador;
use App\Models\CobroModel;
use App\Models\SolicitudModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class CobrosControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $usuarios = [];
    private int $solicitudId;
    private int $cobroTramite;
    private int $cobroMulta;

    protected function setUp(): void
    {
        parent::setUp();
        // La caché de permisos es estática: sin esto, una prueba que quita el
        // permiso de cobros a la ventanilla contamina a las que corren después.
        PermisosOperador::reiniciarCache();

        foreach (['administrador', 'operador_ventanilla', 'ciudadano', 'juez_calificador'] as $rol) {
            if ($this->db->table('roles')->where('nombre', $rol)->countAllResults() === 0) {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
            }
            $rolId = (int) $this->db->table('roles')->where('nombre', $rol)->get()->getRow()->id;
            $this->db->table('users')->insert([
                'username' => $rol . '_cob02', 'email' => $rol . '@cob02.test', 'password_hash' => 'x',
                'nombre_completo' => 'Prueba ' . $rol, 'activo' => 1,
            ]);
            $this->usuarios[$rol] = (int) $this->db->insertID();
            $this->db->table('user_roles')->insert(['user_id' => $this->usuarios[$rol], 'role_id' => $rolId]);
        }
        $this->db->table('users')->where('id', $this->usuarios['ciudadano'])->update(['nombre_completo' => 'Laura Ramírez Gómez']);

        $this->solicitudId = (int) (new SolicitudModel())->insert([
            'folio' => 'FOLCOB02TRAMITE00001', 'tramite' => 'UR-TT-T-07', 'ciudadano_id' => $this->usuarios['ciudadano'],
            'estatus' => 'Recibido', 'monto' => 400.00, 'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        (new EstadoSolicitudService())->cambiarEstatus($this->solicitudId, 'Pago pendiente', $this->usuarios['operador_ventanilla']);
        $this->cobroTramite = (int) (new CobroModel())->where('solicitud_id', $this->solicitudId)->first()->id;

        // PostgreSQL aplica la llave foránea agente_placa; el fixture debe crear su agente.
        $this->db->table('agentes_transito')->insert([
            'placa' => 'AGT-204', 'nombre_completo' => 'Agente de prueba', 'password_hash' => 'x',
            'rol' => 'operativo', 'sector' => 'Pruebas', 'activo' => 1,
        ]);

        $this->db->table('boletas_infracciones')->insert([
            'folio' => 'BOLETA-COB02-1', 'agente_placa' => 'AGT-204', 'fecha_infraccion' => date('Y-m-d'),
            'hora_infraccion' => '09:15:00', 'lugar' => 'Calle Hidalgo', 'infractor_nombre' => 'Martín Aguilar Rosas',
            'vehiculo_placas' => 'GTA-123-B', 'estado' => 'calificada',
        ]);
        $this->cobroMulta = (new CobroService())->crearDesdeBoleta((int) $this->db->insertID(), 1000.0, $this->usuarios['juez_calificador']);
    }

    protected function tearDown(): void
    {
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function como(string $rol): self
    {
        return $this->withSession([
            'user_id'  => $this->usuarios[$rol],
            'username' => $rol . '_cob02',
            'roles'    => [$rol],
        ]);
    }

    public function testElAdministradorVeElListadoConSusBotones(): void
    {
        $respuesta = $this->como('administrador')->get('/admin/cobros');

        $respuesta->assertOK();
        $html = $respuesta->response()->getBody();
        $this->assertStringContainsString('Laura Ramírez Gómez', $html);
        $this->assertStringContainsString('Martín Aguilar Rosas', $html);
        $this->assertStringContainsString('Permiso de Carga y Descarga', $html);
        $this->assertStringContainsString('data-cobro-accion="cobrar"', $html);
        $this->assertStringContainsString('Mandar a completar', $html);
    }

    public function testElJuezUsaElMismoListadoEnSuPanel(): void
    {
        $respuesta = $this->como('juez_calificador')->get('/juez/cobros');

        $respuesta->assertOK();
        $html = $respuesta->response()->getBody();
        $this->assertStringContainsString('Martín Aguilar Rosas', $html);
        $this->assertStringContainsString('/juez/cobros', $html);
        $this->assertStringContainsString('value="juez_calificador" selected', $html);
    }

    public function testUnCiudadanoNoEntraAlListado(): void
    {
        $this->como('ciudadano')->get('/admin/cobros')->assertRedirect();
        $this->como('ciudadano')->get('/juez/cobros')->assertRedirect();
    }

    public function testCobrarUnTramiteLoApruebaYPasaLaSolicitudAPagado(): void
    {
        $respuesta = $this->como('operador_ventanilla')->post('/admin/cobros/' . $this->cobroTramite . '/cobrar', [
            'lugar_pago' => 'ventanilla',
            'volver'     => '/admin/cobros?estatus=pendiente',
        ]);

        $respuesta->assertRedirectTo('/admin/cobros?estatus=pendiente');
        $this->assertStringContainsString('Recibo REC-', (string) session('message'));
        $this->assertSame('aprobado', (new CobroModel())->find($this->cobroTramite)->estatus);
        $this->assertSame('Pagado', (new SolicitudModel())->find($this->solicitudId)->estatus);
    }

    public function testBanBajioPideLosDatosDelComprobante(): void
    {
        $this->como('juez_calificador')->post('/juez/cobros/' . $this->cobroMulta . '/cobrar', ['lugar_pago' => 'banbajio']);
        $this->assertStringContainsString('importe', (string) session('error'));

        $this->como('juez_calificador')->post('/juez/cobros/' . $this->cobroMulta . '/cobrar', [
            'lugar_pago' => 'banbajio', 'importe_pagado' => '600', 'fecha_pago_banco' => date('Y-m-d'),
        ]);
        $this->assertStringContainsString('comprobante', (string) session('error'));
        $this->assertSame('pendiente', (new CobroModel())->find($this->cobroMulta)->estatus);

        // Multa de hoy: 1,000 con 40 % de pronto pago.
        $this->como('juez_calificador')->post('/juez/cobros/' . $this->cobroMulta . '/cobrar', [
            'lugar_pago' => 'banbajio', 'referencia_pago' => 'OP-55', 'importe_pagado' => '$600.00', 'fecha_pago_banco' => date('Y-m-d'),
        ]);
        $this->assertSame('aprobado', (new CobroModel())->find($this->cobroMulta)->estatus);
    }

    public function testBuscarPorReferenciaBancaria(): void
    {
        $referencia = (new CobroModel())->find($this->cobroTramite)->referencia_bancaria;

        $html = $this->como('operador_ventanilla')->get('/admin/cobros/banco?referencia=' . urlencode(chunk_split($referencia, 4, ' ')))->response()->getBody();
        $this->assertStringContainsString('Laura Ramírez Gómez', $html);
        $this->assertStringContainsString('Registrar comprobante de BanBajío', $html);

        $html = $this->como('operador_ventanilla')->get('/admin/cobros/banco?referencia=123')->response()->getBody();
        $this->assertStringContainsString('La referencia no es válida', $html);

        $html = $this->como('operador_ventanilla')->get('/admin/cobros?q=' . $referencia)->response()->getBody();
        $this->assertStringContainsString('Laura Ramírez Gómez', $html);
        $this->assertStringNotContainsString('Martín Aguilar Rosas', $html);
    }

    public function testLaHojaDePagoMuestraLaReferenciaBancaria(): void
    {
        $referencia = (new CobroModel())->find($this->cobroTramite)->referencia_bancaria;

        $html = $this->como('administrador')->get('/documentos/hoja-pago/solicitud/FOLCOB02TRAMITE00001')->response()->getBody();
        $this->assertStringContainsString($referencia, $html);
    }

    public function testMandarACompletarPideMotivoYAparaceEnSuFiltro(): void
    {
        $this->como('administrador')->post('/admin/cobros/' . $this->cobroTramite . '/completar', ['motivo' => ' ']);
        $this->assertSame('Escriba el motivo.', session('error'));

        $this->como('administrador')->post('/admin/cobros/' . $this->cobroTramite . '/completar', ['motivo' => 'Falta la identificación oficial.']);
        $cobro = (new CobroModel())->find($this->cobroTramite);
        $this->assertSame('pendiente', $cobro->estatus);
        $this->assertSame(1, (int) $cobro->pendiente_documentos);

        $html = $this->como('administrador')->get('/admin/cobros?estatus=por_completar')->response()->getBody();
        $this->assertStringContainsString('Falta la identificación oficial.', $html);
        $this->assertStringNotContainsString('Martín Aguilar Rosas', $html);
    }

    public function testCancelarDejaElMotivoEnElDetalle(): void
    {
        $this->como('juez_calificador')->post('/juez/cobros/' . $this->cobroMulta . '/cancelar', ['motivo' => 'Boleta duplicada.'])
            ->assertRedirectTo('/juez/cobros/' . $this->cobroMulta);
        $this->assertSame('cancelado', (new CobroModel())->find($this->cobroMulta)->estatus);

        $html = $this->como('juez_calificador')->get('/juez/cobros/' . $this->cobroMulta)->response()->getBody();
        $this->assertStringContainsString('Boleta duplicada.', $html);
        $this->assertStringContainsString('Cancelado', $html);
        $this->assertStringNotContainsString('data-cobro-accion="cobrar"', $html);
    }

    public function testUnaRutaDeVueltaAjenaSeIgnora(): void
    {
        $this->como('administrador')->post('/admin/cobros/' . $this->cobroTramite . '/cancelar', [
            'motivo' => 'Prueba', 'volver' => 'https://otro-sitio.example/robo',
        ])->assertRedirectTo('/admin/cobros/' . $this->cobroTramite);
    }

    public function testCorteDeCajaDelDiaEnPantallaYEnCsv(): void
    {
        $this->como('operador_ventanilla')->post('/admin/cobros/' . $this->cobroTramite . '/cobrar', ['lugar_pago' => 'ventanilla']);

        $html = $this->como('administrador')->get('/admin/cobros/corte')->response()->getBody();
        $this->assertStringContainsString('Total cobrado', $html);
        $this->assertStringContainsString('$400.00', $html);
        $this->assertStringContainsString('Laura Ramírez Gómez', $html);
        $this->assertStringContainsString('Permiso de Carga y Descarga', $html);

        $csv = $this->como('juez_calificador')->get('/juez/cobros/corte/csv?periodo=mes');
        $csv->assertOK();
        $this->assertStringContainsString('text/csv', $csv->response()->getHeaderLine('Content-Type'));
        $cuerpo = $csv->response()->getBody();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $cuerpo);
        $this->assertStringContainsString('Laura Ramírez Gómez', $cuerpo);
        $this->assertStringContainsString('Total del corte', $cuerpo);
        $this->assertStringContainsString('400.00', $cuerpo);
    }

    public function testElOperadorSinPermisoDeCobrosNoEntra(): void
    {
        $permisos = PermisosOperador::todos();
        $permisos['cobros'] = false;
        PermisosOperador::guardar($permisos);

        $this->como('operador_ventanilla')->get('/admin/cobros')->assertRedirect();
        $this->como('operador_ventanilla')->get('/admin/cobros/corte')->assertRedirect();
        $this->como('administrador')->get('/admin/cobros')->assertOK();
    }
}
