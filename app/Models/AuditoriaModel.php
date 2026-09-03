<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use DateTime;

class AuditoriaModel extends Model
{
    protected $table            = 'auditoria';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'entidad',
        'entidad_id',
        'accion',
        'usuario_id',
        'fecha',
        'detalle',
        'created_at',
    ];
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = '';
    protected $deletedField     = '';

    public function registrar(string $entidad, $entidadId, string $accion, ?int $usuarioId, ?array $detalle): void
    {
        $ahora = new DateTime();
        $fecha = $ahora->format('Y-m-d H:i:s');

        $data = [
            'entidad'    => $entidad,
            'entidad_id' => $entidadId,
            'accion'     => $accion,
            'usuario_id' => $usuarioId,
            'fecha'      => $fecha,
            'detalle'    => $detalle !== null ? json_encode($detalle) : null,
            'created_at' => $fecha,
        ];

        $this->insert($data);
    }

    public function porEntidad(string $entidad, $entidadId): array
    {
        return $this->where('entidad', $entidad)
            ->where('entidad_id', $entidadId)
            ->orderBy('fecha', 'DESC')
            ->findAll();
    }

    public function buscar(array $filtros = []): array
    {
        if (! empty($filtros['entidad'])) {
            $this->where('auditoria.entidad', $filtros['entidad']);
        }
        if (! empty($filtros['accion'])) {
            $this->where('auditoria.accion', $filtros['accion']);
        }
        if (! empty($filtros['usuario_id']) && ctype_digit((string) $filtros['usuario_id'])) {
            $this->where('auditoria.usuario_id', (int) $filtros['usuario_id']);
        }
        if (! empty($filtros['fecha_desde'])) {
            $this->where('auditoria.fecha >=', $filtros['fecha_desde'] . ' 00:00:00');
        }
        if (! empty($filtros['fecha_hasta'])) {
            $this->where('auditoria.fecha <=', $filtros['fecha_hasta'] . ' 23:59:59');
        }

        return $this->select('auditoria.*, users.username, users.nombre_completo')
            ->join('users', 'users.id = auditoria.usuario_id', 'left')
            ->orderBy('auditoria.fecha', 'DESC')
            ->paginate(25);
    }
}
