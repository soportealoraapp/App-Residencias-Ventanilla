<?php declare(strict_types=1);

$dbPath = __DIR__ . '/../writable/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Cargar catálogo de 201 infracciones
$stmt = $pdo->query("SELECT id, fundamento_legal, descripcion, categoria_actor, monto_min_uma, monto_max_uma FROM catalogo_infracciones ORDER BY id ASC");
$infracciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total infracciones en BD: " . count($infracciones) . "\n";
