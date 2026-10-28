<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\SolicitudModel;
use App\Models\SolicitudDatoModel;
use App\Models\HistorialEstatusModel;
use App\Models\AuditoriaModel;
use DateTime;

class EstadoSolicitudService
{
    private const CRITERIOS_AUTORIZACION_UR05 = [
        'afluencia_baja',
        'sin_transporte_publico',
        'horario_no_entorpece',
    ];

    public const ESTATUS_UR_TT_T_07 = [
        'RECIBIDO' => 'Recibido',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'PAGADO' => 'Pagado',
        'PERMISO_EMITIDO' => 'Permiso emitido',
        'VIGENTE' => 'Vigente',
        'VENCIDO' => 'Vencido',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_UR_TT_T_04 = [
        'RECIBIDO' => 'Recibido',
        'EN_REVISION' => 'En revisión documental',
        'PREVENCION' => 'Prevención',
        'DOCUMENTOS_COMPLETOS' => 'Documentos completos',
        'ESTUDIO_TECNICO' => 'En estudio técnico',
        'DICTAMEN_FAVORABLE' => 'Dictamen favorable',
        'PENDIENTE_INSPECCION' => 'Pendiente de inspección',
        'PENDIENTE_REVISTA' => 'Pendiente de revista mecánica',
        'SEGURO_PENDIENTE' => 'Seguro pendiente de validación',
        'AUTORIZADO_PAGO' => 'Autorizado para pago',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'PAGADO' => 'Pagado',
        'PERMISO_EMITIDO' => 'Permiso emitido',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_UR_TT_T_05 = [
        'RECIBIDO' => 'Recibido',
        'EN_VALIDACION' => 'En validación',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'PAGADO' => 'Pagado',
        'PERMISO_EMITIDO' => 'Permiso emitido',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_UR_TT_T_06 = [
        'RECIBIDO' => 'Recibido',
        'EN_REVISION' => 'En revisión documental',
        'PREVENCION' => 'Prevención',
        'DICTAMINADO_APROBADO' => 'Dictaminado aprobado',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'CONCLUIDO' => 'Concluido',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_UR_TT_T_01 = [
        'RECIBIDO' => 'Recibido',
        'EN_REVISION' => 'En revisión',
        'EVALUACION_COMPARATIVA' => 'Evaluación comparativa',
        'SELECCIONADO' => 'Seleccionado',
        'NO_SELECCIONADO' => 'No seleccionado',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'PAGADO' => 'Pagado',
        'TITULO_EMITIDO' => 'Título emitido',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_UR_TT_T_02 = [
        'RECIBIDO' => 'Recibido',
        'CITA_AGENDADA' => 'Cita agendada',
        'VERIFICADO' => 'Verificado',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'PAGADO' => 'Pagado',
        'CONSTANCIA_EMITIDA' => 'Constancia emitida',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_UR_TT_T_03 = [
        'RECIBIDO' => 'Recibido',
        'EN_REVISION' => 'En revisión',
        'APROBADO' => 'Aprobado',
        'RECHAZADO' => 'Rechazado',
    ];

    public const ESTATUS_MAESTRO = [
        'Recibido',
        'En revisión',
        'Evaluación comparativa',
        'Seleccionado',
        'No seleccionado',
        'Cita agendada',
        'Verificado',
        'Aprobado',
        'En revisión documental',
        'En validación',
        'Documentos completos',
        'En estudio técnico',
        'Dictamen favorable',
        'Pendiente de inspección',
        'Pendiente de revista mecánica',
        'Seguro pendiente de validación',
        'Autorizado para pago',
        'Prevención',
        'Dictaminado aprobado',
        'Pago pendiente',
        'Pagado',
        'Permiso emitido',
        'Título emitido',
        'Constancia emitida',
        'Vigente',
        'Vencido',
        'Concluido',
        'Rechazado',
    ];

    public const TRANSICIONES_VALIDAS = [
        'UR-TT-T-01' => [
            'Recibido' => ['En revisión', 'Evaluación comparativa', 'Seleccionado', 'No seleccionado', 'Rechazado'],
            'En revisión' => ['Evaluación comparativa', 'Seleccionado', 'No seleccionado', 'Rechazado'],
            'Evaluación comparativa' => ['Seleccionado', 'No seleccionado', 'Rechazado'],
            // Ficha UR-TT-T-01: el ganador cubre los derechos y recibe su título-concesión.
            'Seleccionado' => ['Pago pendiente'],
            'Pago pendiente' => ['Pagado', 'Rechazado'],
            'Pagado' => ['Título emitido'],
            'Título emitido' => [],
            'No seleccionado' => [],
            'Rechazado' => [],
        ],
        'UR-TT-T-02' => [
            'Recibido' => ['Cita agendada', 'Rechazado'],
            'Cita agendada' => ['Verificado', 'Rechazado'],
            // Ficha UR-TT-T-02: con el vehículo verificado se paga al juez y se entrega la constancia.
            'Verificado' => ['Pago pendiente'],
            'Pago pendiente' => ['Pagado', 'Rechazado'],
            'Pagado' => ['Constancia emitida'],
            'Constancia emitida' => [],
            'Rechazado' => [],
        ],
        'UR-TT-T-03' => [
            'Recibido' => ['En revisión', 'Rechazado'],
            'En revisión' => ['Aprobado', 'Rechazado'],
            'Aprobado' => [],
            'Rechazado' => [],
        ],
        'UR-TT-T-07' => [
            'Recibido' => ['Pago pendiente', 'Rechazado'],
            'Pago pendiente' => ['Pagado', 'Rechazado'],
            'Pagado' => ['Permiso emitido', 'Rechazado'],
            'Permiso emitido' => ['Vigente'],
            'Vigente' => ['Vencido'],
            'Vencido' => [],
            'Rechazado' => [],
        ],
        'UR-TT-T-04' => [
            'Recibido' => ['En revisión documental', 'Rechazado'],
            'En revisión documental' => ['Prevención', 'Documentos completos', 'Rechazado'],
            'Prevención' => ['En revisión documental', 'Rechazado'],
            'Documentos completos' => ['En estudio técnico', 'Rechazado'],
            'En estudio técnico' => ['Dictamen favorable', 'Rechazado'],
            'Dictamen favorable' => ['Pendiente de inspección', 'Rechazado'],
            'Pendiente de inspección' => ['Pendiente de revista mecánica', 'Rechazado'],
            'Pendiente de revista mecánica' => ['Seguro pendiente de validación', 'Rechazado'],
            'Seguro pendiente de validación' => ['Autorizado para pago', 'Rechazado'],
            'Autorizado para pago' => ['Pago pendiente', 'Rechazado'],
            'Pago pendiente' => ['Pagado', 'Rechazado'],
            'Pagado' => ['Permiso emitido'],
            'Permiso emitido' => [],
            'Rechazado' => [],
        ],
        'UR-TT-T-05' => [
            'Recibido' => ['En validación', 'Rechazado'],
            'En validación' => ['Pago pendiente', 'Rechazado'],
            'Pago pendiente' => ['Pagado', 'Rechazado'],
            'Pagado' => ['Permiso emitido'],
            'Permiso emitido' => [],
            'Rechazado' => [],
        ],
        'UR-TT-T-06' => [
            'Recibido' => ['En revisión documental', 'Rechazado'],
            'En revisión documental' => ['Prevención', 'Dictaminado aprobado', 'Rechazado'],
            'Prevención' => ['En revisión documental', 'Rechazado'],
            'Dictaminado aprobado' => ['Pago pendiente', 'Rechazado'],
            'Pago pendiente' => ['Concluido', 'Rechazado'],
            'Concluido' => [],
            'Rechazado' => [],
        ],
    ];

    public function transicionValida(string $tramite, string $estatusActual, string $nuevoEstatus): bool
    {
        if (!isset(self::TRANSICIONES_VALIDAS[$tramite])) {
            return false;
        }

        if (!isset(self::TRANSICIONES_VALIDAS[$tramite][$estatusActual])) {
            return false;
        }

        return in_array($nuevoEstatus, self::TRANSICIONES_VALIDAS[$tramite][$estatusActual], true);
    }

    public function cambiarEstatus(int $solicitudId, string $nuevoEstatus, ?int $usuarioId = null, ?string $comentario = null, ?array $auditoriaDetalle = null): bool
    {
        return $this->cambiarEstatusInterno($solicitudId, $nuevoEstatus, $usuarioId, $comentario, $auditoriaDetalle, false);
    }

    /**
     * Autoriza el pago de UR-05 únicamente desde "En validación" y después de
     * comprobar los tres criterios de seguridad vial. La captura de criterios,
     * el cambio de estado, el historial, la auditoría y el cobro comparten la
     * misma transacción.
     *
     * @param array<string, mixed> $criterios
     */
    public function autorizarPagoUr05(int $solicitudId, array $criterios, ?int $usuarioId = null, ?string $comentario = null): bool
    {
        foreach (self::CRITERIOS_AUTORIZACION_UR05 as $campo) {
            if (($criterios[$campo] ?? null) !== '1') {
                return false;
            }
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        // PostgreSQL bloquea la fila durante toda la operación. Así una segunda
        // autorización concurrente revalida el estado que dejó la primera.
        if ($db->DBDriver === 'Postgre') {
            $solicitud = $db->query(
                'SELECT tramite, estatus FROM solicitudes WHERE id = ? FOR UPDATE',
                [$solicitudId]
            )->getRow();
        } else {
            $solicitud = (new SolicitudModel())->find($solicitudId);
        }

        if ($solicitud === null
            || $solicitud->tramite !== 'UR-TT-T-05'
            || $solicitud->estatus !== 'En validación') {
            $db->transRollback();
            return false;
        }

        $datosModel = new SolicitudDatoModel();
        $datos = $datosModel->porSolicitudAgrupado($solicitudId);
        foreach (self::CRITERIOS_AUTORIZACION_UR05 as $campo) {
            $datos[$campo] = '1';
        }
        $datos['observaciones_validacion'] = trim((string) $comentario);
        $datosModel->guardarDatos($solicitudId, $datos);

        $detalle = ['criterios_ur05' => array_fill_keys(self::CRITERIOS_AUTORIZACION_UR05, true)];
        $ok = $this->cambiarEstatusInterno(
            $solicitudId,
            'Pago pendiente',
            $usuarioId,
            trim((string) $comentario) ?: 'Criterios de cierre validados. Pago autorizado.',
            $detalle,
            true
        );

        if (! $ok) {
            $db->transRollback();
            return false;
        }

        if (! $db->transStatus()) {
            $db->transRollback();
            return false;
        }

        return $db->transCommit();
    }

    private function cambiarEstatusInterno(int $solicitudId, string $nuevoEstatus, ?int $usuarioId, ?string $comentario, ?array $auditoriaDetalle, bool $autorizarPagoUr05): bool
    {
        $usuarioId = ($usuarioId !== null && $usuarioId > 0) ? $usuarioId : null;
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);

        if ($solicitud === null) {
            return false;
        }

        // UR-05 exige una revisión específica antes de generar el cobro. Esta
        // condición protege también llamadas directas al endpoint genérico.
        if ($solicitud->tramite === 'UR-TT-T-05'
            && $nuevoEstatus === 'Pago pendiente'
            && ! $autorizarPagoUr05) {
            return false;
        }

        if (!$this->transicionValida($solicitud->tramite, $solicitud->estatus, $nuevoEstatus)) {
            return false;
        }

        if (($nuevoEstatus === 'Prevención' || $nuevoEstatus === 'Rechazado') && ($comentario === null || trim($comentario) === '')) {
            return false;
        }

        $estatusAnterior = $solicitud->estatus;

        $db = \Config\Database::connect();
        $db->transStart();

        $solicitudModel->update($solicitudId, ['estatus' => $nuevoEstatus]);

        $historialModel = model(HistorialEstatusModel::class);
        $ahora = new DateTime();
        $historialModel->insert([
            'solicitud_id' => $solicitudId,
            'estatus_anterior' => $estatusAnterior,
            'estatus_nuevo' => $nuevoEstatus,
            'usuario_id' => $usuarioId,
            'fecha' => $ahora->format('Y-m-d H:i:s'),
            'comentario' => $comentario,
        ]);

        $auditoriaModel = model(AuditoriaModel::class);
        $detalle = array_merge(
            ['estatus_anterior' => $estatusAnterior, 'estatus_nuevo' => $nuevoEstatus],
            $auditoriaDetalle ?? []
        );
        $auditoriaModel->registrar('solicitudes', $solicitudId, 'cambio_estatus_a_' . slug($nuevoEstatus), $usuarioId, $detalle);

        if ($nuevoEstatus === 'Pagado' || $nuevoEstatus === 'Concluido') {
            $solicitudModel->update($solicitudId, ['fecha_resolucion' => $ahora->format('Y-m-d H:i:s')]);
        }

        // El cobro sigue a la solicitud: se crea en "Pago pendiente", se aprueba
        // al pagarse y se cancela si la solicitud se rechaza.
        (new CobroService())->sincronizarConSolicitud($solicitudId, $nuevoEstatus, $usuarioId, $comentario);

        $db->transComplete();

        return $db->transStatus();
    }

    public function calcularVigenciaT07(int $solicitudId, string $periodo): void
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);

        if ($solicitud === null || $solicitud->tramite !== 'UR-TT-T-07') {
            return;
        }

        $fechaPagoRaw = $solicitud->fecha_pago;
        if ($fechaPagoRaw instanceof \DateTimeInterface) {
            $fechaInicio = DateTime::createFromInterface($fechaPagoRaw);
        } elseif (!empty($fechaPagoRaw)) {
            $fechaInicio = new DateTime((string) $fechaPagoRaw);
        } else {
            $fechaInicio = new DateTime();
        }
        $fechaFin = clone $fechaInicio;

        switch ($periodo) {
            case 'dia':
                $fechaFin->modify('+1 day');
                break;
            case 'mes':
                $fechaFin->modify('+1 month');
                break;
            case 'semestre':
                $fechaFin->modify('+6 months');
                break;
            case 'anio':
                $fechaFin->modify('+1 year');
                break;
        }

        $solicitudModel->update($solicitudId, [
            'fecha_vigencia_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_vigencia_fin' => $fechaFin->format('Y-m-d'),
        ]);
    }
}
