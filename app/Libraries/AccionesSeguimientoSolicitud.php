<?php declare(strict_types=1);

namespace App\Libraries;

/**
 * Presentación y contrato del endpoint genérico de seguimiento de solicitudes.
 * Las transiciones siguen perteneciendo a EstadoSolicitudService; aquí se les
 * da una etiqueta y se separan las que requieren un flujo especializado.
 */
final class AccionesSeguimientoSolicitud
{
    /** @var array<string, array{etiqueta: string, icono: string, clase: string, motivo: bool, confirmacion: string|null}> */
    private const DESCRIPTORES = [
        'En revisión' => ['etiqueta' => 'Iniciar revisión', 'icono' => 'bi-search', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Evaluación comparativa' => ['etiqueta' => 'Iniciar evaluación comparativa', 'icono' => 'bi-list-check', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Seleccionado' => ['etiqueta' => 'Seleccionar solicitud', 'icono' => 'bi-check-circle', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Seleccionar esta solicitud en la evaluación comparativa?'],
        'No seleccionado' => ['etiqueta' => 'Marcar no seleccionada', 'icono' => 'bi-dash-circle', 'clase' => 'btn-outline-secondary', 'motivo' => false, 'confirmacion' => '¿Marcar esta solicitud como no seleccionada?'],
        'Cita agendada' => ['etiqueta' => 'Agendar verificación', 'icono' => 'bi-calendar-event', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Verificado' => ['etiqueta' => 'Aprobar dictamen de verificación', 'icono' => 'bi-clipboard-check', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => null],
        'Aprobado' => ['etiqueta' => 'Aprobar solicitud', 'icono' => 'bi-check-circle', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Aprobar esta solicitud?'],
        'En revisión documental' => ['etiqueta' => 'Iniciar revisión documental', 'icono' => 'bi-file-earmark-search', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Prevención' => ['etiqueta' => 'Pedir documentos', 'icono' => 'bi-file-earmark-exclamation', 'clase' => 'btn-outline-warning', 'motivo' => true, 'confirmacion' => '¿Enviar esta solicitud a prevención?'],
        'Documentos completos' => ['etiqueta' => 'Confirmar documentos completos', 'icono' => 'bi-files', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'En estudio técnico' => ['etiqueta' => 'Iniciar estudio técnico', 'icono' => 'bi-clipboard-data', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Dictamen favorable' => ['etiqueta' => 'Registrar dictamen favorable', 'icono' => 'bi-file-earmark-check', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Registrar el dictamen como favorable?'],
        'Pendiente de inspección' => ['etiqueta' => 'Enviar a inspección', 'icono' => 'bi-search', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Pendiente de revista mecánica' => ['etiqueta' => 'Enviar a revista mecánica', 'icono' => 'bi-tools', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Seguro pendiente de validación' => ['etiqueta' => 'Enviar seguro a validación', 'icono' => 'bi-shield-check', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Autorizado para pago' => ['etiqueta' => 'Autorizar para pago', 'icono' => 'bi-check2-circle', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Autorizar esta solicitud para pago?'],
        'En validación' => ['etiqueta' => 'Iniciar validación', 'icono' => 'bi-search', 'clase' => 'btn-outline-primary', 'motivo' => false, 'confirmacion' => null],
        'Dictaminado aprobado' => ['etiqueta' => 'Aprobar dictamen', 'icono' => 'bi-file-earmark-check', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Aprobar el dictamen de esta solicitud?'],
        'Pago pendiente' => ['etiqueta' => 'Autorizar pago', 'icono' => 'bi-cash-coin', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Autorizar el pago? Se generará un cobro pendiente.'],
        'Pagado' => ['etiqueta' => 'Registrar pago', 'icono' => 'bi-cash-stack', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => null],
        'Concluido' => ['etiqueta' => 'Registrar pago y concluir', 'icono' => 'bi-cash-stack', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => null],
        'Permiso emitido' => ['etiqueta' => 'Emitir permiso', 'icono' => 'bi-file-earmark-check', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Emitir el permiso de esta solicitud?'],
        'Vigente' => ['etiqueta' => 'Activar vigencia', 'icono' => 'bi-calendar-check', 'clase' => 'btn-success', 'motivo' => false, 'confirmacion' => '¿Marcar este permiso como vigente?'],
        'Vencido' => ['etiqueta' => 'Marcar permiso vencido', 'icono' => 'bi-calendar-x', 'clase' => 'btn-outline-secondary', 'motivo' => false, 'confirmacion' => '¿Marcar este permiso como vencido?'],
        'Rechazado' => ['etiqueta' => 'Rechazar solicitud', 'icono' => 'bi-x-circle', 'clase' => 'btn-outline-danger', 'motivo' => true, 'confirmacion' => '¿Rechazar esta solicitud? Esta acción no se puede deshacer.'],
    ];

    /** @var array<string, array<string, string[]>> */
    private const FLUJOS_ESPECIALIZADOS = [
        'UR-TT-T-01' => [
            'Recibido' => ['Seleccionado', 'No seleccionado'],
            'En revisión' => ['Seleccionado', 'No seleccionado'],
            'Evaluación comparativa' => ['Seleccionado', 'No seleccionado'],
        ],
        'UR-TT-T-02' => [
            'Recibido' => ['Cita agendada'],
            'Cita agendada' => ['Verificado', 'Rechazado'],
        ],
    ];

    /** @var string[] Estados que solo puede producir el flujo real de cobro. */
    private const RESULTADOS_DE_COBRO = ['Pagado', 'Concluido'];

    /**
     * @return array<int, array{destino: string, etiqueta: string, icono: string, clase: string, motivo: bool, confirmacion: string|null}>
     */
    public static function genericas(string $tramite, string $estatusActual): array
    {
        $acciones = [];
        foreach (EstadoSolicitudService::TRANSICIONES_VALIDAS[$tramite][$estatusActual] ?? [] as $destino) {
            if (self::permiteEndpointGenerico($tramite, $estatusActual, $destino)) {
                $acciones[] = ['destino' => $destino] + self::descriptor($destino);
            }
        }

        return $acciones;
    }

    public static function permiteEndpointGenerico(string $tramite, string $estatusActual, string $destino): bool
    {
        if (! in_array($destino, EstadoSolicitudService::TRANSICIONES_VALIDAS[$tramite][$estatusActual] ?? [], true)) {
            return false;
        }
        if (in_array($destino, self::RESULTADOS_DE_COBRO, true)) {
            return false;
        }

        return ! in_array($destino, self::FLUJOS_ESPECIALIZADOS[$tramite][$estatusActual] ?? [], true);
    }

    /** @return array{etiqueta: string, icono: string, clase: string, motivo: bool, confirmacion: string|null} */
    public static function descriptor(string $destino): array
    {
        return self::DESCRIPTORES[$destino] ?? [
            'etiqueta' => $destino,
            'icono' => 'bi-arrow-right-circle',
            'clase' => 'btn-outline-primary',
            'motivo' => false,
            'confirmacion' => null,
        ];
    }

    public static function requiereFlujoEspecializado(string $tramite, string $estatusActual): bool
    {
        return (self::FLUJOS_ESPECIALIZADOS[$tramite][$estatusActual] ?? []) !== [];
    }

    public static function requiereRegistroCobro(string $tramite, string $estatusActual): bool
    {
        foreach (EstadoSolicitudService::TRANSICIONES_VALIDAS[$tramite][$estatusActual] ?? [] as $destino) {
            if (in_array($destino, self::RESULTADOS_DE_COBRO, true)) {
                return true;
            }
        }

        return false;
    }
}
