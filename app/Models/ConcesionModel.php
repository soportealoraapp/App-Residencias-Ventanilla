<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ConcesionModel extends Model
{
    protected $table            = 'concesiones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'numero_titulo',
        'titular_actual',
        'vehiculo_placas',
        'vehiculo_num_serie',
        'tipo_persona',
        'vigencia_inicio',
        'vigencia_fin',
        'estatus',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function findByNumeroTitulo(string $num)
    {
        return $this->where('numero_titulo', $num)->first();
    }
}
