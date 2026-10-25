<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\AuditoriaModel;
use App\Models\BoletaCalificacionDetalleModel;
use App\Models\BoletaCalificacionModel;
use App\Models\BoletaInfraccionModel;
use App\Models\CobroModel;
use CodeIgniter\Database\BaseConnection;
use Throwable;

class CalificarBoletaService
{
    public function __construct(
        private readonly ?ResolverMontoInfraccionService $montos = null,
        private readonly ?CobroService $cobros = null,
        private readonly ?BaseConnection $db = null,
    ) {
    }

    /** @return list<array<string,mixed>> */
    public function faltasDeBoleta(object $boleta): array
    {
        $valor = $boleta->faltas_json ?? null;
        for ($i = 0; $i < 2 && is_string($valor); $i++) {
            $valor = json_decode($valor, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new CalificacionBoletaException('Las faltas de la boleta están dañadas y no se pueden calificar.');
            }
        }
        if (! is_array($valor) || $valor === [] || ! array_is_list($valor)) {
            throw new CalificacionBoletaException('La boleta no contiene un listado válido de faltas.');
        }
        foreach ($valor as $falta) {
            if (! is_array($falta)) {
                throw new CalificacionBoletaException('Las faltas de la boleta están incompletas y no se pueden calificar.');
            }
        }
        return $valor;
    }

    public function vistaPrevia(object $boleta, array $seleccionesUma): array
    {
        try {
            $calculo = $this->resolver()->calcularCalificacion($this->faltasDeBoleta($boleta), $seleccionesUma);
            $calculo['estimacion'] = $this->resolver()->aplicarDescuentoRecargo($calculo['monto_base'], $boleta->fecha_infraccion ?? null);
            return $calculo;
        } catch (CalificacionBoletaException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CalificacionBoletaException($e->getMessage(), 0, $e);
        }
    }

    /** @return array{calificacion_id:int,cobro_id:int,calculo:array} */
    public function calificar(int $boletaId, string $nombreInfractor, array $seleccionesUma, int $juezId): array
    {
        $nombreInfractor = trim(preg_replace('/\s+/', ' ', $nombreInfractor) ?? '');
        if ($nombreInfractor === '' || strcasecmp($nombreInfractor, 'Conductor Ausente') === 0) {
            throw new CalificacionBoletaException('Es obligatorio confirmar o capturar el nombre del infractor.');
        }
        if (mb_strlen($nombreInfractor) > 180) {
            throw new CalificacionBoletaException('El nombre del infractor no puede exceder 180 caracteres.');
        }
        if ($juezId <= 0) {
            throw new CalificacionBoletaException('No se pudo identificar al juez calificador.');
        }

        $db = $this->conexion();
        $db->transBegin();
        try {
            $boleta = $this->boletaBloqueada($boletaId, $db);
            if ($boleta === null) {
                throw new CalificacionBoletaException('La boleta no existe.');
            }
            if ((string) $boleta->estado !== 'recibida') {
                throw new CalificacionBoletaException('La boleta ya no está pendiente de calificación.');
            }

            $calificaciones = new BoletaCalificacionModel();
            if ($calificaciones->where('boleta_id', $boletaId)->first() !== null) {
                throw new CalificacionBoletaException('La boleta ya fue calificada.');
            }
            if ((new CobroModel())->vigenteDeBoleta($boletaId) !== null) {
                throw new CalificacionBoletaException('La boleta ya tiene un cobro vigente sin una calificación vinculada.');
            }
            $calculo = $this->vistaPrevia($boleta, $seleccionesUma);
            $calificacionId = (int) $calificaciones->insert([
                'boleta_id' => $boletaId,
                'juez_id' => $juezId,
                'nombre_infractor' => $nombreInfractor,
                'valor_uma' => $calculo['valor_uma'],
                'total_uma' => $calculo['total_uma'],
                'monto_base' => $calculo['monto_base'],
                'calificada_en' => date('Y-m-d H:i:s'),
            ]);
            if ($calificacionId <= 0) {
                throw new CalificacionBoletaException('No se pudo guardar la calificación.');
            }

            $detalles = new BoletaCalificacionDetalleModel();
            foreach ($calculo['detalles'] as $detalle) {
                if (! $detalles->insert(['calificacion_id' => $calificacionId] + $detalle)) {
                    throw new CalificacionBoletaException('No se pudo guardar el detalle de la calificación.');
                }
            }

            $cobroId = $this->servicioCobros()->crearDesdeBoleta($boletaId, (float) $calculo['monto_base'], $juezId, $nombreInfractor);
            if ($cobroId <= 0 || ! $calificaciones->update($calificacionId, ['cobro_id' => $cobroId])) {
                throw new CalificacionBoletaException('No se pudo vincular el cobro generado.');
            }
            if (! (new BoletaInfraccionModel())->update($boletaId, ['infractor_nombre' => $nombreInfractor, 'estado' => 'calificada'])) {
                throw new CalificacionBoletaException('No se pudo actualizar el estado de la boleta.');
            }

            model(AuditoriaModel::class)->registrar('boletas_infracciones', $boletaId, 'calificar', $juezId, [
                'calificacion_id' => $calificacionId,
                'cobro_id' => $cobroId,
                'total_uma' => $calculo['total_uma'],
                'monto_base' => $calculo['monto_base'],
            ]);
            if (! $db->transStatus() || ! $db->transCommit()) {
                throw new CalificacionBoletaException('No se pudo completar la calificación.');
            }
            return ['calificacion_id' => $calificacionId, 'cobro_id' => $cobroId, 'calculo' => $calculo];
        } catch (Throwable $e) {
            $db->transRollback();
            if ($e instanceof CalificacionBoletaException) {
                throw $e;
            }
            throw new CalificacionBoletaException('No se pudo completar la calificación.', 0, $e);
        }
    }

    private function boletaBloqueada(int $boletaId, BaseConnection $db): ?object
    {
        if ($db->DBDriver === 'Postgre') {
            return $db->query('SELECT * FROM boletas_infracciones WHERE id = ? FOR UPDATE', [$boletaId])->getRow();
        }
        return $db->table('boletas_infracciones')->where('id', $boletaId)->get()->getRow();
    }

    private function resolver(): ResolverMontoInfraccionService
    {
        return $this->montos ?? new ResolverMontoInfraccionService();
    }

    private function servicioCobros(): CobroService
    {
        return $this->cobros ?? new CobroService($this->resolver());
    }

    private function conexion(): BaseConnection
    {
        return $this->db ?? \Config\Database::connect();
    }
}
