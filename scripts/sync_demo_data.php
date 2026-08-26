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

// 1. Roles
$pdo->exec("INSERT INTO public.roles (id, nombre, descripcion) VALUES
    (1, 'administrador', 'Acceso total al panel administrativo'),
    (2, 'operador_ventanilla', 'Revision y cobro de solicitudes'),
    (3, 'ciudadano', 'Usuario publico que solicita tramites')
ON CONFLICT (id) DO NOTHING");

// 2. Demo users with password '12345678'
$hash = password_hash('12345678', PASSWORD_DEFAULT);

$users = [
    [1, 'admin', 'admin@uriangato.gob.mx', 'Administrador del Sistema', $hash, null, null, null],
    [2, 'operador1', 'operador1@uriangato.gob.mx', 'Operador Ventanilla Uno', $hash, null, null, null],
    [3, 'ciudadano1', 'ciudadano@example.com', 'Juan Perez Garcia', $hash, 'PEGJ800101XXX', '4711234567', 'Calle Madero #123, Uriangato, Gto.']
];

foreach ($users as $u) {
    $stmt = $pdo->prepare("INSERT INTO public.users (id, username, email, nombre_completo, password_hash, rfc, telefono, domicilio, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON CONFLICT (id) DO UPDATE SET
            password_hash = EXCLUDED.password_hash,
            username = EXCLUDED.username,
            email = EXCLUDED.email,
            nombre_completo = EXCLUDED.nombre_completo,
            activo = EXCLUDED.activo");
    $stmt->execute($u);
}

// 3. User roles
$userRoles = [
    [1, 1, 1], // admin -> administrador
    [2, 2, 2], // operador1 -> operador_ventanilla
    [3, 3, 3]  // ciudadano1 -> ciudadano
];

foreach ($userRoles as $ur) {
    $stmt = $pdo->prepare("INSERT INTO public.user_roles (id, user_id, role_id)
        VALUES (?, ?, ?)
        ON CONFLICT (id) DO UPDATE SET
            user_id = EXCLUDED.user_id,
            role_id = EXCLUDED.role_id");
    $stmt->execute($ur);
}

// 4. Assign role to any extra user like id 4
$stmt = $pdo->query("SELECT id FROM public.users WHERE id > 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $uid = $row['id'];
    $check = $pdo->prepare("SELECT 1 FROM public.user_roles WHERE user_id = ?");
    $check->execute([$uid]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO public.user_roles (user_id, role_id) VALUES (?, 3)");
        $ins->execute([$uid]);
    }
}

// 5. Sync sequences
$pdo->exec("SELECT setval(pg_get_serial_sequence('public.roles', 'id'), COALESCE((SELECT MAX(id) FROM public.roles), 1))");
$pdo->exec("SELECT setval(pg_get_serial_sequence('public.users', 'id'), COALESCE((SELECT MAX(id) FROM public.users), 1))");
$pdo->exec("SELECT setval(pg_get_serial_sequence('public.user_roles', 'id'), COALESCE((SELECT MAX(id) FROM public.user_roles), 1))");

echo "=== Usuarios y roles sincronizados exitosamente ===\n";
$q = $pdo->query("SELECT u.id, u.username, u.email, r.nombre AS rol FROM public.users u JOIN public.user_roles ur ON u.id = ur.user_id JOIN public.roles r ON r.id = ur.role_id ORDER BY u.id");
while ($row = $q->fetch(PDO::FETCH_ASSOC)) {
    echo "ID {$row['id']}: {$row['username']} ({$row['email']}) -> Rol: [{$row['rol']}] (Password: 12345678)\n";
}
