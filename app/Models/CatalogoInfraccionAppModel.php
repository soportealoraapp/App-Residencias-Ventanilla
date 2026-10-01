<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CatalogoInfraccionAppModel extends Model
{
    protected $table            = 'catalogo_infracciones_app';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'fundamento_legal',
        'descripcion',
        'categoria',
        'monto_min_uma',
        'monto_max_uma',
        'activo',
        'orden',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
