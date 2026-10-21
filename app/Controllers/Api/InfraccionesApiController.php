<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\AgenteTokenService;
use App\Models\BoletaChecklistItemModel;
use App\Models\CatalogoInfraccionAppModel;
use App\Models\ParametroAppModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class InfraccionesApiController extends Controller
{
    /**
     * Retorna el catálogo de infracciones que consume la app móvil.
     *
     * Fuente primaria: catálogo administrado desde el panel web
     * (catalogo_infracciones_app), de modo que el alta/edición/baja que hace el
     * administrador se refleje en la app. Si ese catálogo está vacío se usa el
     * checklist oficial de la boleta física (boleta_checklist_item + catalogo_infracciones)
     * como respaldo para no dejar la app sin faltas.
     *
     * El valor de la UMA se toma de parametros_app, la misma tabla que edita el panel.
     */
    public function checklist(): ResponseInterface
    {
        $valorUma = (float) (new ParametroAppModel())->getValor('valor_uma_vigente', '117.31');

        // 1. Fuente primaria: catálogo administrable desde el panel web.
        $filasApp = (new CatalogoInfraccionAppModel())
            ->where('activo', 1)
            ->orderBy('orden', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (! empty($filasApp)) {
            $agrupado = [];
            $total = 0;
            foreach ($filasApp as $r) {
                $cat = strtoupper(trim((string) ($r->categoria ?? ''))) ?: 'GENERAL';
                if (! isset($agrupado[$cat])) {
                    $agrupado[$cat] = [];
                }
                $agrupado[$cat][] = $this->formatearItemCatalogoApp($r, $valorUma);
                $total++;
            }

            return $this->response->setJSON([
                'success'           => true,
                'fuente'            => 'catalogo_infracciones_app',
                'valor_uma_vigente' => $valorUma,
                'total_items'       => $total,
                'categorias'        => $agrupado,
            ]);
        }

        // 2. Respaldo: checklist oficial de la boleta física.
        $items = (new BoletaChecklistItemModel())->getChecklistCompleto();

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
            'fuente'                => 'checklist_oficial',
            'valor_uma_vigente'     => $valorUma,
            'total_items'           => count($items),
            'total_mapeados'        => $mapeados,
            'total_sin_mapeo'       => $sinMapeo,
            'categorias'            => $agrupado,
        ]);
    }

    /**
     * Adapta una fila del catálogo administrable (catalogo_infracciones_app) a la
     * forma que la app móvil espera en /api/checklist-infracciones.
     */
    private function formatearItemCatalogoApp(object $fila, float $valorUma): array
    {
        $minUma = $fila->monto_min_uma !== null ? (float) $fila->monto_min_uma : null;
        $maxUma = $fila->monto_max_uma !== null ? (float) $fila->monto_max_uma : null;

        return [
            'checklist_item_id'   => (int) $fila->id,
            'etiqueta_casilla'    => $fila->fundamento_legal,
            'fundamento_legal'    => $fila->fundamento_legal,
            'descripcion_oficial' => $fila->descripcion,
            'categoria_actor'     => $fila->categoria,
            'monto_min_uma'       => $minUma,
            'monto_max_uma'       => $maxUma,
            'monto_min_pesos'     => $minUma !== null ? round($minUma * $valorUma, 2) : null,
            'monto_max_pesos'     => $maxUma !== null ? round($maxUma * $valorUma, 2) : null,
        ];
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

        $agente = $this->agenteAutenticado();
        if ($agente === null) {
            return $this->respuestaNoAutenticado();
        }
        $placaAgente = strtoupper(trim((string) $agente->placa));

        $boletaModel = new \App\Models\BoletaInfraccionModel();

        // Se valida el lote completo antes de escribir: un agente solo puede
        // enviar boletas a su nombre y no puede sobrescribir folios de otro agente.
        foreach ($boletas as $b) {
            if (! is_array($b)) {
                continue;
            }
            $folio = trim((string)($b['folio'] ?? ''));
            $placa = strtoupper(trim((string)($b['agente_placa'] ?? '')));

            foreach (['conductor_ausente', 'sin_placas'] as $campoBooleano) {
                if (! array_key_exists($campoBooleano, $b) || ! is_bool($b[$campoBooleano])) {
                    return $this->response->setStatusCode(422)->setJSON([
                        'success' => false,
                        'message' => "El campo {$campoBooleano} de la boleta {$folio} debe ser booleano.",
                    ]);
                }
            }

            if ($placa !== '' && $placa !== $placaAgente) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => "La boleta {$folio} no corresponde al agente autenticado.",
                ]);
            }

            if ($folio !== '' && ! $this->folioPermitido($boletaModel, $folio, $placaAgente)) {
                return $this->respuestaFolioAjeno($folio);
            }
        }

        $sincronizadas = 0;
        $errores = [];

        foreach ($boletas as $b) {
            if (! is_array($b)) {
                $errores[] = "Boleta con formato inválido.";
                continue;
            }
            $folio = trim((string)($b['folio'] ?? ''));
            $placa = $placaAgente;

            if ($folio === '') {
                $errores[] = "Boleta sin folio válido.";
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
                'agente_nombre'         => $agente->nombre_completo,
                'fecha_infraccion'      => $b['fecha_infraccion'] ?? date('Y-m-d'),
                'hora_infraccion'       => $b['hora_infraccion'] ?? date('H:i:s'),
                'lugar'                 => $b['lugar'] ?? 'No especificado',
                'latitud'               => isset($b['latitud']) && is_numeric($b['latitud']) ? (float)$b['latitud'] : null,
                'longitud'              => isset($b['longitud']) && is_numeric($b['longitud']) ? (float)$b['longitud'] : null,
                'conductor_ausente'     => $b['conductor_ausente'],
                'infractor_nombre'      => $b['infractor_nombre'] ?? null,
                'infractor_domicilio'   => $b['infractor_domicilio'] ?? null,
                'infractor_licencia'    => $b['infractor_licencia'] ?? null,
                'vehiculo_placas'       => $b['vehiculo_placas'] ?? null,
                'sin_placas'            => $b['sin_placas'],
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

        $agente = $this->agenteAutenticado();
        if ($agente === null) {
            return $this->respuestaNoAutenticado();
        }
        $placaAgente = strtoupper(trim((string) $agente->placa));
        $boletaModel = new \App\Models\BoletaInfraccionModel();

        $folio = trim((string)($this->request->getPost('folio') ?? 'sin_folio'));
        $tipo  = $this->tipoEvidencia($this->request->getPost('tipo'));
        $folioLimpio = preg_replace('/[^A-Za-z0-9_-]/', '_', $folio);

        $file = $this->request->getFile('foto');
        if ($file !== null && $file->isValid()) {
            if (! $this->folioPermitido($boletaModel, $folio, $placaAgente)) {
                return $this->respuestaFolioAjeno($folio);
            }

            // La extensión se deduce del contenido, nunca del nombre enviado por el cliente.
            $ext = strtolower((string) $file->guessExtension());
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['jpg', 'png', 'webp'], true)) {
                return $this->response->setStatusCode(415)->setJSON([
                    'success' => false,
                    'message' => 'Solo se aceptan imágenes JPG, PNG o WEBP.',
                ]);
            }
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
            $tipo  = $this->tipoEvidencia($json['tipo'] ?? $tipo);
            $folioLimpio = preg_replace('/[^A-Za-z0-9_-]/', '_', $folio);

            if (! $this->folioPermitido($boletaModel, $folio, $placaAgente)) {
                return $this->respuestaFolioAjeno($folio);
            }

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

    private function agenteAutenticado(): ?object
    {
        // El filtro agenteToken ya validó la solicitud; se revalida para fallar cerrado.
        return (new AgenteTokenService())->agenteDeSolicitud($this->request);
    }

    private function respuestaNoAutenticado(): ResponseInterface
    {
        return $this->response->setStatusCode(401)->setJSON([
            'success' => false,
            'message' => 'Sesión no válida o expirada. Inicia sesión nuevamente.',
        ]);
    }

    private function respuestaFolioAjeno(string $folio): ResponseInterface
    {
        return $this->response->setStatusCode(403)->setJSON([
            'success' => false,
            'message' => "El folio {$folio} pertenece a otro agente.",
        ]);
    }

    /**
     * Un folio es del agente si aún no existe o si ya está registrado a su placa.
     */
    private function folioPermitido(\App\Models\BoletaInfraccionModel $boletaModel, string $folio, string $placaAgente): bool
    {
        $existente = $boletaModel->where('folio', $folio)->first();

        return $existente === null || strtoupper(trim((string) $existente->agente_placa)) === $placaAgente;
    }

    private function tipoEvidencia(mixed $tipo): string
    {
        $tipo = is_string($tipo) ? trim($tipo) : '';

        return in_array($tipo, ['placa', 'contexto', 'documento'], true) ? $tipo : 'evidencia';
    }
}
