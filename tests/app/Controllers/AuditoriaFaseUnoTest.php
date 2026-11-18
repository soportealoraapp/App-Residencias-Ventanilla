<?php declare(strict_types=1);

namespace Tests\Controllers;

use App\Libraries\EstadoSolicitudService;
use App\Libraries\PermisosOperador;
use App\Models\SolicitudModel;
use App\Models\VerificacionFisicaModel;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Auditoría de Félix, Fase 1: A3 (pago simulado), A5 (permisos del operador
 * en rutas del portal), M1 (cita UR-02 ajena) y M4 (CSRF en eliminar).
 */
class AuditoriaFaseUnoTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    /** @var array<string, int> */
    private array $usuarios = [];

    protected function setUp(): void
    {
        parent::setUp();
        PermisosOperador::reiniciarCache();
        // Apagada explícitamente: el .env local puede encenderla para la demo.
        putenv('APP_ENABLE_PAGO_EN_LINEA=false');
        $_ENV['APP_ENABLE_PAGO_EN_LINEA'] = 'false';

        foreach (['administrador', 'operador_ventanilla', 'ciudadano', 'otro_ciudadano'] as $clave) {
            $rol = $clave === 'otro_ciudadano' ? 'ciudadano' : $clave;
            if ($this->db->table('roles')->where('nombre', $rol)->countAllResults() === 0) {
                $this->db->table('roles')->insert(['nombre' => $rol, 'descripcion' => $rol]);
            }
            $rolId = (int) $this->db->table('roles')->where('nombre', $rol)->get()->getRow()->id;
            $this->db->table('users')->insert([
                'username' => $clave . '_f1', 'email' => $clave . '@f1.test', 'password_hash' => 'x',
                'nombre_completo' => 'Prueba ' . $clave, 'activo' => 1,
            ]);
            $this->usuarios[$clave] = (int) $this->db->insertID();
            $this->db->table('user_roles')->insert(['user_id' => $this->usuarios[$clave], 'role_id' => $rolId]);
        }
    }

    protected function tearDown(): void
    {
        putenv('APP_ENABLE_PAGO_EN_LINEA');
        unset($_ENV['APP_ENABLE_PAGO_EN_LINEA']);
        PermisosOperador::reiniciarCache();
        parent::tearDown();
    }

    private function como(string $clave): self
    {
        $rol = $clave === 'otro_ciudadano' ? 'ciudadano' : $clave;

        return $this->withSession([
            'user_id'  => $this->usuarios[$clave],
            'username' => $clave . '_f1',
            'roles'    => [$rol],
        ]);
    }

    private function solicitud(string $tramite, string $estatus, string $folio, float $monto = 400.0): int
    {
        return (int) (new SolicitudModel())->insert([
            'folio' => $folio, 'tramite' => $tramite, 'ciudadano_id' => $this->usuarios['ciudadano'],
            'estatus' => $estatus, 'monto' => $monto, 'fecha_solicitud' => date('Y-m-d H:i:s'),
        ]);
    }

    private function encenderPagoEnLinea(): void
    {
        putenv('APP_ENABLE_PAGO_EN_LINEA=true');
        $_ENV['APP_ENABLE_PAGO_EN_LINEA'] = 'true';
    }

    // ─── A3 · Pago simulado ───────────────────────────────────────────────

    public function testSinBanderaElPagoEnLineaNoEmiteElPermiso(): void
    {
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 'FOLF1T07SINBANDERA01');
        (new EstadoSolicitudService())->cambiarEstatus($id, 'Pago pendiente', $this->usuarios['operador_ventanilla']);

        $this->como('ciudadano')->post('/portal/tramites/carga-descarga/pagar/' . $id);

        $this->assertSame('Pago pendiente', (new SolicitudModel())->find($id)->estatus);
    }

    public function testSinBanderaElResumenNoMuestraElBotonDePagar(): void
    {
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 'FOLF1T07SINBOTON0001');
        (new EstadoSolicitudService())->cambiarEstatus($id, 'Pago pendiente', $this->usuarios['operador_ventanilla']);

        $cuerpo = $this->como('ciudadano')->get('/portal/tramites/carga-descarga/resumen/FOLF1T07SINBOTON0001')->getBody();

        $this->assertStringNotContainsString('Confirmar y pagar', $cuerpo);
        $this->assertStringContainsString('hoja-pago', $cuerpo);
    }

    public function testConBanderaT07SoloSePagaDesdePagoPendiente(): void
    {
        $this->encenderPagoEnLinea();
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 'FOLF1T07RECIBIDO0001');

        $this->como('ciudadano')->post('/portal/tramites/carga-descarga/pagar/' . $id);

        $this->assertSame('Recibido', (new SolicitudModel())->find($id)->estatus);
    }

    public function testConBanderaT07PagaYQuedaVigente(): void
    {
        $this->encenderPagoEnLinea();
        $id = $this->solicitud('UR-TT-T-07', 'Recibido', 'FOLF1T07CONBANDERA01');
        (new EstadoSolicitudService())->cambiarEstatus($id, 'Pago pendiente', $this->usuarios['operador_ventanilla']);

        $this->como('ciudadano')->post('/portal/tramites/carga-descarga/pagar/' . $id);

        $this->assertSame('Vigente', (new SolicitudModel())->find($id)->estatus);
    }

    // ─── A5 · Permisos del operador en rutas del portal ───────────────────

    public function testOperadorSinPermisoDeConvocatoriasNoEligeGanadores(): void
    {
        $permisos = PermisosOperador::todos();
        $permisos['convocatorias'] = false;
        PermisosOperador::guardar($permisos);

        $this->como('operador_ventanilla')
            ->post('/portal/tramites/ur-01/convocatorias/1/seleccionar', ['solicitud_id' => 1])
            ->assertRedirectTo('/admin/dashboard');

        $this->como('operador_ventanilla')
            ->get('/portal/tramites/ur-01/convocatorias/1/solicitudes')
            ->assertRedirectTo('/admin/dashboard');
    }

    public function testOperadorSinPermisoDeSolicitudesNoDictaminaUr02(): void
    {
        $permisos = PermisosOperador::todos();
        $permisos['solicitudes'] = false;
        PermisosOperador::guardar($permisos);

        $this->como('operador_ventanilla')
            ->post('/portal/tramites/ur-02/solicitudes/1/resultado', ['resultado' => 'aprobado'])
            ->assertRedirectTo('/admin/dashboard');
    }

    public function testOperadorConPermisoSigueEntrandoALasPostulaciones(): void
    {
        $respuesta = $this->como('operador_ventanilla')->get('/portal/tramites/ur-01/convocatorias/1/solicitudes');

        $this->assertFalse($respuesta->isRedirect());
    }

    // ─── M1 · Cita UR-02 ──────────────────────────────────────────────────

    public function testOtroCiudadanoNoPuedeVerNiReemplazarLaCita(): void
    {
        $id = $this->solicitud('UR-TT-T-02', 'Recibido', 'FOLF1UR02AJENA000001', 64.90);
        $fecha = date('Y-m-d\TH:i', strtotime('+2 days 10:00'));

        $this->como('otro_ciudadano')->get('/portal/tramites/ur-02/solicitud/FOLF1UR02AJENA000001/cita')
            ->assertRedirectTo('/portal/mis-solicitudes');
        $this->como('otro_ciudadano')->post('/portal/tramites/ur-02/solicitud/FOLF1UR02AJENA000001/cita/guardar', ['fecha_cita' => $fecha])
            ->assertRedirectTo('/portal/mis-solicitudes');

        $this->assertSame('Recibido', (new SolicitudModel())->find($id)->estatus);
        $this->assertNull((new VerificacionFisicaModel())->primerPorSolicitud($id));
    }

    public function testElTitularAgendaYReprogramaSinPerderLaVerificacion(): void
    {
        $id = $this->solicitud('UR-TT-T-02', 'Recibido', 'FOLF1UR02PROPIA00001', 64.90);
        $ruta = '/portal/tramites/ur-02/solicitud/FOLF1UR02PROPIA00001/cita/guardar';

        $this->como('ciudadano')->post($ruta, ['fecha_cita' => date('Y-m-d\TH:i', strtotime('+2 days 10:00'))]);
        $primera = (new VerificacionFisicaModel())->primerPorSolicitud($id);
        $this->assertSame('Cita agendada', (new SolicitudModel())->find($id)->estatus);

        $this->como('ciudadano')->post($ruta, ['fecha_cita' => date('Y-m-d\TH:i', strtotime('+3 days 12:00'))]);
        $segunda = (new VerificacionFisicaModel())->primerPorSolicitud($id);

        $this->assertSame((int) $primera->id, (int) $segunda->id);
        $this->assertStringStartsWith(date('Y-m-d', strtotime('+3 days')), (string) $segunda->fecha_cita);
    }

    public function testNoSeReprogramaUnaCitaConDictamen(): void
    {
        $id = $this->solicitud('UR-TT-T-02', 'Recibido', 'FOLF1UR02DICTAMEN001', 64.90);
        $ruta = '/portal/tramites/ur-02/solicitud/FOLF1UR02DICTAMEN001/cita/guardar';
        $this->como('ciudadano')->post($ruta, ['fecha_cita' => date('Y-m-d\TH:i', strtotime('+2 days 10:00'))]);
        $verificacion = (new VerificacionFisicaModel())->primerPorSolicitud($id);
        (new VerificacionFisicaModel())->update((int) $verificacion->id, ['resultado' => 'aprobado']);

        $this->como('ciudadano')->post($ruta, ['fecha_cita' => date('Y-m-d\TH:i', strtotime('+5 days 10:00'))]);

        $despues = (new VerificacionFisicaModel())->primerPorSolicitud($id);
        $this->assertSame('aprobado', $despues->resultado);
        $this->assertSame((string) $verificacion->fecha_cita, (string) $despues->fecha_cita);
    }

    // ─── M4 · CSRF en eliminar tarifa y concesión ─────────────────────────

    public function testLosFormulariosDeEliminarLlevanTokenCsrf(): void
    {
        $this->db->table('tarifas')->insert([
            'tramite' => 'UR-TT-T-07', 'criterio' => 'prueba_csrf', 'monto' => 10, 'vigente_desde' => date('Y-m-d'), 'descripcion' => 'Prueba CSRF',
        ]);
        $cuerpo = $this->como('administrador')->get('/admin/tarifas')->getBody();

        $this->assertMatchesRegularExpression('#id="form-eliminar-\d+"[^>]*>\s*<input type="hidden" name="' . csrf_token() . '"#', $cuerpo);
    }
}
