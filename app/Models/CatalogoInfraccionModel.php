<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CatalogoInfraccionModel extends Model
{
    protected $table            = 'catalogo_infracciones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'fundamento_legal',
        'descripcion',
        'categoria_actor',
        'monto_min_uma',
        'monto_max_uma',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
