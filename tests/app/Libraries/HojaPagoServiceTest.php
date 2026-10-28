<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\CobroService;
use App\Libraries\HojaPagoService;
use App\Models\CobroModel;
use App\Models\SolicitudModel;
use Tests\Support\DatabaseTestCase;

/**
 * COB-05: hoja de ayuda para el pago.
 *
 * Comprueba que el desglose impreso coincide con lo que el ciudadano terminará
 * pagando: el monto del trámite cuando no hay cobro, el descuento por pronto
 * pago, el recargo por mora y lo condonado.
 */
class HojaPagoServiceTest extends DatabaseTestCase
{
    private HojaPagoService $hoja;
    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoja = new HojaPagoService();

        $this->db->table('users')->insert([
            'username' => 'laura', 'email' => 'laura@example.com', 'password_hash' => 'x',
            'nombre_completo' => 'Laura Ramírez Gómez', 'activo' => 1,
        ]);
        $this->ciudadanoId = (int) $this->db->insertID();
    }

    private function solicitud(float $monto = 287.0, string $tramite = 'UR-TT-T-05'): int
    {
        return (int) (new SolicitudModel())->insert([
            'folio'           => 'TEST' . strtoupper(substr(md5(uniqid('', true)), 0, 12)),
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->ciudadanoId,
            'estatus'         => 'Recibido',
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

    public function testUnaSolicitudSinCobroImprimeElMontoDelTramite(): void
    {
        $id = $this->solicitud(287.0);

        $hoja = $this->hoja->paraSolicitud($id);
        $this->assertNotNull($hoja);
        $this->assertSame('solicitud', $hoja['origen']);
        $this->assertNull($hoja['folio_cobro']);
        $this->assertSame('sin cobro', $hoja['estatus']);
        $this->assertSame('Laura Ramírez Gómez', $hoja['nombre']);
        $this->assertStringContainsString('Permiso para Cierre de Calle', (string) $hoja['concepto']);
        $this->assertEqualsWithDelta(287.0, $hoja['monto_base'], 0.001);
        $this->assertEqualsWithDelta(287.0, $hoja['total'], 0.001);
        $this->assertEqualsWithDelta(0.0, $hoja['descuento'], 0.001);
        $this->assertEqualsWithDelta(0.0, $hoja['recargo'], 0.001);
    }

    public function testUnaSolicitudConCobroMuestraSuFolioYTotal(): void
    {
        $id = $this->solicitud(400.0, 'UR-TT-T-07');
        $cobroId = (new CobroService())->crearDesdeSolicitud($id, $this->ciudadanoId);
        (new CobroModel())->update($cobroId, ['condonado' => 50.0]);

        $hoja = $this->hoja->paraSolicitud($id);
        $this->assertNotNull($hoja);
        $this->assertSame((new CobroModel())->find($cobroId)->folio, $hoja['folio_cobro']);
        $this->assertSame('pendiente', $hoja['estatus']);
        $this->assertEqualsWithDelta(50.0, $hoja['condonado'], 0.001);
        $this->assertEqualsWithDelta(400.0, $hoja['total'], 0.001);
    }

    public function testUnaMultaRecienteConservaElDescuentoPorProntoPago(): void
    {
        $boletaId = $this->boleta(date('Y-m-d'));
        (new CobroService())->crearDesdeBoleta($boletaId, 1000.0);

        $hoja = $this->hoja->paraBoleta($boletaId);
        $this->assertNotNull($hoja);
        $this->assertSame('boleta', $hoja['origen']);
        $this->assertTrue($hoja['aplica_descuento']);
        $this->assertEqualsWithDelta(40.0, $hoja['porcentaje_descuento'], 0.001);
        $this->assertEqualsWithDelta(400.0, $hoja['descuento'], 0.001);
        $this->assertEqualsWithDelta(0.0, $hoja['recargo'], 0.001);
        $this->assertEqualsWithDelta(600.0, $hoja['total'], 0.001);
        $this->assertNotEmpty($hoja['folio_cobro']);
        $this->assertSame('Martín Aguilar Rosas', $hoja['nombre']);
    }

    public function testLaFechaLimiteCuentaSoloDiasHabiles(): void
    {
        // Del lunes 19 de octubre de 2026, diez días hábiles terminan el 2 de
        // noviembre: se saltan los fines de semana intermedios.
        $this->assertSame('2026-11-02', $this->hoja->fechaLimiteDescuento('2026-10-19'));
        $this->assertNull($this->hoja->fechaLimiteDescuento(''));
    }

    public function testUnaMultaAtrasadaSumaRecargoMensual(): void
    {
        $boletaId = $this->boleta(date('Y-m-d', strtotime('-3 months -2 days')), 'Conductor Ausente');
        (new CobroService())->crearDesdeBoleta($boletaId, 1000.0);

        $hoja = $this->hoja->paraBoleta($boletaId);
        $this->assertNotNull($hoja);
        $this->assertFalse($hoja['aplica_descuento']);
        $this->assertSame(3, $hoja['meses_mora']);
        $this->assertEqualsWithDelta(5.0, $hoja['porcentaje_recargo'], 0.001);
        $this->assertEqualsWithDelta(150.0, $hoja['recargo'], 0.001);
        $this->assertEqualsWithDelta(1150.0, $hoja['total'], 0.001);
        // Sin nombre del infractor, la hoja identifica al propietario del vehículo.
        $this->assertStringContainsString('GTA-123-B', (string) $hoja['nombre']);
    }

    public function testLoCondonadoRestaDelTotal(): void
    {
        $boletaId = $this->boleta(date('Y-m-d', strtotime('-3 months -2 days')));
        $cobroId  = (new CobroService())->crearDesdeBoleta($boletaId, 1000.0);
        (new CobroModel())->update($cobroId, ['condonado' => 150.0]);

        $hoja = $this->hoja->paraBoleta($boletaId);
        $this->assertNotNull($hoja);
        $this->assertEqualsWithDelta(150.0, $hoja['condonado'], 0.001);
        $this->assertEqualsWithDelta(1000.0, $hoja['total'], 0.001);
    }

    public function testUnaBoletaSinCalificarSeImprimeEnCeroYLoExplica(): void
    {
        $boletaId = $this->boleta(date('Y-m-d'));

        $hoja = $this->hoja->paraBoleta($boletaId);
        $this->assertNotNull($hoja);
        $this->assertEqualsWithDelta(0.0, $hoja['total'], 0.001);
        $this->assertSame('pendiente', $hoja['estatus']);
        $this->assertStringContainsString('calificar', implode(' ', (array) $hoja['notas']));
    }

    public function testLosLugaresDePagoSonLosDelServicioDeCobros(): void
    {
        $lugares = $this->hoja->lugaresDePago();

        $this->assertSame(['juez_calificador', 'tesoreria', 'banbajio'], array_column($lugares, 'clave'));
        foreach ($lugares as $lugar) {
            $this->assertSame(CobroService::LUGARES_PAGO[$lugar['clave']], $lugar['titulo']);
            $this->assertNotSame('', $lugar['detalle']);
        }
    }

    public function testUnRegistroInexistenteNoArmaHoja(): void
    {
        $this->assertNull($this->hoja->paraSolicitud(999999));
        $this->assertNull($this->hoja->paraBoleta(999999));
    }
}
