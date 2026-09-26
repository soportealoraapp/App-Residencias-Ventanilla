<?php
/**
 * Script para capturar la respuesta JSON real del endpoint /api/checklist-infracciones
 * mostrando la categoría DOCUMENTACIÓN completa como muestra del payload.
 */
$dbPath = __DIR__ . '/../writable/database.sqlite';
$db = new SQLite3($dbPath);

// Obtener valor UMA
$uma = (float)$db->querySingle("SELECT valor FROM parametros_sistema WHERE clave = 'valor_uma_vigente'");

// Obtener todos los items con JOIN
$res = $db->query("
    SELECT
        b.id AS checklist_item_id,
        b.categoria_boleta,
        b.etiqueta_casilla,
        b.catalogo_infraccion_id,
        c.fundamento_legal,
        c.descripcion AS descripcion_oficial,
        c.categoria_actor,
        c.monto_min_uma,
        c.monto_max_uma
    FROM boleta_checklist_item b
    LEFT JOIN catalogo_infracciones c ON c.id = b.catalogo_infraccion_id
    ORDER BY b.categoria_boleta, b.id
");

$categorias = [];
$total_items = 0;
$total_mapeados = 0;
$total_sin_mapeo = 0;

while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $cat = $row['categoria_boleta'] ?? 'Otros';
    $minUma = $row['monto_min_uma'] !== null ? (float)$row['monto_min_uma'] : null;
    $maxUma = $row['monto_max_uma'] !== null ? (float)$row['monto_max_uma'] : null;
    $hasMapa = !empty($row['catalogo_infraccion_id']);

    if ($hasMapa) $total_mapeados++;
    else $total_sin_mapeo++;
    $total_items++;

    $categorias[$cat][] = [
        'checklist_item_id'      => (int)$row['checklist_item_id'],
        'etiqueta_casilla'       => $row['etiqueta_casilla'],
        'catalogo_infraccion_id' => $hasMapa ? (int)$row['catalogo_infraccion_id'] : null,
        'fundamento_legal'       => $row['fundamento_legal'],
        'descripcion_oficial'    => $row['descripcion_oficial'],
        'categoria_actor'        => $row['categoria_actor'],
        'monto_min_uma'          => $minUma,
        'monto_max_uma'          => $maxUma,
        'monto_min_pesos'        => $minUma !== null ? round($minUma * $uma, 2) : null,
        'monto_max_pesos'        => $maxUma !== null ? round($maxUma * $uma, 2) : null,
    ];
}

$payload = [
    'success'           => true,
    'valor_uma_vigente' => $uma,
    'total_items'       => $total_items,
    'total_mapeados'    => $total_mapeados,
    'total_sin_mapeo'   => $total_sin_mapeo,
    'categorias'        => $categorias,
];

echo "=== RESPUESTA REAL DEL ENDPOINT GET /api/checklist-infracciones ===\n\n";
echo "Resumen:\n";
echo "  valor_uma_vigente : \${$uma}\n";
echo "  total_items       : {$total_items}\n";
echo "  total_mapeados    : {$total_mapeados}\n";
echo "  total_sin_mapeo   : {$total_sin_mapeo}\n\n";

echo "=== CATEGORÍA COMPLETA: DOCUMENTACIÓN ===\n";
echo json_encode(['DOCUMENTACIÓN' => $categorias['DOCUMENTACIÓN']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n=== CATEGORÍA COMPLETA: SEÑALAMIENTOS (primeros 5) ===\n";
echo json_encode(['SEÑALAMIENTOS' => array_slice($categorias['SEÑALAMIENTOS'], 0, 5)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

$db->close();
