<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\AgenteTokenModel;
use CodeIgniter\Controller;

/**
 * Sesiones de la app de agentes de tránsito: cada token emitido, a qué agente
 * pertenece, cuándo venció y cuándo se usó por última vez.
 *
 * La pantalla existe porque el desglose del sistema muestra cuatro cifras de
 * sesiones y cada una debe abrir el listado filtrado por exactamente ese estado.
 * El hash del token no se lista en ninguna parte: es el secreto de acceso.
 */
class SesionesAppController extends Controller
{
    public function index()
    {
        $filtros = $this->filtros();

        // Dos instancias: paginate() limpia el builder y el conteo necesita ver
        // las mismas condiciones sin la página.
        $conteo        = new AgenteTokenModel();
        $totalFiltrado = $conteo->aplicarFiltros($filtros)->countAllResults();

        $listado  = new AgenteTokenModel();
        $sesiones = $listado->buscar($filtros);

        return view('admin/sesiones_app', [
            'sesiones'      => $sesiones,
            'pager'         => $listado->pager,
            'filtros'       => $filtros,
            'totalFiltrado' => $totalFiltrado,
            'global'        => (new AgenteTokenModel())->resumenEstados(),
            'porAgente'     => (new AgenteTokenModel())->resumenPorAgenteVigente(),
            'estados'       => AgenteTokenModel::ESTADOS,
            'camposOrden'   => AgenteTokenModel::ORDENES,
        ]);
    }

    /**
     * Filtros validados uno por uno; un valor mal formado se descarta en lugar de
     * llegar a la consulta.
     *
     * @return array<string, string>
     */
    private function filtros(): array
    {
        $estado = (string) ($this->request->getGet('estado') ?? '');
        if (! array_key_exists($estado, AgenteTokenModel::ESTADOS)) {
            $estado = '';
        }

        $placa = strtoupper(trim((string) ($this->request->getGet('placa') ?? '')));
        if (preg_match('/^[A-Z0-9\- ]{1,20}$/', $placa) !== 1) {
            $placa = '';
        }

        $q = trim((string) ($this->request->getGet('q') ?? ''));
        if (mb_strlen($q) > 80) {
            $q = mb_substr($q, 0, 80);
        }

        $orden = (string) ($this->request->getGet('orden') ?? '');
        if (! array_key_exists($orden, AgenteTokenModel::ORDENES)) {
            $orden = 'created_at';
        }
        $direccion = strtolower((string) ($this->request->getGet('dir') ?? '')) === 'asc' ? 'ASC' : 'DESC';

        return [
            'estado'    => $estado,
            'placa'     => $placa,
            'q'         => $q,
            'orden'     => $orden,
            'direccion' => $direccion,
        ];
    }
}
