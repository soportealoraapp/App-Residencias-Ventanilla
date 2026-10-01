<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AgenteTransitoModel extends Model
{
    protected $table            = 'agentes_transito';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'placa',
        'nombre_completo',
        'password_hash',
        'rol',
        'sector',
        'activo',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Buscar agente por placa activa.
     */
    public function getPorPlaca(string $placa): ?object
    {
        return $this->where('placa', strtoupper(trim($placa)))->first();
    }
}
