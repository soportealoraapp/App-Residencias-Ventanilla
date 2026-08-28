<?php declare(strict_types=1);

namespace Tests\Libraries;

use Tests\Support\DatabaseTestCase;
use App\Libraries\EstadoSolicitudService;
use App\Models\SolicitudModel;
use App\Models\UserModel;
use DateTime;

class EstadoSolicitudServiceTest extends DatabaseTestCase
{
    protected EstadoSolicitudService $service;
    protected SolicitudModel $solicitudModel;
    protected UserModel $userModel;
    protected int $demoUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EstadoSolicitudService();
        $this->solicitudModel = new SolicitudModel();
        $this->userModel = new UserModel();

        $this->demoUserId = (int)$this->userModel->insert([
            'username'        => 'testuser',
            'email'           => 'test@example.com',
            'password_hash'   => password_hash('12345678', PASSWORD_BCRYPT),
            'nombre_completo' => 'Usuario Prueba',
            'activo'          => 1,
        ]);
    }

    protected function crearSolicitud(string $tramite, string $estatus, ?DateTime $fechaPago = null): int
    {
        $fechaSolicitud = new DateTime();
        return (int)$this->solicitudModel->insert([
            'folio'           => 'TEST-' . uniqid(),
            'tramite'         => $tramite,
            'ciudadano_id'    => $this->demoUserId,
            'estatus'         => $estatus,
            'monto'           => 100.00,
            'fecha_solicitud' => $fechaSolicitud->format('Y-m-d H:i:s'),
            'fecha_pago'      => $fechaPago !== null ? $fechaPago->format('Y-m-d H:i:s') : null,
        ]);
    }

    public function testTransicionValida_T07_RecibidoAPagoPendiente_ReturnsTrue(): void
    {
        $resultado = $this->service->transicionValida('UR-TT-T-07', 'Recibido', 'Pago pendiente');
        $this->assertTrue($resultado);
    }

    public function testTransicionValida_T07_VigenteARecibido_ReturnsFalse(): void
    {
        $resultado = $this->service->transicionValida('UR-TT-T-07', 'Vigente', 'Recibido');
        $this->assertFalse($resultado);
    }

    public function testTransicionValida_T06_RecibidoAEnRevision_True(): void
    {
        $resultado = $this->service->transicionValida('UR-TT-T-06', 'Recibido', 'En revisión documental');
        $this->assertTrue($resultado);
    }

    public function testTransicionValida_T06_EnRevisionADictaminado_True(): void
    {
        $resultado = $this->service->transicionValida('UR-TT-T-06', 'En revisión documental', 'Dictaminado aprobado');
        $this->assertTrue($resultado);
    }

    public function testTransicionValida_T06_DictaminadoARechazado_True(): void
    {
        $resultado = $this->service->transicionValida('UR-TT-T-06', 'Dictaminado aprobado', 'Rechazado');
        $this->assertTrue($resultado);
    }

    public function testTransicionesUr04_RecorridoInicialEsValido(): void
    {
        $this->assertTrue($this->service->transicionValida('UR-TT-T-04', 'Recibido', 'En revisión documental'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-04', 'En revisión documental', 'Documentos completos'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-04', 'Documentos completos', 'En estudio técnico'));
        $this->assertFalse($this->service->transicionValida('UR-TT-T-04', 'Recibido', 'Pago pendiente'));
    }

    public function testTransicionesUr04_AutorizacionPagoEsAnteriorAlPago(): void
    {
        $this->assertTrue($this->service->transicionValida('UR-TT-T-04', 'Seguro pendiente de validación', 'Autorizado para pago'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-04', 'Autorizado para pago', 'Pago pendiente'));
        $this->assertFalse($this->service->transicionValida('UR-TT-T-04', 'Autorizado para pago', 'Pagado'));
    }

    public function testTransicionesUr05_FlujoDirectoYRechazo(): void
    {
        $this->assertTrue($this->service->transicionValida('UR-TT-T-05', 'Recibido', 'En validación'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-05', 'En validación', 'Pago pendiente'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-05', 'En validación', 'Rechazado'));
        $this->assertFalse($this->service->transicionValida('UR-TT-T-05', 'Recibido', 'Pagado'));
    }

    public function testCambiarEstatus_SinComentarioEnPrevencion_ReturnsFalse(): void
    {
        $solicitudId = $this->crearSolicitud('UR-TT-T-06', 'En revisión documental');

        $resultado = $this->service->cambiarEstatus(
            $solicitudId,
            'Prevención',
            $this->demoUserId,
            null
        );

        $this->assertFalse($resultado);

        $actualizada = $this->solicitudModel->find($solicitudId);
        $this->assertEquals('En revisión documental', $actualizada->estatus);
    }

    public function testCambiarEstatus_ConComentarioEnPrevencion_ReturnsTrue(): void
    {
        $solicitudId = $this->crearSolicitud('UR-TT-T-06', 'En revisión documental');

        $resultado = $this->service->cambiarEstatus(
            $solicitudId,
            'Prevención',
            $this->demoUserId,
            'Falta firma en el contrato de cesión.'
        );

        $this->assertTrue($resultado);

        $actualizada = $this->solicitudModel->find($solicitudId);
        $this->assertEquals('Prevención', $actualizada->estatus);
    }

    public function testTransicionValida_T01_EvaluacionASeleccionado_True(): void
    {
        $this->assertTrue($this->service->transicionValida('UR-TT-T-01', 'Evaluación comparativa', 'Seleccionado'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-01', 'Evaluación comparativa', 'No seleccionado'));
    }

    public function testTransicionValida_T02_CitaAVerificado_True(): void
    {
        $this->assertTrue($this->service->transicionValida('UR-TT-T-02', 'Recibido', 'Cita agendada'));
        $this->assertTrue($this->service->transicionValida('UR-TT-T-02', 'Cita agendada', 'Verificado'));
    }

    public function testTransicionValida_T03_RevisionAAprobado_True(): void
    {
        $this->assertTrue($this->service->transicionValida('UR-TT-T-03', 'En revisión', 'Aprobado'));
    }

    public function testCalcularVigenciaT07_PeriodoAnio_FechaFinCoincide(): void
    {
        $fechaPago = new DateTime('2025-01-15 10:30:00');
        $solicitudId = $this->crearSolicitud('UR-TT-T-07', 'Pagado', $fechaPago);

        $this->service->calcularVigenciaT07($solicitudId, 'anio');

        $actualizada = $this->solicitudModel->find($solicitudId);

        $this->assertEquals('2025-01-15', $actualizada->fecha_vigencia_inicio);
        $this->assertEquals('2026-01-15', $actualizada->fecha_vigencia_fin);
    }
}
