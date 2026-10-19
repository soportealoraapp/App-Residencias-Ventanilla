<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;
use DateTime;

class AuditoriaModel extends Model
{
    protected $table            = 'auditoria';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'entidad',
        'entidad_id',
        'accion',
        'usuario_id',
        'fecha',
        'detalle',
        'registro_borrados',
        'created_at',
    ];
    protected $useTimestamps    = false;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = '';
    protected $deletedField     = '';

    public function registrar(string $entidad, int|string|null $entidadId, string $accion, ?int $usuarioId, ?array $detalle = null, string|array|null $registroBorrado = null): void
    {
        try {
            $ahora = new DateTime();
            $fecha = $ahora->format('Y-m-d H:i:s');

            $data = [
                'entidad'           => $entidad,
                'entidad_id'        => $entidadId,
                'accion'            => $accion,
                'usuario_id'        => $usuarioId,
                'fecha'             => $fecha,
                // Sin escapar el unicode para que el detalle se lea en español,
                // y sustituyendo bytes inválidos: un nombre de archivo mal codificado
                // no debe dejar el registro de auditoría sin detalle.
                'detalle'           => $detalle !== null ? json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
                'registro_borrados' => $registroBorrado !== null ? (is_string($registroBorrado) ? $registroBorrado : json_encode($registroBorrado, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)) : null,
                'created_at'        => $fecha,
            ];

            $this->insert($data);
        } catch (\Throwable $e) {
            log_message('error', 'Error al registrar auditoría: ' . $e->getMessage());
        }
    }

    public function porEntidad(string $entidad, int|string|null $entidadId): array
    {
        return $this->where('entidad', $entidad)
            ->where('entidad_id', $entidadId)
            ->orderBy('fecha', 'DESC')
            ->findAll();
    }

    /**
     * Cuenta inicios de sesión fallidos desde una IP en los últimos minutos.
     *
     * Se apoya en la tabla de auditoría (ya existente) para que el límite funcione
     * en servidores sin estado como Vercel, donde la caché es efímera y el
     * Throttler de CI4 no persiste entre instancias.
     */
    public function intentosFallidosPorIp(string $ip, int $minutos = 15): int
    {
        if ($ip === '') {
            return 0;
        }

        $desde = date('Y-m-d H:i:s', time() - ($minutos * 60));

        $filas = $this->select('detalle')
            ->where('accion', 'login_fallido')
            ->where('fecha >=', $desde)
            ->orderBy('fecha', 'DESC')
            ->limit(1000)
            ->get()
            ->getResult();

        $total = 0;
        foreach ($filas as $fila) {
            $detalle = $fila->detalle ?? null;
            if (is_string($detalle)) {
                $detalle = json_decode($detalle, true);
            }
            if (is_array($detalle) && ($detalle['ip'] ?? null) === $ip) {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Cuenta registros de ciudadanos creados desde una IP en los últimos minutos.
     * Frena el alta automatizada masiva (spam) apoyándose en la BD, ya que la
     * caché es efímera en entornos serverless como Vercel.
     */
    public function registrosPorIp(string $ip, int $minutos = 60): int
    {
        if ($ip === '') {
            return 0;
        }

        $desde = date('Y-m-d H:i:s', time() - ($minutos * 60));

        $filas = $this->select('detalle')
            ->where('accion', 'registro_ciudadano')
            ->where('fecha >=', $desde)
            ->orderBy('fecha', 'DESC')
            ->limit(1000)
            ->get()
            ->getResult();

        $total = 0;
        foreach ($filas as $fila) {
            $detalle = $fila->detalle ?? null;
            if (is_string($detalle)) {
                $detalle = json_decode($detalle, true);
            }
            if (is_array($detalle) && ($detalle['ip'] ?? null) === $ip) {
                $total++;
            }
        }

        return $total;
    }

    public function buscar(array $filtros = []): array
    {
        if (! empty($filtros['entidad'])) {
            $this->where('auditoria.entidad', $filtros['entidad']);
        }
        if (! empty($filtros['accion'])) {
            $this->where('auditoria.accion', $filtros['accion']);
        }
        // Identificador del registro afectado: permite abrir la trazabilidad de
        // una sola solicitud, documento o boleta desde su propio expediente.
        // La columna es BIGINT: solo se acepta un valor numérico para no enviar
        // texto a una comparación entera.
        $entidadId = (string) ($filtros['entidad_id'] ?? '');
        if ($entidadId !== '' && ctype_digit($entidadId)) {
            $this->where('auditoria.entidad_id', (int) $entidadId);
        }
        if (! empty($filtros['usuario_id']) && ctype_digit((string) $filtros['usuario_id'])) {
            $this->where('auditoria.usuario_id', (int) $filtros['usuario_id']);
        }
        if (! empty($filtros['fecha_desde'])) {
            $this->where('auditoria.fecha >=', $filtros['fecha_desde'] . ' 00:00:00');
        }
        if (! empty($filtros['fecha_hasta'])) {
            $this->where('auditoria.fecha <=', $filtros['fecha_hasta'] . ' 23:59:59');
        }

        return $this->select('auditoria.*, users.username, users.nombre_completo')
            ->join('users', 'users.id = auditoria.usuario_id', 'left')
            ->orderBy('auditoria.fecha', 'DESC')
            ->paginate(25);
    }
}
