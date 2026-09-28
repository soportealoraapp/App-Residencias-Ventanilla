<?php declare(strict_types=1);

namespace Tests\Controllers\Api;

use App\Database\Seeds\BoletaChecklistSeeder;
use App\Database\Seeds\CatalogoInfraccionesSeeder;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class InfraccionesApiControllerTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogoInfraccionesSeeder::class);
        $this->seed(BoletaChecklistSeeder::class);
    }

    public function testGetChecklistInfracciones_EndpointResponde200YJsonValido(): void
    {
        $result = $this->get('api/checklist-infracciones');

        $result->assertStatus(200);
        $result->assertHeader('Content-Type', 'application/json; charset=UTF-8');

        $json = json_decode($result->getJSON(), true);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('valor_uma_vigente', $json);
        $this->assertArrayHasKey('total_items', $json);
        $this->assertArrayHasKey('categorias', $json);
        $this->assertEquals(117.31, $json['valor_uma_vigente']);
        $this->assertEquals(80, $json['total_items']);

        // Check category structure
        $this->assertArrayHasKey('DOCUMENTACIÓN', $json['categorias']);
        $this->assertArrayHasKey('SEÑALAMIENTOS', $json['categorias']);
        $this->assertArrayHasKey('ESTACIONAMIENTOS', $json['categorias']);

        // Check first item of DOCUMENTACIÓN: "Falta de tarjeta de circulación" → Art. 87 Frac. IV
        // Real catalog: monto_min_uma=2.0, monto_max_uma=5.0 @ UMA=$117.31
        $docItems = $json['categorias']['DOCUMENTACIÓN'];
        $this->assertNotEmpty($docItems);
        $firstItem = $docItems[0];
        $this->assertEquals('Falta de tarjeta de circulación', $firstItem['etiqueta_casilla']);
        $this->assertEquals('Art. 87 Frac. IV', $firstItem['fundamento_legal']);
        $this->assertEquals(2.0, (float)$firstItem['monto_min_uma']);
        $this->assertEquals(5.0, (float)$firstItem['monto_max_uma']);
        $this->assertEquals(234.62, (float)$firstItem['monto_min_pesos']); // 2 × 117.31
        $this->assertEquals(586.55, (float)$firstItem['monto_max_pesos']); // 5 × 117.31
    }
}

