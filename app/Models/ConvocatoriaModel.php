<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ConvocatoriaModel extends Model
{
    protected $table = 'convocatorias';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'fecha_publicacion', 'periodo_registro_inicio', 'periodo_registro_fin', 'bases', 'estatus',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function vigente(int $id): ?object
    {
        try {
            $hoy = date('Y-m-d');

            return $this->where('id', $id)
                ->where('estatus', 'Vigente')
                ->where('periodo_registro_inicio <=', $hoy)
                ->where('periodo_registro_fin >=', $hoy)
                ->first();
        } catch (\Throwable $e) {
            log_message('error', 'ConvocatoriaModel::vigente error: ' . $e->getMessage());
            return null;
        }
    }

    public function primeraVigente(): ?object
    {
        try {
            $hoy = date('Y-m-d');

            return $this->where('estatus', 'Vigente')
                ->where('periodo_registro_inicio <=', $hoy)
                ->where('periodo_registro_fin >=', $hoy)
                ->orderBy('id', 'DESC')
                ->first();
        } catch (\Throwable $e) {
            log_message('error', 'ConvocatoriaModel::primeraVigente error: ' . $e->getMessage());
            return null;
        }
    }
}
