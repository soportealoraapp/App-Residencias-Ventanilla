<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AgenteTokenModel extends Model
{
    protected $table            = 'agentes_transito_tokens';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'agente_id',
        'token_hash',
        'expira_en',
        'ultimo_uso_en',
        'revocado_en',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    /**
     * Estados posibles de una sesion de la app, definidos con la misma regla que usa
     * el desglose del sistema: si esta revocada lo demas no importa.
     */
    public const ESTADOS = [
        'vigentes'  => 'Vigentes ahora',
        'vencidas'  => 'Vencidas sin cerrar',
        'revocadas' => 'Revocadas',
    ];

    /** Columnas por las que se puede ordenar el listado. */
    public const ORDENES = [
        'expira_en'     => 'Vencimiento',
        'ultimo_uso_en' => 'Ultimo uso',
        'created_at'    => 'Fecha de alta',
    ];

    /**
     * Listado paginado de sesiones.
     *
     * El hash del token nunca se selecciona: es el secreto con el que la app entra,
     * y mostrarlo (aunque sea partido) no aporta nada al control administrativo.
     *
     * @param array<string, string> $filtros
     * @return list<object>
     */
    public function buscar(array $filtros = []): array
    {
        return $this->aplicarFiltros($filtros)
            ->select("agentes_transito_tokens.id as id,
                agentes_transito_tokens.agente_id as agente_id,
                agentes_transito_tokens.expira_en as expira_en,
                agentes_transito_tokens.ultimo_uso_en as ultimo_uso_en,
                agentes_transito_tokens.revocado_en as revocado_en,
                agentes_transito_tokens.created_at as created_at,
                agentes_transito.placa as placa,
                agentes_transito.nombre_completo as nombre,
                agentes_transito.sector as sector,
                agentes_transito.activo as agente_activo")
            ->orderBy('agentes_transito_tokens.' . ($filtros['orden'] ?? 'created_at'), $filtros['direccion'] ?? 'DESC')
            ->orderBy('agentes_transito_tokens.id', 'DESC')
            ->paginate(25);
    }

    /**
     * Filtros comunes al listado y a su conteo. La union vive aqui porque el conteo
     * usa otra instancia del modelo y necesita ver la misma tabla.
     *
     * @param array<string, string> $filtros
     */
    public function aplicarFiltros(array $filtros): self
    {
        $this->join('agentes_transito', 'agentes_transito.id = agentes_transito_tokens.agente_id', 'left');

        $ahora  = date('Y-m-d H:i:s');
        $estado = (string) ($filtros['estado'] ?? '');

        if ($estado === 'vigentes') {
            $this->where('agentes_transito_tokens.revocado_en IS NULL', null, false)
                ->where('agentes_transito_tokens.expira_en >=', $ahora);
        } elseif ($estado === 'vencidas') {
            $this->where('agentes_transito_tokens.revocado_en IS NULL', null, false)
                ->where('agentes_transito_tokens.expira_en <', $ahora);
        } elseif ($estado === 'revocadas') {
            $this->where('agentes_transito_tokens.revocado_en IS NOT NULL', null, false);
        }

        if (($filtros['placa'] ?? '') !== '') {
            $this->where('agentes_transito.placa', strtoupper((string) $filtros['placa']));
        }

        if (($filtros['q'] ?? '') !== '') {
            $q = (string) $filtros['q'];
            $this->groupStart()
                ->like('agentes_transito.placa', $q)
                ->orLike('agentes_transito.nombre_completo', $q)
                ->orLike('agentes_transito.sector', $q)
                ->groupEnd();
        }

        return $this;
    }

    /**
     * Cuanto vive cada estado de sesion, sin filtros: son las cifras del encabezado.
     *
     * @return array<string, int>
     */
    public function resumenEstados(): array
    {
        $ahora = date('Y-m-d H:i:s');

        $fila = $this->db->table('agentes_transito_tokens')
            ->select("COUNT(*) as total,
                SUM(CASE WHEN revocado_en IS NULL AND expira_en >= '$ahora' THEN 1 ELSE 0 END) as vigentes,
                SUM(CASE WHEN revocado_en IS NULL AND expira_en < '$ahora' THEN 1 ELSE 0 END) as vencidas,
                SUM(CASE WHEN revocado_en IS NOT NULL THEN 1 ELSE 0 END) as revocadas,
                MAX(ultimo_uso_en) as ultima_actividad", false)
            ->get()->getRowArray();

        return [
            'total'             => (int) ($fila['total'] ?? 0),
            'vigentes'          => (int) ($fila['vigentes'] ?? 0),
            'vencidas'          => (int) ($fila['vencidas'] ?? 0),
            'revocadas'         => (int) ($fila['revocadas'] ?? 0),
            'ultima_actividad'  => (string) ($fila['ultima_actividad'] ?? ''),
        ];
    }

    /**
     * Sesiones abiertas por agente, para enlazar la tarjeta de «en turno».
     *
     * @return list<array{placa: string, nombre: string, total: int}>
     */
    public function resumenPorAgenteVigente(): array
    {
        $ahora = date('Y-m-d H:i:s');

        return array_map(static fn (array $r): array => [
            'placa'  => (string) $r['placa'],
            'nombre' => (string) ($r['nombre_completo'] ?: $r['placa']),
            'total'  => (int) $r['total'],
        ], $this->db->table('agentes_transito_tokens')
            ->select('agentes_transito.placa as placa, MAX(agentes_transito.nombre_completo) as nombre_completo, COUNT(*) as total')
            ->join('agentes_transito', 'agentes_transito.id = agentes_transito_tokens.agente_id')
            ->where('agentes_transito_tokens.revocado_en IS NULL', null, false)
            ->where('agentes_transito_tokens.expira_en >=', $ahora)
            ->groupBy('agentes_transito.placa')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray());
    }
}
