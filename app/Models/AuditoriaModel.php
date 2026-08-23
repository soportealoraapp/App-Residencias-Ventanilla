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
}
