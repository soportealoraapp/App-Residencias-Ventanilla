<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ParametroSistemaModel extends Model
{
    protected $table            = 'parametros_sistema';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'clave',
        'valor',
        'descripcion',
        'actualizado_en',
    ];
    protected $useTimestamps    = false;

    /**
     * Obtiene el valor de un parámetro por su clave.
     */
    public function getValor(string $clave, ?string $default = null): ?string
    {
        $row = $this->where('clave', $clave)->first();
        return $row !== null ? (string) $row->valor : $default;
    }
}
