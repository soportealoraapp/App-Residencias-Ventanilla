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
        // Las migraciones cargan las tarifas oficiales; cada prueba arma las suyas.
        $this->db->table('tarifas')->emptyTable();
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
        $this->crearTarifa('UR-TT-T-07', 'particular_dia', 191.00, 0);

        $resultado = $this->service->calcularMontoUrTtT07('particular', 'dia');

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(191.00, $resultado, 0.001);
    }

    public function testCalcularMontoUrTtT07_ParticularSeCobraPorCamion(): void
    {
        // Ficha UR-TT-T-07: $1,151.00 por mes y por 1 camión.
        $this->crearTarifa('UR-TT-T-07', 'particular_mes', 1151.00, 0);

        $this->assertEqualsWithDelta(1151.00, $this->service->calcularMontoUrTtT07('particular', 'mes'), 0.001);
        $this->assertEqualsWithDelta(3453.00, $this->service->calcularMontoUrTtT07('particular', 'mes', 3), 0.001);
        $this->assertEqualsWithDelta(1151.00, $this->service->calcularMontoUrTtT07('particular', 'mes', 0), 0.001);
    }

    public function testCalcularMontoUrTtT07_Empresa_EsCuotaFijaHasta15Camiones(): void
    {
        // Ficha UR-TT-T-07: empresa establecida en el municipio, hasta 15 camiones por mes $5,732.00.
        $this->crearTarifa('UR-TT-T-07', 'empresa_mes', 5732.00, 0);

        $this->assertEqualsWithDelta(5732.00, $this->service->calcularMontoUrTtT07('empresa', 'mes', 3), 0.001);
        $this->assertEqualsWithDelta(5732.00, $this->service->calcularMontoUrTtT07('empresa', 'mes', 15), 0.001);
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

    public function testCalcularMontoUrTtT07_Empresa_NoSeMultiplicaPorCamion(): void
    {
        $this->crearTarifa('UR-TT-T-07', 'empresa_mes', 5732.00, 0);

        $resultado = $this->service->calcularMontoUrTtT07('empresa', 'mes', 20);

        $this->assertNotNull($resultado);
        $this->assertEqualsWithDelta(5732.00, $resultado, 0.001);
    }

    public function testCalcularMonto_Ur01Ur02Ur03DesdeCatalogo(): void
    {
        $this->crearTarifa('UR-TT-T-01', 'base', 9055.20, 1);
        $this->crearTarifa('UR-TT-T-02', 'base', 64.90, 1);
        $this->crearTarifa('UR-TT-T-03', 'base', 0.00, 0);

        $this->assertEqualsWithDelta(9055.20, $this->service->calcularMonto('UR-TT-T-01'), 0.001);
        $this->assertEqualsWithDelta(64.90, $this->service->calcularMonto('UR-TT-T-02'), 0.001);
        // Ficha UR-TT-T-03: la orden de plaqueo no tiene costo.
        $this->assertEqualsWithDelta(0.00, $this->service->calcularMonto('UR-TT-T-03'), 0.001);
    }

    public function testCalcularMonto_Ur04_ValorProvisional(): void
    {
        $this->crearTarifa('UR-TT-T-04', 'base', 156.94, 1);

        $this->assertEqualsWithDelta(156.94, $this->service->calcularMonto('UR-TT-T-04'), 0.001);
        $this->assertTrue($this->service->esPlaceholder('UR-TT-T-04', 'base'));
    }

    public function testCalcularMonto_Ur05_ValorProvisional(): void
    {
        $this->crearTarifa('UR-TT-T-05', 'base', 287.00, 1);

        $this->assertEqualsWithDelta(287.00, $this->service->calcularMonto('UR-TT-T-05'), 0.001);
        $this->assertTrue($this->service->esPlaceholder('UR-TT-T-05', 'base'));
    }
}
