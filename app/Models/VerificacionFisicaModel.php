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

    /**
     * Expresion SQL que decide si una cita sigue sin dictamen.
     *
     * Una cadena vacia significa lo mismo que NULL: el inspector no capturo resultado.
     * Se comparte con el desglose del sistema para que la misma cifra signifique lo
     * mismo en las dos pantallas.
     */
    public const SIN_DICTAMEN_SQL = "(verificaciones_fisicas.resultado IS NULL OR verificaciones_fisicas.resultado = '')";

    /** Columnas por las que el listado puede ordenarse. */
    public const ORDENES = [
        'fecha_cita' => 'Fecha de la cita',
        'created_at' => 'Fecha de registro',
    ];

    /**
     * Listar paginado respetando exactamente los filtros del encabezado.
     *
     * @param array<string, string> $filtros
     * @return list<object>
     */
    public function buscar(array $filtros = []): array
    {
        return $this->aplicarFiltros($filtros)
            ->select('verificaciones_fisicas.*, solicitudes.folio as folio, solicitudes.tramite as tramite, solicitudes.estatus as estatus')
            ->orderBy('verificaciones_fisicas.' . ($filtros['orden'] ?? 'fecha_cita'), $filtros['direccion'] ?? 'DESC')
            ->orderBy('verificaciones_fisicas.id', 'DESC')
            ->paginate(25);
    }

    /**
     * Aplica los filtros compartidos por el listado y por su conteo.
     *
     * Las uniones viven aqui, y no en buscar(), porque el conteo del encabezado
     * usa una instancia distinta y necesita ver las mismas tablas.
     *
     * @param array<string, string> $filtros
     */
    public function aplicarFiltros(array $filtros): self
    {
        $this->join('solicitudes', 'solicitudes.id = verificaciones_fisicas.solicitud_id', 'left');

        $ahora = date('Y-m-d H:i:s');
        $estado = (string) ($filtros['estado'] ?? '');

        if ($estado === 'sin_dictamen') {
            $this->where(self::SIN_DICTAMEN_SQL, null, false);
        } elseif ($estado === 'con_dictamen') {
            $this->where('verificaciones_fisicas.resultado IS NOT NULL', null, false)
                ->where('verificaciones_fisicas.resultado !=', '');
        } elseif ($estado === 'vencidas') {
            $this->where('verificaciones_fisicas.fecha_cita <', $ahora)->where(self::SIN_DICTAMEN_SQL, null, false);
        } elseif ($estado === 'proximas') {
            $this->where('verificaciones_fisicas.fecha_cita >=', $ahora);
        }

        if (($filtros['resultado'] ?? '') !== '') {
            $resultado = (string) $filtros['resultado'];

            // El reparto por dictamen agrupa nulos y vacios bajo «sin_dictamen», así que
            // ese valor consultado debe abrir exactamente esas mismas filas.
            if ($resultado === 'sin_dictamen') {
                $this->where(self::SIN_DICTAMEN_SQL, null, false);
            } else {
                $this->where('verificaciones_fisicas.resultado', $resultado);
            }
        }

        if (($filtros['tramite'] ?? '') !== '') {
            $this->where('solicitudes.tramite', (string) $filtros['tramite']);
        }

        if (($filtros['desde'] ?? '') !== '') {
            $this->where('verificaciones_fisicas.fecha_cita >=', substr((string) $filtros['desde'], 0, 10) . ' 00:00:00');
        }

        if (($filtros['hasta'] ?? '') !== '') {
            $this->where('verificaciones_fisicas.fecha_cita <=', substr((string) $filtros['hasta'], 0, 10) . ' 23:59:59');
        }

        if (($filtros['q'] ?? '') !== '') {
            $q     = (string) $filtros['q'];
            $like  = '%' . $q . '%';

            // El nombre del interesado no vive en solicitudes sino en datos clave/valor,
            // igual que lo busca el listado de solicitudes.
            $this->groupStart()
                ->like('solicitudes.folio', $q)
                ->orLike('verificaciones_fisicas.observaciones', $q)
                ->orWhere("EXISTS (SELECT 1 FROM solicitud_datos sd WHERE sd.solicitud_id = solicitudes.id AND sd.clave IN ('razon_social_o_nombre', 'solicitante_nombre', 'nombre_solicitante') AND sd.valor LIKE " . $this->escape($like) . ")")
                ->groupEnd();
        }

        return $this;
    }

    /**
     * Cifras globales del encabezado. Se calculan sin filtros: son el total real.
     *
     * La hora de referencia la pone PHP, igual que los filtros del listado, para que
     * «vencidas» y «proximas» sumen lo mismo aqui que al pulsar la tarjeta.
     *
     * @return array<string, int>
     */
    public function resumenGlobal(): array
    {
        $ahora = date('Y-m-d H:i:s');

        $fila = $this->db->table('verificaciones_fisicas')
            ->select("COUNT(*) as total,"
                . ' SUM(CASE WHEN ' . self::SIN_DICTAMEN_SQL . ' THEN 1 ELSE 0 END) as sin_dictamen,'
                . ' SUM(CASE WHEN ' . self::SIN_DICTAMEN_SQL . " AND fecha_cita < '$ahora' THEN 1 ELSE 0 END) as vencidas,"
                . " SUM(CASE WHEN fecha_cita >= '$ahora' THEN 1 ELSE 0 END) as proximas", false)
            ->get()->getRowArray();

        $total       = (int) ($fila['total'] ?? 0);
        $sinDictamen = (int) ($fila['sin_dictamen'] ?? 0);

        return [
            'total'        => $total,
            'sin_dictamen' => $sinDictamen,
            'con_dictamen' => $total - $sinDictamen,
            'vencidas'     => (int) ($fila['vencidas'] ?? 0),
            'proximas'     => (int) ($fila['proximas'] ?? 0),
        ];
    }

    /**
     * Reparto de las citas por dictamen capturado.
     *
     * @return list<array{resultado: string, total: int}>
     */
    public function resumenPorResultado(): array
    {
        return array_map(static fn (array $r): array => [
            'resultado' => (string) ($r['resultado'] ?? 'sin_dictamen'),
            'total'     => (int) $r['total'],
        ], $this->db->table('verificaciones_fisicas')
            ->select("COALESCE(NULLIF(resultado, ''), 'sin_dictamen') as resultado, COUNT(*) as total", false)
            ->groupBy('resultado')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray());
    }
}
