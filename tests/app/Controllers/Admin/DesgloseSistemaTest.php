<?php declare(strict_types=1);

namespace Tests\Controllers\Admin;

use App\Libraries\DesgloseSistema;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Desglose del sistema y filtros del listado de solicitudes.
 *
 * El desglose es la pantalla donde el administrador ve todo lo que pasa en la
 * plataforma y cada cifra debe abrir exactamente el conjunto de registros que
 * muestra, por eso se prueban a la vez la ruta protegida y los filtros que usa.
 */
class DesgloseSistemaTest extends DatabaseTestCase
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

        $this->adminId    = $this->crearUsuario('admin_dgs', 'administrador');
        $this->operadorId = $this->crearUsuario('op_dgs', 'operador_ventanilla');
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

    /**
     * Solicitud de prueba con canal, pago y resolución controlados.
     *
     * @param array<string, string> $extras
     */
    private function crearSolicitud(string $folio, string $tramite, string $estatus, float $monto, string $fechaSolicitud, array $extras = []): int
    {
        $this->db->table('solicitudes')->insert(array_merge([
            'folio'           => $folio,
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->adminId,
            'estatus'         => $estatus,
            'monto'           => $monto,
            'fecha_solicitud' => $fechaSolicitud,
        ], $extras));

        return (int) $this->db->insertID();
    }

    private function marcarPresencial(int $solicitudId): void
    {
        $this->db->table('solicitud_datos')->insert([
            'solicitud_id' => $solicitudId,
            'clave'        => 'origen',
            'valor'        => 'presencial',
        ]);
    }

    private function comoAdmin(): self
    {
        return $this->withSession(['user_id' => $this->adminId, 'username' => 'admin_dgs', 'roles' => ['administrador']]);
    }

    // ------------------------------------------------------------------
    // Acceso a la pantalla
    // ------------------------------------------------------------------

    public function testElAdministradorAbreElDesglose(): void
    {
        $this->crearSolicitud('DGS-1', 'UR-TT-T-03', 'Pago pendiente', 500.0, date('Y-m-d 10:00:00'));

        $response = $this->comoAdmin()->get('/admin/desglose');

        $response->assertStatus(200);
        $response->assertSee('Desglose');
    }

    public function testElOperadorNoAccedeAlDesglose(): void
    {
        $response = $this->withSession(['user_id' => $this->operadorId, 'username' => 'op_dgs', 'roles' => ['operador_ventanilla']])
            ->get('/admin/desglose');

        $response->assertRedirect();
    }

    public function testElDesgloseMuestraLasMetricasSembradas(): void
    {
        $presencial = $this->crearSolicitud('DGS-P', 'UR-TT-T-05', 'Pago pendiente', 287.0, date('Y-m-d 09:00:00'));
        $this->marcarPresencial($presencial);
        $this->crearSolicitud('DGS-D', 'UR-TT-T-05', 'Recibido', 287.0, date('Y-m-d 08:00:00'));

        $body = $this->comoAdmin()->get('/admin/desglose')->getBody();

        // El desglose muestra agregados, no expedientes: volumetría, dinero y canales.
        $this->assertStringContainsString('Solicitudes por estatus', $body);
        $this->assertStringContainsString('UR-TT-T-05', $body);
        $this->assertStringContainsString('287', $body);
        $this->assertStringContainsString('Ventanilla', $body);
        $this->assertStringContainsString('Portal digital', $body);

        // Cada cifra es un enlace que abre el listado con ese filtro aplicado.
        $this->assertStringContainsString('origen=presencial', $body);
        $this->assertStringContainsString('origen=digital', $body);
        $this->assertStringContainsString('estatus=Pago+pendiente', $body);
    }

    /**
     * Ninguna tarjeta de métricas puede quedarse como adorno: o es ella un enlace,
     * o lleva dentro al menos un enlace con destino. Si alguien añade una cifra
     * nueva sin pantalla detrás, esta prueba lo avisa antes de subir.
     *
     * @return array<int, string>
     */
    private function tarjetasSinEnlace(string $ruta, int $minimoEsperado): array
    {
        $body = $this->comoAdmin()->get($ruta)->getBody();

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $body);
        libxml_clear_errors();
        $xpath = new \DOMXPath($doc);

        $clases  = ['dgs-kpi', 'stat-card-elevated', 'dgs-tarjeta'];
        $cond    = [];
        foreach ($clases as $c) {
            $cond[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')";
        }
        $tarjetas = $xpath->query('//*[' . implode(' or ', $cond) . ']');
        self::assertGreaterThan($minimoEsperado, $tarjetas->length, $ruta . ' debería estar renderizando sus tarjetas de métricas.');

        $sinEnlace = [];
        foreach ($tarjetas as $el) {
            // Vale ser el enlace, estar dentro de uno (la tarjeta envuelta en <a>)
            // o llevar dentro un enlace hacia su desglose.
            $destino = $xpath->query('ancestor-or-self::a[@href and normalize-space(@href) != ""]', $el)->length > 0
                || $xpath->query('.//a[@href and normalize-space(@href) != ""]', $el)->length > 0;
            if (! $destino) {
                $sinEnlace[] = trim(preg_replace('/\s+/', ' ', $el->textContent));
            }
        }

        return $sinEnlace;
    }

    public function testNingunaMetricaDelDesgloseQuedaSinDestino(): void
    {
        $this->assertSame([], $this->tarjetasSinEnlace('/admin/desglose', 20), 'Hay cifras sin enlace en el desglose.');
    }

    public function testNingunaMetricaDelInicioQuedaSinDestino(): void
    {
        $this->assertSame([], $this->tarjetasSinEnlace('/admin/dashboard', 8), 'Hay cifras sin enlace en el inicio.');
    }

    // ------------------------------------------------------------------
    // Filtros del listado que alimentan el desglose
    // ------------------------------------------------------------------

    public function testFiltroDeCanalSeparaVentanillaDelPortal(): void
    {
        $presencial = $this->crearSolicitud('CAN-P', 'UR-TT-T-03', 'Recibido', 50.0, date('Y-m-d 09:00:00'));
        $this->marcarPresencial($presencial);
        $this->crearSolicitud('CAN-D', 'UR-TT-T-03', 'Recibido', 50.0, date('Y-m-d 09:00:00'));

        $presenciales = $this->comoAdmin()->get('/admin/solicitudes?origen=presencial')->getBody();
        $this->assertStringContainsString('CAN-P', $presenciales);
        $this->assertStringNotContainsString('CAN-D', $presenciales);

        $digitales = $this->comoAdmin()->get('/admin/solicitudes?origen=digital')->getBody();
        $this->assertStringContainsString('CAN-D', $digitales);
        $this->assertStringNotContainsString('CAN-P', $digitales);
    }

    public function testFiltroResueltoSoloMuestraExpedientesSinCerrar(): void
    {
        $this->crearSolicitud('RES-SI', 'UR-TT-T-03', 'Concluido', 50.0, date('Y-m-d 09:00:00'), ['fecha_resolucion' => date('Y-m-d 10:00:00')]);
        $this->crearSolicitud('RES-NO', 'UR-TT-T-03', 'Recibido', 50.0, date('Y-m-d 09:00:00'));

        $body = $this->comoAdmin()->get('/admin/solicitudes?resuelto=no')->getBody();

        $this->assertStringContainsString('RES-NO', $body);
        $this->assertStringNotContainsString('RES-SI', $body);
    }

    public function testElPeriodoPuedeAplicarseALaFechaDePago(): void
    {
        $ayer = date('Y-m-d 09:00:00', strtotime('-1 day'));
        $hoy  = date('Y-m-d 09:00:00');

        // Solicitada ayer pero cobrada hoy: solo debe aparecer en la ventana de pago.
        $this->crearSolicitud('FPA-PAGO', 'UR-TT-T-03', 'Pagado', 500.0, $ayer, ['fecha_pago' => $hoy]);
        $this->crearSolicitud('FPA-OTRO', 'UR-TT-T-03', 'Pagado', 500.0, date('Y-m-d 09:00:00', strtotime('-40 days')), ['fecha_pago' => date('Y-m-d 09:00:00', strtotime('-40 days'))]);

        $porPago = $this->comoAdmin()
            ->get('/admin/solicitudes?pago=cobrado&fecha_campo=pago&desde=' . date('Y-m-d') . '&hasta=' . date('Y-m-d'))
            ->getBody();
        $this->assertStringContainsString('FPA-PAGO', $porPago);
        $this->assertStringNotContainsString('FPA-OTRO', $porPago);

        // La misma ventana aplicada a la fecha de solicitud deja el resultado distinto.
        $porSolicitud = $this->comoAdmin()
            ->get('/admin/solicitudes?fecha_campo=solicitud&desde=' . date('Y-m-d') . '&hasta=' . date('Y-m-d'))
            ->getBody();
        $this->assertStringNotContainsString('FPA-PAGO', $porSolicitud);
    }

    public function testElFiltroDeMesAcotaPorAnioMes(): void
    {
        $this->crearSolicitud('MES-A', 'UR-TT-T-03', 'Recibido', 50.0, date('Y-m-15 09:00:00'));
        $this->crearSolicitud('MES-B', 'UR-TT-T-03', 'Recibido', 50.0, date('Y-m-15 09:00:00', strtotime('-2 month')));

        $body = $this->comoAdmin()->get('/admin/solicitudes?mes=' . date('Y-m'))->getBody();

        $this->assertStringContainsString('MES-A', $body);
        $this->assertStringNotContainsString('MES-B', $body);
    }

    public function testUnMesInventadoNoRompeElListado(): void
    {
        $this->crearSolicitud('MES-OK', 'UR-TT-T-03', 'Recibido', 50.0, date('Y-m-d 09:00:00'));

        // Valores que no pasan la validación se ignoran: el listado sigue completo.
        $body = $this->comoAdmin()->get('/admin/solicitudes?mes=2099-13&origen=todos&orden=campo_inyectado&dir=bajar')->getBody();

        $this->assertStringContainsString('MES-OK', $body);
    }

    public function testOrdenarPorMontoColocaElMayorPrimero(): void
    {
        $this->crearSolicitud('MON-B', 'UR-TT-T-03', 'Recibido', 100.0, date('Y-m-d 09:00:00'));
        $this->crearSolicitud('MON-A', 'UR-TT-T-03', 'Recibido', 900.0, date('Y-m-d 10:00:00'));

        $body = $this->comoAdmin()->get('/admin/solicitudes?orden=monto&dir=desc')->getBody();

        $this->assertLessThan(strpos($body, 'MON-B'), strpos($body, 'MON-A'), 'El monto alto debe aparecer antes que el bajo.');
    }

    // ------------------------------------------------------------------
    // La biblioteca de agregación
    // ------------------------------------------------------------------

    public function testLasAlertasSenalanLoPendiente(): void
    {
        $ayer = date('Y-m-d 09:00:00', strtotime('-20 days'));
        $this->crearSolicitud('ALR-1', 'UR-TT-T-05', 'Pago pendiente', 287.0, $ayer);
        $this->crearSolicitud('ALR-2', 'UR-TT-T-03', 'Recibido', 50.0, $ayer);

        $alertas = (new DesgloseSistema())->alertas();
        $rotulos = array_column($alertas, 'rotulo');

        $this->assertContains('Pendientes de cobro', $rotulos);
        $this->assertContains('Solicitudes sin movimiento hace más de 7 días', $rotulos);
    }

    public function testLosKpisSumaNombresDineroYDocumentos(): void
    {
        $presencial = $this->crearSolicitud('KPI-P', 'UR-TT-T-03', 'Pagado', 1000.0, date('Y-m-d 09:00:00'), ['fecha_pago' => date('Y-m-d 09:30:00')]);
        $this->marcarPresencial($presencial);
        $this->crearSolicitud('KPI-D', 'UR-TT-T-03', 'Pago pendiente', 500.0, date('Y-m-d 09:00:00'));
        $this->crearSolicitud('KPI-C', 'UR-TT-T-03', 'Concluido', 50.0, date('Y-m-d 09:00:00'), ['fecha_resolucion' => date('Y-m-d 11:00:00')]);

        $kpis = (new DesgloseSistema())->kpis();

        $this->assertSame(3, $kpis['solicitudes_total']);
        $this->assertSame(1000.0, (float) $kpis['ingresos_cobrados']);
        $this->assertSame(500.0, (float) $kpis['ingresos_por_cobrar']);
        $this->assertSame(1, $kpis['cobradas']);
        // Un expediente pagado sigue abierto hasta que se emite el permiso;
        // solo conclude el que tiene estatus de cierre.
        $this->assertSame(2, $kpis['en_tramite']);
        $this->assertSame(1, $kpis['ciudadanos_activos']);
    }

    public function testElDesglosePorEstatusCubreElTotal(): void
    {
        $this->crearSolicitud('EST-1', 'UR-TT-T-03', 'Recibido', 10.0, date('Y-m-d 09:00:00'));
        $this->crearSolicitud('EST-2', 'UR-TT-T-03', 'Pagado', 10.0, date('Y-m-d 09:00:00'));
        $this->crearSolicitud('EST-3', 'UR-TT-T-03', 'Rechazado', 10.0, date('Y-m-d 09:00:00'));

        $porEstatus = (new DesgloseSistema())->porEstatus();

        $this->assertSame(3, $porEstatus['total']);
        $this->assertSame(3, array_sum(array_column($porEstatus['lista'], 'total')));
        foreach ($porEstatus['lista'] as $fila) {
            $this->assertNotEmpty($fila['estatus']);
        }
    }

    public function testElInicioDelPanelTambienEsClicableParaElOperador(): void
    {
        $this->crearSolicitud('INI-1', 'UR-TT-T-03', 'Recibido', 10.0, date('Y-m-d 09:00:00'));

        $response = $this->withSession(['user_id' => $this->operadorId, 'username' => 'op_dgs', 'roles' => ['operador_ventanilla']])
            ->get('/admin/dashboard');

        $response->assertStatus(200);
        // El operador no ve accesos al desglose, que es exclusivo del administrador.
        $this->assertStringNotContainsString('/admin/desglose', $response->getBody());
    }

    // ------------------------------------------------------------------
    // Trazabilidad desde el expediente
    // ------------------------------------------------------------------

    private function crearDocumento(int $solicitudId, string $nombre): int
    {
        $this->db->table('documentos')->insert([
            'solicitud_id'    => $solicitudId,
            'tipo_documento'  => 'identificacion oficial',
            'nombre_original' => $nombre,
            'ruta_interna'    => 'documentos/' . $nombre,
            'mime_type'       => 'application/pdf',
            'tamano_bytes'    => 20480,
            'hash_sha256'     => str_repeat('a', 64),
            'usuario_id'      => $this->adminId,
            'fecha_carga'     => date('Y-m-d H:i:s'),
            'validado'        => 0,
        ]);

        return (int) $this->db->insertID();
    }

    public function testLaAuditoriaSePuedeFiltrarPorRegistro(): void
    {
        $uno = $this->crearSolicitud('TRA-1', 'UR-TT-T-03', 'Recibido', 10.0, date('Y-m-d 09:00:00'));
        $otro = $this->crearSolicitud('TRA-2', 'UR-TT-T-03', 'Recibido', 10.0, date('Y-m-d 09:00:00'));

        $auditoria = new \App\Models\AuditoriaModel();
        $auditoria->registrar('solicitudes', $uno, 'documento_validado', $this->adminId, ['nombre_original' => 'de TRA-1']);
        $auditoria->registrar('solicitudes', $otro, 'documento_validado', $this->adminId, ['nombre_original' => 'de TRA-2']);

        $body = $this->comoAdmin()->get('/admin/auditoria?entidad=solicitudes&entidad_id=' . $uno)->getBody();

        // El enlace "Trazabilidad" del expediente debe mostrar solo sus propios eventos.
        $this->assertStringContainsString('de TRA-1', $body);
        $this->assertStringNotContainsString('de TRA-2', $body);
    }

    public function testValidarDocumentoAlternaYEscribaAuditoria(): void
    {
        $solicitud  = $this->crearSolicitud('VDOC-1', 'UR-TT-T-03', 'Recibido', 10.0, date('Y-m-d 09:00:00'));
        $documentoId = $this->crearDocumento($solicitud, 'ine.pdf');

        $this->comoAdmin()->post('/admin/solicitudes/validar-documento/' . $documentoId);

        $this->assertSame(1, (int) $this->db->table('documentos')->where('id', $documentoId)->get()->getRow()->validado);
        $this->seeInDatabase('auditoria', ['entidad' => 'documentos', 'entidad_id' => $documentoId, 'accion' => 'documento_validado']);

        // En la segunda llamada el control revierte: es un interruptor, no un botón de sentido único.
        $this->comoAdmin()->post('/admin/solicitudes/validar-documento/' . $documentoId);

        $this->assertSame(0, (int) $this->db->table('documentos')->where('id', $documentoId)->get()->getRow()->validado);
        $this->seeInDatabase('auditoria', ['entidad' => 'documentos', 'entidad_id' => $documentoId, 'accion' => 'documento_validacion_revocada']);
    }

    public function testElDetalleDeAuditoriaConservaElEspanolDelDetalle(): void
    {
        $solicitud = $this->crearSolicitud('AUD-Ñ', 'UR-TT-T-03', 'Recibido', 10.0, date('Y-m-d 09:00:00'));

        (new \App\Models\AuditoriaModel())->registrar('solicitudes', $solicitud, 'creacion', $this->adminId, ['motivo' => 'Renovación de concesión']);

        $guardado = $this->db->table('auditoria')->where('accion', 'creacion')->get()->getRow();

        // Sin JSON_UNESCAPED_UNICODE el texto llegaría escapado y la bitácora sería ilegible.
        $this->assertStringContainsString('Renovación', (string) $guardado->detalle);
    }

    // ------------------------------------------------------------------
    // Sesiones de la app de agentes de tránsito
    // ------------------------------------------------------------------

    private function crearAgente(string $placa): int
    {
        $this->db->table('agentes_transito')->insert([
            'placa'           => $placa,
            'nombre_completo' => 'Agente ' . $placa,
            'password_hash'   => password_hash('secreto123', PASSWORD_DEFAULT),
            'rol'             => 'operativo',
            'sector'          => 'Sector Centro',
            'activo'          => 1,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Token de sesión tal y como lo deja AgenteTokenService::emitir().
     */
    private function crearSesion(int $agenteId, string $expiraEn, ?string $revocadoEn = null, ?string $ultimoUsoEn = null): void
    {
        static $semilla = 0;
        $semilla++;

        $this->db->table('agentes_transito_tokens')->insert([
            'agente_id'     => $agenteId,
            'token_hash'    => hash('sha256', 'sesion-prueba-' . $semilla),
            'expira_en'     => $expiraEn,
            'revocado_en'   => $revocadoEn,
            'ultimo_uso_en' => $ultimoUsoEn,
        ]);
    }

    public function testElDesgloseClasificaLasSesionesDeLaApp(): void
    {
        $enTurno  = $this->crearAgente('P-01');
        $caducada = $this->crearAgente('P-02');
        $cerrada  = $this->crearAgente('P-03');

        $this->crearSesion($enTurno, date('Y-m-d H:i:s', time() + 3600), null, date('Y-m-d H:i:s', time() - 60));
        $this->crearSesion($caducada, date('Y-m-d H:i:s', time() - 3600));
        $this->crearSesion($cerrada, date('Y-m-d H:i:s', time() + 3600), date('Y-m-d H:i:s'));

        $transito = (new DesgloseSistema())->appTransito();

        $this->assertSame(3, (int) $transito['sesiones_total']);
        $this->assertSame(1, (int) $transito['sesiones_vigentes']);
        $this->assertSame(1, (int) $transito['sesiones_vencidas']);
        $this->assertSame(1, (int) $transito['sesiones_revocadas']);

        // «En turno» solo lista al agente cuyo token no venció ni se revocó.
        $this->assertSame(['P-01'], array_column((array) $transito['agentes_en_turno'], 'placa'));
        $this->assertNotSame('', (string) $transito['ultima_actividad_app']);
    }

    public function testElDesgloseEnlazaLaSesionVigenteConLasBoletasDeEseAgente(): void
    {
        $agente = $this->crearAgente('T-90');
        $this->crearSesion($agente, date('Y-m-d H:i:s', time() + 7200), null, date('Y-m-d H:i:s'));

        $body = $this->comoAdmin()->get('/admin/desglose')->getBody();

        $this->assertStringContainsString('Sesiones de la app de agentes', $body);
        $this->assertStringContainsString('/admin/app-transito/boletas?placa=T-90', $body);

        // Sin tokens no debe inventar agentes en turno: lo honesto es decirlo.
        $this->db->table('agentes_transito_tokens')->where('agente_id', $agente)->delete();
        $sinSesiones = $this->comoAdmin()->get('/admin/desglose')->getBody();

        $this->assertStringNotContainsString('/admin/app-transito/boletas?placa=T-90', $sinSesiones);
        // El acento sale como entidad HTML en la respuesta, así que se busca el trozo sin tildes.
        $this->assertStringContainsString('agente tiene la app abierta', $sinSesiones);
    }

    // ------------------------------------------------------------------
    // Verificaciones físicas y configuración del dinero
    // ------------------------------------------------------------------

    private function crearCita(int $solicitudId, string $fechaCita, ?string $resultado = null): void
    {
        $this->db->table('verificaciones_fisicas')->insert([
            'solicitud_id' => $solicitudId,
            'fecha_cita'   => $fechaCita,
            'resultado'    => $resultado,
        ]);
    }

    public function testElDesgloseSeparaCitasFuturasDeLasQueDebenSuDictamen(): void
    {
        $futura   = $this->crearSolicitud('VER-FUT', 'UR-TT-T-02', 'En revisión', 10.0, date('Y-m-d 09:00:00'));
        $rezagada = $this->crearSolicitud('VER-REZ', 'UR-TT-T-02', 'En revisión', 10.0, date('Y-m-d 09:00:00'));
        $cerrada  = $this->crearSolicitud('VER-OK', 'UR-TT-T-02', 'Concluido', 10.0, date('Y-m-d 09:00:00'));

        $this->crearCita($futura, date('Y-m-d H:i:s', time() + 86400));
        $this->crearCita($rezagada, date('Y-m-d H:i:s', time() - 86400));
        $this->crearCita($cerrada, date('Y-m-d H:i:s', time() - 172800), 'Aprobado');

        $verif = (new DesgloseSistema())->verificaciones();

        $this->assertSame(3, (int) $verif['total']);
        $this->assertSame(2, (int) $verif['pendientes']);
        $this->assertSame(1, (int) $verif['con_dictamen']);
        $this->assertSame(1, (int) $verif['vencidas_sin_dictamen']);

        // Cada lista muestra lo que promete: la agenda mira hacia adelante y la
        // columna roja solo trae citas que ya pasaron sin resultado capturado.
        $this->assertSame(['VER-FUT'], array_column((array) $verif['proximas'], 'folio'));
        $this->assertSame(['VER-REZ'], array_column((array) $verif['rezagadas'], 'folio'));
    }

    public function testCadaCitaDelDesgloseAbreSuExpediente(): void
    {
        $rezagada = $this->crearSolicitud('VER-IRL', 'UR-TT-T-02', 'En revisión', 10.0, date('Y-m-d 09:00:00'));
        $this->crearCita($rezagada, date('Y-m-d H:i:s', time() - 86400));

        $body = $this->comoAdmin()->get('/admin/desglose')->getBody();

        // El enlace va por folio, que es como la ruta del expediente resuelve.
        $this->assertStringContainsString('admin/solicitudes/VER-IRL', $body);
        $this->assertStringContainsString('Citas que ya pasaron sin resultado', $body);
    }

    /**
     * Las tarjetas de verificaciones del desglose enlazan la agenda nueva, y el
     * filtro al que apuntan tiene que devolver exactamente la cifra impresa.
     */
    public function testLasTarjetasDeVerificacionesAbrenSuPropioFiltro(): void
    {
        $futura   = $this->crearSolicitud('VER-T1', 'UR-TT-T-02', 'En revisión', 10.0, date('Y-m-d 09:00:00'));
        $rezagada = $this->crearSolicitud('VER-T2', 'UR-TT-T-02', 'En revisión', 10.0, date('Y-m-d 09:00:00'));
        $dictada  = $this->crearSolicitud('VER-T3', 'UR-TT-T-02', 'Concluido', 10.0, date('Y-m-d 09:00:00'));

        $this->crearCita($futura, date('Y-m-d H:i:s', time() + 86400));
        $this->crearCita($rezagada, date('Y-m-d H:i:s', time() - 86400));
        $this->crearCita($dictada, date('Y-m-d H:i:s', time() - 172800), 'Aprobado');

        $body = $this->comoAdmin()->get('/admin/desglose')->getBody();
        foreach (['', '?estado=con_dictamen', '?estado=sin_dictamen', '?estado=vencidas'] as $consulta) {
            $this->assertStringContainsString('/admin/verificaciones' . $consulta, $body);
        }

        $verif = (new DesgloseSistema())->verificaciones();
        $this->assertSame(3, (int) $verif['total']);

        // Cada enlace, al abrirse, cuenta lo mismo que decía la tarjeta. «Próximas»
        // no sale del desglose (su lista está recortada a 8), sino de su propio resumen.
        $global = (new \App\Models\VerificacionFisicaModel())->resumenGlobal();
        $esperado = [
            ''                  => $verif['total'],
            'con_dictamen'      => $verif['con_dictamen'],
            'sin_dictamen'      => $verif['pendientes'],
            'vencidas'          => $verif['vencidas_sin_dictamen'],
            'proximas'          => $global['proximas'],
        ];
        foreach ($esperado as $estado => $cifra) {
            $listado = new \App\Models\VerificacionFisicaModel();
            $filtrado = $listado->aplicarFiltros(['estado' => (string) $estado])->countAllResults();
            $this->assertSame((int) $cifra, $filtrado, 'El filtro ' . $estado . ' no reproduce la cifra del desglose.');
        }
    }

    /**
     * Igual para las sesiones: la regla del desglose y la del listado son la misma
     * consulta, así que ninguna tarjeta puede prometer una cifra que no se abre.
     */
    public function testLasTarjetasDeSesionesAbrenSuPropioFiltro(): void
    {
        $enTurno  = $this->crearAgente('S-T1');
        $caducada = $this->crearAgente('S-T2');
        $cerrada  = $this->crearAgente('S-T3');

        $this->crearSesion($enTurno, date('Y-m-d H:i:s', time() + 3600), null, date('Y-m-d H:i:s', time() - 60));
        $this->crearSesion($caducada, date('Y-m-d H:i:s', time() - 3600));
        $this->crearSesion($cerrada, date('Y-m-d H:i:s', time() + 3600), date('Y-m-d H:i:s'));

        $body = $this->comoAdmin()->get('/admin/desglose')->getBody();
        foreach (['vigentes', 'vencidas', 'revocadas'] as $estado) {
            $this->assertStringContainsString('/admin/sesiones-app?estado=' . $estado, $body);
        }

        $transito = (new DesgloseSistema())->appTransito();
        $esperado = [
            'vigentes'  => $transito['sesiones_vigentes'],
            'vencidas'  => $transito['sesiones_vencidas'],
            'revocadas' => $transito['sesiones_revocadas'],
            ''          => $transito['sesiones_total'],
        ];
        foreach ($esperado as $estado => $cifra) {
            $listado = new \App\Models\AgenteTokenModel();
            $filtrado = $listado->aplicarFiltros(['estado' => (string) $estado])->countAllResults();
            $this->assertSame((int) $cifra, $filtrado, 'El filtro ' . $estado . ' no reproduce la cifra del desglose.');
        }
    }

    public function testSinUmaConfiguradaElDesgloseLoAvisa(): void
    {
        $rotulos = array_column((new DesgloseSistema())->alertas(), 'rotulo');
        $this->assertContains('No hay valor de UMA vigente cargado', $rotulos);

        $this->db->table('parametros_sistema')->insert([
            'clave' => 'valor_uma_vigente', 'valor' => '108.57', 'descripcion' => 'UMA 2026',
        ]);

        $rotulosConUma = array_column((new DesgloseSistema())->alertas(), 'rotulo');
        $this->assertNotContains('No hay valor de UMA vigente cargado', $rotulosConUma);
        $this->assertSame(108.57, (new DesgloseSistema())->catalogos()['uma_vigente']);
    }

    public function testLasSeccionesSeCalculanUnaSolaVezPorInstancia(): void
    {
        $desglose = new DesgloseSistema();
        $this->assertSame(0.0, $desglose->catalogos()['uma_vigente']);

        $this->db->table('parametros_sistema')->insert(['clave' => 'valor_uma_vigente', 'valor' => '108.57']);

        // La instancia que ya calculó la sección la repite sin volver a la base;
        // una instancia nueva sí la relee. Así `alertas()` no duplica consultas.
        $this->assertSame(0.0, $desglose->catalogos()['uma_vigente']);
        $this->assertSame(108.57, (new DesgloseSistema())->catalogos()['uma_vigente']);
    }
}
