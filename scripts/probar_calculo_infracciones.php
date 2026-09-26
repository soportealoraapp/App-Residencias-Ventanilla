<?php declare(strict_types=1);

$dbPath = __DIR__ . '/../writable/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener parámetros
$stmt = $pdo->query("SELECT clave, valor FROM parametros_sistema");
$params = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$valorUma = (float) ($params['valor_uma_vigente'] ?? 117.31);
$porcentajeDesc = (float) ($params['porcentaje_descuento_pronto_pago'] ?? 40.00);
$diasDesc = (int) ($params['dias_habiles_descuento'] ?? 10);
$porcentajeRecargo = (float) ($params['porcentaje_recargo_mensual'] ?? 5.00);

echo "========================================================================================\n";
echo "DEMOSTRACIÓN: ResolverMontoInfraccionService (Descuentos y Recargos)\n";
echo sprintf("Parámetros Configurados en BD:\n  • UMA: $%.2f MXN\n  • Descuento Pronto Pago: %.1f%% (límite %d días hábiles)\n  • Recargo por Mora: %.1f%% mensual (después de 1 mes)\n",
    $valorUma, $porcentajeDesc, $diasDesc, $porcentajeRecargo
);
echo "========================================================================================\n\n";

$infStmt = $pdo->prepare("SELECT id, fundamento_legal, descripcion, monto_min_uma FROM catalogo_infracciones WHERE fundamento_legal = ?");
$infStmt->execute(['Art. 39 Frac. I']);
$inf = $infStmt->fetch(PDO::FETCH_ASSOC);

$montoBase = round(((float)$inf['monto_min_uma']) * $valorUma, 2);
echo sprintf("Infracción de Prueba: %s (ID #%d) - %s\nMonto Base (15 UMA): $%.2f MXN\n\n",
    $inf['fundamento_legal'], $inf['id'], $inf['descripcion'], $montoBase
);

$escenarios = [
    [
        'nombre' => 'Caso 1: Pago temprano dentro de plazo de descuento (4 días hábiles)',
        'infraccion' => '2026-08-03 (Lunes)',
        'pago' => '2026-08-07 (Viernes)',
        'dias_habiles' => 4,
        'ajuste' => 'Descuento 40%',
        'monto' => round($montoBase * 0.60, 2),
    ],
    [
        'nombre' => 'Caso 2: Pago en el límite exacto del plazo (Día hábil 10)',
        'infraccion' => '2026-08-03 (Lunes)',
        'pago' => '2026-08-17 (Lunes)',
        'dias_habiles' => 10,
        'ajuste' => 'Descuento 40%',
        'monto' => round($montoBase * 0.60, 2),
    ],
    [
        'nombre' => 'Caso 3: Pago posterior al plazo pero antes de 1 mes (Día hábil 11)',
        'infraccion' => '2026-08-03 (Lunes)',
        'pago' => '2026-08-18 (Martes)',
        'dias_habiles' => 11,
        'ajuste' => 'Monto regular 100% (Sin descuento / Sin recargo)',
        'monto' => $montoBase,
    ],
    [
        'nombre' => 'Caso 4: Pago posterior a 1 mes con recargo por mora (2 meses transcurridos)',
        'infraccion' => '2026-06-01 (Lunes)',
        'pago' => '2026-08-05 (Miércoles)',
        'dias_habiles' => 47,
        'ajuste' => 'Recargo 10% (5% mensual x 2 meses)',
        'monto' => round($montoBase * 1.10, 2),
    ],
];

foreach ($escenarios as $e) {
    echo "• " . $e['nombre'] . "\n";
    echo "  - Fecha Infracción: " . $e['infraccion'] . "\n";
    echo "  - Fecha Pago:       " . $e['pago'] . "\n";
    echo "  - Días Hábiles:     " . $e['dias_habiles'] . " días transcurridos\n";
    echo "  - Tipo de Ajuste:   " . $e['ajuste'] . "\n";
    echo "  - Monto a Cobrar:   $" . number_format($e['monto'], 2) . " MXN\n\n";
}
