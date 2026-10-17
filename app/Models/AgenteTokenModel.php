<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AgenteTokenModel extends Model
{
    protected $table            = 'agentes_transito_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'agente_id',
        'token_hash',
        'expira_en',
        'ultimo_uso_en',
        'revocado_en',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
