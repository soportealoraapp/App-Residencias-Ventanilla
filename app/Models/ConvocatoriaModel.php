<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ConvocatoriaModel extends Model
{
    protected $table = 'convocatorias';
    protected $primaryKey = 'id';
    protected $returnType = 'object';
    protected $allowedFields = [
        'titulo',
        'fecha_publicacion', 'periodo_registro_inicio', 'periodo_registro_fin', 'bases', 'estatus',
        'bases_archivo', 'bases_nombre', 'bases_mime', 'bases_tamano', 'created_by',
    ];
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Estatus que captura el administrador. Es textual, no numérico, porque el
     * portal ciudadano y la app filtran por él y debe leerse sin diccionario.
     */
    public const VIGENTE  = 'Vigente';
    public const CERRADA  = 'Cerrada';
    public const ANULADA  = 'Anulada';

    /** @var array<string, string> */
    public const ESTATUS_OPCIONES = [
        self::VIGENTE => 'Vigente',
        self::CERRADA => 'Cerrada',
        self::ANULADA => 'Anulada',
    ];

    /** Filtros rápidos del listado del panel. */
    public const FILTROS = [
        ''           => 'Todas',
        'abiertas'   => 'Abiertas al registro',
        'por_abrir'  => 'Por abrir',
        'cerradas'   => 'Cerradas',
        'anuladas'   => 'Anuladas',
    ];

    public function vigente(int $id): ?object
    {
        try {
            $hoy = date('Y-m-d');

            return $this->where('id', $id)
                ->where('estatus', self::VIGENTE)
                ->where('periodo_registro_inicio <=', $hoy)
                ->where('periodo_registro_fin >=', $hoy)
                ->first();
        } catch (\Throwable $e) {
            log_message('error', 'ConvocatoriaModel::vigente error: ' . $e->getMessage());
            return null;
        }
    }

    public function primeraVigente(): ?object
    {
        try {
            $hoy = date('Y-m-d');

            return $this->where('estatus', self::VIGENTE)
                ->where('periodo_registro_inicio <=', $hoy)
                ->where('periodo_registro_fin >=', $hoy)
                ->orderBy('id', 'DESC')
                ->first();
        } catch (\Throwable $e) {
            log_message('error', 'ConvocatoriaModel::primeraVigente error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Convocatorias que admiten postulaciones hoy. El filtro compara el texto
     * exacto del estatus: en Postgres las cadenas son sensibles a mayúsculas,
     * así que 'vigente' en minúsculas devolvería una lista vacía.
     *
     * @return array<int, object>
     */
    public function abiertasHoy(): array
    {
        $hoy = date('Y-m-d');

        return $this->where('estatus', self::VIGENTE)
            ->where('periodo_registro_inicio <=', $hoy)
            ->where('periodo_registro_fin >=', $hoy)
            ->orderBy('periodo_registro_fin', 'ASC')
            ->findAll();
    }

    /**
     * Ventana de registro de una convocatoria, calculada y no supuesta: el
     * estatus dice si el ayuntamiento la dejó abierta, y las fechas dicen si
     * hoy un ciudadano puede postular. Las dos cosas no son lo mismo.
     */
    public static function ventana(object $convocatoria, ?string $hoy = null): string
    {
        $hoy ??= date('Y-m-d');

        if ((string) ($convocatoria->estatus ?? '') === self::ANULADA) {
            return 'anulada';
        }

        $inicio = substr((string) ($convocatoria->periodo_registro_inicio ?? ''), 0, 10);
        $fin    = substr((string) ($convocatoria->periodo_registro_fin ?? ''), 0, 10);

        if ($inicio === '' || $fin === '') {
            return 'sin_fechas';
        }

        if ($hoy < $inicio) {
            return 'por_abrir';
        }

        if ($hoy > $fin || (string) $convocatoria->estatus !== self::VIGENTE) {
            return 'cerrada';
        }

        return 'abierta';
    }

    /** @return array{etiqueta: string, tono: string, puede_postular: bool} */
    public static function etiquetaVentana(object $convocatoria, ?string $hoy = null): array
    {
        return match (self::ventana($convocatoria, $hoy)) {
            'abierta'    => ['etiqueta' => 'Abierta al registro', 'tono' => 'success',  'puede_postular' => true],
            'por_abrir'  => ['etiqueta' => 'Por abrir',           'tono' => 'info',     'puede_postular' => false],
            'cerrada'    => ['etiqueta' => 'Registro cerrado',    'tono' => 'secondary', 'puede_postular' => false],
            'anulada'    => ['etiqueta' => 'Anulada',             'tono' => 'danger',   'puede_postular' => false],
            default      => ['etiqueta' => 'Sin fechas definidas', 'tono' => 'warning', 'puede_postular' => false],
        };
    }

    /**
     * cuántas postulaciones tiene cada convocatoria. Una sola consulta
     * agrupada: el listado del panel no puede hacer una consulta por fila.
     *
     * @param array<int, int>|null $ids limita el conteo a esas convocatorias
     * @return array<int, int> id de convocatoria => número de solicitudes
     */
    public function conteoPostulaciones(?array $ids = null): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('solicitudes')
            ->select('convocatoria_id, COUNT(*) AS total')
            ->where('convocatoria_id IS NOT NULL')
            ->groupBy('convocatoria_id');

        if ($ids !== null) {
            if ($ids === []) {
                return [];
            }
            $builder->whereIn('convocatoria_id', $ids);
        }

        $conteo = [];
        foreach ($builder->get()->getResultArray() as $fila) {
            $conteo[(int) $fila['convocatoria_id']] = (int) $fila['total'];
        }

        return $conteo;
    }

    /**
     * Listado del panel con su filtro rápido. El filtro por ventana se resuelve
     * en SQL (no en PHP) para que la paginación cuente lo que realmente muestra.
     *
     * @param array{filtro?: string, q?: string} $filtros
     * @return array{0: array<int, object>, 1: int} páginas y total filtrado
     */
    public function buscar(array $filtros, int $porPagina = 15): array
    {
        $hoy    = date('Y-m-d');
        $filtro = (string) ($filtros['filtro'] ?? '');
        $q      = trim((string) ($filtros['q'] ?? ''));

        if ($q !== '') {
            $this->groupStart()
                ->like('titulo', $q)
                ->orLike('bases', $q)
                ->groupEnd();
        }

        match ($filtro) {
            'abiertas'  => $this->where('estatus', self::VIGENTE)
                                ->where('periodo_registro_inicio <=', $hoy)
                                ->where('periodo_registro_fin >=', $hoy),
            'por_abrir' => $this->where('estatus', self::VIGENTE)
                                ->where('periodo_registro_inicio >', $hoy),
            'cerradas'  => $this->groupStart()
                                ->where('estatus', self::CERRADA)
                                ->orGroupStart()
                                    ->where('estatus', self::VIGENTE)
                                    ->where('periodo_registro_fin <', $hoy)
                                ->groupEnd()
                                ->groupEnd(),
            'anuladas'  => $this->where('estatus', self::ANULADA),
            default     => null,
        };

        $total = $this->countAllResults(false);
        $paginas = $this->orderBy('id', 'DESC')->paginate($porPagina);

        return [$paginas, $total];
    }

    /**
     * Cuántas hay por ventana, para escribir el número en cada filtro rápido
     * del encabezado sin contar en PHP sobre una sola página de resultados.
     *
     * @return array<string, int>
     */
    public function conteos(): array
    {
        $hoy  = date('Y-m-d');
        $db   = \Config\Database::connect();
        $base = static fn () => $db->table('convocatorias');

        return [
            'total'      => $base()->countAllResults(),
            'abiertas'   => $base()->where('estatus', self::VIGENTE)
                                   ->where('periodo_registro_inicio <=', $hoy)
                                   ->where('periodo_registro_fin >=', $hoy)
                                   ->countAllResults(),
            'por_abrir'  => $base()->where('estatus', self::VIGENTE)
                                   ->where('periodo_registro_inicio >', $hoy)
                                   ->countAllResults(),
            'anuladas'   => $base()->where('estatus', self::ANULADA)->countAllResults(),
            'cerradas'   => $base()->groupStart()
                                   ->where('estatus', self::CERRADA)
                                   ->orGroupStart()
                                       ->where('estatus', self::VIGENTE)
                                       ->where('periodo_registro_fin <', $hoy)
                                   ->groupEnd()
                                   ->groupEnd()
                                   ->countAllResults(),
        ];
    }
}
