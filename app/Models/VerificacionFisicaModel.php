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
        try {
            return $this->where('solicitud_id', $solicitudId)->orderBy('fecha_cita', 'DESC')->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'VerificacionFisicaModel::porSolicitud error: ' . $e->getMessage());
            return [];
        }
    }

    public function primerPorSolicitud(int $solicitudId): ?object
    {
        try {
            $res = $this->where('solicitud_id', $solicitudId)->orderBy('fecha_cita', 'DESC')->first();
            return $res ? (object) $res : null;
        } catch (\Throwable $e) {
            log_message('error', 'VerificacionFisicaModel::primerPorSolicitud error: ' . $e->getMessage());
            return null;
        }
    }
}
