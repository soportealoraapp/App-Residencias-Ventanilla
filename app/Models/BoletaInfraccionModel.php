<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class BoletaInfraccionModel extends Model
{
    protected $table            = 'boletas_infracciones';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'folio',
        'agente_placa',
        'agente_nombre',
        'fecha_infraccion',
        'hora_infraccion',
        'lugar',
        'latitud',
        'longitud',
        'conductor_ausente',
        'infractor_nombre',
        'infractor_domicilio',
        'infractor_licencia',
        'vehiculo_placas',
        'sin_placas',
        'vehiculo_marca',
        'vehiculo_linea',
        'vehiculo_color',
        'vehiculo_tipo',
        'falta_fundamento_legal',
        'falta_descripcion',
        'falta_categoria',
        'falta_monto_min_uma',
        'falta_monto_max_uma',
        'faltas_json',
        'hechos',
        'garantias_retenidas',
        'inventario_grua',
        'foto_placa_url',
        'foto_contexto_url',
        'foto_documento_url',
        'estado',
        'sincronizado_en',
        'creado_en_dispositivo',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
}
