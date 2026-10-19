<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Models\VerificacionFisicaModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Agenda de verificaciones físicas.
 *
 * La pantalla existe para que las tarjetas de verificaciones del desglose abran
 * un listado que reproduce exactamente la cifra que muestran, así que aquí se
 * prueba esa correspondencia estado por estado, igual que en el almacén de
 * documentos.
 */
class VerificacionesAdminTest extends DatabaseTestCase
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

        $this->adminId    = $this->crearUsuario('ver_admin', 'administrador');
        $this->operadorId = $this->crearUsuario('ver_op', 'operador_ventanilla');
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

    private function crearSolicitud(string $folio, string $tramite = 'UR-TT-T-02'): int
    {
        $this->db->table('solicitudes')->insert([
            'folio'           => $folio,
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->adminId,
            'estatus'         => 'En revisión',
            'monto'           => 500,
            'fecha_solicitud' => date('Y-m-d 10:00:00'),
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Cita de inspección. Las observaciones sirven de marca para saber qué filas
     * llegaron al HTML: la pantalla las imprime tal cual.
     */
    private function crearCita(int $solicitudId, string $fechaCita, ?string $resultado = null, string $observaciones = ''): void
    {
        $this->db->table('verificaciones_fisicas')->insert([
            'solicitud_id'  => $solicitudId,
            'fecha_cita'    => $fechaCita,
            'resultado'     => $resultado,
            'observaciones' => $observaciones !== '' ? $observaciones : null,
        ]);
    }

    private function comoAdmin(): self
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'ver_admin', 'roles' => ['administrador']]);
    }

    /**
     * Siembra una cita de cada tipo y devuelve las marcas para localizarlas.
     */
    private function sembrarTresCitas(): void
    {
        $futura = $this->crearSolicitud('VER-FUTURA');
        $pasada = $this->crearSolicitud('VER-PASADA');
        $dictada = $this->crearSolicitud('VER-DICTADA');

        $this->crearCita($futura, date('Y-m-d H:i:s', time() + 86400), null, 'MARCA_FUTURA');
        $this->crearCita($pasada, date('Y-m-d H:i:s', time() - 86400), '', 'MARCA_PASADA');
        $this->crearCita($dictada, date('Y-m-d H:i:s', time() - 172800), 'Aprobado', 'MARCA_DICTADA');
    }

    // ------------------------------------------------------------------
    // Acceso
    // ------------------------------------------------------------------

    public function testSinSesionNoSePuedeVerLaAgenda(): void
    {
        $this->get('/admin/verificaciones')->assertRedirect();
    }

    public function testElCiudadanoNoAccedeALaAgenda(): void
    {
        $ciudadano = $this->crearUsuario('ver_ciudadano', 'ciudadano');

        $this->withSession(['user_id' => $ciudadano, 'username' => 'ver_ciudadano', 'roles' => ['ciudadano']])
            ->get('/admin/verificaciones')
            ->assertRedirect();
    }

    public function testElAdministradorAbreLaAgendaYEnlazaCadaExpediente(): void
    {
        $this->sembrarTresCitas();

        $body = $this->comoAdmin()->get('/admin/verificaciones')->getBody();

        self::assertStringContainsString('VER-FUTURA', $body);
        // El folio abre el expediente, que es donde se captura el dictamen.
        self::assertStringContainsString('admin/solicitudes/VER-PASADA', $body);
    }

    public function testLaVentanillaTambienPuedeSeguirLasCitas(): void
    {
        $solicitud = $this->crearSolicitud('VER-VENTANILLA');
        $this->crearCita($solicitud, date('Y-m-d H:i:s', time() + 3600), null, 'cita de ventanilla');

        $response = $this->withSession(['user_id' => $this->operadorId, 'username' => 'ver_op', 'roles' => ['operador_ventanilla']])
            ->get('/admin/verificaciones');

        $response->assertStatus(200);
        $response->assertSee('VER-VENTANILLA');
    }

    // ------------------------------------------------------------------
    // Cada cifra enlazada reproduce su propio conjunto
    // ------------------------------------------------------------------

    /**
     * El encabezado imprime «filtradas de totales»; si el filtro no devolviera lo
     * que promete la tarjeta del desglose, esa línea lo delataría.
     */
    public function testCadaEstadoFiltraExactamenteLoQueAnuncia(): void
    {
        $this->sembrarTresCitas();
        $global = (new VerificacionFisicaModel())->resumenGlobal();

        self::assertSame(3, $global['total']);
        self::assertSame(2, $global['sin_dictamen']);
        self::assertSame(1, $global['con_dictamen']);
        self::assertSame(1, $global['vencidas']);
        self::assertSame(1, $global['proximas']);

        $esperado = [
            ''             => $global['total'],
            'proximas'     => $global['proximas'],
            'vencidas'     => $global['vencidas'],
            'sin_dictamen' => $global['sin_dictamen'],
            'con_dictamen' => $global['con_dictamen'],
        ];

        foreach ($esperado as $estado => $cifra) {
            $modelado = (new VerificacionFisicaModel())->aplicarFiltros(['estado' => (string) $estado])->countAllResults();
            self::assertSame($cifra, $modelado, 'El filtro ' . $estado . ' no coincide con el resumen.');

            $consulta = $estado === '' ? '' : '?estado=' . $estado;
            $body     = $this->comoAdmin()->get('/admin/verificaciones' . $consulta)->getBody();
            self::assertStringContainsString($cifra . ' de ' . $global['total'], $body, 'El encabezado de ' . $estado . ' miente.');
        }
    }

    public function testLaCitaVencidaSoloSaleDondeSePromete(): void
    {
        $this->sembrarTresCitas();

        $vencidas = $this->comoAdmin()->get('/admin/verificaciones?estado=vencidas')->getBody();
        self::assertStringContainsString('MARCA_PASADA', $vencidas);
        self::assertStringNotContainsString('MARCA_FUTURA', $vencidas);
        self::assertStringNotContainsString('MARCA_DICTADA', $vencidas);

        $proximas = $this->comoAdmin()->get('/admin/verificaciones?estado=proximas')->getBody();
        self::assertStringContainsString('MARCA_FUTURA', $proximas);
        self::assertStringNotContainsString('MARCA_PASADA', $proximas);
    }

    /**
     * El reparto por dictamen agrupa nulos y vacíos bajo «sin_dictamen»; el chip que
     * usa ese valor tiene que abrir exactamente esas mismas filas, no cero.
     */
    public function testElDictamenSinRegistroAbreLasMismasCitasQueSuTarjeta(): void
    {
        $this->sembrarTresCitas();
        $global = (new VerificacionFisicaModel())->resumenGlobal();

        $body = $this->comoAdmin()->get('/admin/verificaciones?resultado=sin_dictamen')->getBody();

        self::assertStringContainsString($global['sin_dictamen'] . ' de ' . $global['total'], $body);
        self::assertStringContainsString('MARCA_FUTURA', $body);
        self::assertStringContainsString('MARCA_PASADA', $body);
        self::assertStringNotContainsString('MARCA_DICTADA', $body);
    }

    public function testElFiltroPorDictamenCapturadoDejaFueraLasDemas(): void
    {
        $this->sembrarTresCitas();

        $body = $this->comoAdmin()->get('/admin/verificaciones?resultado=Aprobado')->getBody();

        self::assertStringContainsString('MARCA_DICTADA', $body);
        self::assertStringNotContainsString('MARCA_PASADA', $body);
    }

    public function testElFiltroPorTramiteNoMezclaOtrosNegocios(): void
    {
        $verificacion = $this->crearSolicitud('VER-T02');
        $this->crearCita($verificacion, date('Y-m-d H:i:s', time() + 3600), null, 'cita T-02');
        $plaqueo = $this->crearSolicitud('VER-T03', 'UR-TT-T-03');
        $this->crearCita($plaqueo, date('Y-m-d H:i:s', time() + 3600), null, 'cita T-03');

        $body = $this->comoAdmin()->get('/admin/verificaciones?tramite=UR-TT-T-02')->getBody();

        self::assertStringContainsString('cita T-02', $body);
        self::assertStringNotContainsString('cita T-03', $body);
    }

    /**
     * El nombre del interesado no vive en solicitudes sino en datos clave/valor;
     * buscarlo debe encontrar la cita igual que en el listado de solicitudes.
     */
    public function testLaBusquedaTambienAparecePorElNombreDelInteresado(): void
    {
        $conNombre = $this->crearSolicitud('VER-NOMBRE');
        $this->crearCita($conNombre, date('Y-m-d H:i:s', time() + 3600), null, 'observacion buscada');
        $sinNombre = $this->crearSolicitud('VER-OTRO');
        $this->crearCita($sinNombre, date('Y-m-d H:i:s', time() + 3600), null, 'otra observacion');

        $this->db->table('solicitud_datos')->insert([
            'solicitud_id' => $conNombre,
            'clave'        => 'razon_social_o_nombre',
            'valor'        => 'Transportes El Faro S.A.',
        ]);

        $body = $this->comoAdmin()->get('/admin/verificaciones?q=' . rawurlencode('El Faro'))->getBody();

        self::assertStringContainsString('VER-NOMBRE', $body);
        self::assertStringNotContainsString('VER-OTRO', $body);
    }

    public function testElRangoDeFechasAcotaLaAgenda(): void
    {
        $esteAnio = $this->crearSolicitud('VER-HOY');
        $antigua  = $this->crearSolicitud('VER-ANTES');
        $this->crearCita($esteAnio, date('Y-m-d H:i:s'), null, 'cita de hoy');
        $this->crearCita($antigua, '2020-03-10 09:00:00', null, 'cita antigua');

        // El rango se interpreta por día completo: desde el 1 de enero de este año.
        $body = $this->comoAdmin()->get('/admin/verificaciones?desde=' . date('Y-01-01') . '&hasta=' . date('Y-12-31'))->getBody();

        self::assertStringContainsString('cita de hoy', $body);
        self::assertStringNotContainsString('cita antigua', $body);

        $unaDecadaAntes = $this->comoAdmin()->get('/admin/verificaciones?desde=2020-03-01&hasta=2020-03-31')->getBody();
        self::assertStringContainsString('cita antigua', $unaDecadaAntes);
        self::assertStringNotContainsString('cita de hoy', $unaDecadaAntes);
    }

    // ------------------------------------------------------------------
    // Validación de los parámetros de la URL
    // ------------------------------------------------------------------

    public function testUnParametroMalFormadoNoRompeLaAgenda(): void
    {
        $this->sembrarTresCitas();
        $global = (new VerificacionFisicaModel())->resumenGlobal();

        $body = $this->comoAdmin()
            ->get('/admin/verificaciones?estado=' . rawurlencode("vencidas' OR 1=1 --")
                . '&tramite=' . rawurlencode('ur-tt; drop')
                . '&desde=2026-13-45&hasta=ayer&resultado=' . rawurlencode(str_repeat('x', 40)))
            ->getBody();

        // Todos los filtros se descartan y la agenda sigue mostrando lo completo.
        self::assertStringContainsString($global['total'] . ' de ' . $global['total'], $body);
        self::assertStringContainsString('MARCA_FUTURA', $body);
        self::assertStringContainsString('MARCA_DICTADA', $body);
    }

    public function testElOrdenPorCitaRespetaLaDireccionElegida(): void
    {
        $vieja  = $this->crearSolicitud('VER-ORD-VIEJA');
        $nueva  = $this->crearSolicitud('VER-ORD-NUEVA');
        $this->crearCita($vieja, '2020-01-05 09:00:00', null, 'orden antigua');
        $this->crearCita($nueva, '2030-01-05 09:00:00', null, 'orden futura');

        $desc = $this->comoAdmin()->get('/admin/verificaciones?orden=fecha_cita&dir=desc')->getBody();
        self::assertLessThan(strpos($desc, 'orden antigua'), (int) strpos($desc, 'orden futura'));

        $asc = $this->comoAdmin()->get('/admin/verificaciones?orden=fecha_cita&dir=asc')->getBody();
        self::assertLessThan(strpos($asc, 'orden futura'), (int) strpos($asc, 'orden antigua'));
    }

    public function testUnOrdenInventadoSeDescarta(): void
    {
        $this->sembrarTresCitas();

        $body = $this->comoAdmin()->get('/admin/verificaciones?orden=resultado;DROP&dir=' . rawurlencode('asc,1'))->getBody();

        self::assertStringContainsString('MARCA_PASADA', $body);
    }
}
