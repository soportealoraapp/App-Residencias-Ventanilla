<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\SolicitudModel;
use App\Models\HistorialEstatusModel;
use App\Models\AuditoriaModel;
use DateTime;

class EstadoSolicitudService
{
    public const ESTATUS_UR_TT_T_07 = [
        'RECIBIDO' => 'Recibido',
        'PAGO_PENDIENTE' => 'Pago pendiente',
        'PAGADO' => 'Pagado',
        'PERMISO_EMITIDO' => 'Permiso emitido',
        'VIGENTE' => 'Vigente',
        'VENCIDO' => 'Vencido',
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

    public const ESTATUS_MAESTRO = [
        'Recibido',
        'En revisión documental',
        'Prevención',
        'Dictaminado aprobado',
        'Pago pendiente',
        'Pagado',
        'Permiso emitido',
        'Vigente',
        'Vencido',
        'Concluido',
        'Rechazado',
    ];

    public const TRANSICIONES_VALIDAS = [
        'UR-TT-T-07' => [
            'Recibido' => ['Pago pendiente', 'Rechazado'],
            'Pago pendiente' => ['Pagado', 'Rechazado'],
            'Pagado' => ['Permiso emitido', 'Rechazado'],
            'Permiso emitido' => ['Vigente'],
            'Vigente' => ['Vencido'],
            'Vencido' => [],
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
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);

        if ($solicitud === null) {
            return false;
        }

        if (!$this->transicionValida($solicitud->tramite, $solicitud->estatus, $nuevoEstatus)) {
            return false;
        }

        if (($nuevoEstatus === 'Prevención' || $nuevoEstatus === 'Rechazado') && ($comentario === null || trim($comentario) === '')) {
            return false;
        }

        $estatusAnterior = $solicitud->estatus;
        $solicitudModel->update($solicitudId, ['estatus' => $nuevoEstatus]);

        $historialModel = new HistorialEstatusModel();
        $ahora = new DateTime();
        $historialModel->insert([
            'solicitud_id' => $solicitudId,
            'estatus_anterior' => $estatusAnterior,
            'estatus_nuevo' => $nuevoEstatus,
            'usuario_id' => $usuarioId,
            'fecha' => $ahora->format('Y-m-d H:i:s'),
            'comentario' => $comentario,
        ]);

        $auditoriaModel = new AuditoriaModel();
        $detalle = array_merge(
            ['estatus_anterior' => $estatusAnterior, 'estatus_nuevo' => $nuevoEstatus],
            $auditoriaDetalle ?? []
        );
        $auditoriaModel->registrar('solicitudes', $solicitudId, 'cambio_estatus_a_' . slug($nuevoEstatus), $usuarioId, $detalle);

        if ($nuevoEstatus === 'Pagado' || $nuevoEstatus === 'Concluido') {
            $solicitudModel->update($solicitudId, ['fecha_resolucion' => $ahora->format('Y-m-d H:i:s')]);
        }

        return true;
    }

    public function calcularVigenciaT07(int $solicitudId, string $periodo): void
    {
        $solicitudModel = new SolicitudModel();
        $solicitud = $solicitudModel->find($solicitudId);

        if ($solicitud === null || $solicitud->tramite !== 'UR-TT-T-07') {
            return;
        }

        $fechaInicio = !empty($solicitud->fecha_pago) ? new DateTime($solicitud->fecha_pago) : new DateTime();
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
