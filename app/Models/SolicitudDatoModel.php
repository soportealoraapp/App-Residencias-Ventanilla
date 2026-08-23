<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SolicitudDatoModel extends Model
{
    protected $table            = 'solicitud_datos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'solicitud_id',
        'clave',
        'valor',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function guardarDatos(int $solicitudId, array $datosClaveValor): void
    {
        $this->where('solicitud_id', $solicitudId)->delete();

        $batch = [];
        foreach ($datosClaveValor as $clave => $valor) {
            $batch[] = [
                'solicitud_id' => $solicitudId,
                'clave'        => $clave,
                'valor'        => $valor,
            ];
        }

        if (!empty($batch)) {
            $this->insertBatch($batch);
        }
    }

    public function porSolicitudAgrupado(int $solicitudId): array
    {
        $rows = $this->where('solicitud_id', $solicitudId)->findAll();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->clave] = $row->valor;
        }

        return $result;
    }
}
