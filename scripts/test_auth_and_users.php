<?php
$envFile = __DIR__ . '/../.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
        $parts = explode('=', $line, 2);
        $env[trim($parts[0])] = trim(trim($parts[1]), "\"'");
    }
}

$dsn = sprintf(
    "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
    $env['database.default.hostname'],
    $env['database.default.port'] ?? '5432',
    $env['database.default.database'],
    $env['database.default.sslmode'] ?? 'require'
);

$pdo = new PDO($dsn, $env['database.default.username'], $env['database.default.password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

echo "=== TABLA public.users (donde la app guarda y lee usuarios) ===\n";
$stmt = $pdo->query("SELECT u.id, u.username, u.email, u.password_hash, r.nombre AS rol FROM public.users u LEFT JOIN public.user_roles ur ON u.id = ur.user_id LEFT JOIN public.roles r ON r.id = ur.role_id ORDER BY u.id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $verify12345678 = password_verify('12345678', $row['password_hash']) ? 'VALIDA (12345678)' : 'INVALIDA o OTRA PASSWORD';
    $isBcrypt = str_starts_with($row['password_hash'], '$2y$') || str_starts_with($row['password_hash'], '$2a$');
    echo sprintf(
        "ID: %d | User: %s | Email: %s | Rol: %s\n   Hash en BD: %s\n   Es Bcrypt: %s | Password '12345678': %s\n\n",
        $row['id'],
        $row['username'],
        $row['email'],
        $row['rol'] ?? 'SIN ROL',
        substr($row['password_hash'], 0, 30) . '...',
        $isBcrypt ? 'SI' : 'NO (texto plano o hash incompatible)',
        $verify12345678
    );
}

echo "=== TABLA auth.users (Supabase Authentication Dashboard) ===\n";
try {
    $stmtAuth = $pdo->query("SELECT id, email, created_at FROM auth.users ORDER BY created_at");
    $authUsers = $stmtAuth->fetchAll(PDO::FETCH_ASSOC);
    if (empty($authUsers)) {
        echo "No hay usuarios en auth.users (Supabase Auth está vacío).\n";
    } else {
        foreach ($authUsers as $au) {
            echo sprintf("Auth ID: %s | Email: %s | Creado: %s\n", $au['id'], $au['email'], $au['created_at']);
        }
    }
} catch (Exception $e) {
    echo "No se pudo consultar auth.users: " . $e->getMessage() . "\n";
}
