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

    /**
     * Listado global de archivos con su expediente y quien lo subió.
     *
     * La ventanilla necesita responder «¿dónde está el comprobante de X?» sin
     * ir folio por folio, por eso se traen folio, trámite y estatus en la misma
     * consulta: un solo viaje a la base para pintar la tabla completa.
     *
     * @param array<string, string> $filtros
     * @return array<int, object>
     */
    public function buscar(array $filtros = []): array
    {
        return $this->aplicarFiltros($filtros)
            ->select('documentos.*, solicitudes.folio as folio, solicitudes.tramite as tramite, solicitudes.estatus as estatus, users.nombre_completo as usuario_nombre, users.username as usuario_username')
            ->orderBy('documentos.' . ($filtros['orden'] ?? 'fecha_carga'), $filtros['direccion'] ?? 'DESC')
            ->orderBy('documentos.id', 'DESC')
            ->paginate(25);
    }

    /**
     * Cuántos archivos hay por tipo, con su peso y cuántos ya se revisaron.
     * Sirve de etiquetas de filtro con conteo real en la pantalla de documentos
     * y del desglose, para que ninguna cifra se enlaze a un listado vacío.
     *
     * @return array<int, array<string, int|string>>
     */
    public function resumenPorTipo(): array
    {
        $filas = $this->db->table('documentos')
            ->select("COALESCE(NULLIF(tipo_documento, ''), 'sin_clasificar') as tipo, COUNT(*) as total, SUM(tamano_bytes) as bytes, SUM(CASE WHEN validado = 1 THEN 1 ELSE 0 END) as validados", false)
            ->groupBy('tipo')
            ->orderBy('total', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $r): array => [
            'tipo'      => (string) ($r['tipo'] ?? 'sin_clasificar'),
            'total'     => (int) $r['total'],
            'bytes'     => (float) ($r['bytes'] ?? 0),
            'validados' => (int) ($r['validados'] ?? 0),
        ], $filas);
    }

    /**
     * Totales del almacén sin filtros: son las cifras que las tarjetas muestran
     * y que el enlace «sin filtrar» debe reproducir exactamente.
     *
     * @return array<string, int|float>
     */
    public function resumenGlobal(): array
    {
        $fila = $this->db->table('documentos')
            ->select('COUNT(*) as total, SUM(tamano_bytes) as bytes, SUM(CASE WHEN validado = 1 THEN 1 ELSE 0 END) as validados')
            ->get()
            ->getRowArray();

        $total     = (int) ($fila['total'] ?? 0);
        $validados = (int) ($fila['validados'] ?? 0);

        return [
            'total'      => $total,
            'bytes'      => (float) ($fila['bytes'] ?? 0),
            'validados'  => $validados,
            'por_validar' => $total - $validados,
        ];
    }

    /**
     * Condiciones y relaciones comunes al conteo y al listado: dos instancias
     * del modelo aplican exactamente el mismo filtro para que el encabezado no
     * mienta. Los enlaces son muchos-a-uno, así que no duplican renglones.
     *
     * @param array<string, string> $filtros
     */
    public function aplicarFiltros(array $filtros): self
    {
        $this->join('solicitudes', 'solicitudes.id = documentos.solicitud_id', 'left');
        $this->join('users', 'users.id = documentos.usuario_id', 'left');

        if (($filtros['tipo'] ?? '') !== '') {
            if ($filtros['tipo'] === 'sin_clasificar') {
                $this->groupStart()->where('documentos.tipo_documento IS NULL', null, false)->orWhere('documentos.tipo_documento', '')->groupEnd();
            } else {
                $this->where('documentos.tipo_documento', $filtros['tipo']);
            }
        }
        if (($filtros['validado'] ?? '') === 'si') {
            $this->where('documentos.validado', 1);
        } elseif (($filtros['validado'] ?? '') === 'no') {
            $this->where('documentos.validado', 0);
        }
        if (($filtros['mes'] ?? '') !== '') {
            $this->where('documentos.fecha_carga >=', $filtros['mes'] . '-01 00:00:00');
            $this->where('documentos.fecha_carga <=', date('Y-m-t 23:59:59', strtotime($filtros['mes'] . '-01')));
        }
        if (($filtros['tramite'] ?? '') !== '') {
            $this->where('solicitudes.tramite', $filtros['tramite']);
        }
        if (($filtros['q'] ?? '') !== '') {
            $q = (string) $filtros['q'];
            $this->groupStart()
                ->like('solicitudes.folio', $q)
                ->orLike('documentos.nombre_original', $q)
                ->groupEnd();
        }

        return $this;
    }
}
