<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Models\AgenteTokenModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Sesiones de la app de agentes de tránsito.
 *
 * Dos razones para existir esta pantalla: que cada tarjeta de sesiones del
 * desglose abra un listado con exactamente su cifra, y que el administrador pueda
 * auditar los accesos móviles sin que nunca se fugue el secreto del token.
 */
class SesionesAppAdminTest extends DatabaseTestCase
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

        $this->adminId    = $this->crearUsuario('sis_admin', 'administrador');
        $this->operadorId = $this->crearUsuario('sis_op', 'operador_ventanilla');
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

    private function crearAgente(string $placa, bool $activo = true, string $sector = 'Sector Centro'): int
    {
        $this->db->table('agentes_transito')->insert([
            'placa'           => $placa,
            'nombre_completo' => 'Oficial ' . $placa,
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'rol'             => 'operativo',
            'sector'          => $sector,
            'activo'          => $activo ? 1 : 0,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Token tal y como lo deja AgenteTokenService::emitir(). Devuelve el hash para
     * poder comprobar que nunca aparece en la respuesta.
     */
    private function crearSesion(int $agenteId, string $expiraEn, ?string $revocadoEn = null, ?string $ultimoUsoEn = null): string
    {
        static $semilla = 0;
        $semilla++;

        $hash = hash('sha256', 'hash-de-prueba-' . $semilla);

        $this->db->table('agentes_transito_tokens')->insert([
            'agente_id'     => $agenteId,
            'token_hash'    => $hash,
            'expira_en'     => $expiraEn,
            'revocado_en'   => $revocadoEn,
            'ultimo_uso_en' => $ultimoUsoEn,
        ]);

        return $hash;
    }

    private function comoAdmin(): self
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'sis_admin', 'roles' => ['administrador']]);
    }

    /**
     * Una sesión de cada estado: vigente, caducada sin cerrar y revocada.
     *
     * @return array<string, string> Hash de cada una, por clave.
     */
    private function sembrarTresSesiones(): array
    {
        $vigente  = $this->crearAgente('AG-VIG');
        $caducada = $this->crearAgente('AG-CAD');
        $revocada = $this->crearAgente('AG-REV');

        return [
            'vigentes'  => $this->crearSesion($vigente, date('Y-m-d H:i:s', time() + 3600), null, date('Y-m-d H:i:s', time() - 60)),
            'vencidas'  => $this->crearSesion($caducada, date('Y-m-d H:i:s', time() - 3600)),
            'revocadas' => $this->crearSesion($revocada, date('Y-m-d H:i:s', time() + 3600), date('Y-m-d H:i:s', time() - 10)),
        ];
    }

    // ------------------------------------------------------------------
    // Acceso
    // ------------------------------------------------------------------

    public function testSinSesionNoSePuedenVerLasSesiones(): void
    {
        $this->get('/admin/sesiones-app')->assertRedirect();
    }

    public function testElCiudadanoNoAccedeAlRegistroDeAccesos(): void
    {
        $ciudadano = $this->crearUsuario('sis_ciudadano', 'ciudadano');

        $this->withSession(['user_id' => $ciudadano, 'username' => 'sis_ciudadano', 'roles' => ['ciudadano']])
            ->get('/admin/sesiones-app')
            ->assertRedirect();
    }

    public function testElAdministradorAbreElRegistroYVeaSuAgente(): void
    {
        $this->sembrarTresSesiones();

        $body = $this->comoAdmin()->get('/admin/sesiones-app')->getBody();

        self::assertStringContainsString('Oficial AG-VIG', $body);
        self::assertStringContainsString('AG-CAD', $body);
        // La placa lleva a las boletas de ese agente, que sí admiten el filtro.
        self::assertStringContainsString('/admin/app-transito/boletas?placa=AG-REV', $body);
    }

    public function testLaVentanillaTambienPuedeRevisarLosAccesos(): void
    {
        $agente = $this->crearAgente('AG-OP');
        $this->crearSesion($agente, date('Y-m-d H:i:s', time() + 3600));

        $response = $this->withSession(['user_id' => $this->operadorId, 'username' => 'sis_op', 'roles' => ['operador_ventanilla']])
            ->get('/admin/sesiones-app');

        $response->assertStatus(200);
        $response->assertSee('AG-OP');
    }

    // ------------------------------------------------------------------
    // Seguridad: el secreto de acceso no se lista
    // ------------------------------------------------------------------

    /**
     * El hash es lo que autoriza a la app móvil; ni la tabla, ni las tarjetas del
     * teléfono, ni los atributos de los enlaces pueden reproducirlo.
     */
    public function testElHashDelTokenNuncaSaleEnLaPantalla(): void
    {
        $hashes = $this->sembrarTresSesiones();

        $cuerpos = [
            $this->comoAdmin()->get('/admin/sesiones-app')->getBody(),
            $this->comoAdmin()->get('/admin/sesiones-app?estado=vigentes')->getBody(),
            $this->comoAdmin()->get('/admin/sesiones-app?q=' . rawurlencode('AG-VIG'))->getBody(),
        ];

        foreach ($cuerpos as $i => $body) {
            foreach ($hashes as $hash) {
                self::assertStringNotContainsString($hash, $body, 'La sesión filtrada ' . $i . ' filtró el hash.');
                // Tampoco medio hash copiado: cualquier prefijo largo sería usable.
                self::assertStringNotContainsString(substr($hash, 0, 20), $body);
            }
        }
    }

    // ------------------------------------------------------------------
    // Cada cifra enlazada reproduce su propio conjunto
    // ------------------------------------------------------------------

    public function testCadaEstadoFiltraExactamenteLoQueAnuncia(): void
    {
        $this->sembrarTresSesiones();
        $global = (new AgenteTokenModel())->resumenEstados();

        self::assertSame(3, $global['total']);
        self::assertSame(1, $global['vigentes']);
        self::assertSame(1, $global['vencidas']);
        self::assertSame(1, $global['revocadas']);

        foreach (['' => 3, 'vigentes' => 1, 'vencidas' => 1, 'revocadas' => 1] as $estado => $cifra) {
            $modelado = (new AgenteTokenModel())->aplicarFiltros(['estado' => (string) $estado])->countAllResults();
            self::assertSame($cifra, $modelado, 'El filtro ' . $estado . ' no coincide con el resumen.');

            $consulta = $estado === '' ? '' : '?estado=' . $estado;
            $body     = $this->comoAdmin()->get('/admin/sesiones-app' . $consulta)->getBody();
            self::assertStringContainsString($cifra . ' de 3', $body, 'El encabezado de ' . $estado . ' miente.');
        }
    }

    /**
     * Revocada manda sobre el vencimiento: una sesión cerrada a tiempo no puede
     * contarse como vigente solo porque su fecha de caducidad aún no llega.
     */
    public function testUnaSesionRevocadaNoCuentaComoVigenteAunqueNoHayaVencido(): void
    {
        $agente = $this->crearAgente('AG-Doble');
        $this->crearSesion($agente, date('Y-m-d H:i:s', time() + 86400), date('Y-m-d H:i:s', time() - 5));

        $global = (new AgenteTokenModel())->resumenEstados();
        self::assertSame(0, $global['vigentes']);
        self::assertSame(1, $global['revocadas']);

        $vigentes = $this->comoAdmin()->get('/admin/sesiones-app?estado=vigentes')->getBody();
        self::assertStringContainsString('0 de 1', $vigentes);
        self::assertStringNotContainsString('AG-Doble', $vigentes);

        $revocadas = $this->comoAdmin()->get('/admin/sesiones-app?estado=revocadas')->getBody();
        self::assertStringContainsString('AG-Doble', $revocadas);
    }

    /**
     * Los chips de «agente vigente» se muestran pase lo que pase con el filtro, así que
     * la prueba de exclusión mira la fila del listado (enlace a sus boletas), no el nombre.
     */
    public function testElFiltroPorPlacaDejaFueraLosDemasAgentes(): void
    {
        $this->sembrarTresSesiones();

        $body = $this->comoAdmin()->get('/admin/sesiones-app?placa=AG-CAD')->getBody();

        self::assertStringContainsString('1 de 3', $body);
        self::assertStringContainsString('/admin/app-transito/boletas?placa=AG-CAD', $body);
        self::assertStringNotContainsString('/admin/app-transito/boletas?placa=AG-VIG', $body);
    }

    public function testLaBusquedaAceptaNombreYSector(): void
    {
        $norte = $this->crearAgente('AG-NORTE', true, 'Sector Norte');
        $this->crearSesion($norte, date('Y-m-d H:i:s', time() + 3600));
        $centro = $this->crearAgente('AG-CENTRO', true, 'Sector Centro');
        $this->crearSesion($centro, date('Y-m-d H:i:s', time() + 3600));

        $porSector = $this->comoAdmin()->get('/admin/sesiones-app?q=' . rawurlencode('Sector Norte'))->getBody();
        self::assertStringContainsString('/admin/app-transito/boletas?placa=AG-NORTE', $porSector);
        self::assertStringNotContainsString('/admin/app-transito/boletas?placa=AG-CENTRO', $porSector);

        $porNombre = $this->comoAdmin()->get('/admin/sesiones-app?q=' . rawurlencode('Oficial AG-CENTRO'))->getBody();
        self::assertStringContainsString('/admin/app-transito/boletas?placa=AG-CENTRO', $porNombre);
        self::assertStringNotContainsString('/admin/app-transito/boletas?placa=AG-NORTE', $porNombre);
    }

    public function testUnAgenteDadoDeBajaSeAvisayNoSeInventaSuNombre(): void
    {
        $baja = $this->crearAgente('AG-BAJA', false);
        $this->crearSesion($baja, date('Y-m-d H:i:s', time() + 3600));

        $body = $this->comoAdmin()->get('/admin/sesiones-app')->getBody();

        // El acento sale como entidad HTML, así que se busca el trozo sin tilde.
        self::assertStringContainsString('Agente dado de baja', $body);
    }

    // ------------------------------------------------------------------
    // Validación de los parámetros de la URL
    // ------------------------------------------------------------------

    public function testUnParametroMalFormadoNoRompeElRegistro(): void
    {
        $this->sembrarTresSesiones();

        $body = $this->comoAdmin()
            ->get('/admin/sesiones-app?estado=' . rawurlencode("vigentes' OR 1=1 --")
                . '&placa=' . rawurlencode('AG-CAD; DROP')
                . '&orden=' . rawurlencode('token_hash')
                . '&dir=' . rawurlencode('asc; 1'))
            ->getBody();

        // Se descartan los tres filtros raros y el listado sigue mostrando todo.
        self::assertStringContainsString('3 de 3', $body);
        self::assertStringContainsString('AG-VIG', $body);
        self::assertStringContainsString('AG-CAD', $body);
    }

    public function testElOrdenPorVencimientoRespetaLaDireccion(): void
    {
        $manana = $this->crearAgente('AG-MANANA');
        $pasado = $this->crearAgente('AG-PASADO');
        $this->crearSesion($manana, date('Y-m-d H:i:s', time() + 86400));
        $this->crearSesion($pasado, date('Y-m-d H:i:s', time() - 86400));

        $desc = $this->comoAdmin()->get('/admin/sesiones-app?orden=expira_en&dir=desc')->getBody();
        self::assertLessThan(
            strpos($desc, '/admin/app-transito/boletas?placa=AG-PASADO'),
            (int) strpos($desc, '/admin/app-transito/boletas?placa=AG-MANANA')
        );

        $asc = $this->comoAdmin()->get('/admin/sesiones-app?orden=expira_en&dir=asc')->getBody();
        self::assertLessThan(
            strpos($asc, '/admin/app-transito/boletas?placa=AG-MANANA'),
            (int) strpos($asc, '/admin/app-transito/boletas?placa=AG-PASADO')
        );
    }

    /**
     * La cifra «por agente» de la pantalla debe sumar lo mismo que la tarjeta de
     * vigentes: si no, el administrador contaría accesos dos veces.
     */
    public function testLosChipsPorAgenteRepartenLasSesionesVigentes(): void
    {
        $doble = $this->crearAgente('AG-DOBLE');
        $otro  = $this->crearAgente('AG-OTRO');
        $this->crearSesion($doble, date('Y-m-d H:i:s', time() + 3600));
        $this->crearSesion($doble, date('Y-m-d H:i:s', time() + 7200));
        $this->crearSesion($otro, date('Y-m-d H:i:s', time() + 3600));
        $this->crearSesion($otro, date('Y-m-d H:i:s', time() - 3600));

        $porAgente = (new AgenteTokenModel())->resumenPorAgenteVigente();
        $totales   = array_column($porAgente, 'total', 'placa');

        self::assertSame(2, $totales['AG-DOBLE']);
        self::assertSame(1, $totales['AG-OTRO']);
        self::assertSame(
            (new AgenteTokenModel())->resumenEstados()['vigentes'],
            array_sum($totales),
            'Los chips por agente deben sumar justo las sesiones vigentes.'
        );

        $body = $this->comoAdmin()->get('/admin/sesiones-app')->getBody();
        self::assertStringContainsString('/admin/sesiones-app?placa=AG-DOBLE', $body);
    }
}
