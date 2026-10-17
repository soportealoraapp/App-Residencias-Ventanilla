<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\DocumentoUploader;
use App\Libraries\SupabaseStorage;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Sin Supabase configurado, los documentos se guardan en disco local y se
 * entregan con URL firmada; con esto los trámites funcionan en local.
 */
class AlmacenamientoLocalTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private string $dir;
    private array $envOriginal = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['SUPABASE_URL', 'SUPABASE_SERVICE_ROLE_KEY', 'STORAGE_LOCAL_PATH'] as $k) {
            $this->envOriginal[$k] = $_ENV[$k] ?? null;
        }
        $this->dir = WRITEPATH . 'test_tmp' . DIRECTORY_SEPARATOR . 'storage_' . bin2hex(random_bytes(4));
        $_ENV['SUPABASE_URL'] = '';
        $_ENV['SUPABASE_SERVICE_ROLE_KEY'] = '';
        $_ENV['STORAGE_LOCAL_PATH'] = $this->dir;
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
        $this->borrarDirectorio($this->dir);
        parent::tearDown();
    }

    private function borrarDirectorio(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $n) {
            $ruta = $dir . DIRECTORY_SEPARATOR . $n;
            is_dir($ruta) ? $this->borrarDirectorio($ruta) : unlink($ruta);
        }
        rmdir($dir);
    }

    public function testSinSupabaseGuardaYEntregaConUrlFirmada(): void
    {
        $storage = new SupabaseStorage();
        $this->assertTrue($storage->esLocal());
        $this->assertTrue($storage->subir('documentos', 'solicitud_1/a.pdf', '%PDF-1.4 prueba', 'application/pdf'));
        $this->assertTrue($storage->existe('documentos', 'solicitud_1/a.pdf'));
        $this->assertSame('%PDF-1.4 prueba', $storage->descargar('documentos', 'solicitud_1/a.pdf'));

        $url = (string) $storage->urlFirmada('documentos', 'solicitud_1/a.pdf');
        $query = [];
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $ok = $this->get('archivos/documentos', $query);
        $ok->assertOK();
        $this->assertSame('%PDF-1.4 prueba', $ok->response()->getBody());
        $this->assertStringStartsWith('application/pdf', $ok->response()->getHeaderLine('Content-Type'));

        $this->get('archivos/documentos', ['sig' => 'alterada'] + $query)->assertStatus(403);
        $this->get('archivos/documentos', ['exp' => time() - 10] + $query)->assertStatus(403);

        $this->assertTrue($storage->eliminar('documentos', ['solicitud_1/a.pdf']));
        $this->assertFalse($storage->existe('documentos', 'solicitud_1/a.pdf'));
    }

    public function testRechazaRutasFueraDeSuCarpeta(): void
    {
        $storage = new SupabaseStorage();
        $this->assertNull($storage->rutaArchivoLocal('documentos', '../../.env'));
        $this->assertNull($storage->rutaArchivoLocal('logs', 'x.txt'));
        $this->assertFalse($storage->subir('documentos', '../fuera.pdf', 'x', 'application/pdf'));
    }

    public function testDocumentoUploaderGuardaEnLocalYRegistraDocumento(): void
    {
        $this->db->table('users')->insert(['username' => 'c1', 'email' => 'c1@x.com', 'password_hash' => 'x', 'activo' => 1]);
        $userId = (int) $this->db->insertID();
        $this->db->table('solicitudes')->insert([
            'folio' => 'F-PRUEBA-1', 'tramite' => 'UR-TT-T-05', 'ciudadano_id' => $userId,
            'estatus' => 'Recibido', 'monto' => 287, 'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
        $solicitudId = (int) $this->db->insertID();

        $tmp = tempnam(sys_get_temp_dir(), 'doc');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");
        $archivo = new UploadedFile($tmp, 'ine.pdf', 'application/pdf', filesize($tmp), UPLOAD_ERR_OK);

        $doc = (new DocumentoUploader())->subir($archivo, 'identificacion_oficial', $solicitudId, $userId);

        $this->assertNotNull($doc);
        $this->assertTrue((new SupabaseStorage())->existe('documentos', $doc->ruta_interna));
        @unlink($tmp);
    }

    public function testCierreDeCalleSinDocumentosSeRegistra(): void
    {
        $this->db->table('users')->insert(['username' => 'c2', 'email' => 'c2@x.com', 'password_hash' => 'x', 'activo' => 1]);
        $userId = (int) $this->db->insertID();

        $result = $this->withSession(['user_id' => $userId, 'roles' => ['ciudadano']])
            ->post('portal/tramites/cierre-calle/guardar', [
                'nombre_solicitante' => 'Juan Prueba',
                'domicilio'          => 'Calle Hidalgo 123',
                'fecha_cierre'       => date('Y-m-d', strtotime('+10 days')),
                'hora_inicio'        => '09:00',
                'hora_fin'           => '13:00',
                'calle_tramo'        => 'Hidalgo entre Juárez y Morelos',
                'colonia'            => 'Centro',
                'tipo_cierre'        => 'parcial',
                'motivo_evento'      => 'Fiesta patronal',
                'descripcion_evento' => 'Colocación de toldos en la vía pública.',
            ]);

        $solicitud = $this->db->table('solicitudes')->where('ciudadano_id', $userId)->get()->getRow();
        $this->assertNotNull($solicitud);
        $result->assertRedirectTo(site_url('portal/tramites/cierre-calle/resumen/' . $solicitud->folio));
    }
}
