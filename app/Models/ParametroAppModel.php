<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ParametroAppModel extends Model
{
    protected $table            = 'parametros_app';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'clave',
        'valor',
        'descripcion',
        'tipo',
        'editable',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Obtiene el valor de un parámetro por su clave.
     */
    public function getValor(string $clave, ?string $default = null): ?string
    {
        $row = $this->where('clave', $clave)->first();
        return $row !== null ? (string) $row->valor : $default;
    }

    /**
     * Obtiene todos los parámetros como mapa asociativo clave => valor.
     */
    public function getMapaParametros(): array
    {
        $rows = $this->findAll();
        $map = [];
        foreach ($rows as $r) {
            $map[$r->clave] = $r->valor;
        }
        return $map;
    }

    /**
     * Actualiza o inserta un parámetro.
     */
    public function setValor(string $clave, string $valor, ?string $descripcion = null, string $tipo = 'texto'): bool
    {
        $row = $this->where('clave', $clave)->first();
        if ($row !== null) {
            return $this->update($row->id, [
                'valor'      => $valor,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return (bool) $this->insert([
            'clave'       => $clave,
            'valor'       => $valor,
            'descripcion' => $descripcion,
            'tipo'        => $tipo,
            'editable'    => 1,
        ]);
    }
}
