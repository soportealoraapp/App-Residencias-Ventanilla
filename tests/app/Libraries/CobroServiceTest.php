<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\CobroException;
use App\Libraries\CobroService;
use App\Libraries\EstadoSolicitudService;
use App\Models\CobroModel;
use App\Models\SolicitudModel;
use App\Models\UserModel;
use Tests\Support\DatabaseTestCase;

class CobroServiceTest extends DatabaseTestCase
{
    private CobroService $cobros;
    private EstadoSolicitudService $estados;
    private int $ciudadanoId;
    private int $operadorId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cobros  = new CobroService();
        $this->estados = new EstadoSolicitudService();

        $usuarios = new UserModel();
        $this->ciudadanoId = (int) $usuarios->insert([
            'username' => 'laura', 'email' => 'laura@example.com', 'password_hash' => 'x',
            'nombre_completo' => 'Laura Ramírez Gómez', 'activo' => 1,
        ]);
        $this->operadorId = (int) $usuarios->insert([
            'username' => 'operador1', 'email' => 'operador@example.com', 'password_hash' => 'x',
            'nombre_completo' => 'Operador Ventanilla', 'activo' => 1,
        ]);

        // PostgreSQL aplica la llave foránea real de boletas hacia agentes.
        $this->db->table('agentes_transito')->insert([
            'placa' => 'AGT-204',
            'nombre_completo' => 'Agente de prueba',
            'password_hash' => 'x',
            'rol' => 'operativo',
            'activo' => 1,
        ]);
    }

    private function solicitud(string $tramite = 'UR-TT-T-05', string $estatus = 'Recibido', float $monto = 287.0): int
    {
        return (int) (new SolicitudModel())->insert([
            'folio'           => 'TEST' . strtoupper(substr(md5(uniqid('', true)), 0, 12)),
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->ciudadanoId,
            'estatus'         => $estatus,
            'monto'           => $monto,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
    }

    private function boleta(string $fechaInfraccion, string $infractor = 'Martín Aguilar Rosas'): int
    {
        $this->db->table('boletas_infracciones')->insert([
            'folio'            => 'BOLETA-TEST-' . uniqid(),
            'agente_placa'     => 'AGT-204',
            'fecha_infraccion' => $fechaInfraccion,
            'hora_infraccion'  => '10:30:00',
            'lugar'            => 'Av. Hidalgo esq. Morelos, Uriangato',
            'infractor_nombre' => $infractor,
            'vehiculo_placas'  => 'GTA-123-B',
            'estado'           => 'recibida',
        ]);
        return (int) $this->db->insertID();
    }

    private function cobroDeSolicitud(int $solicitudId): ?object
    {
        return (new CobroModel())->where('solicitud_id', $solicitudId)->orderBy('id', 'DESC')->first();
    }

    public function testLlegarAPagoPendienteCreaUnCobroPendiente(): void
    {
        $id = $this->solicitud();
        $this->assertTrue($this->estados->cambiarEstatus($id, 'En validación', $this->operadorId));
        $this->assertTrue($this->estados->autorizarPagoUr05($id, ['afluencia_baja' => '1', 'sin_transporte_publico' => '1', 'horario_no_entorpece' => '1'], $this->operadorId, 'Pago autorizado.'));

        $cobro = $this->cobroDeSolicitud($id);
        $this->assertNotNull($cobro);
        $this->assertSame('pendiente', $cobro->estatus);
        $this->assertSame('solicitud', $cobro->origen);
        $this->assertSame('Laura Ramírez Gómez', $cobro->nombre_pagador);
        $this->assertStringStartsWith('Permiso para Cierre de Calle · folio ', $cobro->concepto);
        $this->assertEqualsWithDelta(287.0, (float) $cobro->total, 0.001);
        $this->assertMatchesRegularExpression('/^COB-\d{4}-\d{6}$/', $cobro->folio);

        // Idempotente: no duplica.
        $this->assertSame((int) $cobro->id, $this->cobros->crearDesdeSolicitud($id));
        $this->assertSame(1, (new CobroModel())->where('solicitud_id', $id)->countAllResults());
    }

    public function testCobrarApruebaElCobroYLaSolicitudPasaAPagado(): void
    {
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 400.0);
        $this->estados->cambiarEstatus($id, 'Pago pendiente', $this->operadorId);
        $cobro = $this->cobroDeSolicitud($id);

        $this->cobros->marcarCobrado((int) $cobro->id, 'juez_calificador', null, $this->operadorId);

        $cobro = (new CobroModel())->find((int) $cobro->id);
        $this->assertSame('aprobado', $cobro->estatus);
        $this->assertSame('juez_calificador', $cobro->lugar_pago);
        $this->assertSame('REC-' . date('Y') . '-000001', $cobro->folio_recibo);
        $this->assertSame($this->operadorId, (int) $cobro->cobrado_por);
        $this->assertNotNull($cobro->fecha_cobro);
        $this->assertSame('Pagado', (new SolicitudModel())->find($id)->estatus);

        $acciones = array_map(static fn ($m) => $m->accion, $this->cobros->historial((int) $cobro->id));
        $this->assertSame(['crear', 'cobrar'], $acciones);

        // El siguiente recibo es consecutivo.
        $otra = $this->solicitud('UR-TT-T-07', 'Recibido', 400.0);
        $this->estados->cambiarEstatus($otra, 'Pago pendiente', $this->operadorId);
        $this->cobros->marcarCobrado((int) $this->cobroDeSolicitud($otra)->id, 'banbajio', 'BB-998877', $this->operadorId);
        $this->assertSame('REC-' . date('Y') . '-000002', $this->cobroDeSolicitud($otra)->folio_recibo);
        $this->assertSame('BB-998877', $this->cobroDeSolicitud($otra)->referencia_pago);
    }

    public function testEnUr06ElPagoLlevaLaSolicitudAConcluido(): void
    {
        $id = $this->solicitud('UR-TT-T-06', 'Dictaminado aprobado', 500.0);
        $this->estados->cambiarEstatus($id, 'Pago pendiente', $this->operadorId);

        $this->cobros->marcarCobrado((int) $this->cobroDeSolicitud($id)->id, 'ventanilla', null, $this->operadorId);

        $this->assertSame('Concluido', (new SolicitudModel())->find($id)->estatus);
    }

    public function testConcesionYDespintadoTienenPasoDePagoComoEnSusFichas(): void
    {
        // UR-TT-T-01: el seleccionado cubre los derechos y recibe el título-concesión.
        $concesion = $this->solicitud('UR-TT-T-01', 'Seleccionado', 9055.20);
        $this->assertTrue($this->estados->cambiarEstatus($concesion, 'Pago pendiente', $this->operadorId));
        $this->cobros->marcarCobrado((int) $this->cobroDeSolicitud($concesion)->id, 'tesoreria', null, $this->operadorId);
        $this->assertSame('Pagado', (new SolicitudModel())->find($concesion)->estatus);
        $this->assertTrue($this->estados->cambiarEstatus($concesion, 'Título emitido', $this->operadorId));

        // UR-TT-T-02: con el vehículo verificado se paga al juez y se entrega la constancia.
        $despintado = $this->solicitud('UR-TT-T-02', 'Verificado', 64.90);
        $this->assertTrue($this->estados->cambiarEstatus($despintado, 'Pago pendiente', $this->operadorId));
        $this->assertEqualsWithDelta(64.90, (float) $this->cobroDeSolicitud($despintado)->total, 0.001);
        $this->cobros->marcarCobrado((int) $this->cobroDeSolicitud($despintado)->id, 'juez_calificador', null, $this->operadorId);
        $this->assertTrue($this->estados->cambiarEstatus($despintado, 'Constancia emitida', $this->operadorId));

        // La orden de plaqueo (UR-TT-T-03) no tiene costo ni paso de pago.
        $this->assertNotContains('Pago pendiente', array_merge(...array_values(EstadoSolicitudService::TRANSICIONES_VALIDAS['UR-TT-T-03'])));
    }

    public function testRechazarLaSolicitudCancelaElCobroPendiente(): void
    {
        $id = $this->solicitud();
        $this->estados->cambiarEstatus($id, 'En validación', $this->operadorId);
        $this->estados->autorizarPagoUr05($id, ['afluencia_baja' => '1', 'sin_transporte_publico' => '1', 'horario_no_entorpece' => '1'], $this->operadorId);
        $this->assertTrue($this->estados->cambiarEstatus($id, 'Rechazado', $this->operadorId, 'La calle es ruta de transporte.'));

        $cobro = $this->cobroDeSolicitud($id);
        $this->assertSame('cancelado', $cobro->estatus);
        $this->assertStringContainsString('La calle es ruta de transporte.', $cobro->motivo);
    }

    public function testPagoPorOtraViaApruebaElCobro(): void
    {
        // Así llega el pago en línea simulado de T-05 y T-07.
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 400.0);
        $this->estados->cambiarEstatus($id, 'Pago pendiente', null);
        $this->estados->cambiarEstatus($id, 'Pagado', null, 'Pago procesado vía mock BanBajío ref: X');

        $cobro = $this->cobroDeSolicitud($id);
        $this->assertSame('aprobado', $cobro->estatus);
        $this->assertNotNull($cobro->folio_recibo);
    }

    public function testMandarACompletarYCancelarPidenMotivo(): void
    {
        $id = $this->solicitud('UR-TT-T-07');
        $this->estados->cambiarEstatus($id, 'Pago pendiente', $this->operadorId);
        $cobroId = (int) $this->cobroDeSolicitud($id)->id;

        $this->cobros->mandarACompletar($cobroId, 'Falta la identificación oficial.', $this->operadorId);
        $cobro = (new CobroModel())->find($cobroId);
        $this->assertSame('pendiente', $cobro->estatus);
        $this->assertSame(1, (int) $cobro->pendiente_documentos);
        $this->assertSame('Falta la identificación oficial.', $cobro->motivo);

        try {
            $this->cobros->cancelar($cobroId, '   ', $this->operadorId);
            $this->fail('Cancelar sin motivo debió fallar.');
        } catch (CobroException $e) {
            $this->assertSame('Escriba el motivo.', $e->getMessage());
        }

        $this->cobros->cancelar($cobroId, 'El ciudadano desistió.', $this->operadorId);
        $this->assertSame('cancelado', (new CobroModel())->find($cobroId)->estatus);

        $this->expectException(CobroException::class);
        $this->cobros->marcarCobrado($cobroId, 'ventanilla');
    }

    public function testCreaLosCobrosQueFaltabanSinDuplicar(): void
    {
        // Solicitudes cargadas directo en "Pago pendiente", sin pasar por el servicio de estatus.
        $sinCobro = $this->solicitud('UR-TT-T-07', 'Pago pendiente', 400.0);
        $otra     = $this->solicitud('UR-TT-T-05', 'Pago pendiente', 287.0);
        $this->solicitud('UR-TT-T-07', 'Recibido', 400.0);
        $conCobro = $this->solicitud('UR-TT-T-07', 'Recibido', 400.0);
        $this->estados->cambiarEstatus($conCobro, 'Pago pendiente', $this->operadorId);

        $this->assertSame(2, $this->cobros->crearPendientesFaltantes());
        $this->assertSame(0, $this->cobros->crearPendientesFaltantes());

        $this->assertSame('pendiente', $this->cobroDeSolicitud($sinCobro)->estatus);
        $this->assertNotNull($this->cobroDeSolicitud($otra));
        $this->assertSame(1, (new CobroModel())->where('solicitud_id', $conCobro)->countAllResults());
        $this->assertSame(3, (new CobroModel())->countAllResults());
    }

    public function testMultaPagadaAProntoPagoLlevaDescuento(): void
    {
        $boletaId = $this->boleta(date('Y-m-d'));
        $cobroId  = $this->cobros->crearDesdeBoleta($boletaId, 1000.0, $this->operadorId);

        $cobro = (new CobroModel())->find($cobroId);
        $this->assertSame('boleta', $cobro->origen);
        $this->assertSame('Martín Aguilar Rosas', $cobro->nombre_pagador);
        $this->assertEqualsWithDelta(400.0, (float) $cobro->descuento, 0.001);
        $this->assertEqualsWithDelta(600.0, (float) $cobro->total, 0.001);

        $this->cobros->marcarCobrado($cobroId, 'juez_calificador', null, $this->operadorId);
        $this->assertSame('pagada', $this->db->table('boletas_infracciones')->where('id', $boletaId)->get()->getRow()->estado);
    }

    public function testPagoEnBanBajioValidaElComprobante(): void
    {
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 400.0);
        $this->estados->cambiarEstatus($id, 'Pago pendiente', $this->operadorId);
        $cobro = $this->cobroDeSolicitud($id);
        $this->assertSame(service('referenciaBancaria')->generar('solicitud', (int) $cobro->id), $cobro->referencia_bancaria);

        // Se encuentra aunque se escriba con espacios.
        $ref = $cobro->referencia_bancaria;
        $this->assertSame((int) $cobro->id, (int) $this->cobros->buscarPorReferencia(substr($ref, 0, 4) . ' ' . substr($ref, 4))->id);
        $this->assertNull($this->cobros->buscarPorReferencia('100000000191'));

        foreach ([
            ['', date('Y-m-d'), 400.0, 'comprobante'],
            ['OP-1', date('Y-m-d', strtotime('+1 day')), 400.0, 'posterior a hoy'],
            ['OP-1', date('Y-m-d'), 350.0, 'no coincide'],
        ] as [$comprobante, $fecha, $importe, $mensaje]) {
            try {
                $this->cobros->registrarPagoBancario((int) $cobro->id, $comprobante, $fecha, $importe, $this->operadorId);
                $this->fail('Debió rechazar: ' . $mensaje);
            } catch (CobroException $e) {
                $this->assertStringContainsString($mensaje, $e->getMessage());
            }
        }
        $this->assertSame('pendiente', $this->cobroDeSolicitud($id)->estatus);

        $this->cobros->registrarPagoBancario((int) $cobro->id, 'OP-778899', date('Y-m-d'), 400.0, $this->operadorId);
        $cobro = $this->cobroDeSolicitud($id);
        $this->assertSame('aprobado', $cobro->estatus);
        $this->assertSame('banbajio', $cobro->lugar_pago);
        $this->assertSame('OP-778899', $cobro->referencia_pago);
        $this->assertSame(date('Y-m-d'), substr((string) $cobro->fecha_pago_banco, 0, 10));
        $this->assertEqualsWithDelta(400.0, (float) $cobro->importe_pagado, 0.001);
        $this->assertSame('Pagado', (new SolicitudModel())->find($id)->estatus);
    }

    public function testMultaPagadaEnElBancoConProntoPagoConservaElDescuento(): void
    {
        // La multa se pagó en el banco a tiempo, pero el comprobante se registra
        // tres meses después: cuenta la fecha del comprobante, no la del registro.
        $infraccion = date('Y-m-d', strtotime('-3 months'));
        $boletaId = $this->boleta($infraccion);
        $cobroId  = $this->cobros->crearDesdeBoleta($boletaId, 1000.0);
        $this->db->table('cobros')->where('id', $cobroId)->update(['created_at' => $infraccion . ' 12:00:00']);

        $this->cobros->registrarPagoBancario($cobroId, 'OP-1', $infraccion, 600.0);

        $cobro = (new CobroModel())->find($cobroId);
        $this->assertSame('aprobado', $cobro->estatus);
        $this->assertEqualsWithDelta(400.0, (float) $cobro->descuento, 0.001);
        $this->assertEqualsWithDelta(0.0, (float) $cobro->recargo, 0.001);
        $this->assertEqualsWithDelta(600.0, (float) $cobro->total, 0.001);
    }

    public function testMultaAtrasadaSeRecalculaConRecargoAlCobrar(): void
    {
        $boletaId = $this->boleta(date('Y-m-d', strtotime('-3 months -2 days')), 'Conductor Ausente');
        $cobroId  = $this->cobros->crearDesdeBoleta($boletaId, 1000.0);

        $cobro = (new CobroModel())->find($cobroId);
        $this->assertSame('Propietario del vehículo GTA-123-B', $cobro->nombre_pagador);
        // 3 meses de mora a 5 % mensual.
        $this->assertEqualsWithDelta(150.0, (float) $cobro->recargo, 0.001);
        $this->assertEqualsWithDelta(1150.0, (float) $cobro->total, 0.001);

        // Lo condonado (COB-11) se respeta al recalcular.
        (new CobroModel())->update($cobroId, ['condonado' => 150.0]);
        $this->cobros->marcarCobrado($cobroId, 'banbajio', 'BB-1');
        $this->assertEqualsWithDelta(1000.0, (float) (new CobroModel())->find($cobroId)->total, 0.001);
    }
}
