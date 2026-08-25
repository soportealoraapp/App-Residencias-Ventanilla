<?php declare(strict_types=1);

/*
 * Front Controller para Vercel (Serverless Function PHP).
 * Vercel monta esta función en el endpoint /api/index.php y, mediante
 * rewrites en vercel.json, TODO el tráfico web termina aquí.
 *
 * Equivale exactamente a public/index.php de CodeIgniter 4 pero:
 *   1. __DIR__ aquí es /var/task/user/api en vez de /public.
 *   2. Hay que apuntar FCPATH al directorio public/ real.
 */

use CodeIgniter\Boot;
use Config\Paths;

if (getenv('CI_ENVIRONMENT') === false && ! defined('CI_ENVIRONMENT')) {
    define('CI_ENVIRONMENT', 'production');
}

$minPhpVersion = '8.1';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;
    exit(1);
}

$rootDir = dirname(__DIR__);

define('FCPATH', $rootDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

require $rootDir . '/app/Config/Paths.php';

$paths = new Paths();

require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
