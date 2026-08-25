<?php

$password = '12345678';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password hash para '{$password}':\n";
echo $hash . "\n\n";

$sql = <<<SQL
-- Actualizar password hashes de usuarios demo
UPDATE public.users SET password_hash = '{$hash}' WHERE id IN (1, 2, 3);
-- Verificar
SELECT id, username, email FROM public.users ORDER BY id;
SQL;

echo "SQL de actualizacion:\n";
echo $sql . "\n";
