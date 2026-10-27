<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\CobroException;
use App\Libraries\CobroService;
use App\Libraries\CondonacionRecargo;
use App\Libraries\CorteCajaService;
use App\Libraries\HojaPagoService;
use App\Models\CobroModel;
use DateTimeImmutable;
use Tests\Support\DatabaseTestCase;

/** COB-11: condonación de recargos. Secuencial en SQLite; no prueba concurrencia real. */
class CondonacionRecargoTest extends DatabaseTestCase
{
    private CobroService $cobros;
    private int $juez;
    private int $admin;
    private int $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cobros   = new CobroService();
        $this->juez     = $this->usuario('juez_calificador');
        $this->admin    = $this->usuario('administrador');
        $this->operador = $this->usuario('operador_ventanilla');

        $this->db->table('agentes_transito')->insert([
            'placa' => 'AGT-204', 'nombre_completo' => 'Agente de prueba', 'password_hash' => 'x', 'rol' => 'operativo', 'activo' => 1,
        ]);
    }

    private function usuario(string $rol): int
    {
        if ($this->db->table('roles')->where('nombre', $rol)->countAllResults() === 0) {
            $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
        }
        $rolId = (int) $this->db->table('roles')->where('nombre', $rol)->get()->getRow()->id;
        $this->db->table('users')->insert([
            'username' => $rol . '_cob11', 'email' => $rol . '@cob11.test', 'password_hash' => 'x',
            'nombre_completo' => 'Prueba ' . $rol, 'activo' => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $rolId]);

        return $id;
    }

    /** Multa de $1000 con 3 meses de mora: recargo de $150. */
    private function multaConRecargo(string $hace = '-3 months -2 days'): int
    {
        $this->db->table('boletas_infracciones')->insert([
            'folio' => 'BOLETA-COB11-' . uniqid(), 'agente_placa' => 'AGT-204',
            'fecha_infraccion' => date('Y-m-d', strtotime($hace)), 'hora_infraccion' => '10:30:00',
            'lugar' => 'Av. Hidalgo', 'infractor_nombre' => 'Martín Aguilar Rosas', 'vehiculo_placas' => 'GTA-123-B', 'estado' => 'calificada',
        ]);

        return $this->cobros->crearDesdeBoleta((int) $this->db->insertID(), 1000.0, $this->juez);
    }

    private function condonar(int $cobroId, string $importe, string $motivo = 'Situación económica', ?int $usuario = null): array
    {
        $version = $this->cobros->vistaCondonacion($cobroId)['version'];

        return $this->cobros->condonar($cobroId, $importe, $motivo, $usuario ?? $this->juez, $version);
    }

    private function rechaza(callable $f, string $mensaje): void
    {
        try {
            $f();
            $this->fail('Debió rechazar: ' . $mensaje);
        } catch (CobroException $e) {
            $this->assertStringContainsString($mensaje, $e->getMessage());
        }
    }

    public function testCondonacionParcialRegistraMovimientoYAuditoria(): void
    {
        $id = $this->multaConRecargo();
        $r  = $this->condonar($id, '50.50');

        $cobro = (new CobroModel())->find($id);
        $this->assertEqualsWithDelta(50.5, (float) $cobro->condonado, 0.001);
        $this->assertEqualsWithDelta(1099.5, (float) $cobro->total, 0.001);
        $this->assertEqualsWithDelta(1000.0, (float) $cobro->monto_base, 0.001);
        $this->assertSame('pendiente', $cobro->estatus);

        $mov = $this->db->table('cobro_movimientos')->where('cobro_id', $id)->where('accion', 'condonar')->get()->getRow();
        $this->assertSame($this->juez, (int) $mov->usuario_id);
        $this->assertSame('Situación económica', $mov->motivo);
        $d = json_decode($mov->detalle, true);
        $this->assertEqualsWithDelta(50.5, $d['importe_adicional'], 0.001);
        $this->assertEqualsWithDelta(0.0, $d['condonado_anterior'], 0.001);
        $this->assertEqualsWithDelta(50.5, $d['condonado_nuevo'], 0.001);
        $this->assertEqualsWithDelta(150.0, $d['recargo'], 0.001);
        $this->assertEqualsWithDelta(1150.0, $d['total_anterior'], 0.001);
        $this->assertEqualsWithDelta(1099.5, $d['total_nuevo'], 0.001);
        $this->assertEqualsWithDelta(1099.5, $r['total_nuevo'], 0.001);
        $this->assertNotEmpty($mov->fecha);
    }

    public function testCondonacionTotalDejaElTotalEnElMontoBase(): void
    {
        $id = $this->multaConRecargo();
        $this->condonar($id, '150');

        $this->assertEqualsWithDelta(1000.0, (float) (new CobroModel())->find($id)->total, 0.001);
        $vista = $this->cobros->vistaCondonacion($id);
        $this->assertEqualsWithDelta(0.0, $vista['disponible'], 0.001);
        $this->rechaza(fn () => $this->condonar($id, '0.01'), 'No hay recargo disponible');
    }

    public function testImportesInvalidosYMotivoObligatorio(): void
    {
        $id = $this->multaConRecargo();
        foreach (['0', '0.00', '-5', '10,5', '10.555', '1e2', '', 'abc', '150.01', '151'] as $malo) {
            $this->rechaza(fn () => $this->condonar($id, $malo), '');
        }
        $this->rechaza(fn () => $this->condonar($id, '10', '   '), 'motivo');
        $cobro = (new CobroModel())->find($id);
        $this->assertEqualsWithDelta(0.0, (float) $cobro->condonado, 0.001);
        $this->assertSame(0, $this->db->table('cobro_movimientos')->where('accion', 'condonar')->countAllResults());
    }

    public function testLasCondonacionesSeAcumulanSinSobrescribirse(): void
    {
        $id = $this->multaConRecargo();
        $this->condonar($id, '40');
        $this->condonar($id, '60', 'Segunda autorización', $this->admin);

        $this->assertEqualsWithDelta(100.0, (float) (new CobroModel())->find($id)->condonado, 0.001);
        $movs = $this->db->table('cobro_movimientos')->where('accion', 'condonar')->orderBy('id')->get()->getResult();
        $this->assertCount(2, $movs);
        $segundo = json_decode($movs[1]->detalle, true);
        $this->assertEqualsWithDelta(40.0, $segundo['condonado_anterior'], 0.001);
        $this->assertEqualsWithDelta(100.0, $segundo['condonado_nuevo'], 0.001);
        $this->assertSame($this->admin, (int) $movs[1]->usuario_id);

        $this->rechaza(fn () => $this->condonar($id, '50.01'), 'excede el recargo disponible');
    }

    public function testElReenvioDelMismoFormularioNoDuplica(): void
    {
        $id      = $this->multaConRecargo();
        $version = $this->cobros->vistaCondonacion($id)['version'];

        $this->cobros->condonar($id, '30', 'Motivo', $this->juez, $version);
        $this->rechaza(fn () => $this->cobros->condonar($id, '30', 'Motivo', $this->juez, $version), 'cambió');

        $this->assertEqualsWithDelta(30.0, (float) (new CobroModel())->find($id)->condonado, 0.001);
        $this->assertSame(1, $this->db->table('cobro_movimientos')->where('accion', 'condonar')->countAllResults());
    }

    public function testSoloAutorizanJuezYAdministrador(): void
    {
        $id = $this->multaConRecargo();
        $this->assertTrue(CondonacionRecargo::puedeAutorizar($this->juez));
        $this->assertTrue(CondonacionRecargo::puedeAutorizar($this->admin));
        $this->assertFalse(CondonacionRecargo::puedeAutorizar($this->operador));
        $this->assertFalse(CondonacionRecargo::puedeAutorizar(null));
        $this->assertFalse(CondonacionRecargo::puedeAutorizar(999999));

        $this->rechaza(fn () => $this->condonar($id, '10', 'Motivo', $this->operador), 'no está autorizada');
        $this->assertEqualsWithDelta(0.0, (float) (new CobroModel())->find($id)->condonado, 0.001);
    }

    public function testNoSeCondonanTramitesNiCobrosAprobadosOCancelados(): void
    {
        $tramite = (int) (new CobroModel())->insert([
            'folio' => 'COB-2099-000001', 'origen' => 'solicitud', 'nombre_pagador' => 'X', 'concepto' => 'T',
            'monto_base' => 100, 'descuento' => 0, 'recargo' => 0, 'condonado' => 0, 'total' => 100, 'estatus' => 'pendiente',
        ]);
        $this->rechaza(fn () => $this->cobros->condonar($tramite, '10', 'M', $this->juez, $this->cobros->vistaCondonacion($tramite)['version']), 'Solo se condonan');
        $this->assertNotNull($this->cobros->vistaCondonacion($tramite)['error']);

        $pagado = $this->multaConRecargo();
        $this->cobros->marcarCobrado($pagado, 'juez_calificador', null, $this->juez);
        $this->rechaza(fn () => $this->cobros->condonar($pagado, '10', 'M', $this->juez, $this->cobros->vistaCondonacion($pagado)['version']), 'ya fue cobrado');

        $cancelado = $this->multaConRecargo();
        $this->cobros->cancelar($cancelado, 'Error', $this->juez);
        $this->rechaza(fn () => $this->cobros->condonar($cancelado, '10', 'M', $this->juez, $this->cobros->vistaCondonacion($cancelado)['version']), 'cancelado');
    }

    public function testElPagoDespuesDeCondonarCobraElTotalReducido(): void
    {
        $id = $this->multaConRecargo();
        $this->condonar($id, '100');
        $this->cobros->marcarCobrado($id, 'juez_calificador', null, $this->juez);

        $cobro = (new CobroModel())->find($id);
        $this->assertSame('aprobado', $cobro->estatus);
        $this->assertEqualsWithDelta(1050.0, (float) $cobro->total, 0.001);
        $this->assertEqualsWithDelta(100.0, (float) $cobro->condonado, 0.001);
    }

    public function testElPagoBancarioExigeElTotalConLaCondonacion(): void
    {
        $id = $this->multaConRecargo();
        $this->condonar($id, '100');

        $this->rechaza(fn () => $this->cobros->registrarPagoBancario($id, 'OP-1', date('Y-m-d'), 1150.0, $this->juez), 'no coincide');
        $this->assertSame('pendiente', (new CobroModel())->find($id)->estatus);

        $this->cobros->registrarPagoBancario($id, 'OP-1', date('Y-m-d'), 1050.0, $this->juez);
        $this->assertSame('aprobado', (new CobroModel())->find($id)->estatus);
    }

    public function testElRecargoFuturoSeCobraNormalYLaCondonacionNoBajaDelPrincipal(): void
    {
        $id = $this->multaConRecargo();
        $this->condonar($id, '150');
        // Pasa un mes más: el recargo vigente es 200 y solo 150 están condonados.
        $this->db->table('boletas_infracciones')->update(['fecha_infraccion' => date('Y-m-d', strtotime('-4 months -2 days'))]);

        $vista = $this->cobros->vistaCondonacion($id);
        $this->assertEqualsWithDelta(200.0, $vista['recargo'], 0.001);
        $this->assertEqualsWithDelta(50.0, $vista['disponible'], 0.001);
        $this->assertEqualsWithDelta(1050.0, $vista['total'], 0.001);

        // Si el recargo bajara (fecha de pago anterior), lo aplicado se acota y el total no baja de la base.
        $this->db->table('cobros')->where('id', $id)->update(['condonado' => 900]);
        $this->assertGreaterThanOrEqual(1000.0, $this->cobros->vistaCondonacion($id)['total']);
        $this->assertTrue($this->cobros->vistaCondonacion($id)['excede_recargo']);
    }

    public function testSiFallaElMovimientoSeDeshaceLaCondonacion(): void
    {
        $servicio = new class () extends CobroService {
            protected function movimiento(object $cobro, string $accion, string $estatus, ?string $motivo = null, ?int $usuarioId = null, array $detalle = []): void
            {
                throw new \RuntimeException('falla simulada');
            }
        };
        $id = $this->multaConRecargo();
        $version = $servicio->vistaCondonacion($id)['version'];

        $this->rechaza(fn () => $servicio->condonar($id, '10', 'Motivo', $this->juez, $version), 'No se pudo registrar');
        $this->assertEqualsWithDelta(0.0, (float) (new CobroModel())->find($id)->condonado, 0.001);
    }

    public function testLaHojaDeUnCobroAprobadoConservaSusImportes(): void
    {
        $id = $this->multaConRecargo();
        $this->condonar($id, '100');
        $this->cobros->marcarCobrado($id, 'juez_calificador', null, $this->juez);
        $boletaId = (int) (new CobroModel())->find($id)->boleta_id;

        // La infracción "envejece" un mes más: el documento no cambia.
        $this->db->table('boletas_infracciones')->update(['fecha_infraccion' => date('Y-m-d', strtotime('-6 months'))]);
        $hoja = (new HojaPagoService())->paraBoleta($boletaId);

        $this->assertTrue($hoja['importes_registrados']);
        $this->assertEqualsWithDelta(1050.0, $hoja['total'], 0.001);
        $this->assertEqualsWithDelta(100.0, $hoja['condonado'], 0.001);
        $this->assertEqualsWithDelta(150.0, $hoja['recargo'], 0.001);
    }

    public function testElReportePorPeriodoYAutorizadorSumaCadaMovimiento(): void
    {
        $pendiente = $this->multaConRecargo();
        $pagado    = $this->multaConRecargo();
        $this->condonar($pendiente, '40');
        $this->condonar($pendiente, '10', 'Otra', $this->admin);
        $this->condonar($pagado, '25');
        $this->cobros->marcarCobrado($pagado, 'juez_calificador', null, $this->juez);

        $hoy     = new DateTimeImmutable('today');
        $servicio = new CorteCajaService();
        $r = $servicio->condonaciones($hoy, $hoy);
        $this->assertSame(3, $r['cantidad']);
        $this->assertEqualsWithDelta(75.0, $r['total'], 0.001);
        $this->assertEqualsWithDelta(50.0, $r['por_estado']['pendiente'], 0.001);
        $this->assertEqualsWithDelta(25.0, $r['por_estado']['aprobado'], 0.001);

        $soloAdmin = $servicio->condonaciones($hoy, $hoy, $this->admin);
        $this->assertSame(1, $soloAdmin['cantidad']);
        $this->assertEqualsWithDelta(10.0, $soloAdmin['total'], 0.001);

        $ayer = new DateTimeImmutable('-3 days');
        $this->assertSame(0, $servicio->condonaciones($ayer, $ayer)['cantidad']);
    }

    public function testElCorteSoloSumaLoCondonadoDeCobrosAprobados(): void
    {
        $pendiente = $this->multaConRecargo();
        $pagado    = $this->multaConRecargo();
        $this->condonar($pendiente, '40');
        $this->condonar($pagado, '25');
        $this->cobros->marcarCobrado($pagado, 'juez_calificador', null, $this->juez);

        $hoy   = new DateTimeImmutable('today');
        $corte = (new CorteCajaService())->corte($hoy, $hoy);
        $this->assertSame(1, $corte['cantidad']);
        $this->assertEqualsWithDelta(25.0, $corte['condonado'], 0.001);
    }
}
