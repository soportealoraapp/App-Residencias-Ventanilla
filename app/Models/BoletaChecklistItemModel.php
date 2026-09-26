<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class BoletaChecklistItemModel extends Model
{
    protected $table            = 'boleta_checklist_item';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'categoria_boleta',
        'etiqueta_casilla',
        'catalogo_infraccion_id',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    /**
     * Obtiene el checklist completo con datos unidos del catálogo oficial.
     */
    public function getChecklistCompleto(): array
    {
        return $this->select('
                boleta_checklist_item.id AS checklist_item_id,
                boleta_checklist_item.categoria_boleta,
                boleta_checklist_item.etiqueta_casilla,
                boleta_checklist_item.catalogo_infraccion_id,
                catalogo_infracciones.fundamento_legal,
                catalogo_infracciones.descripcion AS descripcion_oficial,
                catalogo_infracciones.categoria_actor,
                catalogo_infracciones.monto_min_uma,
                catalogo_infracciones.monto_max_uma
            ')
            ->join('catalogo_infracciones', 'catalogo_infracciones.id = boleta_checklist_item.catalogo_infraccion_id', 'left')
            ->orderBy('boleta_checklist_item.categoria_boleta', 'ASC')
            ->orderBy('boleta_checklist_item.id', 'ASC')
            ->findAll();
    }
}
