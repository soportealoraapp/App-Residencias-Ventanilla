<?php
// Script para verificar datos reales de catalogo_infracciones para los artículos usados en el seeder
$dbPath = __DIR__ . '/../writable/database.sqlite';
$db = new SQLite3($dbPath);

$articulos = [
    'Art. 87 Frac. III',
    'Art. 87 Frac. IV',
    'Art. 87 Frac. I a)',
    'Art. 89 Frac. II',
    'Art. 39 Frac. V',
    'Art. 39 Frac. VII',
    'Art. 39 Frac. IX',
    'Art. 39 Frac. X',
    'Art. 39 Frac. XI',
    'Art. 40 Frac. I',
    'Art. 40 Frac. III',
    'Art. 40 Frac. VI',
];

echo "=== MONTOS REALES DEL CATÁLOGO ===\n";
foreach ($articulos as $art) {
    $stmt = $db->prepare("SELECT fundamento_legal, descripcion, monto_min_uma, monto_max_uma FROM catalogo_infracciones WHERE fundamento_legal = :f");
    $stmt->bindValue(':f', $art);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        printf("%-30s  min=%.2f  max=%.2f  desc: %s\n", $row['fundamento_legal'], $row['monto_min_uma'], $row['monto_max_uma'], substr($row['descripcion'], 0, 60));
    } else {
        printf("%-30s  NO ENCONTRADO\n", $art);
    }
}

echo "\n=== DATOS ACTUALES DE BOLETA CHECKLIST (primeros 10) ===\n";
$res = $db->query("
    SELECT b.id, b.categoria_boleta, b.etiqueta_casilla, c.fundamento_legal, c.monto_min_uma, c.monto_max_uma
    FROM boleta_checklist_item b
    LEFT JOIN catalogo_infracciones c ON c.id = b.catalogo_infraccion_id
    LIMIT 10
");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    printf("[%s] %s => %s  min=%.2f max=%.2f\n",
        $row['categoria_boleta'],
        $row['etiqueta_casilla'],
        $row['fundamento_legal'] ?? 'NULL',
        (float)($row['monto_min_uma'] ?? 0),
        (float)($row['monto_max_uma'] ?? 0)
    );
}

$db->close();
