<?php declare(strict_types=1);

namespace Tests\Libraries;

use Tests\Support\DatabaseTestCase;
use App\Libraries\TarifarioService;
use App\Models\TarifaModel;
use DateTime;

class TarifarioServiceTest extends DatabaseTestCase
{
    protected TarifarioService $service;
    protected TarifaModel $tarifaModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TarifarioService();
        $this->tarifaModel = new TarifaModel();
    }

    protected function crearTarifa(
        string $tramite,
        string $criterio,
        float $monto,
        int $placeholderOficial = 1,
        ?DateTime $vigenteDesde = null,
        ?DateTime $vigenteHasta = null
    ): void {
        if ($vigenteDesde === null) {
            $vigenteDesde = new DateTime();
        }
        $this->tarifaModel->insert([
            'tramite'            => $tramite,
            'criterio'           => $criterio,
            'monto'              => $monto,
            'vigente_desde'      => $vigenteDesde->format('Y-m-d'),
            'vigente_hasta'      => $vigenteHasta !== null ? $vigenteHasta->format('Y-m-d') : null,
            'descripcion'        => 'Tarifa de prueba',
            'placeholder_oficial' => $placeholderOficial,
        ]);
    }

    public function testCalcularMontoUrTtT07_Particular_Dia(): void
    {
        $this->crearTarifa('UR-TT-T-07', 'particular_dia', 50.00, 1);

        $resultado = $this->service->calcularMontoUrTtT07('particular', 'dia');

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(50.00, $resultado, 0.001);
    }

    public function testCalcularMontoUrTtT07_Empresa_Mes_TresCamiones(): void
    {
        $this->crearTarifa('UR-TT-T-07', 'empresa_mes', 1000.00, 1);

        $resultado = $this->service->calcularMontoUrTtT07('empresa', 'mes', 3);

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(3000.00, $resultado, 0.001);
    }

    public function testCalcularMontoUrTtT07_SinTarifaExistente(): void
    {
        $resultado = $this->service->calcularMontoUrTtT07('particular', 'semestre');

        $this->assertNull($resultado);
    }

    public function testCalcularMontoUrTtT06_ValorFijo(): void
    {
        $this->crearTarifa('UR-TT-T-06', 'cesion_concesion_base', 9055.20, 1);

        $resultado = $this->service->calcularMontoUrTtT06();

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(9055.20, $resultado, 0.001);
    }

    public function testEsPlaceholder_TarifaPlaceholderTrue_ReturnsTrue(): void
    {
        $this->crearTarifa('UR-TT-T-07', 'particular_dia', 50.00, 1);

        $resultado = $this->service->esPlaceholder('UR-TT-T-07', 'particular_dia');

        $this->assertTrue($resultado);
    }

    public function testEsPlaceholder_TarifaVerificada_ReturnsFalse(): void
    {
        $this->crearTarifa('UR-TT-T-07', 'empresa_anio', 10000.00, 0);

        $resultado = $this->service->esPlaceholder('UR-TT-T-07', 'empresa_anio');

        $this->assertFalse($resultado);
    }

    public function testCalcularMontoUrTtT07_Empresa_CamionesExcede15_ReturnsSame15Max(): void
    {
        $this->crearTarifa('UR-TT-T-07', 'empresa_mes', 1000.00, 1);

        $resultado = $this->service->calcularMontoUrTtT07('empresa', 'mes', 20);

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(15000.00, $resultado, 0.001);
    }

    public function testCalcularMonto_Ur01Ur02Ur03DesdeCatalogo(): void
    {
        $this->crearTarifa('UR-TT-T-01', 'base', 9055.20, 1);
        $this->crearTarifa('UR-TT-T-02', 'base', 64.90, 1);
        $this->crearTarifa('UR-TT-T-03', 'base', 50.00, 1);

        $this->assertEqualsWithDelta(9055.20, $this->service->calcularMonto('UR-TT-T-01'), 0.001);
        $this->assertEqualsWithDelta(64.90, $this->service->calcularMonto('UR-TT-T-02'), 0.001);
        $this->assertEqualsWithDelta(50.00, $this->service->calcularMonto('UR-TT-T-03'), 0.001);
    }
}
