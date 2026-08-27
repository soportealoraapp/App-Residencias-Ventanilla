<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class VerificacionFisicaModel extends Model
{
    protected $table = 'verificaciones_fisicas';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = ['solicitud_id', 'fecha_cita', 'resultado', 'observaciones'];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function porSolicitud(int $solicitudId): array
    {
        return $this->where('solicitud_id', $solicitudId)->orderBy('fecha_cita', 'DESC')->findAll();
    }

    public function primerPorSolicitud(int $solicitudId): ?object
    {
        $res = $this->where('solicitud_id', $solicitudId)->orderBy('fecha_cita', 'DESC')->first();
        return $res ? (object) $res : null;
    }
}
