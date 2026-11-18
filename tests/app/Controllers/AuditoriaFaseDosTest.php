<?php declare(strict_types=1);

namespace Tests\Controllers;

use App\Libraries\CuentaCiudadanoService;
use App\Libraries\EvidenciaBoleta;
use App\Libraries\PermisosOperador;
use App\Models\AgenteTransitoModel;
use App\Models\BoletaInfraccionModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Auditoría de Félix, Fase 2: A1 (acuse con código de activación), A4
 * (evidencias privadas), A6 (boletas resueltas contra el catálogo del
 * servidor) y M2 (solo URLs de evidencia propias).
 */
class AuditoriaFaseDosTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    /** @var array<string, int> */
    private array $usuarios = [];
    private string $dirStorage;
    private array $envOriginal = [];

    protected function setUp(): void
    {
        parent::setUp();
        PermisosOperador::reiniciarCache();

        foreach (['SUPABASE_URL', 'SUPABASE_SERVICE_ROLE_KEY', 'STORAGE_LOCAL_PATH'] as $k) {
            $this->envOriginal[$k] = $_ENV[$k] ?? null;
        }
        $this->dirStorage = WRITEPATH . 'test_tmp' . DIRECTORY_SEPARATOR . 'f2_' . bin2hex(random_bytes(4));
        $_ENV['SUPABASE_URL'] = '';
        $_ENV['SUPABASE_SERVICE_ROLE_KEY'] = '';
        $_ENV['STORAGE_LOCAL_PATH'] = $this->dirStorage;

        foreach (['administrador', 'operador_ventanilla', 'ciudadano', 'juez_calificador'] as $rol) {
            if ($this->db->table('roles')->where('nombre', $rol)->countAllResults() === 0) {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
            }
            $rolId = (int) $this->db->table('roles')->where('nombre', $rol)->get()->getRow()->id;
            $this->db->table('users')->insert([
                'username' => $rol . '_f2', 'email' => $rol . '@f2.test', 'password_hash' => 'x',
                'nombre_completo' => 'Prueba ' . $rol, 'activo' => 1,
            ]);
            $this->usuarios[$rol] = (int) $this->db->insertID();
            $this->db->table('user_roles')->insert(['user_id' => $this->usuarios[$rol], 'role_id' => $rolId]);
        }

        if ($this->db->table('catalogo_infracciones_app')->where('fundamento_legal', 'Art. 39 Frac. III')->countAllResults() === 0) {
            $this->db->table('catalogo_infracciones_app')->insert([
                'fundamento_legal' => 'Art. 39 Frac. III', 'descripcion' => 'Exceso de velocidad', 'categoria' => 'CONDUCTORES',
                'monto_min_uma' => 2, 'monto_max_uma' => 4, 'activo' => 1, 'orden' => 1,
            ]);
        }
        (new AgenteTransitoModel())->insert([
            'placa' => 'AGT-F2', 'nombre_completo' => 'Agente F2', 'password_hash' => password_hash('secreto123', PASSWORD_BCRYPT),
            'rol' => 'operativo', 'sector' => 'Pruebas', 'activo' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->envOriginal as $k => $v) {
            if ($v === null) {
                unset($_ENV[$k]);
            } else {
                $_ENV[$k] = $v;
            }
        }
        if (is_dir($this->dirStorage)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->dirStorage, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->dirStorage);
        }
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function como(string $rol): self
    {
        return $this->withSession(['user_id' => $this->usuarios[$rol], 'username' => $rol . '_f2', 'roles' => [$rol]]);
    }

    private function tokenAgente(): string
    {
        return json_decode($this->post('api/agentes/login', ['placa' => 'AGT-F2', 'password' => 'secreto123'])->getJSON(), true)['token'];
    }

    /** @param array<string, mixed> $extra */
    private function sincronizar(string $folio, array $extra = []): array
    {
        $payload = array_merge([
            'folio' => $folio, 'agente_placa' => 'AGT-F2', 'fecha_infraccion' => date('Y-m-d'),
            'hora_infraccion' => '10:30', 'lugar' => 'Centro', 'conductor_ausente' => false, 'sin_placas' => false,
            'faltas_json' => [['id' => 'f1', 'fundamentoLegal' => 'Art. 39 Frac. III', 'descripcion' => 'x', 'categoria' => 'x', 'montoMinUma' => '1', 'montoMaxUma' => '99']],
        ], $extra);

        $respuesta = $this->withHeaders(['Authorization' => 'Bearer ' . $this->tokenAgente()])
            ->withBodyFormat('json')->post('api/infracciones/sincronizar', $payload);

        return json_decode($respuesta->getJSON(), true);
    }

    // ─── A1 · Acuse con código de activación ──────────────────────────────

    public function testElAcuseMuestraUnCodigoQueSirveParaReclamar(): void
    {
        $id = (new CuentaCiudadanoService())->crearSinAcceso(['nombre' => 'Rosa', 'apellido' => 'Méndez']);

        $cuerpo = $this->como('operador_ventanilla')->get('/admin/solicitudes/acuse-activacion/' . $id)->getBody();

        $this->assertMatchesRegularExpression('/[A-Z2-9]{4}-[A-Z2-9]{4}/', $cuerpo);
        preg_match('/<span class="valor">([A-Z2-9]{4}-[A-Z2-9]{4})<\/span>/', $cuerpo, $m);
        $this->assertNotNull((new CuentaCiudadanoService())->cuentaPorCodigoActivacion($m[1]));
    }

    public function testNoHayAcuseParaUnaCuentaQueYaTieneAcceso(): void
    {
        $this->como('operador_ventanilla')->get('/admin/solicitudes/acuse-activacion/' . $this->usuarios['ciudadano'])
            ->assertRedirectTo('/admin/solicitudes/nueva?ciudadano_id=' . $this->usuarios['ciudadano']);
    }

    public function testUnCiudadanoNoPuedeGenerarAcuses(): void
    {
        $id = (new CuentaCiudadanoService())->crearSinAcceso(['nombre' => 'Rosa', 'apellido' => 'Méndez']);

        $respuesta = $this->como('ciudadano')->get('/admin/solicitudes/acuse-activacion/' . $id);

        $this->assertTrue($respuesta->isRedirect());
        $this->assertNull($this->db->table('users')->where('id', $id)->get()->getRow()->activacion_hash);
    }

    // ─── A6 · Boletas contra el catálogo del servidor ─────────────────────

    public function testElRangoUmaSaleDelCatalogoNoDelDispositivo(): void
    {
        $this->sincronizar('FOLIO-F2-UMA');

        $boleta = (new BoletaInfraccionModel())->where('folio', 'FOLIO-F2-UMA')->first();
        $this->assertEqualsWithDelta(2.0, (float) $boleta->falta_monto_min_uma, 0.001);
        $this->assertEqualsWithDelta(4.0, (float) $boleta->falta_monto_max_uma, 0.001);
        $this->assertSame('Exceso de velocidad', $boleta->falta_descripcion);
        $this->assertStringNotContainsString('99', (string) $boleta->faltas_json);
    }

    public function testUnaFaltaFueraDelCatalogoSeRechaza(): void
    {
        $json = $this->sincronizar('FOLIO-F2-INVENTADA', ['faltas_json' => [['fundamentoLegal' => 'Art. 999', 'montoMinUma' => '0', 'montoMaxUma' => '0']]]);

        $this->assertSame(0, $json['sincronizadas']);
        $this->assertNull((new BoletaInfraccionModel())->where('folio', 'FOLIO-F2-INVENTADA')->first());
    }

    public function testFechasFuturasOMuyViejasSeRechazan(): void
    {
        foreach (['futura' => date('Y-m-d', strtotime('+5 days')), 'vieja' => date('Y-m-d', strtotime('-30 days')), 'mala' => '2026-02-30'] as $caso => $fecha) {
            $json = $this->sincronizar('FOLIO-F2-FECHA-' . $caso, ['fecha_infraccion' => $fecha]);
            $this->assertSame(0, $json['sincronizadas'], $caso);
        }
    }

    // ─── A4 / M2 · Evidencias ─────────────────────────────────────────────

    public function testLaEvidenciaSoloLaVeElPersonal(): void
    {
        $url = json_decode($this->withHeaders(['Authorization' => 'Bearer ' . $this->tokenAgente()])->withBodyFormat('json')
            ->post('api/infracciones/evidencias', ['folio' => 'FOLIO-F2-FOTO', 'tipo' => 'placa', 'base64' => self::PNG])
            ->getJSON(), true)['url'];
        $ruta = EvidenciaBoleta::rutaSegura($url, 'FOLIO-F2-FOTO');
        $this->assertNotNull($ruta);

        $juez = $this->como('juez_calificador')->get($ruta);
        $juez->assertStatus(200);
        $this->assertSame('image/png', $juez->response()->getHeaderLine('Content-Type'));

        $this->assertTrue($this->como('ciudadano')->get($ruta)->isRedirect());
        $this->assertTrue($this->get($ruta)->isRedirect());
    }

    public function testLaSincronizacionDescartaUrlsQueNoSonEvidenciasPropias(): void
    {
        $this->sincronizar('FOLIO-F2-XSS', [
            'foto_placa_url'     => 'javascript:alert(document.cookie)',
            'foto_contexto_url'  => 'https://evil.test/evidencias/FOLIO-F2-XSS/placa_' . str_repeat('a', 32) . '.png',
            'foto_documento_url' => site_url('evidencias/OTRO-FOLIO/placa_' . str_repeat('b', 32) . '.png'),
        ]);

        $boleta = (new BoletaInfraccionModel())->where('folio', 'FOLIO-F2-XSS')->first();
        $this->assertNull($boleta->foto_placa_url);
        $this->assertNull($boleta->foto_documento_url);
        $this->assertNull($boleta->foto_contexto_url);
    }

    public function testRutaSeguraRechazaEsquemasYNombresInventados(): void
    {
        $this->assertNull(EvidenciaBoleta::rutaSegura('javascript:alert(1)'));
        $this->assertNull(EvidenciaBoleta::rutaSegura('/evidencias/F1/placa_123.png'));
        $this->assertNull(EvidenciaBoleta::rutaSegura('/evidencias/F1/../placa_' . str_repeat('a', 32) . '.png'));
        $this->assertNull(EvidenciaBoleta::rutaSegura('/evidencias/F1/placa_' . str_repeat('a', 32) . '.php'));
        $this->assertSame('/evidencias/F1/placa_' . str_repeat('a', 32) . '.png', EvidenciaBoleta::rutaSegura('/evidencias/F1/placa_' . str_repeat('a', 32) . '.png', 'F1'));
    }
}
