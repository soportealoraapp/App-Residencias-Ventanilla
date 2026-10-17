<?php declare(strict_types=1);

namespace Tests\Controllers\Api;

use App\Models\AgenteTransitoModel;
use App\Models\BoletaInfraccionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class ApiAgentesTransitoYBoletasTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Insertar agente de prueba
        $agenteModel = new AgenteTransitoModel();
        $agenteModel->where('placa', 'AGT-TEST-1')->delete();
        $agenteModel->insert([
            'placa'           => 'AGT-TEST-1',
            'nombre_completo' => 'Oficial de Prueba',
            'password_hash'   => password_hash('secreto123', PASSWORD_BCRYPT),
            'rol'             => 'operativo',
            'sector'          => 'Sector Norte',
            'activo'          => 1,
        ]);
    }

    public function testLoginAgente_ConCredencialesCorrectas_RetornaPerfilSinSesionServidor(): void
    {
        $response = $this->post('api/agentes/login', [
            'placa'    => 'AGT-TEST-1',
            'password' => 'secreto123',
        ]);

        $response->assertStatus(200);
        $json = json_decode($response->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertArrayNotHasKey('token', $json);
        $this->assertEquals('AGT-TEST-1', $json['agente']['placa']);
        $this->assertEquals('Oficial de Prueba', $json['agente']['nombre_completo']);
    }

    public function testLoginAgente_ConPasswordIncorrecta_Retorna401(): void
    {
        $response = $this->post('api/agentes/login', [
            'placa'    => 'AGT-TEST-1',
            'password' => 'password_erronea',
        ]);

        $response->assertStatus(401);
        $json = json_decode($response->getJSON(), true);
        $this->assertFalse($json['success']);
    }

    public function testLoginAgente_PlacaInexistenteORetirada_Retorna401(): void
    {
        $response = $this->post('api/agentes/login', [
            'placa' => 'AGT-NO-EXISTE',
            'password' => 'secreto123',
        ]);
        $response->assertStatus(401);

        $agenteModel = new AgenteTransitoModel();
        $agente = $agenteModel->getPorPlaca('AGT-TEST-1');
        $agenteModel->update($agente->id, ['activo' => 0]);

        $response = $this->post('api/agentes/login', [
            'placa' => 'AGT-TEST-1',
            'password' => 'secreto123',
        ]);
        $response->assertStatus(401);
    }

    public function testAutorregistroMovilNoTieneRutaPublica(): void
    {
        try {
            $this->post('api/agentes/registro', [
                'placa' => 'AGT-UNAUTHORIZED',
                'nombre_completo' => 'Cuenta No Autorizada',
                'password' => 'secreto123',
            ]);
            $this->fail('El endpoint público de autorregistro no debe tener una ruta disponible.');
        } catch (PageNotFoundException) {
            $this->assertNull((new AgenteTransitoModel())->getPorPlaca('AGT-UNAUTHORIZED'));
        }
    }

    public function testGetParametros_RetornaValoresValidos(): void
    {
        $response = $this->get('api/parametros');
        $response->assertStatus(200);

        $json = json_decode($response->getJSON(), true);
        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('valor_uma_vigente', $json['parametros']);
        $this->assertArrayHasKey('fotos_obligatorias', $json['parametros']);
    }

    public function testSincronizarBoleta_InsertaYActualizaBoletaCorrectamente(): void
    {
        $folioTest = 'FOLIO-TEST-' . time();
        $payload = [
            'folio'                  => $folioTest,
            'agente_placa'           => 'AGT-TEST-1',
            'agente_nombre'          => 'Oficial de Prueba',
            'fecha_infraccion'       => '2026-10-13',
            'hora_infraccion'        => '10:30:00',
            'lugar'                  => 'Portal Independencia y Morelos',
            'latitud'                => 20.1412,
            'longitud'               => -101.1432,
            'conductor_ausente'      => 0,
            'infractor_nombre'       => 'Juan Pérez',
            'vehiculo_placas'        => 'GT-884-A',
            'sin_placas'             => 0,
            'vehiculo_marca'         => 'Nissan',
            'vehiculo_linea'         => 'Tsuru',
            'vehiculo_color'         => 'Rojo',
            'vehiculo_tipo'          => 'particular',
            'falta_fundamento_legal' => 'Art. 87 Frac. I a)',
            'falta_descripcion'      => 'Circular sin placas',
            'falta_categoria'        => 'PLACAS',
            'falta_monto_min_uma'    => 5.0,
            'falta_monto_max_uma'    => 10.0,
            'garantias_retenidas'    => ['placa_delantera'],
            'hechos'                 => 'Vehículo estacionado sin placa delantera visible.',
            'estado'                 => 'recibida',
        ];

        $response = $this->post('api/infracciones/sincronizar', $payload);
        $response->assertStatus(200);

        $json = json_decode($response->getJSON(), true);
        $this->assertTrue($json['success']);
        $this->assertEquals(1, $json['sincronizadas']);

        // Verificar persistencia en base de datos
        $boletaModel = new BoletaInfraccionModel();
        $guardada = $boletaModel->where('folio', $folioTest)->first();
        $this->assertNotNull($guardada);
        $this->assertEquals('AGT-TEST-1', $guardada->agente_placa);
        $this->assertEquals('Juan Pérez', $guardada->infractor_nombre);
        $this->assertEquals('GT-884-A', $guardada->vehiculo_placas);

        // Limpiar
        $boletaModel->where('folio', $folioTest)->delete();
    }
}
