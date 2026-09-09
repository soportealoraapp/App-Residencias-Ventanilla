<?php declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Solicitud extends Entity
{
    protected $casts = [
        'id'              => 'integer',
        'ciudadano_id'    => 'integer',
        'concesion_id'    => '?integer',
        'convocatoria_id' => '?integer',
        'monto'           => 'float',
        'fecha_solicitud' => 'datetime',
        'fecha_resolucion'=> '?datetime',
        'fecha_pago'      => '?datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    protected $dates = [
        'fecha_solicitud',
        'fecha_resolucion',
        'fecha_pago',
        'created_at',
        'updated_at',
    ];

    /**
     * Retorna verdadero si la solicitud está pagada o permiso emitido.
     */
    public function estaCompletada(): bool
    {
        return in_array($this->attributes['estatus'] ?? '', ['Pagado', 'Permiso emitido', 'Concluido', 'Vigente'], true);
    }
}
