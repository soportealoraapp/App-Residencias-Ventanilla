<?php declare(strict_types=1);

$dbPath = __DIR__ . '/../writable/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Conteo total y mapeo
$stmt = $pdo->query("
    SELECT 
        b.id,
        b.categoria_boleta,
        b.etiqueta_casilla,
        b.catalogo_infraccion_id,
        c.fundamento_legal,
        c.descripcion AS descripcion_oficial,
        c.monto_min_uma,
        c.monto_max_uma
    FROM boleta_checklist_item b
    LEFT JOIN catalogo_infracciones c ON c.id = b.catalogo_infraccion_id
    ORDER BY b.id ASC
");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mapeadas = [];
$sinMapeo = [];

foreach ($items as $item) {
    if (!empty($item['catalogo_infraccion_id'])) {
        $mapeadas[] = $item;
    } else {
        $sinMapeo[] = $item;
    }
}

echo "=================================================================\n";
echo "REPORTE DE MAPEO: BOLETA FÍSICA ↔ CATÁLOGO OFICIAL (201 INFRACCIONES)\n";
echo "=================================================================\n";
echo "Total de casillas de la boleta física: " . count($items) . "\n";
echo "Casillas mapeadas con fundamento legal: " . count($mapeadas) . "\n";
echo "Casillas sin mapeo claro (pendientes):  " . count($sinMapeo) . "\n\n";

echo "--- LISTA DE CASILLAS SIN MAPEO CLARO (PENDIENTES DE MOVILIDAD) ---\n";
foreach ($sinMapeo as $idx => $s) {
    echo sprintf("%d. [%s] \"%s\"\n", $idx + 1, $s['categoria_boleta'], $s['etiqueta_casilla']);
}
