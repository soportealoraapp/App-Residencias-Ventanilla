<?php declare(strict_types=1);
namespace App\Models;
use CodeIgniter\Model;
class BoletaCalificacionDetalleModel extends Model
{
    protected $table = 'boleta_calificacion_detalles';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['calificacion_id', 'orden', 'falta_id_origen', 'fundamento_legal', 'descripcion', 'categoria', 'monto_min_uma', 'monto_max_uma', 'uma_asignada', 'subtotal_pesos'];
    protected $useTimestamps = false;
}
