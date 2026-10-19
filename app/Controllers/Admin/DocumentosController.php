<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\DocumentoModel;
use CodeIgniter\Controller;

/**
 * Almacén global de documentos: todos los archivos que entraron al sistema,
 * sin importar de qué expediente vengan.
 *
 * Existe porque el desglose y el panel necesitan enlazar sus tarjetas de
 * documentos a un listado que reproduzca exactamente la cifra que muestran;
 * enlazar a una pantalla que no existe sería mentir con un número.
 */
class DocumentosController extends Controller
{
    /** Columnas por las que se puede ordenar; evita inyectar nombres al orderBy. */
    private const ORDENES = [
        'fecha_carga'  => 'Fecha de carga',
        'tamano_bytes' => 'Tamaño',
    ];

    public function index()
    {
        $filtros = $this->filtros();

        // Dos instancias: paginate() limpia el builder al terminar y el conteo
        // del encabezado necesita ver las mismas condiciones sin la página.
        $conteo = new DocumentoModel();
        $totalFiltrado = $conteo->aplicarFiltros($filtros)->countAllResults();

        $listado = new DocumentoModel();
        $documentos = $listado->buscar($filtros);

        $global    = (new DocumentoModel())->resumenGlobal();
        $porTipo   = (new DocumentoModel())->resumenPorTipo();
        $mesActual = date('Y-m');

        // «Del mes» solo tiene sentido como cifra global: si ya se filtró por
        // mes, la tarjeta debe mostrar lo que hay en pantalla, no el total.
        $delMes = $filtros['mes'] === ''
            ? (new DocumentoModel())->aplicarFiltros(['mes' => $mesActual])->countAllResults()
            : $totalFiltrado;

        return view('admin/documentos', [
            'documentos'    => $documentos,
            'pager'         => $listado->pager,
            'filtros'       => $filtros,
            'totalFiltrado' => $totalFiltrado,
            'global'        => $global,
            'porTipo'       => $porTipo,
            'mesActual'     => $mesActual,
            'delMes'        => $delMes,
            'camposOrden'   => self::ORDENES,
        ]);
    }

    /**
     * Filtros desde la consulta HTTP, validados uno por uno: cualquier valor
     * mal formado se descarta en vez de llegar a la consulta.
     *
     * @return array<string, string>
     */
    private function filtros(): array
    {
        $tipo = trim((string) ($this->request->getGet('tipo') ?? ''));
        if (preg_match('/^[a-z0-9_\-]{1,100}$/i', $tipo) !== 1) {
            $tipo = '';
        }

        $mes = trim((string) ($this->request->getGet('mes') ?? ''));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) !== 1) {
            $mes = '';
        }

        $validado = (string) ($this->request->getGet('validado') ?? '');
        if (! in_array($validado, ['si', 'no'], true)) {
            $validado = '';
        }

        $tramite = trim((string) ($this->request->getGet('tramite') ?? ''));
        if (preg_match('/^[A-Z0-9\-]{1,30}$/', $tramite) !== 1) {
            $tramite = '';
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if (mb_strlen($q) > 80) {
            $q = mb_substr($q, 0, 80);
        }

        $orden = (string) ($this->request->getGet('orden') ?? '');
        if (! array_key_exists($orden, self::ORDENES)) {
            $orden = 'fecha_carga';
        }
        $direccion = strtolower((string) ($this->request->getGet('dir') ?? '')) === 'asc' ? 'ASC' : 'DESC';

        return [
            'tipo'      => $tipo,
            'validado'  => $validado,
            'mes'       => $mes,
            'tramite'   => $tramite,
            'q'         => $q,
            'orden'     => $orden,
            'direccion' => $direccion,
        ];
    }
}
