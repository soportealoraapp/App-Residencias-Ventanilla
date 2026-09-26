<?php declare(strict_types=1);

$dbPath = __DIR__ . '/../writable/database.sqlite';
$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Obtener valor UMA
$stmt = $pdo->prepare("SELECT valor FROM parametros_sistema WHERE clave = 'valor_uma_vigente'");
$stmt->execute();
$umaRow = $stmt->fetch(PDO::FETCH_ASSOC);
$valorUma = (float) ($umaRow['valor'] ?? 117.31);

echo "=================================================================\n";
echo "PRUEBA EN VIVO: ResolverMontoInfraccionService (Cálculo UMA)\n";
echo "Valor UMA Vigente en BD (parametros_sistema): $" . number_format($valorUma, 2) . " MXN\n";
echo "=================================================================\n\n";

$ejemplos = [
    'Art. 39 Frac. I',
    'Art. 39 Frac. III',
    'Art. 89 Frac. I',
    'Art. 92 Frac. V',
    'Art. 390 Frac. I'
];

foreach ($ejemplos as $fundamento) {
    $stmt = $pdo->prepare("SELECT id, fundamento_legal, descripcion, categoria_actor, monto_min_uma, monto_max_uma FROM catalogo_infracciones WHERE fundamento_legal = ?");
    $stmt->execute([$fundamento]);
    $inf = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($inf) {
        $minUma = (float) $inf['monto_min_uma'];
        $maxUma = (float) $inf['monto_max_uma'];
        $montoCalculado = round($minUma * $valorUma, 2);
        $montoMax = round($maxUma * $valorUma, 2);

        echo sprintf(
            "[%s] ID #%d - %s\n" .
            "  • Descripción: %s\n" .
            "  • Actor: %s\n" .
            "  • Rango Legal: %.2f a %.2f UMA\n" .
            "  • Monto Automático (MÍNIMO): $%.2f MXN (%.2f UMA × $%.2f)\n" .
            "  • Rango en Pesos: $%.2f a $%.2f MXN\n\n",
            $inf['fundamento_legal'],
            $inf['id'],
            $inf['categoria_actor'],
            $inf['descripcion'],
            $inf['categoria_actor'],
            $minUma,
            $maxUma,
            $montoCalculado,
            $minUma,
            $valorUma,
            $montoCalculado,
            $montoMax
        );
    }
}
