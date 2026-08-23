<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use DateTime;

class TarifaModel extends Model
{
    protected $table            = 'tarifas';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'tramite',
        'criterio',
        'monto',
        'vigente_desde',
        'vigente_hasta',
        'descripcion',
        'placeholder_oficial',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function vigente(string $tramite, string $criterio, ?DateTime $fecha = null): ?object
    {
        if ($fecha === null) {
            $fecha = new DateTime();
        }

        $fechaStr = $fecha->format('Y-m-d');

        return $this->where('tramite', $tramite)
            ->where('criterio', $criterio)
            ->where('vigente_desde <=', $fechaStr)
            ->groupStart()
            ->where('vigente_hasta IS NULL')
            ->orWhere('vigente_hasta >=', $fechaStr)
            ->groupEnd()
            ->orderBy('vigente_desde', 'DESC')
            ->first();
    }
}
