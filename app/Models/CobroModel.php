<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class CobroModel extends Model
{
    protected $table            = 'cobros';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'folio',
        'origen',
        'solicitud_id',
        'boleta_id',
        'ciudadano_id',
        'nombre_pagador',
        'concepto',
        'monto_base',
        'descuento',
        'recargo',
        'condonado',
        'total',
        'detalle_calculo',
        'estatus',
        'pendiente_documentos',
        'motivo',
        'lugar_pago',
        'referencia_pago',
        'folio_recibo',
        'cobrado_por',
        'fecha_cobro',
        'created_by',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    /** Cobro vigente (pendiente o aprobado) de una solicitud, si existe. */
    public function vigenteDeSolicitud(int $solicitudId): ?object
    {
        return $this->where('solicitud_id', $solicitudId)
            ->whereIn('estatus', ['pendiente', 'aprobado'])
            ->orderBy('id', 'DESC')
            ->first();
    }

    /** Cobro vigente (pendiente o aprobado) de una boleta, si existe. */
    public function vigenteDeBoleta(int $boletaId): ?object
    {
        return $this->where('boleta_id', $boletaId)
            ->whereIn('estatus', ['pendiente', 'aprobado'])
            ->orderBy('id', 'DESC')
            ->first();
    }
}
