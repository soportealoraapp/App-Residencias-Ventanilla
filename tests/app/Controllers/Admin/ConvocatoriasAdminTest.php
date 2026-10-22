<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Libraries\FolioGenerator;
use App\Models\SolicitudModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * CRUD de convocatorias UR-01 en el panel.
 *
 * Cubre lo que antes no existía: el listado con sus filtros de ventana, el alta,
 * la edición, la negativa a borrar una convocatoria con postulaciones y el 404
 * honesto cuando una convocatoria no existe. Hasta ahora la sección apuntaba por
 * hardcode a la convocatoria #1 y, si esa fila no estaba, mostraba una página que
 * fingía "no hay datos" aunque el problema fuera otro.
 */
class ConvocatoriasAdminTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $operadorId;
    private int $ciudadanoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->table('roles')->insertBatch([
            ['nombre' => 'administrador', 'descripcion' => 'admin'],
            ['nombre' => 'operador_ventanilla', 'descripcion' => 'operador'],
            ['nombre' => 'ciudadano', 'descripcion' => 'ciudadano'],
        ]);
        $roles = [];
        foreach ($this->db->table('roles')->get()->getResult() as $rol) {
            $roles[$rol->nombre] = (int) $rol->id;
        }

        $this->adminId     = $this->crearUsuario('admin_conv', $roles['administrador']);
        $this->operadorId  = $this->crearUsuario('op_conv', $roles['operador_ventanilla']);
        $this->ciudadanoId = $this->crearUsuario('ciud_conv', $roles['ciudadano']);
    }

    private function crearUsuario(string $username, int $roleId): int
    {
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'nombre_completo' => 'Usuario ' . $username,
            'activo'          => 1,
        ]);
        $id = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $id, 'role_id' => $roleId]);

        return $id;
    }

    /**
     * Inserta una convocatoria directo en la base: así las pruebas de listado y
     * edición no dependen del formulario de alta.
     */
    private function sembrarConvocatoria(array $cambios = []): int
    {
        $fila = array_merge([
            'titulo'                  => 'Convocatoria de prueba',
            'fecha_publicacion'       => date('Y-m-d', strtotime('-5 days')),
            'periodo_registro_inicio' => date('Y-m-d', strtotime('-1 day')),
            'periodo_registro_fin'    => date('Y-m-d', strtotime('+29 days')),
            'bases'                   => 'Bases de prueba para el listado.',
            'estatus'                 => 'Vigente',
        ], $cambios);

        $this->db->table('convocatorias')->insert($fila);

        return (int) $this->db->insertID();
    }

    private function comoAdmin(): self
    {
        return $this->withSession([
            'user_id'  => $this->adminId,
            'username' => 'admin_conv',
            'roles'    => ['administrador'],
        ]);
    }

    private function comoOperador(): self
    {
        return $this->withSession([
            'user_id'  => $this->operadorId,
            'username' => 'op_conv',
            'roles'    => ['operador_ventanilla'],
        ]);
    }

    /** Cuerpo del POST de alta, con la ventana bien puesta. */
    private function formularioConvocatoria(array $cambios = []): array
    {
        return array_merge([
            'titulo'                  => 'Convocatoria OTT-01/2026',
            'fecha_publicacion'       => date('Y-m-d'),
            'periodo_registro_inicio' => date('Y-m-d'),
            'periodo_registro_fin'    => date('Y-m-d', strtotime('+30 days')),
            'bases'                   => 'Se otorgarán 5 concesiones de ruta urbana.',
            'estatus'                 => 'Vigente',
        ], $cambios);
    }

    // ─── LISTADO ───────────────────────────────────────────────────

    public function testIndexMuestraElBotonDeNuevaConvocatoria(): void
    {
        $response = $this->comoAdmin()->get('/admin/convocatorias');
        $response->assertStatus(200);
        $response->assertSee('Nueva convocatoria');
    }

    public function testIndexListaLasConvocatoriasConSuVentana(): void
    {
        $this->sembrarConvocatoria(['titulo' => 'Convocatoria Abierta 2026']);

        $response = $this->comoAdmin()->get('/admin/convocatorias');
        $response->assertStatus(200);
        $response->assertSee('Convocatoria Abierta 2026');
        $response->assertSee('Abierta al registro');
    }

    public function testFiltroPorVentanaExcluyeLasDemas(): void
    {
        $this->sembrarConvocatoria(['titulo' => 'Abierta Hoy', 'estatus' => 'Vigente']);
        $this->sembrarConvocatoria(['titulo' => 'Ya Cerrada', 'estatus' => 'Cerrada']);

        $response = $this->comoAdmin()->get('/admin/convocatorias?filtro=abiertas');
        $response->assertStatus(200);
        $response->assertSee('Abierta Hoy');
        $response->assertDontSee('Ya Cerrada');
    }

    public function testVigenteFueraDeVentanaNoCuentaComoAbierta(): void
    {
        // El estatus lo captura el administrador; la ventana la dictan las fechas.
        $this->sembrarConvocatoria([
            'titulo'                  => 'Por Abrir El Mes Que Viene',
            'periodo_registro_inicio' => date('Y-m-d', strtotime('+10 days')),
            'periodo_registro_fin'    => date('Y-m-d', strtotime('+40 days')),
        ]);

        $cerradas = $this->comoAdmin()->get('/admin/convocatorias?filtro=abiertas');
        $cerradas->assertStatus(200);
        $cerradas->assertDontSee('Por Abrir El Mes Que Viene');

        $porAbrir = $this->comoAdmin()->get('/admin/convocatorias?filtro=por_abrir');
        $porAbrir->assertStatus(200);
        $porAbrir->assertSee('Por Abrir El Mes Que Viene');
    }

    public function testLaBusquedaEncuentraPorTitulo(): void
    {
        $this->sembrarConvocatoria(['titulo' => 'Convocatoria Ruta Norte 2026']);
        $this->sembrarConvocatoria(['titulo' => 'Convocatoria Ruta Sur 2026']);

        $response = $this->comoAdmin()->get('/admin/convocatorias?q=Ruta+Norte');
        $response->assertStatus(200);
        $response->assertSee('Convocatoria Ruta Norte 2026');
        $response->assertDontSee('Convocatoria Ruta Sur 2026');
    }

    // ─── ALTA ──────────────────────────────────────────────────────

    public function testFormularioDeAltaResponde(): void
    {
        $response = $this->comoAdmin()->get('/admin/convocatorias/nueva');
        $response->assertStatus(200);
        $response->assertSee('Publicar convocatoria');
    }

    public function testGuardarCreaLaConvocatoriaYRedirige(): void
    {
        $response = $this->comoAdmin()->post('/admin/convocatorias/guardar', $this->formularioConvocatoria());
        $response->assertRedirect();

        $creada = $this->db->table('convocatorias')->where('titulo', 'Convocatoria OTT-01/2026')->get()->getRow();
        $this->assertNotFalse($creada, 'La convocatoria no se guardó.');
        $this->assertSame('Vigente', $creada->estatus);
        $this->assertSame('Se otorgarán 5 concesiones de ruta urbana.', $creada->bases);
        $this->assertSame($this->adminId, (int) $creada->created_by);
    }

    public function testGuardarRechazaTituloVacio(): void
    {
        $antes = $this->db->table('convocatorias')->countAllResults();

        $response = $this->comoAdmin()->post('/admin/convocatorias/guardar', $this->formularioConvocatoria(['titulo' => '']));
        $response->assertRedirect();

        $this->assertSame($antes, $this->db->table('convocatorias')->countAllResults());
    }

    public function testGuardarRechazaFinAntesQueInicio(): void
    {
        $antes = $this->db->table('convocatorias')->countAllResults();

        $response = $this->comoAdmin()->post('/admin/convocatorias/guardar', $this->formularioConvocatoria([
            'titulo'                  => 'Convocatoria Con Ventana Invertida',
            'periodo_registro_inicio' => date('Y-m-d', strtotime('+20 days')),
            'periodo_registro_fin'    => date('Y-m-d'),
        ]));
        $response->assertRedirect();

        $this->assertSame($antes, $this->db->table('convocatorias')->countAllResults(),
            'Se coló una convocatoria con fechas imposibles.');
    }

    public function testGuardarRechazaEstatusInventado(): void
    {
        // La base acepta cualquier cadena, así que el control lo pone el panel:
        // un estatus fuera de Vigente/Cerrada/Anulada dejaría la convocatoria
        // invisible en todos los filtros.
        $antes = $this->db->table('convocatorias')->countAllResults();

        $response = $this->comoAdmin()->post('/admin/convocatorias/guardar', $this->formularioConvocatoria([
            'titulo'  => 'Convocatoria Estatus Raro',
            'estatus' => 'Aprovada',
        ]));
        $response->assertRedirect();

        $this->assertSame($antes, $this->db->table('convocatorias')->countAllResults());
    }

    // ─── EDICIÓN ───────────────────────────────────────────────────

    public function testFormularioDeEdicionCargaLosDatos(): void
    {
        $id = $this->sembrarConvocatoria(['titulo' => 'Convocatoria Editable']);

        $response = $this->comoAdmin()->get('/admin/convocatorias/editar/' . $id);
        $response->assertStatus(200);
        $response->assertSee('Convocatoria Editable');
        $response->assertSee('Guardar cambios');
    }

    public function testActualizarCierraLaConvocatoria(): void
    {
        $id = $this->sembrarConvocatoria(['titulo' => 'Convocatoria A Cerrar']);

        $response = $this->comoAdmin()->post('/admin/convocatorias/actualizar/' . $id, $this->formularioConvocatoria([
            'titulo'  => 'Convocatoria A Cerrar',
            'bases'   => 'Bases actualizadas.',
            'estatus' => 'Cerrada',
        ]));
        $response->assertRedirect();

        $fila = $this->db->table('convocatorias')->where('id', $id)->get()->getRow();
        $this->assertNotFalse($fila);
        $this->assertSame('Cerrada', $fila->estatus);
        $this->assertSame('Bases actualizadas.', $fila->bases);
    }

    public function testEditarConvocatoriaInexistenteLanzaCuatroCuatro(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->comoAdmin()->get('/admin/convocatorias/editar/999999');
    }

    // ─── BAJA ──────────────────────────────────────────────────────

    public function testEliminarSoloEstaAbiertoAlAdministrador(): void
    {
        $id = $this->sembrarConvocatoria();

        // El operador de ventanilla puede publicar y editar, no borrar.
        $response = $this->comoOperador()->post('/admin/convocatorias/eliminar/' . $id);
        $response->assertRedirect();

        $this->assertSame(1, $this->db->table('convocatorias')->where('id', $id)->countAllResults());
    }

    public function testEliminarQuitaLaConvocatoriaSinPostulaciones(): void
    {
        $id = $this->sembrarConvocatoria();

        $response = $this->comoAdmin()->post('/admin/convocatorias/eliminar/' . $id);
        $response->assertRedirect();

        $this->assertSame(0, $this->db->table('convocatorias')->where('id', $id)->countAllResults());
    }

    public function testNoSePuedeEliminarUnaConvocatoriaConPostulaciones(): void
    {
        $id = $this->sembrarConvocatoria();

        $solicitudModel = new SolicitudModel();
        $solicitudModel->insert([
            'folio'           => FolioGenerator::generar(),
            'tramite'         => 'UR-TT-T-01',
            'ciudadano_id'    => $this->ciudadanoId,
            'convocatoria_id' => $id,
            'estatus'         => 'Recibido',
            'monto'           => 9055.20,
            'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);

        $response = $this->comoAdmin()->post('/admin/convocatorias/eliminar/' . $id);
        $response->assertRedirect();

        $this->assertSame(1, $this->db->table('convocatorias')->where('id', $id)->countAllResults(),
            'Se eliminó una convocatoria que ya tenía expediente postulado.');
    }

    // ─── EVALUACIÓN Y BASES ────────────────────────────────────────

    public function testEvaluacionDeConvocatoriaInexistenteLanzaCuatroCuatro(): void
    {
        // Antes esta ruta mostraba otra convocatoria o la página "sin datos", que
        // escondía errores de esquema; ahora responde 404 sin disfrazarse.
        $this->expectException(PageNotFoundException::class);
        $this->comoAdmin()->get('/admin/convocatorias/999999/evaluacion');
    }

    public function testEvaluacionMuestraElCuadroComparativo(): void
    {
        $id = $this->sembrarConvocatoria(['titulo' => 'Convocatoria Con Cuadro']);

        $response = $this->comoAdmin()->get('/admin/convocatorias/' . $id . '/evaluacion');
        $response->assertStatus(200);
        $response->assertSee('Cuadro Comparativo de Solicitantes');
    }

    public function testBasesLanzaCuatroCuatroCuandoNoHayPdf(): void
    {
        $id = $this->sembrarConvocatoria();

        $this->expectException(PageNotFoundException::class);
        $this->comoAdmin()->get('/admin/convocatorias/' . $id . '/bases');
    }

    // ─── ENLACES DEL PANEL ─────────────────────────────────────────

    public function testElPanelYaNoApuntaFijoALaConvocatoriaUno(): void
    {
        // La barra lateral, el dashboard y el desglose apuntaban a
        // /admin/convocatorias/1/evaluacion aunque la convocatoria 1 no existiera.
        $body = $this->comoAdmin()->get('/admin/dashboard')->getBody();

        $this->assertStringNotContainsString('/admin/convocatorias/1/evaluacion', $body);
        $this->assertStringContainsString('/admin/convocatorias', $body);
    }

    public function testLaEvaluacionVuelveAlListadoDeConvocatorias(): void
    {
        $id = $this->sembrarConvocatoria();

        $body = $this->comoAdmin()->get('/admin/convocatorias/' . $id . '/evaluacion')->getBody();

        $this->assertStringContainsString('/admin/convocatorias', $body);
        $this->assertStringNotContainsString('/admin/convocatorias/1/evaluacion', $body);
    }

    // ─── UR-01 EN VENTANILLA ───────────────────────────────────────

    public function testFormularioPresencialListaLaConvocatoriaVigente(): void
    {
        // Regresión del filtro con 'vigente' en minúsculas: el estatus se guarda
        // como 'Vigente' y Postgres distingue mayúsculas, así que la lista salía
        // vacía aunque hubiera convocatoria abierta.
        $this->sembrarConvocatoria(['titulo' => 'Convocatoria Visible En Ventanilla']);
        $this->db->table('tarifas')->insert([
            'tramite'             => 'UR-TT-T-01',
            'criterio'            => 'base',
            'monto'               => 9055.20,
            'vigente_desde'       => date('Y-m-d', strtotime('-1 year')),
            'vigente_hasta'       => date('Y-m-d', strtotime('+1 year')),
            'placeholder_oficial' => 0,
            'created_at'          => date('Y-m-d H:i:s'),
        ]);

        $response = $this->comoAdmin()->get('/admin/solicitudes/nueva/UR-TT-T-01?ciudadano_id=' . $this->ciudadanoId);
        $response->assertStatus(200);
        $response->assertSee('Convocatoria Visible En Ventanilla');
    }

    public function testFormularioPresencialAvisaSiNoHayConvocatoriasAbiertas(): void
    {
        $this->sembrarConvocatoria([
            'titulo'                  => 'Convocatoria Fuera De Ventana',
            'periodo_registro_inicio' => date('Y-m-d', strtotime('+5 days')),
            'periodo_registro_fin'    => date('Y-m-d', strtotime('+35 days')),
        ]);

        $response = $this->comoAdmin()->get('/admin/solicitudes/nueva/UR-TT-T-01?ciudadano_id=' . $this->ciudadanoId);
        $response->assertStatus(200);
        $response->assertSee('No hay convocatorias abiertas hoy');
        $response->assertDontSee('Convocatoria Fuera De Ventana');
    }

    public function testElCiudadanoNoAccedeAlasConvocatorias(): void
    {
        $response = $this->withSession([
            'user_id'  => $this->ciudadanoId,
            'username' => 'ciud_conv',
            'roles'    => ['ciudadano'],
        ])->get('/admin/convocatorias');

        $response->assertRedirect();
    }
}
