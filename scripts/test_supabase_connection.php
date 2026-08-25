<?php

echo "=== Prueba de conexion a Supabase (PostgreSQL) ===\n\n";

echo "1. Verificando extensiones PHP...\n";
$required = ['pdo', 'pdo_pgsql', 'pgsql'];
foreach ($required as $ext) {
    $status = extension_loaded($ext) ? 'OK' : 'FALTA';
    echo "   - {$ext}: {$status}\n";
}

echo "\n2. Parseando archivo .env manualmente...\n";
$envFile = __DIR__ . '/../.env';
$env = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            $val = trim($val, "'\"");
            $env[$key] = $val;
        }
    }
}

$hostname = $env['database.default.hostname'] ?? getenv('database.default.hostname');
$database = $env['database.default.database'] ?? getenv('database.default.database');
$username = $env['database.default.username'] ?? getenv('database.default.username');
$password = $env['database.default.password'] ?? getenv('database.default.password');
$port     = $env['database.default.port'] ?? getenv('database.default.port') ?: '5432';
$sslmode  = $env['database.default.sslmode'] ?? getenv('database.default.sslmode') ?: 'require';

echo "   Host: {$hostname}\n";
echo "   DB: {$database}\n";
echo "   User: {$username}\n";
echo "   Port: {$port}\n";
echo "   SSL: {$sslmode}\n";

echo "\n3. Intentando conectar por PDO...\n";
try {
    $dsn = "pgsql:host={$hostname};port={$port};dbname={$database};sslmode={$sslmode}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
    echo "   CONEXION EXITOSA!\n";

    echo "\n4. Consulta: roles disponibles\n";
    $stmt = $pdo->query("SELECT id, nombre, descripcion FROM public.roles ORDER BY id");
    foreach ($stmt->fetchAll() as $row) {
        echo "   [{$row->id}] {$row->nombre} - {$row->descripcion}\n";
    }

    echo "\n5. Consulta: usuarios demo\n";
    $stmt = $pdo->query("SELECT id, username, email, activo FROM public.users ORDER BY id");
    foreach ($stmt->fetchAll() as $row) {
        echo "   [{$row->id}] {$row->username} <{$row->email}> activo:" . ($row->activo ? 'SI' : 'NO') . "\n";
    }

    echo "\n6. Probando password_verify para admin (pass: 12345678)...\n";
    $stmt = $pdo->prepare("SELECT password_hash FROM public.users WHERE username = ?");
    $stmt->execute(['admin']);
    $hash = $stmt->fetchColumn();
    $verified = password_verify('12345678', $hash);
    echo "   Password admin verificado: " . ($verified ? 'CORRECTO' : 'FALLIDO') . "\n";

    echo "\n7. Verificando user_roles (relaciones)...\n";
    $stmt = $pdo->query("SELECT u.username, r.nombre AS rol FROM public.user_roles ur JOIN public.users u ON u.id = ur.user_id JOIN public.roles r ON r.id = ur.role_id ORDER BY u.id");
    foreach ($stmt->fetchAll() as $row) {
        echo "   - {$row->username} -> {$row->rol}\n";
    }

    echo "\n8. Verificando tarifas y concesiones...\n";
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM public.tarifas");
    echo "   Tarifas: {$stmt->fetch()->total}\n";
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM public.concesiones");
    echo "   Concesiones: {$stmt->fetch()->total}\n";

    echo "\n9. Listando todas las tablas en schema public...\n";
    $stmt = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
    foreach ($stmt->fetchAll() as $row) {
        echo "   - {$row->tablename}\n";
    }

    echo "\n=== TODAS LAS PRUEBAS COMPLETADAS EXITOSAMENTE ===\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        echo "\nPDO error info:\n";
        print_r($e->errorInfo);
    }
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
