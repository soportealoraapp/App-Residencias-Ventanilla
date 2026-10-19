<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Almacén global de documentos.
 *
 * La pantalla existe para que las tarjetas de documentos del desglose apunten a
 * un listado que reproduzca exactamente la cifra que muestran, así que aquí se
 * prueba esa correspondencia: el mismo filtro debe devolver los mismos archivos.
 */
class DocumentosAdminTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $roleIds = [];
    private int $adminId;
    private int $operadorId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);
        foreach ($this->db->table('roles')->get()->getResult() as $rol) {
            $this->roleIds[$rol->nombre] = (int) $rol->id;
        }

        $this->adminId    = $this->crearUsuario('doc_admin', 'administrador');
        $this->operadorId = $this->crearUsuario('doc_op', 'operador_ventanilla');
    }

    private function crearUsuario(string $username, string $rol): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Usuario ' . $username,
            'activo'          => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $this->roleIds[$rol]]);

        return $id;
    }

    private function crearSolicitud(string $folio, string $tramite = 'UR-TT-T-03'): int
    {
        $this->db->table('solicitudes')->insert([
            'folio'           => $folio,
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->adminId,
            'estatus'         => 'Recibido',
            'monto'           => 500,
            'fecha_solicitud' => date('Y-m-d 10:00:00'),
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Archivo ya registrado: la pantalla no sube nada, solo consulta lo que
     * el ciudadano o la ventanilla cargaron antes.
     */
    private function crearDocumento(
        int $solicitudId,
        string $nombre,
        string $tipo = 'identificacion_oficial',
        int $validado = 0,
        int $bytes = 204800,
        ?string $fechaCarga = null
    ): int {
        $this->db->table('documentos')->insert([
            'solicitud_id'    => $solicitudId,
            'tipo_documento'  => $tipo,
            'nombre_original' => $nombre,
            'ruta_interna'    => 'solicitud_' . $solicitudId . '/' . $nombre,
            'mime_type'       => 'application/pdf',
            'tamano_bytes'    => $bytes,
            'usuario_id'      => $this->adminId,
            'fecha_carga'     => $fechaCarga ?? date('Y-m-d H:i:s'),
            'validado'        => $validado,
        ]);

        return (int) $this->db->insertID();
    }

    private function comoAdmin(): self
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'doc_admin', 'roles' => ['administrador']]);
    }

    // ------------------------------------------------------------------
    // Acceso
    // ------------------------------------------------------------------

    public function testSinSesionNoSePuedeVerElAlmacen(): void
    {
        $response = $this->get('/admin/documentos');

        $response->assertRedirect();
    }

    public function testElCiudadanoNoAccedeAlAlmacen(): void
    {
        $ciudadano = $this->crearUsuario('doc_ciudadano', 'ciudadano');

        $response = $this->withSession(['user_id' => $ciudadano, 'username' => 'doc_ciudadano', 'roles' => ['ciudadano']])
            ->get('/admin/documentos');

        $response->assertRedirect();
    }

    public function testElAdministradorAbreElAlmacen(): void
    {
        $solicitud = $this->crearSolicitud('DOC-01');
        $this->crearDocumento($solicitud, 'ine_frontal.pdf');

        $response = $this->comoAdmin()->get('/admin/documentos');
        $response->assertStatus(200);

        $body = $response->getBody();
        self::assertStringContainsString('ine_frontal.pdf', $body);
        // El folio debe verse y enlazar al expediente, no solo el archivo.
        self::assertStringContainsString('admin/solicitudes/DOC-01', $body);
    }

    // ------------------------------------------------------------------
    // Filtros: cada cifra enlazada reproduce su propio conjunto
    // ------------------------------------------------------------------

    public function testElFiltroPorTipoDejaFueraLosDemasArchivos(): void
    {
        $solicitud = $this->crearSolicitud('DOC-02');
        $this->crearDocumento($solicitud, 'ine.pdf', 'identificacion_oficial');
        $this->crearDocumento($solicitud, 'circulacion.pdf', 'tarjeta_circulacion');

        $body = $this->comoAdmin()->get('/admin/documentos?tipo=tarjeta_circulacion')->getBody();

        self::assertStringContainsString('circulacion.pdf', $body);
        self::assertStringNotContainsString('ine.pdf', $body);
    }

    public function testElFiltroPorRevisionMuestraSoloLosPendientes(): void
    {
        $solicitud = $this->crearSolicitud('DOC-03');
        $this->crearDocumento($solicitud, 'revisado.pdf', 'identificacion_oficial', 1);
        $this->crearDocumento($solicitud, 'pendiente.pdf', 'identificacion_oficial', 0);

        $body = $this->comoAdmin()->get('/admin/documentos?validado=no')->getBody();

        self::assertStringContainsString('pendiente.pdf', $body);
        self::assertStringNotContainsString('revisado.pdf', $body);
    }

    public function testLaBusquedaTambienAceptaElFolioDelExpediente(): void
    {
        $expediente = $this->crearSolicitud('DOC-BUSCAR');
        $this->crearDocumento($expediente, 'comprobante.pdf');
        $otra        = $this->crearSolicitud('DOC-OTRA');
        $this->crearDocumento($otra, 'titulo.pdf');

        $body = $this->comoAdmin()->get('/admin/documentos?q=DOC-BUSCAR')->getBody();

        self::assertStringContainsString('comprobante.pdf', $body);
        self::assertStringNotContainsString('titulo.pdf', $body);
    }

    public function testUnMesFueraDeRangoNoArrastraElListado(): void
    {
        $solicitud = $this->crearSolicitud('DOC-MES');
        $this->crearDocumento($solicitud, 'de-hoy.pdf', 'identificacion_oficial', 0, 2048, date('Y-m-d H:i:s'));
        $this->crearDocumento($solicitud, 'de-antes.pdf', 'identificacion_oficial', 0, 2048, '2020-03-10 09:00:00');

        $body = $this->comoAdmin()->get('/admin/documentos?mes=2020-03')->getBody();

        self::assertStringContainsString('de-antes.pdf', $body);
        self::assertStringNotContainsString('de-hoy.pdf', $body);
    }

    /**
     * El desglose agrupa los archivos sin tipo como «sin_clasificar»; si la
     * pantalla no devolviera exactamente esos mismos archivos, el enlace de la
     * tarjeta mentiría con su número.
     */
    public function testLosArchivosSinTipoSeAgrupanYSeFiltranJuntos(): void
    {
        $solicitud = $this->crearSolicitud('DOC-SINTIPO');
        $this->crearDocumento($solicitud, 'sin-tipo.pdf', '', 0, 1024);
        $this->crearDocumento($solicitud, 'con-tipo.pdf', 'identificacion_oficial');

        $resumen = (new \App\Models\DocumentoModel())->resumenPorTipo();
        $encontrado = null;
        foreach ($resumen as $fila) {
            if ($fila['tipo'] === 'sin_clasificar') {
                $encontrado = (int) $fila['total'];
            }
        }
        self::assertSame(1, $encontrado, 'El resumen debe contar 1 archivo sin clasificar.');

        $body = $this->comoAdmin()->get('/admin/documentos?tipo=sin_clasificar')->getBody();
        self::assertStringContainsString('sin-tipo.pdf', $body);
        self::assertStringNotContainsString('con-tipo.pdf', $body);
    }

    // ------------------------------------------------------------------
    // Validación de los parámetros de la URL
    // ------------------------------------------------------------------

    public function testUnParametroMalFormadoNoRompeElListado(): void
    {
        $solicitud = $this->crearSolicitud('DOC-VALIDO');
        $this->crearDocumento($solicitud, 'unico.pdf');

        // `1=1` y caracteres fuera del patrón de tipo deben descartarse, no consultarse.
        $body = $this->comoAdmin()->get('/admin/documentos?tipo=' . rawurlencode('a b; drop') . '&validado=' . rawurlencode('todos') . '&mes=2026-13')->getBody();

        self::assertStringContainsString('unico.pdf', $body);
    }

    public function testElOrdenPorTamanoNoInventaColumnas(): void
    {
        $solicitud = $this->crearSolicitud('DOC-ORDEN');
        $this->crearDocumento($solicitud, 'grande.pdf', 'identificacion_oficial', 0, 900000);
        $this->crearDocumento($solicitud, 'chico.pdf', 'identificacion_oficial', 0, 1000);

        $body = $this->comoAdmin()->get('/admin/documentos?orden=tamano_bytes&dir=desc')->getBody();

        self::assertStringContainsString('grande.pdf', $body);
        // El archivo pesado aparece antes que el ligero en la tabla.
        self::assertLessThan(strpos($body, 'chico.pdf'), (int) strpos($body, 'grande.pdf'));
    }

    public function testLaVentanillaTambienPuedeRevisarLosArchivos(): void
    {
        $solicitud = $this->crearSolicitud('DOC-VENTANILLA');
        $this->crearDocumento($solicitud, 'papeles.pdf');

        $response = $this->withSession(['user_id' => $this->operadorId, 'username' => 'doc_op', 'roles' => ['operador_ventanilla']])
            ->get('/admin/documentos');

        $response->assertStatus(200);
        $response->assertSee('papeles.pdf');
    }
}
