<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\BoletaCalificacionModel;
use App\Models\CobroModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Acciones de seguimiento de una boleta en el panel administrativo (COB-12).
 *
 * No califica ni cobra: solo decide qué enlaces mostrar y si la corrección
 * ordinaria sigue permitida. La calificación sigue siendo COB-10 y el pago
 * sigue siendo CobroService.
 */
class BoletaAccionesService
{
    /**
     * Motivo por el que la boleta no admite corrección ordinaria, o null si la
     * admite. Lee estado, calificación y cobros con los datos actuales de la BD.
     */
    public function motivoNoEditable(object $boleta): ?string
    {
        $estado = (string) ($boleta->estado ?? '');
        if ($estado !== 'recibida') {
            return 'Solo una boleta en estado Recibida admite corrección de datos. Esta boleta está en estado ' . ucfirst($estado) . '.';
        }

        $id = (int) $boleta->id;
        if ((new BoletaCalificacionModel())->where('boleta_id', $id)->first() !== null) {
            return 'La boleta ya tiene una calificación asociada y sus datos de origen no pueden editarse.';
        }
        if ((new CobroModel())->where('origen', 'boleta')->where('boleta_id', $id)->first() !== null) {
            return 'La boleta ya tiene un cobro asociado y sus datos de origen no pueden editarse.';
        }

        return null;
    }

    /** SELECT ... FOR UPDATE en PostgreSQL, igual que CalificarBoletaService. */
    public function bloquear(int $boletaId, BaseConnection $db): ?object
    {
        if ($db->DBDriver === 'Postgre') {
            return $db->query('SELECT * FROM boletas_infracciones WHERE id = ? FOR UPDATE', [$boletaId])->getRow();
        }

        return $db->table('boletas_infracciones')->where('id', $boletaId)->get()->getRow();
    }

    /**
     * Acciones por boleta para el listado, con el mínimo de consultas.
     *
     * @param list<object> $boletas
     * @param list<string> $roles
     *
     * @return array<int, array{editar:bool, calificar_url:?string, cobro_url:?string, hoja_url:?string, nota:?string}>
     */
    public function paraLista(array $boletas, array $roles): array
    {
        $ids = array_values(array_unique(array_map(static fn ($b): int => (int) $b->id, $boletas)));
        if ($ids === []) {
            return [];
        }

        $calificaciones = [];
        foreach ((new BoletaCalificacionModel())->whereIn('boleta_id', $ids)->findAll() as $c) {
            $calificaciones[(int) $c->boleta_id] = $c;
        }
        $cobrosPorBoleta = [];
        foreach ((new CobroModel())->where('origen', 'boleta')->whereIn('boleta_id', $ids)->findAll() as $c) {
            $cobrosPorBoleta[(int) $c->boleta_id][(int) $c->id] = $c;
        }

        $esAdmin   = in_array('administrador', $roles, true);
        $esJuez    = in_array('juez_calificador', $roles, true);
        $verAdmin  = $esAdmin || (in_array('operador_ventanilla', $roles, true) && PermisosOperador::activo('cobros'));
        $editarOk  = $esAdmin || (in_array('operador_ventanilla', $roles, true) && PermisosOperador::activo('app_transito_boletas'));

        $acciones = [];
        foreach ($boletas as $b) {
            $id      = (int) $b->id;
            $estado  = (string) ($b->estado ?? '');
            $cobros  = $cobrosPorBoleta[$id] ?? [];
            $accion  = ['editar' => false, 'calificar_url' => null, 'cobro_url' => null, 'hoja_url' => null, 'nota' => null];

            if ($estado === 'recibida') {
                $accion['editar'] = $editarOk && ! isset($calificaciones[$id]) && $cobros === [];
                if ($esJuez && ! isset($calificaciones[$id]) && $cobros === []) {
                    $accion['calificar_url'] = '/juez/boletas/' . $id;
                }
            } elseif ($estado === 'calificada' || $estado === 'pagada') {
                // El cobro se toma del vínculo real de la calificación y se valida que sea de esta boleta.
                $cobro = null;
                $cobroId = (int) ($calificaciones[$id]->cobro_id ?? 0);
                if ($cobroId > 0 && isset($cobros[$cobroId])) {
                    $cobro = $cobros[$cobroId];
                }

                if ($cobro === null) {
                    $accion['nota'] = 'Esta boleta no tiene un cobro vinculado. No se genera otro desde este panel.';
                } elseif ((string) $cobro->estatus === 'cancelado') {
                    $accion['nota'] = 'El cobro vinculado está cancelado. No se genera otro ni se recalifica desde este panel.';
                } else {
                    $base = $verAdmin ? '/admin/cobros/' : ($esJuez ? '/juez/cobros/' : null);
                    if ($base !== null) {
                        $accion['cobro_url'] = $base . (int) $cobro->id;
                        if ($estado === 'pagada' && (string) $cobro->estatus === 'aprobado') {
                            $accion['hoja_url'] = '/documentos/hoja-pago/boleta/' . $id;
                        }
                    }
                }
            }

            $acciones[$id] = $accion;
        }

        return $acciones;
    }
}
