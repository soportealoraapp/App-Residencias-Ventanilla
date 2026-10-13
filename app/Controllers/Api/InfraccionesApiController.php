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

    /**
     * Parámetros de configuración del sistema para la app móvil (UMA, fotos obligatorias, etc.)
     */
    public function parametros(): ResponseInterface
    {
        $origin = $this->request->getHeaderLine('Origin') ?: '*';
        $this->response
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin');

        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        $paramModel = new \App\Models\ParametroAppModel();
        $map = $paramModel->getMapaParametros();

        $defaults = [
            'valor_uma_vigente'       => '117.31',
            'fotos_obligatorias'      => '3',
            'app_version'             => '1.0.0',
            'sync_intervalo_segundos' => '300',
            'municipio'               => 'Uriangato, Guanajuato',
            'modo_estricto_fotos'     => 'true',
        ];

        $resultado = array_merge($defaults, $map);

        return $this->response->setJSON([
            'success'    => true,
            'parametros' => $resultado,
        ]);
    }

    /**
     * Sincroniza una boleta de infracción (o lote) desde la app móvil.
     * Soporta inserción y actualización (upsert por folio).
     */
    public function sincronizar(): ResponseInterface
    {
        $origin = $this->request->getHeaderLine('Origin') ?: '*';
        $this->response
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->setHeader('Access-Control-Max-Age', '7200');

        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        try {
            $payload = $this->request->getJSON(true);
        } catch (\Throwable) {
            $payload = null;
        }

        if (! is_array($payload) || empty($payload)) {
            $payload = $this->request->getPost();
        }

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Datos de boleta inválidos.',
            ]);
        }

        $boletas = isset($payload['boletas']) && is_array($payload['boletas'])
            ? $payload['boletas']
            : (isset($payload['folio']) ? [$payload] : []);

        if (empty($boletas)) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'No se proporcionaron boletas para sincronizar.',
            ]);
        }

        $boletaModel = new \App\Models\BoletaInfraccionModel();
        $sincronizadas = 0;
        $errores = [];

        foreach ($boletas as $b) {
            $folio = trim((string)($b['folio'] ?? ''));
            $placa = strtoupper(trim((string)($b['agente_placa'] ?? '')));

            if ($folio === '' || $placa === '') {
                $errores[] = "Boleta sin folio o agente_placa válido.";
                continue;
            }

            $garantias = $b['garantias_retenidas'] ?? [];
            if (is_array($garantias)) {
                $garantias = json_encode($garantias, JSON_UNESCAPED_UNICODE);
            }

            $faltasJson = $b['faltas_json'] ?? null;
            if (is_array($faltasJson)) {
                $faltasJson = json_encode($faltasJson, JSON_UNESCAPED_UNICODE);
            }

            $data = [
                'folio'                 => $folio,
                'agente_placa'          => $placa,
                'agente_nombre'         => $b['agente_nombre'] ?? null,
                'fecha_infraccion'      => $b['fecha_infraccion'] ?? date('Y-m-d'),
                'hora_infraccion'       => $b['hora_infraccion'] ?? date('H:i:s'),
                'lugar'                 => $b['lugar'] ?? 'No especificado',
                'latitud'               => isset($b['latitud']) && is_numeric($b['latitud']) ? (float)$b['latitud'] : null,
                'longitud'              => isset($b['longitud']) && is_numeric($b['longitud']) ? (float)$b['longitud'] : null,
                'conductor_ausente'     => !empty($b['conductor_ausente']) ? 1 : 0,
                'infractor_nombre'      => $b['infractor_nombre'] ?? null,
                'infractor_domicilio'   => $b['infractor_domicilio'] ?? null,
                'infractor_licencia'    => $b['infractor_licencia'] ?? null,
                'vehiculo_placas'       => $b['vehiculo_placas'] ?? null,
                'sin_placas'            => !empty($b['sin_placas']) ? 1 : 0,
                'vehiculo_marca'        => $b['vehiculo_marca'] ?? null,
                'vehiculo_linea'        => $b['vehiculo_linea'] ?? null,
                'vehiculo_color'        => $b['vehiculo_color'] ?? null,
                'vehiculo_tipo'         => $b['vehiculo_tipo'] ?? 'particular',
                'falta_fundamento_legal'=> $b['falta_fundamento_legal'] ?? null,
                'falta_descripcion'     => $b['falta_descripcion'] ?? null,
                'falta_categoria'       => $b['falta_categoria'] ?? null,
                'falta_monto_min_uma'   => isset($b['falta_monto_min_uma']) && is_numeric($b['falta_monto_min_uma']) ? (float)$b['falta_monto_min_uma'] : null,
                'falta_monto_max_uma'   => isset($b['falta_monto_max_uma']) && is_numeric($b['falta_monto_max_uma']) ? (float)$b['falta_monto_max_uma'] : null,
                'faltas_json'           => $faltasJson,
                'hechos'                => $b['hechos'] ?? null,
                'garantias_retenidas'   => $garantias,
                'inventario_grua'       => $b['inventario_grua'] ?? null,
                'foto_placa_url'        => $b['foto_placa_url'] ?? null,
                'foto_contexto_url'     => $b['foto_contexto_url'] ?? null,
                'foto_documento_url'    => $b['foto_documento_url'] ?? null,
                'estado'                => $b['estado'] ?? 'recibida',
                'sincronizado_en'       => date('Y-m-d H:i:s'),
                'creado_en_dispositivo' => $b['creado_en_dispositivo'] ?? date('Y-m-d H:i:s'),
            ];

            $existente = $boletaModel->where('folio', $folio)->first();
            if ($existente !== null) {
                $boletaModel->update($existente->id, $data);
            } else {
                $boletaModel->insert($data);
            }

            $sincronizadas++;
        }

        return $this->response->setJSON([
            'success'       => $sincronizadas > 0,
            'sincronizadas' => $sincronizadas,
            'errores'       => $errores,
            'message'       => "Se sincronizaron {$sincronizadas} boleta(s) correctamente.",
        ]);
    }

    /**
     * Subida de fotos de evidencia de infracciones (placa, contexto, documento).
     * Guarda en almacenamiento local seguro public/uploads/infracciones/{folio}/
     */
    public function subirEvidencia(): ResponseInterface
    {
        $origin = $this->request->getHeaderLine('Origin') ?: '*';
        $this->response
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->setHeader('Access-Control-Max-Age', '7200');

        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        $folio = trim((string)($this->request->getPost('folio') ?? 'sin_folio'));
        $tipo  = trim((string)($this->request->getPost('tipo') ?? 'evidencia'));
        $folioLimpio = preg_replace('/[^A-Za-z0-9_-]/', '_', $folio);

        $file = $this->request->getFile('foto');
        if ($file !== null && $file->isValid()) {
            $ext = $file->getClientExtension() ?: 'jpg';
            $nombreArchivo = "{$tipo}_" . time() . "_{$folioLimpio}.{$ext}";

            $destDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'infracciones' . DIRECTORY_SEPARATOR . $folioLimpio;
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $file->move($destDir, $nombreArchivo);

            $publicUrl = base_url("uploads/infracciones/{$folioLimpio}/{$nombreArchivo}");
            return $this->response->setJSON([
                'success' => true,
                'url'     => $publicUrl,
            ]);
        }

        $json = $this->request->getJSON(true);
        if (is_array($json) && !empty($json['base64'])) {
            $folio = trim((string)($json['folio'] ?? $folio));
            $tipo  = trim((string)($json['tipo'] ?? $tipo));
            $folioLimpio = preg_replace('/[^A-Za-z0-9_-]/', '_', $folio);

            $destDir = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'infracciones' . DIRECTORY_SEPARATOR . $folioLimpio;
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', (string)$json['base64']);
            $decoded = base64_decode($base64Data, true);
            if ($decoded !== false) {
                $nombreArchivo = "{$tipo}_" . time() . "_{$folioLimpio}.jpg";
                file_put_contents($destDir . DIRECTORY_SEPARATOR . $nombreArchivo, $decoded);
                $publicUrl = base_url("uploads/infracciones/{$folioLimpio}/{$nombreArchivo}");

                return $this->response->setJSON([
                    'success' => true,
                    'url'     => $publicUrl,
                ]);
            }
        }

        return $this->response->setStatusCode(400)->setJSON([
            'success' => false,
            'message' => 'No se recibió un archivo de imagen o base64 válido.',
        ]);
    }
}
