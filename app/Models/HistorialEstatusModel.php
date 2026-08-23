<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class HistorialEstatusModel extends Model
{
    protected $table            = 'historial_estatus';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'solicitud_id',
        'estatus_anterior',
        'estatus_nuevo',
        'usuario_id',
        'fecha',
        'comentario',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function porSolicitud(int $solicitudId): array
    {
        return $this->where('solicitud_id', $solicitudId)->orderBy('fecha', 'ASC')->findAll();
    }
}
