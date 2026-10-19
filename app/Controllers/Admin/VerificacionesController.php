<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\VerificacionFisicaModel;
use CodeIgniter\Controller;

/**
 * Agenda de verificaciones físicas: todas las citas de inspección del sistema,
 * con su expediente y el dictamen que se capturó (o que todavía falta).
 *
 * La pantalla existe porque el desglose enlaza sus tarjetas de verificaciones a
 * un listado que reproduce exactamente la cifra que muestra; enlazar a una
 * pantalla que no existe sería mentir con un número.
 */
class VerificacionesController extends Controller
{
    /** Estados de la cita, con la regla SQL que comparte con el desglose. */
    private const ESTADOS = [
        'proximas'      => 'Próximas citas',
        'vencidas'      => 'Vencidas sin dictamen',
        'con_dictamen'  => 'Con dictamen capturado',
        'sin_dictamen'  => 'Todavía sin dictamen',
    ];

    public function index()
    {
        $filtros = $this->filtros();

        // Dos instancias: paginate() limpia el builder y el conteo del encabezado
        // necesita ver las mismas condiciones sin la página.
        $conteo        = new VerificacionFisicaModel();
        $totalFiltrado = $conteo->aplicarFiltros($filtros)->countAllResults();

        $listado = new VerificacionFisicaModel();
        $citas   = $listado->buscar($filtros);

        return view('admin/verificaciones', [
            'citas'         => $citas,
            'pager'         => $listado->pager,
            'filtros'       => $filtros,
            'totalFiltrado' => $totalFiltrado,
            'global'        => (new VerificacionFisicaModel())->resumenGlobal(),
            'porResultado'  => (new VerificacionFisicaModel())->resumenPorResultado(),
            'estados'       => self::ESTADOS,
            'camposOrden'   => VerificacionFisicaModel::ORDENES,
        ]);
    }

    /**
     * Filtros desde la consulta HTTP, validados uno por uno: cualquier valor mal
     * formado se descarta en lugar de llegar a la consulta.
     *
     * @return array<string, string>
     */
    private function filtros(): array
    {
        $estado = (string) ($this->request->getGet('estado') ?? '');
        if (! array_key_exists($estado, self::ESTADOS)) {
            $estado = '';
        }

        // El dictamen es un texto corto que se envia vinculado a la consulta, no
        // concatenado: basta con acotarlo y quitar caracteres de control.
        $resultado = preg_replace('/[\x00-\x1F\x7F]/u', '', (string) ($this->request->getGet('resultado') ?? ''));
        $resultado = trim((string) $resultado);
        if (mb_strlen($resultado) > 20) {
            $resultado = '';
        }

        $tramite = trim((string) ($this->request->getGet('tramite') ?? ''));
        if (preg_match('/^[A-Z0-9\-]{1,30}$/', $tramite) !== 1) {
            $tramite = '';
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if (mb_strlen($q) > 80) {
            $q = mb_substr($q, 0, 80);
        }

        $desde = $this->fechaValida('desde');
        $hasta = $this->fechaValida('hasta');

        $orden = (string) ($this->request->getGet('orden') ?? '');
        if (! array_key_exists($orden, VerificacionFisicaModel::ORDENES)) {
            $orden = 'fecha_cita';
        }
        $direccion = strtolower((string) ($this->request->getGet('dir') ?? '')) === 'asc' ? 'ASC' : 'DESC';

        return [
            'estado'     => $estado,
            'resultado'  => $resultado,
            'tramite'    => $tramite,
            'q'          => $q,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'orden'      => $orden,
            'direccion'  => $direccion,
        ];
    }

    /**
     * Fecha del formulario en YYYY-MM-DD. No basta con la forma: un 2026-13-45 es
     * escrito con dígitos pero no existe, y consultarlo devolvería una agenda vacía
     * en lugar de avisar. Se descarta y se muestra todo.
     */
    private function fechaValida(string $parametro): string
    {
        $fecha = trim((string) ($this->request->getGet($parametro) ?? ''));

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $m) !== 1) {
            return '';
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $fecha : '';
    }
}
