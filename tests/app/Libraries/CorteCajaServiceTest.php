<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\CobroService;
use App\Libraries\CorteCajaService;
use App\Libraries\EstadoSolicitudService;
use App\Models\CobroModel;
use App\Models\SolicitudModel;
use App\Models\UserModel;
use DateTimeImmutable;
use Tests\Support\DatabaseTestCase;

class CorteCajaServiceTest extends DatabaseTestCase
{
    private CobroService $cobros;
    private int $ciudadanoId;
    private int $operadorId;
    private int $juezId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cobros = new CobroService();
        $usuarios = new UserModel();
        $this->ciudadanoId = (int) $usuarios->insert(['username' => 'laura', 'email' => 'laura@example.com', 'password_hash' => 'x', 'nombre_completo' => 'Laura Ramírez Gómez', 'activo' => 1]);
        $this->operadorId  = (int) $usuarios->insert(['username' => 'operador1', 'email' => 'op@example.com', 'password_hash' => 'x', 'nombre_completo' => 'Operador Ventanilla Uno', 'activo' => 1]);
        $this->juezId      = (int) $usuarios->insert(['username' => 'juez1', 'email' => 'juez@example.com', 'password_hash' => 'x', 'nombre_completo' => 'Juez Calificador Demo', 'activo' => 1]);
    }

    /** Crea un trámite cobrado en la fecha indicada y devuelve el id del cobro. */
    private function cobrado(string $tramite, float $monto, string $fechaCobro, string $lugar, int $cobrador): int
    {
        $id = (int) (new SolicitudModel())->insert([
            'folio' => 'F' . strtoupper(substr(md5(uniqid('', true)), 0, 15)), 'tramite' => $tramite,
            'ciudadano_id' => $this->ciudadanoId, 'estatus' => 'Recibido', 'monto' => $monto, 'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        $estados = new EstadoSolicitudService();
        if ($tramite === 'UR-TT-T-05') {
            $estados->cambiarEstatus($id, 'En validación', $this->operadorId);
            $estados->autorizarPagoUr05($id, ['afluencia_baja' => '1', 'sin_transporte_publico' => '1', 'horario_no_entorpece' => '1'], $this->operadorId);
        } else {
            $estados->cambiarEstatus($id, 'Pago pendiente', $this->operadorId);
        }
        $cobroId = (int) (new CobroModel())->where('solicitud_id', $id)->first()->id;
        $this->cobros->marcarCobrado($cobroId, $lugar, $lugar === 'banbajio' ? 'BB-1' : null, $cobrador);
        (new CobroModel())->update($cobroId, ['fecha_cobro' => $fechaCobro]);

        return $cobroId;
    }

    public function testRangosDeDiaSemanaYMes(): void
    {
        $dia = CorteCajaService::rango('dia', '2026-10-22');
        $this->assertSame('2026-10-22', $dia['desde']->format('Y-m-d'));
        $this->assertSame('2026-10-22', $dia['hasta']->format('Y-m-d'));
        $this->assertSame('22 de octubre de 2026', $dia['etiqueta']);

        // 22 de octubre de 2026 es jueves: la semana va del lunes 19 al domingo 25.
        $semana = CorteCajaService::rango('semana', '2026-10-22');
        $this->assertSame('2026-10-19', $semana['desde']->format('Y-m-d'));
        $this->assertSame('2026-10-25', $semana['hasta']->format('Y-m-d'));

        $mes = CorteCajaService::rango('mes', '2026-02-10');
        $this->assertSame('2026-02-01', $mes['desde']->format('Y-m-d'));
        $this->assertSame('2026-02-28', $mes['hasta']->format('Y-m-d'));
        $this->assertSame('Febrero de 2026', $mes['etiqueta']);

        // Un rango al revés se acomoda; una fecha inválida no truena.
        $rango = CorteCajaService::rango('rango', '', '2026-10-30', '2026-10-01');
        $this->assertSame('2026-10-01', $rango['desde']->format('Y-m-d'));
        $this->assertSame('2026-10-30', $rango['hasta']->format('Y-m-d'));
        $this->assertSame(date('Y-m-d'), CorteCajaService::rango('dia', '2026-13-45')['desde']->format('Y-m-d'));
    }

    public function testElTotalEsLaSumaDeLosRecibosDelPeriodo(): void
    {
        $this->cobrado('UR-TT-T-05', 287.00, '2026-10-22 10:00:00', 'ventanilla', $this->operadorId);
        $this->cobrado('UR-TT-T-07', 400.00, '2026-10-22 18:30:00', 'banbajio', $this->operadorId);
        $this->cobrado('UR-TT-T-07', 400.00, '2026-10-23 09:00:00', 'juez_calificador', $this->juezId);
        $this->cobrado('UR-TT-T-05', 287.00, '2026-10-21 23:59:59', 'ventanilla', $this->operadorId);

        $dia = new DateTimeImmutable('2026-10-22');
        $corte = (new CorteCajaService())->corte($dia, $dia);

        $this->assertSame(2, $corte['cantidad']);
        $this->assertEqualsWithDelta(687.00, $corte['total'], 0.001);
        $this->assertEqualsWithDelta(array_sum(array_map(static fn ($c) => (float) $c->total, $corte['cobros'])), $corte['total'], 0.001);
        $this->assertSame(['cantidad' => 1, 'total' => 400.0], $corte['por_concepto']['Permiso de Carga y Descarga']);
        $this->assertSame(['cantidad' => 1, 'total' => 287.0], $corte['por_lugar']['Ventanilla']);
        $this->assertSame(['cantidad' => 2, 'total' => 687.0], $corte['por_cobrador']['Operador Ventanilla Uno']);

        $semana = CorteCajaService::rango('semana', '2026-10-22');
        $this->assertEqualsWithDelta(1374.00, (new CorteCajaService())->corte($semana['desde'], $semana['hasta'])['total'], 0.001);
        $this->assertEqualsWithDelta(400.00, (new CorteCajaService())->corte($semana['desde'], $semana['hasta'], $this->juezId)['total'], 0.001);
    }

    public function testLosCanceladosSeCuentanAparte(): void
    {
        $id = (int) (new SolicitudModel())->insert([
            'folio' => 'FCANCELADO000001', 'tramite' => 'UR-TT-T-07', 'ciudadano_id' => $this->ciudadanoId,
            'estatus' => 'Recibido', 'monto' => 400.00, 'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        (new EstadoSolicitudService())->cambiarEstatus($id, 'Pago pendiente', $this->operadorId);
        $cobroId = (int) (new CobroModel())->where('solicitud_id', $id)->first()->id;
        $this->cobros->cancelar($cobroId, 'El ciudadano desistió.', $this->operadorId);

        $hoy = new DateTimeImmutable('today');
        $corte = (new CorteCajaService())->corte($hoy, $hoy);

        $this->assertSame(0, $corte['cantidad']);
        $this->assertEqualsWithDelta(0.0, $corte['total'], 0.001);
        $this->assertSame(['cantidad' => 1, 'total' => 400.0], $corte['cancelados']);
    }
}
