<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class DocumentoModel extends Model
{
    protected $table            = 'documentos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'solicitud_id',
        'tipo_documento',
        'nombre_original',
        'ruta_interna',
        'mime_type',
        'tamano_bytes',
        'hash_sha256',
        'usuario_id',
        'fecha_carga',
        'validado',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function porSolicitud(int $solicitudId): array
    {
        return $this->where('solicitud_id', $solicitudId)->orderBy('fecha_carga', 'ASC')->findAll();
    }
}
