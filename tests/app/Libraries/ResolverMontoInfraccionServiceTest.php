<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Database\Seeds\CatalogoInfraccionesSeeder;
use App\Libraries\ResolverMontoInfraccionService;
use App\Models\CatalogoInfraccionModel;
use App\Models\ParametroSistemaModel;
use DateTimeImmutable;
use RuntimeException;
use Tests\Support\DatabaseTestCase;

class ResolverMontoInfraccionServiceTest extends DatabaseTestCase
{
    protected ResolverMontoInfraccionService $service;
    protected CatalogoInfraccionModel $infraccionModel;
    protected ParametroSistemaModel $parametroModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoInfraccionesSeeder::class);
        $this->service         = new ResolverMontoInfraccionService();
        $this->infraccionModel = new CatalogoInfraccionModel();
        $this->parametroModel  = new ParametroSistemaModel();
    }

    public function testCalcularMonto_InfraccionArt39FracI(): void
    {
        // Art. 39 Frac. I: monto_min_uma = 15.0, UMA = 117.31 => 15 * 117.31 = 1759.65
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. I')->first();
        $this->assertNotNull($infraccion);

        $monto = $this->service->calcularMonto($infraccion->id);

        $this->assertNotNull($monto);
        $this->assertEqualsWithDelta(1759.65, $monto, 0.01);
    }

    public function testCalcularMonto_InfraccionArt39FracIV(): void
    {
        // Art. 39 Frac. IV: monto_min_uma = 3.0, UMA = 117.31 => 3 * 117.31 = 351.93
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. IV')->first();
        $this->assertNotNull($infraccion);

        $monto = $this->service->calcularMonto($infraccion->id);

        $this->assertNotNull($monto);
        $this->assertEqualsWithDelta(351.93, $monto, 0.01);
    }

    public function testCalcularMonto_InfraccionArt89FracI(): void
    {
        // Art. 89 Frac. I (conducir ebrio): monto_min_uma = 10.0, UMA = 117.31 => 10 * 117.31 = 1173.10
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 89 Frac. I')->first();
        $this->assertNotNull($infraccion);

        $monto = $this->service->calcularMonto($infraccion->id);

        $this->assertNotNull($monto);
        $this->assertEqualsWithDelta(1173.10, $monto, 0.01);
    }

    public function testCalcularMonto_InfraccionInexistente_RetornaNull(): void
    {
        $monto = $this->service->calcularMonto(99999);
        $this->assertNull($monto);
    }

    public function testCalcularMonto_SinParametroUma_LanzaExcepcion(): void
    {
        $this->parametroModel->where('clave', 'valor_uma_vigente')->delete();

        $infraccion = $this->infraccionModel->first();
        $this->assertNotNull($infraccion);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("El parámetro de sistema 'valor_uma_vigente' no está configurado o no es numérico.");

        $this->service->calcularMonto($infraccion->id);
    }

    public function testCalcularDesglose_RetornaEstructuraCompleta(): void
    {
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. III')->first();
        $this->assertNotNull($infraccion);

        $desglose = $this->service->calcularDesglose($infraccion->id);

        $this->assertIsArray($desglose);
        $this->assertEquals('Art. 39 Frac. III', $desglose['fundamento_legal']);
        $this->assertEquals(30.0, $desglose['monto_min_uma']);
        $this->assertEquals(50.0, $desglose['monto_max_uma']);
        $this->assertEquals(117.31, $desglose['valor_uma_vigente']);
        $this->assertEqualsWithDelta(3519.30, $desglose['monto_calculado'], 0.01);
        $this->assertEqualsWithDelta(5865.50, $desglose['monto_max_pesos'], 0.01);
    }

    // =========================================================================
    // PRUEBAS DE DESCUENTO POR PRONTO PAGO Y RECARGO POR MORA
    // =========================================================================

    /**
     * Caso 1: Pago dentro de los 10 días hábiles (aplica 40% de descuento)
     * Infracción: Lunes 2026-08-03
     * Pago: Viernes 2026-08-07 (4 días hábiles transcurridos)
     * Monto base: 15 UMA * 117.31 = $1759.65
     * Descuento 40%: $703.86
     * Monto final: $1055.79
     */
    public function testCalcularMonto_Caso1_PagoDentroDeLosDiezDiasHabiles_AplicaDescuento40(): void
    {
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. I')->first();
        $this->assertNotNull($infraccion);

        $fechaInfraccion = new DateTimeImmutable('2026-08-03 10:00:00'); // Lunes
        $fechaPago        = new DateTimeImmutable('2026-08-07 14:00:00'); // Viernes (4 días hábiles)

        $detalle = $this->service->calcularMontoConDescuentoRecargoDetalle($infraccion->id, $fechaInfraccion, $fechaPago);

        $this->assertNotNull($detalle);
        $this->assertEquals('descuento_pronto_pago', $detalle['tipo_ajuste']);
        $this->assertTrue($detalle['aplica_descuento']);
        $this->assertFalse($detalle['aplica_recargo']);
        $this->assertEquals(4, $detalle['dias_habiles_transcurridos']);
        $this->assertEquals(40.0, $detalle['porcentaje_descuento']);
        $this->assertEqualsWithDelta(703.86, $detalle['monto_descuento'], 0.01);
        $this->assertEqualsWithDelta(1055.79, $detalle['monto_final'], 0.01);

        $montoDirecto = $this->service->calcularMontoConDescuentoRecargo($infraccion->id, $fechaInfraccion, $fechaPago);
        $this->assertEqualsWithDelta(1055.79, $montoDirecto, 0.01);
    }

    /**
     * Caso 2: Pago justo en el límite (exactamente el día hábil 10)
     * Infracción: Lunes 2026-08-03
     * Días hábiles:
     *  - Sem 1 (4 días): Mar 4, Mié 5, Jue 6, Vie 7
     *  - Sem 2 (5 días): Lun 10, Mar 11, Mié 12, Jue 13, Vie 14 (total 9)
     *  - Sem 3 (1 día):  Lun 17 (Día hábil 10 exacto)
     * Monto final con 40% desc: $1055.79
     */
    public function testCalcularMonto_Caso2_PagoJustoEnLimiteDiezDiasHabiles_AplicaDescuento40(): void
    {
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. I')->first();
        $this->assertNotNull($infraccion);

        $fechaInfraccion = new DateTimeImmutable('2026-08-03 09:00:00'); // Lunes
        $fechaPago        = new DateTimeImmutable('2026-08-17 18:00:00'); // Lunes 17 (Día hábil 10)

        $detalle = $this->service->calcularMontoConDescuentoRecargoDetalle($infraccion->id, $fechaInfraccion, $fechaPago);

        $this->assertNotNull($detalle);
        $this->assertEquals('descuento_pronto_pago', $detalle['tipo_ajuste']);
        $this->assertTrue($detalle['aplica_descuento']);
        $this->assertEquals(10, $detalle['dias_habiles_transcurridos']);
        $this->assertEqualsWithDelta(1055.79, $detalle['monto_final'], 0.01);
    }

    /**
     * Caso 3: Pago después de 10 días hábiles pero antes de 1 mes (monto regular 100%)
     * Infracción: Lunes 2026-08-03
     * Pago: Martes 2026-08-18 (Día hábil 11)
     * Monto regular: $1759.65 (sin descuento, sin recargo)
     */
    public function testCalcularMonto_Caso3_PagoDespuesDeDiezDiasPeroAntesDeUnMes_CobraMontoRegular(): void
    {
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. I')->first();
        $this->assertNotNull($infraccion);

        $fechaInfraccion = new DateTimeImmutable('2026-08-03 09:00:00');
        $fechaPago        = new DateTimeImmutable('2026-08-18 10:00:00'); // Día hábil 11 (dentro del mes)

        $detalle = $this->service->calcularMontoConDescuentoRecargoDetalle($infraccion->id, $fechaInfraccion, $fechaPago);

        $this->assertNotNull($detalle);
        $this->assertEquals('monto_regular', $detalle['tipo_ajuste']);
        $this->assertFalse($detalle['aplica_descuento']);
        $this->assertFalse($detalle['aplica_recargo']);
        $this->assertEquals(11, $detalle['dias_habiles_transcurridos']);
        $this->assertEquals(0.0, $detalle['monto_descuento']);
        $this->assertEquals(0.0, $detalle['monto_recargo']);
        $this->assertEqualsWithDelta(1759.65, $detalle['monto_final'], 0.01);
    }

    /**
     * Caso 4: Pago después de 1 mes con recargo por mora (5% mensual acumulable)
     * Infracción: 2026-06-01
     * Pago: 2026-08-05 (2 meses de mora cumplidos)
     * Monto base: $1759.65
     * Recargo: 5% * 2 meses = 10% ($175.97)
     * Monto final: $1935.62
     */
    public function testCalcularMonto_Caso4_PagoDespuesDeUnMesConMora_AplicaRecargoCincoPorcientoMensual(): void
    {
        $infraccion = $this->infraccionModel->where('fundamento_legal', 'Art. 39 Frac. I')->first();
        $this->assertNotNull($infraccion);

        $fechaInfraccion = new DateTimeImmutable('2026-06-01 10:00:00');
        $fechaPago        = new DateTimeImmutable('2026-08-05 10:00:00'); // 2 meses y 4 días después

        $detalle = $this->service->calcularMontoConDescuentoRecargoDetalle($infraccion->id, $fechaInfraccion, $fechaPago);

        $this->assertNotNull($detalle);
        $this->assertEquals('recargo_mora', $detalle['tipo_ajuste']);
        $this->assertFalse($detalle['aplica_descuento']);
        $this->assertTrue($detalle['aplica_recargo']);
        $this->assertEquals(2, $detalle['meses_mora']);
        $this->assertEquals(5.0, $detalle['porcentaje_recargo_mensual']);
        $this->assertEqualsWithDelta(175.97, $detalle['monto_recargo'], 0.01);
        $this->assertEqualsWithDelta(1935.62, $detalle['monto_final'], 0.01);
    }

    /**
     * Caso 5: Verificación de exclusión de fines de semana en conteo de días hábiles
     */
    public function testContarDiasHabiles_ExcluyeSabadosYDomingos(): void
    {
        $viernes = new DateTimeImmutable('2026-08-07');
        $lunes   = new DateTimeImmutable('2026-08-10');

        // De viernes a lunes: solo transcurre 1 día hábil (el lunes)
        $dias = $this->service->contarDiasHabiles($viernes, $lunes);
        $this->assertEquals(1, $dias);
    }
}
