<?php declare(strict_types=1);

/**
 * Corrige la acentuación del español únicamente en líneas de comentario.
 * Uso: php scripts/acentos_espanol.php [ruta ...]
 * Si no recibe rutas, revisa los archivos tocados por el desglose del sistema.
 */

$mapa = [
    'metricas' => 'métricas',
    'Metricas' => 'Métricas',
    'agrupacion' => 'agrupación',
    'seccion' => 'sección',
    'Seccion' => 'Sección',
    'secciones' => 'secciones',
    'pagina' => 'página',
    'Pagina' => 'Página',
    'paginas' => 'páginas',
    'accion' => 'acción',
    'Accion' => 'Acción',
    'acciones' => 'acciones',
    'codigo' => 'código',
    'Codigo' => 'Código',
    'codigos' => 'códigos',
    'Codigos' => 'Códigos',
    'gestion' => 'gestión',
    'Gestion' => 'Gestión',
    'atencion' => 'atención',
    'Atencion' => 'Atención',
    'estan' => 'están',
    'despues' => 'después',
    'distribucion' => 'distribución',
    'Distribucion' => 'Distribución',
    'tramites' => 'trámites',
    'Tramites' => 'Trámites',
    'ultimos' => 'últimos',
    'Ultimos' => 'Últimos',
    'ultimas' => 'últimas',
    'ultimo' => 'último',
    'Ultimo' => 'Último',
    'ultima' => 'última',
    'Ultima' => 'Última',
    'dias' => 'días',
    'Dias' => 'Días',
    'dia ' => 'día ',
    'numero' => 'número',
    'Numero' => 'Número',
    'numeros' => 'números',
    'historico' => 'histórico',
    'Historico' => 'Histórico',
    'operacion' => 'operación',
    'Operacion' => 'Operación',
    'catalogo' => 'catálogo',
    'Catalogo' => 'Catálogo',
    'catalogos' => 'catálogos',
    'Catalogos' => 'Catálogos',
    'invalida' => 'inválida',
    'invalido' => 'inválido',
    'unico' => 'único',
    'Unico' => 'Único',
    'unica' => 'única',
    'maximo' => 'máximo',
    'Maximo' => 'Máximo',
    'minimo' => 'mínimo',
    'Minimo' => 'Mínimo',
    'linea' => 'línea',
    'Linea' => 'Línea',
    'auditoria' => 'auditoría',
    'Auditoria' => 'Auditoría',
    'bitacora' => 'bitácora',
    'Bitacora' => 'Bitácora',
    'modulo' => 'módulo',
    'Modulo' => 'Módulo',
    'tambien' => 'también',
    'Tambien' => 'También',
    'region' => 'región',
    'credito' => 'crédito',
    'Credito' => 'Crédito',
    'referencia' => 'referencia',
    'revision' => 'revisión',
    'Revision' => 'Revisión',
    'validar' => 'validar',
    'moneda' => 'moneda',
    'registro' => 'registro',
];

// Palabras que requieren contexto y por eso se resuelven con reglas literales.
$literales = [
    'fuera de el.'        => 'fuera de él.',
    'fuera de el '        => 'fuera de él ',
    'igual a el'         => 'igual a él',
    'por tramites'       => 'por trámite',
    'sino que'           => 'sino que',
];

$rutas = array_slice($argv, 1);
if ($rutas === []) {
    $rutas = [
        'app/Libraries/DesgloseSistema.php',
        'app/Controllers/Admin/DesgloseController.php',
        'app/Controllers/Admin/AdminController.php',
        'app/Controllers/Admin/AppTransitoController.php',
        'app/Helpers/url_helper_custom.php',
        'app/Views/admin/desglose.php',
        'app/Views/admin/dashboard.php',
        'app/Views/admin/auditoria.php',
        'app/Views/admin/solicitudes_lista.php',
        'app/Views/admin/app_transito/boletas.php',
        'app/Views/layouts/admin.php',
        'tests/app/Controllers/Admin/DesgloseSistemaTest.php',
    ];
}

$esComentario = static function (string $linea): bool {
    $t = ltrim($linea);

    return str_starts_with($t, '//')
        || str_starts_with($t, '*')
        || str_starts_with($t, '/*')
        || str_starts_with($t, '#');
};

$totalCambios = 0;

foreach ($rutas as $ruta) {
    if (! is_file($ruta)) {
        fwrite(STDERR, "Salteado (no existe): {$ruta}\n");
        continue;
    }

    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        fwrite(STDERR, "No se pudo leer: {$ruta}\n");
        continue;
    }

    $lineas  = preg_split('/\r?\n/', $contenido);
    $salto   = str_contains($contenido, "\r\n") ? "\r\n" : "\n";
    $cambios = 0;

    foreach ($lineas as $i => $linea) {
        if (! $esComentario($linea)) {
            continue;
        }

        $nueva = $linea;
        foreach ($literales as $buscar => $reemplazo) {
            $nueva = str_replace($buscar, $reemplazo, $nueva);
        }
        foreach ($mapa as $buscar => $reemplazo) {
            if ($buscar === $reemplazo) {
                continue;
            }
            // No reescribir nombres técnicos entre comillas o variables ($dias, 'tramite').
            $nueva = preg_replace('/(?<![$\'"\\\\])\b' . preg_quote($buscar, '/') . '\b/u', $reemplazo, $nueva);
        }

        if ($nueva !== $linea) {
            $lineas[$i] = $nueva;
            $cambios++;
            echo basename($ruta) . ':' . ($i + 1) . "\n    - " . trim($linea) . "\n    + " . trim($nueva) . "\n";
        }
    }

    if ($cambios > 0) {
        file_put_contents($ruta, implode($salto, $lineas));
        echo "== {$ruta}: {$cambios} lineas ajustadas ==\n\n";
    } else {
        echo "== {$ruta}: sin cambios ==\n\n";
    }

    $totalCambios += $cambios;
}

echo "Total de lineas ajustadas: {$totalCambios}\n";
