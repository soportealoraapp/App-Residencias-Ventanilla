<?php declare(strict_types=1);
namespace App\Models;
use CodeIgniter\Model;
class BoletaCalificacionModel extends Model
{
    protected $table = 'boleta_calificaciones';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['boleta_id', 'juez_id', 'cobro_id', 'nombre_infractor', 'valor_uma', 'total_uma', 'monto_base', 'calificada_en'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
