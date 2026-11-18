<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\AgenteTokenService;
use App\Models\BoletaChecklistItemModel;
use App\Models\CatalogoInfraccionAppModel;
use App\Models\ParametroAppModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\EvidenciaBoleta;

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
        // Los encabezados CORS los pone el filtro global `cors` (Config\Cors, auditoría B1).

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
        // Los encabezados CORS los pone el filtro global `cors` (Config\Cors, auditoría B1).

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

            // Auditoría A6: una boleta que el juez ya tomó no se toca desde la app.
            $existente = $boletaModel->where('folio', $folio)->first();
            if ($existente !== null && (string) $existente->estado !== 'recibida') {
                $errores[] = "La boleta {$folio} ya fue calificada; no se puede modificar desde la app.";
                continue;
            }

            // Fecha y hora con formato real y dentro de la ventana permitida:
            // deciden el descuento del 40 % o el recargo.
            $fecha = $this->fechaInfraccionValida($b['fecha_infraccion'] ?? null);
            if ($fecha === null) {
                $errores[] = "La boleta {$folio} tiene una fecha de infracción inválida, futura o de hace más de "
                    . self::DIAS_MAXIMOS_SINCRONIZACION . ' días.';
                continue;
            }
            $hora = $this->horaValida($b['hora_infraccion'] ?? null);
            if ($hora === null) {
                $errores[] = "La boleta {$folio} tiene una hora de infracción inválida.";
                continue;
            }

            // Fundamento, descripción y rango UMA salen del catálogo del servidor,
            // nunca de lo que mande el dispositivo.
            $faltas = $this->faltasDesdeCatalogo($b);
            if (is_string($faltas)) {
                $errores[] = "La boleta {$folio}: {$faltas}";
                continue;
            }
            $principal  = $faltas[0];
            $faltasJson = json_encode($faltas, JSON_UNESCAPED_UNICODE);

            $data = [
                'folio'                 => $folio,
                'agente_placa'          => $placa,
                'agente_nombre'         => $agente->nombre_completo,
                'fecha_infraccion'      => $fecha,
                'hora_infraccion'       => $hora,
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
                'falta_fundamento_legal'=> $principal['fundamentoLegal'],
                'falta_descripcion'     => $principal['descripcion'],
                'falta_categoria'       => $principal['categoria'],
                'falta_monto_min_uma'   => array_sum(array_column($faltas, 'montoMinUma')),
                'falta_monto_max_uma'   => array_sum(array_column($faltas, 'montoMaxUma')),
                'faltas_json'           => $faltasJson,
                'hechos'                => $b['hechos'] ?? null,
                'garantias_retenidas'   => $garantias,
                'inventario_grua'       => $b['inventario_grua'] ?? null,
                // Auditoría M2: solo evidencias guardadas por este servidor para este folio.
                'foto_placa_url'        => $this->urlEvidencia($b['foto_placa_url'] ?? null, $folio),
                'foto_contexto_url'     => $this->urlEvidencia($b['foto_contexto_url'] ?? null, $folio),
                'foto_documento_url'    => $this->urlEvidencia($b['foto_documento_url'] ?? null, $folio),
                // El estado administrativo lo controla el servidor, no la app.
                'estado'                => 'recibida',
                'sincronizado_en'       => date('Y-m-d H:i:s'),
                'creado_en_dispositivo' => $this->fechaHoraODefault($b['creado_en_dispositivo'] ?? null),
            ];

            if ($existente !== null) {
                // Mientras siga "recibida" la app puede completar datos; una foto
                // ausente o no válida no borra la que ya estaba guardada.
                foreach (['foto_placa_url', 'foto_contexto_url', 'foto_documento_url'] as $campoFoto) {
                    if ($data[$campoFoto] === null) {
                        unset($data[$campoFoto]);
                    }
                }
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
     * Se guardan en almacenamiento privado (EvidenciaBoleta, auditoría A4); la app
     * recibe una URL del servidor que solo abre el personal con sesión.
     */
    public function subirEvidencia(): ResponseInterface
    {
        // Los encabezados CORS los pone el filtro global `cors` (Config\Cors, auditoría B1).

        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        $agente = $this->agenteAutenticado();
        if ($agente === null) {
            return $this->respuestaNoAutenticado();
        }
        $placaAgente = strtoupper(trim((string) $agente->placa));
        $boletaModel = new \App\Models\BoletaInfraccionModel();

        $file = $this->request->getFile('foto');
        $json = $file === null ? $this->request->getJSON(true) : null;

        if ($file !== null) {
            $folio = trim((string) ($this->request->getPost('folio') ?? ''));
            $tipo  = $this->tipoEvidencia($this->request->getPost('tipo'));
            if (! $file->isValid()) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'No se recibió un archivo de imagen válido.',
                ]);
            }
            $contenido = (string) file_get_contents($file->getTempName());
        } elseif (is_array($json) && ! empty($json['base64'])) {
            $folio = trim((string) ($json['folio'] ?? ''));
            $tipo  = $this->tipoEvidencia($json['tipo'] ?? null);
            $base64 = (string) preg_replace('#^data:image/\w+;base64,#i', '', (string) $json['base64']);
            // Límite antes de decodificar: 5 MB de imagen son ~6.7 MB en base64.
            $decodificado = strlen($base64) <= (int) ceil(EvidenciaBoleta::TAMANO_MAXIMO * 4 / 3) + 4
                ? base64_decode($base64, true)
                : false;
            if ($decodificado === false) {
                return $this->response->setStatusCode(400)->setJSON([
                    'success' => false,
                    'message' => 'La imagen en base64 no es válida o supera 5 MB.',
                ]);
            }
            $contenido = $decodificado;
        } else {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'No se recibió un archivo de imagen o base64 válido.',
            ]);
        }

        if ($folio === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'La evidencia necesita el folio de la boleta.',
            ]);
        }
        if (! $this->folioPermitido($boletaModel, $folio, $placaAgente)) {
            return $this->respuestaFolioAjeno($folio);
        }

        try {
            $url = (new EvidenciaBoleta())->guardar($folio, $tipo, $contenido);
        } catch (\InvalidArgumentException $e) {
            return $this->response->setStatusCode(415)->setJSON([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Evidencia de boleta {folio}: {msg}', ['folio' => $folio, 'msg' => $e->getMessage()]);
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No fue posible guardar la evidencia. Intenta de nuevo.',
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'url'     => $url,
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

    /** Días hacia atrás que se aceptan al sincronizar una boleta capturada sin señal. */
    public const DIAS_MAXIMOS_SINCRONIZACION = 7;

    private function fechaInfraccionValida(mixed $valor): ?string
    {
        $valor = is_string($valor) ? trim($valor) : '';
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
            return null;
        }
        $hoy = new \DateTimeImmutable('today');
        // Un día de margen hacia adelante por diferencias de zona horaria del teléfono.
        if ($fecha > $hoy->modify('+1 day') || $fecha < $hoy->modify('-' . self::DIAS_MAXIMOS_SINCRONIZACION . ' days')) {
            return null;
        }

        return $valor;
    }

    private function horaValida(mixed $valor): ?string
    {
        $valor = is_string($valor) ? trim($valor) : '';
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $valor, $m) !== 1) {
            return null;
        }

        return sprintf('%s:%s:%s', $m[1], $m[2], ($m[3] ?? '') !== '' ? $m[3] : '00');
    }

    private function fechaHoraODefault(mixed $valor): string
    {
        $marca = is_string($valor) && trim($valor) !== '' ? strtotime($valor) : false;

        return date('Y-m-d H:i:s', $marca !== false ? $marca : time());
    }

    private function urlEvidencia(mixed $url, string $folio): ?string
    {
        $ruta = EvidenciaBoleta::rutaSegura(is_string($url) ? $url : null, $folio, true);

        return $ruta === null ? null : site_url(ltrim($ruta, '/'));
    }

    /**
     * Faltas de la boleta resueltas contra catalogo_infracciones_app por su
     * fundamento legal. Devuelve la lista normalizada o un mensaje de error.
     *
     * @return list<array{id:string,fundamentoLegal:string,descripcion:string,categoria:string,montoMinUma:float,montoMaxUma:float}>|string
     */
    private function faltasDesdeCatalogo(array $b): array|string
    {
        $faltas = $b['faltas_json'] ?? null;
        if (is_string($faltas)) {
            $faltas = json_decode($faltas, true);
        }
        if (! is_array($faltas) || $faltas === [] || ! array_is_list($faltas)) {
            $fundamento = trim((string) ($b['falta_fundamento_legal'] ?? ''));
            $faltas = $fundamento !== '' ? [['fundamentoLegal' => $fundamento]] : [];
        }
        if ($faltas === []) {
            return 'no trae faltas.';
        }
        if (count($faltas) > 20) {
            return 'trae demasiadas faltas.';
        }

        $catalogo  = new \App\Models\CatalogoInfraccionAppModel();
        $resueltas = [];
        foreach ($faltas as $i => $falta) {
            $fundamento = is_array($falta) ? trim((string) ($falta['fundamentoLegal'] ?? $falta['fundamento_legal'] ?? '')) : '';
            if ($fundamento === '') {
                return 'una falta no trae fundamento legal.';
            }
            $fila = $catalogo->where('fundamento_legal', $fundamento)
                ->orderBy('activo', 'DESC')
                ->orderBy('orden', 'ASC')
                ->first();
            if ($fila === null || $fila->monto_min_uma === null || $fila->monto_max_uma === null) {
                return "la falta «{$fundamento}» no existe en el catálogo del servidor.";
            }
            $resueltas[] = [
                'id'              => is_array($falta) && isset($falta['id']) && is_scalar($falta['id']) ? (string) $falta['id'] : 'falta-' . $i,
                'fundamentoLegal' => (string) $fila->fundamento_legal,
                'descripcion'     => (string) $fila->descripcion,
                'categoria'       => (string) ($fila->categoria ?? ''),
                'montoMinUma'     => (float) $fila->monto_min_uma,
                'montoMaxUma'     => (float) $fila->monto_max_uma,
            ];
        }

        return $resueltas;
    }

    private function tipoEvidencia(mixed $tipo): string
    {
        $tipo = is_string($tipo) ? trim($tipo) : '';

        return in_array($tipo, ['placa', 'contexto', 'documento'], true) ? $tipo : 'evidencia';
    }
}
