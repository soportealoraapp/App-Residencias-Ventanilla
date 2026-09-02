<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class FormatoTramiteModel extends Model
{
    protected $table            = 'formatos_tramite';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'tramite',
        'nombre',
        'descripcion',
        'nombre_archivo',
        'ruta_interna',
        'mime_type',
        'tamano_bytes',
        'usuario_id',
        'activo',
    ];
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function porTramite(string $tramite): ?object
    {
        return $this->where('tramite', $tramite)
            ->where('activo', 1)
            ->first();
    }

    public function todos(): array
    {
        return $this->orderBy('tramite', 'ASC')->findAll();
    }
}
