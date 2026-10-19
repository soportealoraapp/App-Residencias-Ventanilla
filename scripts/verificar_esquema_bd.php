<?php declare(strict_types=1);

/**
 * Verificador de deriva de esquema (drift).
 *
 * Compara el esquema que el repo define en sus migraciones contra el esquema
 * real de la base de datos configurada en .env, y lista lo que falta.
 *
 * Por qué existe: la base de datos de Supabase se construyó a mano con los
 * archivos de supabase/migrations/, mientras que las pruebas unitarias levantan
 * un SQLite en memoria desde app/Database/Migrations/. Las dos rutas pueden
 * divergir sin que ninguna prueba se queje, y el fallo aparece solo en producción.
 * Este script cierra esa ventana: compara nombres de tablas y de columnas.
 *
 * Uso:
 *   php scripts/verificar_esquema_bd.php              // contra la BD de .env
 *   php scripts/verificar_esquema_bd.php --formato=sql  // imprime el ALTER/CREATE faltante
 *
 * Compara SOLO nombres (no tipos ni restricciones): un DECIMAL y un NUMERIC son
 * el mismo tipo en Postgres y no debe aparecer como diferencia.
 */

$raiz = dirname(__DIR__);
$mostrarSql = in_array('--formato=sql', $argv, true);

// ------------------------------------------------------------------
// 1. Lectura de .env sin depender del framework (el script debe poder
//    correr aunque la app no arranque, que es justo el caso de deriva).
// ------------------------------------------------------------------
$env = [];
foreach (file($raiz . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
    $linea = trim($linea);
    if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
        continue;
    }
    [$k, $v] = explode('=', $linea, 2);
    $env[trim($k)] = trim(trim($v), "\"'");
}

$controlador = $env['database.default.DBDriver'] ?? '';
if ($controlador !== 'Postgre') {
    fwrite(STDERR, "Este verificador espera database.default.DBDriver = Postgre; se encontró: '{$controlador}'.\n");
    exit(2);
}

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $env['database.default.hostname'] ?? '',
    $env['database.default.port'] ?? '5432',
    $env['database.default.database'] ?? '',
    $env['database.default.sslmode'] ?? 'require'
);

try {
    $pdo = new PDO($dsn, $env['database.default.username'] ?? '', $env['database.default.password'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo conectar a la base de datos: ' . $e->getMessage() . "\n");
    exit(2);
}

// ------------------------------------------------------------------
// 2. Qué define el repo.
// ------------------------------------------------------------------
$esperado = [];   // tabla => [columna => true]
$origen   = [];   // tabla => archivo que la define
$vistas   = [];   // vista => archivo

/**
 * Extrae el bloque entre corchetes balanceados que empieza en o después de $pos.
 * Necesario porque las definiciones de columna llevan arreglos anidados
 * ('constraint' => ['a','b']), que un patrón .*? cortaría demasiado pronto.
 */
$bloqueCorchetes = static function (string $codigo, int $pos): ?string {
    $ini = strpos($codigo, '[', $pos);
    if ($ini === false) {
        return null;
    }
    $nivel = 0;
    for ($i = $ini, $n = strlen($codigo); $i < $n; $i++) {
        if ($codigo[$i] === '[') {
            $nivel++;
        } elseif ($codigo[$i] === ']') {
            $nivel--;
            if ($nivel === 0) {
                return substr($codigo, $ini, $i - $ini + 1);
            }
        }
    }
    return null;
};

/** Convierte el arreglo PHP de addField() en lista de columnas. */
$columnasDeBloque = static function (string $bloque): array {
    preg_match_all("/'([a-z_]+)'\s*=>\s*\[/i", $bloque, $m);
    $ignoradas = ['type', 'constraint', 'null', 'auto_increment', 'default', 'unsigned', 'unique'];
    $lista = [];
    foreach ($m[1] as $c) {
        if (!in_array($c, $ignoradas, true)) {
            $lista[$c] = true;
        }
    }
    return $lista;
};

foreach (glob($raiz . '/app/Database/Migrations/*.php') ?: [] as $archivo) {
    $codigo = file_get_contents($archivo);
    $nombre = basename($archivo);

    // Recorrido lineal: cada createTable se queda con los addField previos sin asignar.
    $puntero = 0;
    $pendiente = [];
    while ($puntero < strlen($codigo)) {
        $pAdd    = strpos($codigo, '->addField(', $puntero);
        $pCreate = strpos($codigo, "->createTable('", $puntero);
        $pAltera = strpos($codigo, "->addColumn('", $puntero);

        $candidatos = array_filter([$pAdd, $pCreate, $pAltera], static fn ($p) => $p !== false);
        if (!$candidatos) {
            break;
        }
        $siguiente = min($candidatos);

        if ($siguiente === $pAdd) {
            $bloque = $bloqueCorchetes($codigo, $pAdd);
            $pendiente = array_merge($pendiente, $bloque !== null ? $columnasDeBloque($bloque) : []);
            $puntero = $pAdd + strlen($bloque ?? '') + 1;
            continue;
        }

        if (preg_match("/'([a-z_]+)/", substr($codigo, $siguiente), $mt) === 1) {
            $tabla = $mt[1];
            if ($siguiente === $pCreate) {
                $esperado[$tabla] = array_merge($esperado[$tabla] ?? [], $pendiente);
                $origen[$tabla] ??= $nombre;
                $pendiente = [];
            } else {
                $bloque = $bloqueCorchetes($codigo, strpos($codigo, ',', $pAltera));
                $esperado[$tabla] = array_merge($esperado[$tabla] ?? [], $bloque !== null ? $columnasDeBloque($bloque) : []);
                $origen[$tabla] ??= $nombre;
                $puntero = $pAltera + strlen($bloque ?? '') + 1;
                continue;
            }
        }
        $puntero = $siguiente + 12;
    }

    // CREATE VIEW nombre AS
    if (preg_match_all('/CREATE\s+VIEW\s+([a-z_]+)/i', $codigo, $mv)) {
        foreach ($mv[1] as $v) {
            $vistas[$v] = $nombre;
        }
    }
}

foreach (glob($raiz . '/supabase/migrations/*.sql') ?: [] as $archivo) {
    $codigo = file_get_contents($archivo);
    $nombre = basename($archivo);
    if (preg_match_all('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?(?:public\.)?([a-z_]+)\s*\((.*?)\n\);/is', $codigo, $ms, PREG_SET_ORDER)) {
        // Tipos que Postgres acepta como primer token tras el nombre de columna;
        // exigirlos evita que un fragmento de CHECK o de lista ENUM se lea como columna.
        $tipos = 'character|character varying|varchar|citext|text|integer|smallint|bigint|numeric|decimal'
            . '|boolean|timestamp|timestamptz|date|time|json|jsonb|uuid|serial|bigserial|double precision|real|bytea|money';
        foreach ($ms as $m) {
            $tabla = $m[1];
            $columnas = [];
            foreach (preg_split('/,(?![^(]*\))/', $m[2]) ?: [] as $parte) {
                $parte = trim(preg_replace('/\s+/', ' ', $parte));
                if ($parte === '' || preg_match('/^(?:CONSTRAINT|PRIMARY|UNIQUE|FOREIGN|CHECK|LIKE|EXCLUDE)\b/i', $parte)) {
                    continue;
                }
                if (preg_match('/^"?([a-z_]+)"?\s+(' . $tipos . ')\b/i', $parte, $mc) === 1) {
                    $columnas[$mc[1]] = true;
                }
            }
            $esperado[$tabla] = array_merge($esperado[$tabla] ?? [], $columnas);
            $origen[$tabla] ??= $nombre;
        }
    }
    if (preg_match_all('/CREATE\s+(?:OR REPLACE\s+)?VIEW\s+(?:IF NOT EXISTS\s+)?(?:public\.)?([a-z_]+)/i', $codigo, $mv)) {
        foreach ($mv[1] as $v) {
            $vistas[$v] ??= $nombre;
        }
    }
}
ksort($esperado);

// ------------------------------------------------------------------
// 3. Qué existe realmente.
// ------------------------------------------------------------------
$real = [];
foreach ($pdo->query("SELECT table_name, column_name FROM information_schema.columns WHERE table_schema='public'") as $f) {
    $real[$f['table_name']][$f['column_name']] = true;
}
$vistasReales = [];
foreach ($pdo->query("SELECT table_name FROM information_schema.views WHERE table_schema='public'") as $f) {
    $vistasReales[$f['table_name']] = true;
}
$totalMigraciones = (int) ($pdo->query('SELECT COUNT(*) FROM public.migrations')->fetchColumn() ?: 0);

// ------------------------------------------------------------------
// 4. Diferencias.
// ------------------------------------------------------------------
$faltanTablas = [];
$faltanColumnas = [];
foreach ($esperado as $tabla => $columnas) {
    if (!isset($real[$tabla])) {
        $faltanTablas[$tabla] = $columnas;
        continue;
    }
    foreach (array_keys($columnas) as $c) {
        if (!isset($real[$tabla][$c])) {
            $faltanColumnas[$tabla][] = $c;
        }
    }
}
$sobraTablas = array_values(array_diff(array_keys($real), array_keys($esperado), ['ci_sessions']));

$faltanVistas = array_keys(array_diff_key($vistas, $vistasReales));

echo "=== VERIFICADOR DE DERIVA DE ESQUEMA ===\n";
echo 'Base de datos: ' . $env['database.default.database'] . '@' . $env['database.default.hostname'] . "\n";
echo 'Tablas definidas en el repo: ' . count($esperado) . " · existentes: " . count($real) . "\n";
echo "Historial de migraciones (tabla migrations): {$totalMigraciones} registros\n\n";

if ($faltanTablas) {
    echo "[FALTAN TABLAS]\n";
    foreach ($faltanTablas as $t => $c) {
        printf("  %-26s definida en %s (%d columnas)\n", $t, $origen[$t] ?? '?', count($c));
    }
    echo "\n";
}
if ($faltanColumnas) {
    echo "[FALTAN COLUMNAS]\n";
    foreach ($faltanColumnas as $t => $cs) {
        printf("  %-16s %s\n", $t . '.', implode(', ', $cs));
    }
    echo "\n";
}
if ($faltanVistas) {
    echo "[FALTAN VISTAS]\n";
    foreach ($faltanVistas as $v) {
        printf("  %-26s definida en %s\n", $v, $vistas[$v]);
    }
    echo "\n";
}
if ($sobraTablas) {
    echo "[TABLAS EN LA BD QUE NINGUNA MIGRACIÓN DEL REPO DEFINE] (informativo)\n";
    foreach ($sobraTablas as $t) {
        echo "  {$t}\n";
    }
    echo "\n";
}

if ($mostrarSql && ($faltanTablas || $faltanColumnas)) {
    echo "\n=== SQL PARA APLICAR LO FALTANTE ===\n";
    foreach ($faltanColumnas as $t => $cs) {
        foreach ($cs as $c) {
            echo "ALTER TABLE public.{$t} ADD COLUMN IF NOT EXISTS {$c} bigint NULL;\n";
        }
    }
    echo "-- Las tablas completas hay que crearlas desde la migración que las define (ver lista anterior).\n";
}

if (!$faltanTablas && !$faltanColumnas && !$faltanVistas) {
    echo "OK: el esquema de la base de datos contiene todas las tablas y columnas que el repo define.\n";
    exit(0);
}

echo "\nDERIVA DETECTADA: revisa las secciones afectadas antes de dar por funcionando el panel.\n";
exit(1);
