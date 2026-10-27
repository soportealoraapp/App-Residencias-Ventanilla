<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

class AppTransitoAdminTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private int $adminId;
    private int $boletaId;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Libraries\PermisosOperador::reiniciarCache();

        $this->db->table('roles')->insert(['nombre' => 'administrador', 'descripcion' => 'admin']);
        $rolId = (int) $this->db->insertID();
        $this->db->table('users')->insert([
            'username'      => 'admin01',
            'email'         => 'admin01@example.com',
            'password_hash' => password_hash('secreto123', PASSWORD_DEFAULT),
            'activo'        => 1,
        ]);
        $this->adminId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert(['user_id' => $this->adminId, 'role_id' => $rolId]);

        $this->db->table('agentes_transito')->insert([
            'placa'           => 'AGT-204',
            'nombre_completo' => "Oficial O'Brien",
            'password_hash'   => password_hash('1234', PASSWORD_BCRYPT),
            'rol'             => 'operativo',
            'sector'          => 'Centro',
            'activo'          => 0,
        ]);

        $this->db->table('boletas_infracciones')->insert([
            'folio'            => 'BOLETA-TEST-1',
            'agente_placa'     => 'AGT-204',
            'agente_nombre'    => 'Oficial',
            'fecha_infraccion' => '2026-10-01',
            'hora_infraccion'  => '10:15:00',
            'lugar'            => 'Centro',
            'vehiculo_tipo'    => 'particular',
            'estado'           => 'recibida',
        ]);
        $this->boletaId = (int) $this->db->insertID();
    }

    private function comoAdmin(): self
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'admin01', 'roles' => ['administrador']]);
    }

    public function testVistasDeAppTransitoRenderizan(): void
    {
        $this->comoAdmin()->get('admin/app-transito')->assertOK();
        $this->comoAdmin()->get('admin/app-transito/boletas')->assertOK();
        $this->comoAdmin()->get('admin/app-transito/boletas/editar/' . $this->boletaId)->assertOK();
    }

    public function testEditarBoletaRecibidaCorrigeDatosYConservaEstadoYProtegidos(): void
    {
        $antes = $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow();

        $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, [
            'fecha_infraccion' => '2026-10-01',
            'hora_infraccion'  => '10:15',
            'lugar'            => 'Av. Hidalgo',
            'vehiculo_tipo'    => 'particular',
            'vehiculo_placas'  => 'gtc441e',
            'folio'            => 'OTRO-FOLIO',
            'agente_placa'     => 'OTRA',
            'falta_monto_min_uma' => '99',
        ])->assertRedirectTo(site_url('admin/app-transito/boletas'));

        $boleta = $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow();
        $this->assertSame('recibida', $boleta->estado);
        $this->assertSame('Av. Hidalgo', $boleta->lugar);
        $this->assertSame('GTC441E', $boleta->vehiculo_placas);
        $this->assertSame($antes->folio, $boleta->folio);
        $this->assertSame($antes->agente_placa, $boleta->agente_placa);
        $this->assertEquals($antes->falta_monto_min_uma, $boleta->falta_monto_min_uma);

        $audit = $this->db->table('auditoria')->where('entidad', 'boletas_infracciones')->where('accion', 'editar')->get()->getRow();
        $this->assertNotNull($audit);
        $this->assertSame($this->boletaId, (int) $audit->entidad_id);
    }

    public function testFormularioNoTieneSelectorDeEstadoYMuestraEstadoComoTexto(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas/editar/' . $this->boletaId)->getBody();
        $this->assertStringNotContainsString('name="estado"', $html);
        $this->assertStringContainsString('id="estado-actual"', $html);
        $this->assertStringContainsString('Recibida', $html);
    }

    public function testListadoConservaFiltrosPorEstado(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        foreach (['recibida', 'procesada', 'calificada', 'pagada', 'cancelada', 'archivada'] as $estado) {
            $this->assertStringContainsString('/admin/app-transito/boletas?estado=' . $estado, $html);
        }
    }

    public function testPostConEstadoSeRechazaCompletoSinCambiosParciales(): void
    {
        foreach (['estado', 'status', 'Estatus'] as $clave) {
            $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, [
                $clave             => 'pagada',
                'fecha_infraccion' => '2026-10-01',
                'hora_infraccion'  => '10:15',
                'lugar'            => 'Lugar manipulado',
                'vehiculo_tipo'    => 'particular',
            ])->assertRedirectTo(site_url('admin/app-transito/boletas/editar/' . $this->boletaId));

            $boleta = $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow();
            $this->assertSame('recibida', $boleta->estado);
            $this->assertSame('Centro', $boleta->lugar);
        }
        $this->assertSame(0, $this->db->table('auditoria')->where('accion', 'editar')->countAllResults());
    }

    /** @dataProvider estadosNoEditables */
    public function testBoletaFueraDeRecibidaNoSeEditaPorInterfazNiPorPeticion(string $estado): void
    {
        $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->update(['estado' => $estado]);

        $this->comoAdmin()->get('admin/app-transito/boletas/editar/' . $this->boletaId)
            ->assertRedirectTo(site_url('admin/app-transito/boletas'));
        $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, $this->datosValidos())
            ->assertRedirectTo(site_url('admin/app-transito/boletas'));

        $boleta = $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow();
        $this->assertSame($estado, $boleta->estado);
        $this->assertSame('Centro', $boleta->lugar);
    }

    /** @return array<string, array{string}> */
    public static function estadosNoEditables(): array
    {
        return [
            'procesada' => ['procesada'], 'calificada' => ['calificada'], 'pagada' => ['pagada'],
            'cancelada' => ['cancelada'], 'archivada' => ['archivada'],
        ];
    }

    public function testRecibidaConCalificacionOCobroAsociadoNoSeEdita(): void
    {
        $cobroId = $this->crearCobro();
        $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, $this->datosValidos())
            ->assertRedirectTo(site_url('admin/app-transito/boletas'));
        $this->assertSame('Centro', $this->boleta()->lugar, 'cobro asociado');

        $this->db->table('cobros')->where('id', $cobroId)->delete();
        $this->crearCalificacion(null);
        $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, $this->datosValidos())
            ->assertRedirectTo(site_url('admin/app-transito/boletas'));
        $this->assertSame('Centro', $this->boleta()->lugar, 'calificación asociada');
        $this->assertSame('recibida', $this->boleta()->estado);
    }

    /** Secuencial: demuestra la revalidación con datos frescos, no una carrera real. */
    public function testFormularioAbiertoAntesDeCalificarNoSobrescribeBoletaAvanzada(): void
    {
        $this->comoAdmin()->get('admin/app-transito/boletas/editar/' . $this->boletaId)->assertOK();

        $cobroId = $this->crearCobro();
        $this->crearCalificacion($cobroId);
        $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->update(['estado' => 'calificada']);

        $this->comoAdmin()->post('admin/app-transito/boletas/actualizar/' . $this->boletaId, $this->datosValidos())
            ->assertRedirectTo(site_url('admin/app-transito/boletas'));

        $this->assertSame('Centro', $this->boleta()->lugar);
        $this->assertSame('calificada', $this->boleta()->estado);
    }

    public function testAccionesDeRecibidaSegunRol(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('Corregir datos', $html);
        $this->assertStringNotContainsString('Calificar boleta', $html);
        $this->assertStringNotContainsString('/juez/boletas/', $html);

        $html = (string) $this->conSesion(['administrador', 'juez_calificador'])->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('Calificar boleta', $html);
        $this->assertStringContainsString('/juez/boletas/' . $this->boletaId, $html);
    }

    public function testAccionesDeCalificadaEnlazanElCobroVinculado(): void
    {
        $ajeno = $this->crearCobro(); // cobro de otra boleta no debe usarse
        $this->db->table('boletas_infracciones')->insert([
            'folio' => 'BOLETA-TEST-2', 'agente_placa' => 'AGT-204', 'fecha_infraccion' => '2026-10-02',
            'hora_infraccion' => '10:00:00', 'lugar' => 'Otro', 'vehiculo_tipo' => 'particular', 'estado' => 'calificada',
        ]);
        $segunda = (int) $this->db->insertID();
        $propio = (new \App\Libraries\CobroService())->crearDesdeBoleta($segunda, 500.0, $this->adminId);
        $this->crearCalificacion($propio, $segunda);

        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('/admin/cobros/' . $propio, $html);
        $this->assertStringNotContainsString('/admin/cobros/' . $ajeno . '"', $html);
        $this->assertStringNotContainsString('Calificar boleta', $html);
        $this->assertStringNotContainsString('bi-pencil', $html);

        $html = (string) $this->conSesion(['administrador', 'juez_calificador'])->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('/admin/cobros/' . $propio, $html);
    }

    public function testCalificadaSinCobroOConCobroCanceladoExplicaSinEnlace(): void
    {
        $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->update(['estado' => 'calificada']);
        $this->crearCalificacion(null);
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('no tiene un cobro vinculado', $html);
        $this->assertDoesNotMatchRegularExpression('#/admin/cobros/\d+#', $html);

        $cobroId = $this->crearCobro();
        $this->db->table('boleta_calificaciones')->where('boleta_id', $this->boletaId)->update(['cobro_id' => $cobroId]);
        $this->db->table('cobros')->where('id', $cobroId)->update(['estatus' => 'cancelado']);
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('cancelado. No se genera otro ni se recalifica', $html);
        $this->assertStringNotContainsString('/admin/cobros/' . $cobroId, $html);
        $this->assertSame(1, $this->db->table('cobros')->where('boleta_id', $this->boletaId)->countAllResults());
    }

    public function testPagadaMuestraCobroYHojaSinEdicion(): void
    {
        $cobroId = $this->crearCobro();
        $this->crearCalificacion($cobroId);
        $this->db->table('cobros')->where('id', $cobroId)->update(['estatus' => 'aprobado']);
        $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->update(['estado' => 'pagada']);

        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('/admin/cobros/' . $cobroId, $html);
        $this->assertStringContainsString('/documentos/hoja-pago/boleta/' . $this->boletaId, $html);
        $this->assertStringNotContainsString('Corregir datos', $html);
    }

    public function testOperadorSinPermisoDeCobrosNoVeEnlaceAlCobro(): void
    {
        $cobroId = $this->crearCobro();
        $this->crearCalificacion($cobroId);
        $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->update(['estado' => 'calificada']);

        $permisos = array_fill_keys(\App\Libraries\PermisosOperador::claves(), true);
        $permisos['cobros'] = false;
        \App\Libraries\PermisosOperador::guardar($permisos);

        $html = (string) $this->conSesion(['operador_ventanilla'])->get('admin/app-transito/boletas')->getBody();
        $this->assertStringNotContainsString('/admin/cobros/' . $cobroId, $html);

        $permisos['cobros'] = true;
        \App\Libraries\PermisosOperador::guardar($permisos);
        $html = (string) $this->conSesion(['operador_ventanilla'])->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('/admin/cobros/' . $cobroId, $html);
        \App\Libraries\PermisosOperador::reiniciarCache();
    }

    public function testDetalleUsaDialogoFueraDeLaTarjetaYTablaConBotonCorrecto(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('data-bs-target="#modalBoleta' . $this->boletaId . '"', $html);
        $this->assertSame(1, substr_count($html, 'id="modalBoleta' . $this->boletaId . '"'));
        $this->assertStringContainsString('data-bs-dismiss="modal"', $html);
        $this->assertGreaterThan(strrpos($html, '</table>'), strpos($html, 'id="modalBoleta' . $this->boletaId . '"'));
        $this->assertStringContainsString('BOLETA-TEST-1', $html);
        $this->assertSame(1, substr_count($html, 'bootstrap.bundle'));
    }

    // ─── Filtros del listado ──────────────────────────────────────────────────

    /** Texto de la etiqueta <a> de un filtro, para comprobar su href y su marca. */
    private function chip(string $html, string $texto): string
    {
        $this->assertSame(1, preg_match('#<a href="([^"]*)" class="dgs-nav-item([^"]*)"([^>]*)>\s*(?:<i[^>]*></i>)?\s*' . preg_quote($texto, '#') . '\s*</a>#u', $html, $m), "No se encontró el filtro $texto");

        return html_entity_decode($m[1]) . '|' . trim($m[2]) . '|' . trim($m[3]);
    }

    /** @return list<string> Textos de los filtros marcados como seleccionados. */
    private function chipsActivos(string $html): array
    {
        preg_match_all('#class="dgs-nav-item is-active" aria-current="true"[^>]*>\s*(?:<i[^>]*></i>)?\s*([^<]+?)\s*</a>#u', $html, $m);

        return $m[1];
    }

    private function insertarBoleta(string $folio, string $estado, string $placa = 'AGT-204', string $fecha = '2026-10-02'): int
    {
        $this->db->table('boletas_infracciones')->insert([
            'folio' => $folio, 'agente_placa' => $placa, 'fecha_infraccion' => $fecha,
            'hora_infraccion' => '09:00:00', 'lugar' => 'Calle', 'vehiculo_tipo' => 'particular', 'estado' => $estado,
        ]);

        return (int) $this->db->insertID();
    }

    public function testCadaEstadoFiltraYQuedaMarcadoComoUnicoSeleccionado(): void
    {
        $this->insertarBoleta('FOLIO-CALIFICADA-X', 'calificada');

        foreach (['recibida' => ['BOLETA-TEST-1', 'FOLIO-CALIFICADA-X'], 'calificada' => ['FOLIO-CALIFICADA-X', 'BOLETA-TEST-1']] as $estado => [$presente, $ausente]) {
            $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?estado=' . $estado)->getBody();
            $this->assertStringContainsString($presente, $html);
            $this->assertStringNotContainsString($ausente, $html);
            $this->assertSame([ucfirst($estado)], $this->chipsActivos($html), "Solo $estado debe estar marcado");
            $this->assertStringContainsString('<input type="hidden" name="estado" value="' . $estado . '">', $html);
        }
    }

    public function testSinFiltrosTodasQuedaMarcadaYElFormularioNoLlevaEstadoOculto(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertSame(['Todas'], $this->chipsActivos($html));
        $this->assertStringNotContainsString('name="estado"', $html);
        $this->assertStringNotContainsString('name="falta"', $html);
    }

    public function testTodasQuitaElEstadoYConservaBusquedaAgenteYFecha(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?estado=calificada&q=ABC&placa=AGT-204&fecha=2026-10-01')->getBody();
        [$href, $clase] = explode('|', $this->chip($html, 'Todas'));
        parse_str((string) parse_url($href, PHP_URL_QUERY), $query);
        $this->assertSame(['q' => 'ABC', 'placa' => 'AGT-204', 'fecha' => '2026-10-01'], $query);
        $this->assertStringNotContainsString('is-active', $clase, 'Con un estado activo Todas no se marca');

        // Seguir el enlace de Todas deja a Todas seleccionada y sin estado residual.
        $html = (string) $this->comoAdmin()->get($href)->getBody();
        $this->assertSame(['Todas'], $this->chipsActivos($html));
        $this->assertStringNotContainsString('name="estado"', $html);
    }

    public function testFiltrarConservaEstadoYElEstadoInventadoSeIgnora(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?estado=pagada&q=algo')->getBody();
        $this->assertStringContainsString('<input type="hidden" name="estado" value="pagada">', $html);

        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?estado=inventado')->getBody();
        $this->assertStringContainsString('BOLETA-TEST-1', $html);
        $this->assertSame(['Todas'], $this->chipsActivos($html));
        $this->assertStringNotContainsString('inventado', $html);

        $this->comoAdmin()->get('admin/app-transito/boletas?estado[]=recibida&q[]=x')->assertOK();
    }

    public function testLasDeHoyAlternaLaFechaSinDuplicarParametros(): void
    {
        $hoy = date('Y-m-d');
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?estado=recibida')->getBody();
        [$href] = explode('|', $this->chip($html, 'Las de hoy'));
        parse_str((string) parse_url($href, PHP_URL_QUERY), $query);
        $this->assertEquals(['estado' => 'recibida', 'fecha' => $hoy], $query);
        $this->assertSame(1, substr_count($href, 'fecha='));

        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?fecha=' . $hoy)->getBody();
        $this->assertContains('Las de hoy', $this->chipsActivos($html));
        [$href] = explode('|', $this->chip($html, 'Las de hoy'));
        $this->assertSame('/admin/app-transito/boletas', $href, 'Pulsarlo de nuevo quita el filtro');
    }

    public function testLimpiarQuitaTodosLosFiltros(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas?estado=recibida&q=x&placa=AGT-204&fecha=2026-10-01')->getBody();
        $this->assertStringContainsString('<a href="/admin/app-transito/boletas" class="btn btn-outline-secondary" title="Limpiar">', $html);
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringNotContainsString('title="Limpiar"', $html);
    }

    public function testFiltrosYTablaMantienenElMarcadoDelListadoParaTodosLosAutorizados(): void
    {
        $html = (string) $this->comoAdmin()->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('class="table table-hover align-middle mb-0 table-sticky-header tabla-boletas"', $html);
        $this->assertStringContainsString('<th class="col-acciones text-end">Acciones</th>', $html);
        $this->assertStringContainsString('class="boleta-folio', $html);
        $this->assertStringContainsString('copiarFolio(', $html);
    }

    // ─── Roles y acceso al listado ────────────────────────────────────────────

    /** @param list<string> $roles */
    private function usuarioConRoles(string $username, array $roles): self
    {
        $this->db->table('users')->insert([
            'username' => $username, 'email' => $username . '@example.com',
            'password_hash' => password_hash('secreto123', PASSWORD_DEFAULT), 'activo' => 1,
        ]);
        $userId = (int) $this->db->insertID();
        foreach ($roles as $rol) {
            $fila = $this->db->table('roles')->where('nombre', $rol)->get()->getRow();
            if ($fila === null) {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
                $rolId = (int) $this->db->insertID();
            } else {
                $rolId = (int) $fila->id;
            }
            $this->db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => $rolId]);
        }

        return $this->withSession(['user_id' => $userId, 'username' => $username, 'roles' => $roles]);
    }

    /** @param array<string, bool> $cambios */
    private function permisosOperador(array $cambios): void
    {
        $permisos = array_fill_keys(\App\Libraries\PermisosOperador::claves(), true);
        \App\Libraries\PermisosOperador::guardar($cambios + $permisos);
    }

    public function testOperadorConPermisoVeElListadoConSusFiltrosYRutasPropias(): void
    {
        $this->permisosOperador(['app_transito_boletas' => true, 'app_transito_config' => false]);
        $html = (string) $this->usuarioConRoles('oper01', ['operador_ventanilla'])->get('admin/app-transito/boletas?estado=recibida')->getBody();

        $this->assertSame(['Recibida'], $this->chipsActivos($html));
        $this->assertStringNotContainsString('Volver a Configuraci', $html, 'Sin permiso de configuración no se ofrece ese enlace');
        $this->assertStringContainsString('Corregir datos', $html);
        $this->assertStringNotContainsString('/juez/', $html);
        $this->assertStringNotContainsString('Eliminar', $html);
    }

    public function testOperadorSinPermisoDeBoletasNoAccedeAlListado(): void
    {
        $this->permisosOperador(['app_transito_boletas' => false]);
        $this->usuarioConRoles('oper02', ['operador_ventanilla'])->get('admin/app-transito/boletas')
            ->assertRedirectTo('/admin/dashboard');
    }

    public function testOperadorConPermisoDeConfiguracionVeElEnlaceDeRegreso(): void
    {
        $this->permisosOperador(['app_transito_config' => true]);
        $html = (string) $this->usuarioConRoles('oper03', ['operador_ventanilla'])->get('admin/app-transito/boletas')->getBody();
        $this->assertStringContainsString('Volver a Configuraci', $html);
    }

    public function testCiudadanoYUsuarioSinSesionNoAccedenAlListado(): void
    {
        $this->get('admin/app-transito/boletas')->assertRedirectTo('/auth/login');
        $res = $this->usuarioConRoles('ciud01', ['ciudadano'])->get('admin/app-transito/boletas');
        $res->assertRedirectTo('/');
        $this->assertStringNotContainsString('BOLETA-TEST-1', (string) $res->getBody());
    }

    public function testJuezPuroNoAbreElListadoAdministrativoYSuListadoNoTieneFiltros(): void
    {
        $this->usuarioConRoles('juez01', ['juez_calificador'])->get('admin/app-transito/boletas')->assertRedirectTo('/');

        $html = (string) $this->usuarioConRoles('juez02', ['juez_calificador'])->get('juez/boletas')->getBody();
        $this->assertStringContainsString('BOLETA-TEST-1', $html);
        $this->assertStringContainsString('class="boleta-folio', $html);
        $this->assertStringNotContainsString('dgs-nav', $html);
        $this->assertStringNotContainsString('/admin/', $html);
    }

    public function testCuentaConVariosRolesUsaEnlacesSegunSusPermisosReales(): void
    {
        $cobroId = $this->crearCobro();
        $this->crearCalificacion($cobroId);
        $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->update(['estado' => 'calificada']);

        $this->permisosOperador(['cobros' => false]);
        $html = (string) $this->usuarioConRoles('mixto01', ['operador_ventanilla', 'juez_calificador'])->get('admin/app-transito/boletas?estado=calificada')->getBody();
        $this->assertSame(['Calificada'], $this->chipsActivos($html));
        $this->assertStringContainsString('/juez/cobros/' . $cobroId, $html);
        $this->assertStringNotContainsString('/admin/cobros/' . $cobroId, $html);
    }

    /** @return array<string, string> */
    private function datosValidos(): array
    {
        return [
            'fecha_infraccion' => '2026-10-01',
            'hora_infraccion'  => '10:15',
            'lugar'            => 'Lugar nuevo',
            'vehiculo_tipo'    => 'particular',
        ];
    }

    private function boleta(): object
    {
        return $this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow();
    }

    private function crearCobro(?int $boletaId = null): int
    {
        return (new \App\Libraries\CobroService())->crearDesdeBoleta($boletaId ?? $this->boletaId, 1000.0, $this->adminId);
    }

    private function crearCalificacion(?int $cobroId, ?int $boletaId = null): void
    {
        $this->db->table('boleta_calificaciones')->insert([
            'boleta_id' => $boletaId ?? $this->boletaId, 'juez_id' => $this->adminId, 'cobro_id' => $cobroId,
            'nombre_infractor' => 'Juan Pérez', 'valor_uma' => 117.31, 'total_uma' => 10, 'monto_base' => 1173.1,
            'calificada_en' => '2026-10-01 11:00:00',
        ]);
    }

    /** @param list<string> $roles */
    private function conSesion(array $roles): self
    {
        $userId = $this->adminId;
        foreach ($roles as $rol) {
            $fila = $this->db->table('roles')->where('nombre', $rol)->get()->getRow();
            $rolId = $fila ? (int) $fila->id : (function () use ($rol): int {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
                return (int) $this->db->insertID();
            })();
            if ($this->db->table('user_roles')->where(['user_id' => $userId, 'role_id' => $rolId])->countAllResults() === 0) {
                $this->db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => $rolId]);
            }
        }

        return $this->withSession(['user_id' => $userId, 'username' => 'admin01', 'roles' => $roles]);
    }

    public function testAgenteConBoletasNoSeElimina(): void
    {
        $agente = $this->db->table('agentes_transito')->where('placa', 'AGT-204')->get()->getRow();

        $this->comoAdmin()->post('admin/app-transito/agentes/eliminar/' . $agente->id);

        $this->assertNotNull($this->db->table('agentes_transito')->where('id', $agente->id)->get()->getRow());
    }

    public function testEditarAgenteConservaEstadoInactivo(): void
    {
        $agente = $this->db->table('agentes_transito')->where('placa', 'AGT-204')->get()->getRow();

        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'id'              => (string) $agente->id,
            'placa'           => 'AGT-204',
            'nombre_completo' => 'Oficial Editado',
            'sector'          => 'Norte',
            'rol'             => 'supervisor',
        ]);

        $actualizado = $this->db->table('agentes_transito')->where('id', $agente->id)->get()->getRow();
        $this->assertSame('Oficial Editado', $actualizado->nombre_completo);
        $this->assertSame(0, (int) $actualizado->activo);
    }

    public function testCrearAgenteNuevoGuardaPlacaEnMayusculasYPuedeIniciarSesion(): void
    {
        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'placa' => 'agt-901', 'nombre_completo' => 'Oficial Nuevo', 'sector' => 'Norte',
            'rol' => 'supervisor', 'password' => 'Transito2026',
        ])->assertRedirectTo(site_url('admin/app-transito'));

        $agente = $this->db->table('agentes_transito')->where('placa', 'AGT-901')->get()->getRow();
        $this->assertNotNull($agente);
        $this->assertSame(1, (int) $agente->activo);
        $this->assertTrue(password_verify('Transito2026', $agente->password_hash));
    }

    public function testPlacaDuplicadaEnMinusculasSeRechaza(): void
    {
        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'placa' => 'agt-204', 'nombre_completo' => 'Duplicado', 'rol' => 'operativo', 'password' => 'Transito2026',
        ]);

        $this->assertSame(1, $this->db->table('agentes_transito')->where('placa', 'AGT-204')->countAllResults());
        $this->assertArrayHasKey('placa', (array) session('errors'));
    }

    public function testErrorAlCrearAgenteReabreElModalConLoCapturado(): void
    {
        $this->comoAdmin()->post('admin/app-transito/agentes/guardar', [
            'placa' => 'AGT-777', 'nombre_completo' => 'Oficial Corto', 'rol' => 'operativo', 'password' => 'abc',
        ])->assertRedirectTo(site_url('admin/app-transito'));

        $this->assertTrue((bool) session('reabrir_modal_agente'));
        $this->assertSame('AGT-777', old('placa'));
        $this->assertNull($this->db->table('agentes_transito')->where('placa', 'AGT-777')->get()->getRow());
    }

    public function testEliminarBoletaLaQuita(): void
    {
        $this->comoAdmin()->post('admin/app-transito/boletas/eliminar/' . $this->boletaId);

        $this->assertNull($this->db->table('boletas_infracciones')->where('id', $this->boletaId)->get()->getRow());
    }
}
