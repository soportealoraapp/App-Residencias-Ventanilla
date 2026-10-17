<?php declare(strict_types=1);

namespace Tests\Controllers\Api;

use App\Models\AgenteTokenModel;
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

    public function testLoginAgente_ConCredencialesCorrectas_RetornaPerfilYTokenConExpiracion(): void
    {
        $response = $this->post('api/agentes/login', [
            'placa'    => 'AGT-TEST-1',
            'password' => 'secreto123',
        ]);

        $response->assertStatus(200);
        $json = json_decode($response->getJSON(), true);

        $this->assertTrue($json['success']);
        $this->assertEquals('AGT-TEST-1', $json['agente']['placa']);
        $this->assertEquals('Oficial de Prueba', $json['agente']['nombre_completo']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $json['token']);
        $this->assertSame('Bearer', $json['token_type']);
        $this->assertGreaterThan(date('Y-m-d H:i:s'), $json['expira_en']);

        // En BD solo queda el hash, nunca el token en claro.
        $tokenModel = new AgenteTokenModel();
        $this->assertNull($tokenModel->where('token_hash', $json['token'])->first());
        $this->assertNotNull($tokenModel->where('token_hash', hash('sha256', $json['token']))->first());
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

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->loginToken()])
            ->post('api/infracciones/sincronizar', $payload);
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

    public function testSincronizar_SinToken_Retorna401YNoGuarda(): void
    {
        $response = $this->post('api/infracciones/sincronizar', $this->boleta('FOLIO-SIN-TOKEN', 'AGT-TEST-1'));

        $response->assertStatus(401);
        $this->assertFalse(json_decode($response->getJSON(), true)['success']);
        $this->assertNull((new BoletaInfraccionModel())->where('folio', 'FOLIO-SIN-TOKEN')->first());
    }

    public function testSincronizar_ConTokenInvalido_Retorna401(): void
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . str_repeat('a', 64)])
            ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-TOKEN-FALSO', 'AGT-TEST-1'));

        $response->assertStatus(401);
        $this->assertNull((new BoletaInfraccionModel())->where('folio', 'FOLIO-TOKEN-FALSO')->first());
    }

    public function testSincronizar_ConTokenExpirado_Retorna401(): void
    {
        $token = $this->loginToken();
        $tokenModel = new AgenteTokenModel();
        $registro = $tokenModel->where('token_hash', hash('sha256', $token))->first();
        $tokenModel->update($registro->id, ['expira_en' => date('Y-m-d H:i:s', time() - 60)]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-TOKEN-VENCIDO', 'AGT-TEST-1'));

        $response->assertStatus(401);
    }

    public function testSincronizar_AgenteDesactivadoConTokenVigente_Retorna401(): void
    {
        $token = $this->loginToken();
        $agenteModel = new AgenteTransitoModel();
        $agenteModel->update($agenteModel->getPorPlaca('AGT-TEST-1')->id, ['activo' => 0]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-AGENTE-BAJA', 'AGT-TEST-1'));

        $response->assertStatus(401);
        $this->assertNull((new BoletaInfraccionModel())->where('folio', 'FOLIO-AGENTE-BAJA')->first());
    }

    public function testSincronizar_ConPlacaDeOtroAgente_Retorna403YNoGuarda(): void
    {
        $this->crearSegundoAgente();

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->loginToken()])
            ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-SUPLANTADO', 'AGT-TEST-2'));

        $response->assertStatus(403);
        $this->assertNull((new BoletaInfraccionModel())->where('folio', 'FOLIO-SUPLANTADO')->first());
    }

    public function testSincronizar_NoSobrescribeFolioDeOtroAgente(): void
    {
        $this->crearSegundoAgente();
        $boletaModel = new BoletaInfraccionModel();
        $tokenAgente2 = $this->loginToken('AGT-TEST-2', 'otro-secreto');

        $this->withHeaders(['Authorization' => 'Bearer ' . $tokenAgente2])
            ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-AGENTE-2', 'AGT-TEST-2', 'Lugar original'))
            ->assertStatus(200);

        // El agente 1 intenta reutilizar el folio, tanto con su placa como sin placa.
        $tokenAgente1 = $this->loginToken();
        foreach (['AGT-TEST-1', ''] as $placa) {
            $this->withHeaders(['Authorization' => 'Bearer ' . $tokenAgente1])
                ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-AGENTE-2', $placa, 'Lugar alterado'))
                ->assertStatus(403);
        }

        $guardada = $boletaModel->where('folio', 'FOLIO-AGENTE-2')->first();
        $this->assertSame('AGT-TEST-2', $guardada->agente_placa);
        $this->assertSame('Lugar original', $guardada->lugar);
    }

    public function testSincronizar_ConTokenValidoUsaIdentidadDelToken(): void
    {
        $payload = $this->boleta('FOLIO-TOKEN-OK', 'agt-test-1');
        $payload['agente_nombre'] = 'Nombre inventado';

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->loginToken()])
            ->post('api/infracciones/sincronizar', $payload);

        $response->assertStatus(200);
        $guardada = (new BoletaInfraccionModel())->where('folio', 'FOLIO-TOKEN-OK')->first();
        $this->assertSame('AGT-TEST-1', $guardada->agente_placa);
        $this->assertSame('Oficial de Prueba', $guardada->agente_nombre);
    }

    public function testEvidencias_SinToken_Retorna401(): void
    {
        $response = $this->withBodyFormat('json')->post('api/infracciones/evidencias', [
            'folio'  => 'FOLIO-EVIDENCIA',
            'tipo'   => 'placa',
            'base64' => base64_encode('imagen'),
        ]);

        $response->assertStatus(401);
    }

    public function testEvidencias_ParaFolioDeOtroAgente_Retorna403(): void
    {
        $this->crearSegundoAgente();
        $this->withHeaders(['Authorization' => 'Bearer ' . $this->loginToken('AGT-TEST-2', 'otro-secreto')])
            ->post('api/infracciones/sincronizar', $this->boleta('FOLIO-EVIDENCIA-AJENA', 'AGT-TEST-2'))
            ->assertStatus(200);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->loginToken()])
            ->withBodyFormat('json')
            ->post('api/infracciones/evidencias', [
                'folio'  => 'FOLIO-EVIDENCIA-AJENA',
                'tipo'   => 'placa',
                'base64' => base64_encode('imagen'),
            ]);

        $response->assertStatus(403);
        $this->assertDirectoryDoesNotExist(FCPATH . 'uploads/infracciones/FOLIO-EVIDENCIA-AJENA');
    }

    public function testEvidencias_ConTokenValido_GuardaImagen(): void
    {
        $folio = 'FOLIO-EVIDENCIA-' . bin2hex(random_bytes(4));
        $dir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'infracciones' . DIRECTORY_SEPARATOR . $folio;

        try {
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->loginToken()])
                ->withBodyFormat('json')
                ->post('api/infracciones/evidencias', [
                    'folio'  => $folio,
                    'tipo'   => '../../placa',
                    'base64' => base64_encode('imagen'),
                ]);

            $response->assertStatus(200);
            $archivos = glob($dir . DIRECTORY_SEPARATOR . '*');
            $this->assertCount(1, $archivos);
            // Un "tipo" no reconocido no puede salir del directorio del folio.
            $this->assertStringStartsWith('evidencia_', basename($archivos[0]));
        } finally {
            array_map('unlink', glob($dir . DIRECTORY_SEPARATOR . '*') ?: []);
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    private function loginToken(string $placa = 'AGT-TEST-1', string $password = 'secreto123'): string
    {
        $response = $this->post('api/agentes/login', ['placa' => $placa, 'password' => $password]);
        $response->assertStatus(200);

        return json_decode($response->getJSON(), true)['token'];
    }

    private function crearSegundoAgente(): void
    {
        (new AgenteTransitoModel())->insert([
            'placa'           => 'AGT-TEST-2',
            'nombre_completo' => 'Segundo Oficial',
            'password_hash'   => password_hash('otro-secreto', PASSWORD_BCRYPT),
            'rol'             => 'operativo',
            'sector'          => 'Sector Sur',
            'activo'          => 1,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function boleta(string $folio, string $placa, string $lugar = 'Portal Independencia'): array
    {
        return [
            'folio'            => $folio,
            'agente_placa'     => $placa,
            'fecha_infraccion' => '2026-10-13',
            'hora_infraccion'  => '10:30:00',
            'lugar'            => $lugar,
            'estado'           => 'recibida',
        ];
    }
}
