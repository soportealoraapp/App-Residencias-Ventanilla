<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\BoletaChecklistItemModel;
use App\Models\ParametroSistemaModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class InfraccionesApiController extends Controller
{
    /**
     * Retorna el checklist completo de la boleta física agrupado por categoría
     * con los datos oficiales de catalogo_infracciones y montos calculados.
     *
     * Endpoint consumido por la app móvil agentes-transito.
     */
    public function checklist(): ResponseInterface
    {
        $checklistModel = new BoletaChecklistItemModel();
        $paramModel     = new ParametroSistemaModel();

        $valorUmaStr = $paramModel->getValor('valor_uma_vigente', '117.31');
        $valorUma = (float) $valorUmaStr;

        $items = $checklistModel->getChecklistCompleto();

        $agrupado = [];
        $mapeados = 0;
        $sinMapeo = 0;

        foreach ($items as $item) {
            $cat = $item->categoria_boleta ?? 'Otros';
            if (!isset($agrupado[$cat])) {
                $agrupado[$cat] = [];
            }

            $tieneMapeo = !empty($item->catalogo_infraccion_id);
            if ($tieneMapeo) {
                $mapeados++;
            } else {
                $sinMapeo++;
            }

            $minUma = $item->monto_min_uma !== null ? (float) $item->monto_min_uma : null;
            $maxUma = $item->monto_max_uma !== null ? (float) $item->monto_max_uma : null;

            $agrupado[$cat][] = [
                'checklist_item_id'      => (int) $item->checklist_item_id,
                'etiqueta_casilla'       => $item->etiqueta_casilla,
                'catalogo_infraccion_id' => $item->catalogo_infraccion_id !== null ? (int) $item->catalogo_infraccion_id : null,
                'fundamento_legal'       => $item->fundamento_legal,
                'descripcion_oficial'    => $item->descripcion_oficial,
                'categoria_actor'        => $item->categoria_actor,
                'monto_min_uma'          => $minUma,
                'monto_max_uma'          => $maxUma,
                'monto_min_pesos'        => $minUma !== null ? round($minUma * $valorUma, 2) : null,
                'monto_max_pesos'        => $maxUma !== null ? round($maxUma * $valorUma, 2) : null,
            ];
        }

        return $this->response->setJSON([
            'success'               => true,
            'valor_uma_vigente'     => $valorUma,
            'total_items'           => count($items),
            'total_mapeados'        => $mapeados,
            'total_sin_mapeo'       => $sinMapeo,
            'categorias'            => $agrupado,
        ]);
    }
}
