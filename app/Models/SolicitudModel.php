<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class SolicitudModel extends Model
{
    protected $table            = 'solicitudes';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'folio',
        'tramite',
        'ciudadano_id',
        'concesion_id',
        'convocatoria_id',
        'estatus',
        'monto',
        'fecha_solicitud',
        'fecha_resolucion',
        'fecha_pago',
        'fecha_vigencia_inicio',
        'fecha_vigencia_fin',
        'comentario_rechazo',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function findByFolio(string $folio)
    {
        return $this->where('folio', $folio)->first();
    }

    public function porCiudadano(int $ciudadanoId): array
    {
        return $this->where('ciudadano_id', $ciudadanoId)->orderBy('fecha_solicitud', 'DESC')->findAll();
    }

    public function porTramite(string $tramite)
    {
        return $this->where('tramite', $tramite)->orderBy('fecha_solicitud', 'DESC');
    }

    public function porEstatus(string $estatus)
    {
        return $this->where('estatus', $estatus)->orderBy('fecha_solicitud', 'DESC');
    }

    public function porConvocatoria(int $convocatoriaId): array
    {
        return $this->where('tramite', 'UR-TT-T-01')
            ->where('convocatoria_id', $convocatoriaId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
