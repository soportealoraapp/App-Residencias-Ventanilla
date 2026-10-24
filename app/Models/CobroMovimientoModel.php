<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CobroMovimientoModel extends Model
{
    protected $table            = 'cobro_movimientos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'cobro_id',
        'accion',
        'estatus_anterior',
        'estatus_nuevo',
        'motivo',
        'detalle',
        'usuario_id',
        'fecha',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function porCobro(int $cobroId): array
    {
        return $this->where('cobro_id', $cobroId)->orderBy('id', 'ASC')->findAll();
    }
}
