<?php declare(strict_types=1);

/*
 * Front Controller para el entorno sin servidor (serverless).
 * La plataforma enruta todo el tráfico web hacia este archivo mediante las
 * reglas de reescritura definidas en la configuración de despliegue.
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
        'Se requiere PHP %s o superior para ejecutar el sistema. Versión actual: %s',
        $minPhpVersion,
        PHP_VERSION,
    );
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;
    exit(1);
}

$rootDir = dirname(__DIR__);

// Oculta la version de PHP en las respuestas.
header_remove('X-Powered-By');

// -----------------------------------------------------------------------------
// Vercel invoca este archivo como función, así que SCRIPT_NAME llega como
// '/api/index.php'. CodeIgniter recorta el SCRIPT_NAME segmento a segmento del
// REQUEST_URI (SiteURIFactory::parseRequestURI), de modo que la petición
// '/api/parametros' se convertía en 'parametros' y ninguna ruta declarada con
// el prefijo 'api/' coincidía: en producción los endpoints de la app de
// agentes respondían 404 mientras el resto del sitio funcionaba. El propio
// marco trae la solución en system/rewrite.php: declarar que todas las
// peticiones las atiende /index.php, que es lo que vercel.json reescribe.
// -----------------------------------------------------------------------------
$_SERVER['SCRIPT_NAME'] = '/index.php';

define('FCPATH', $rootDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

require $rootDir . '/app/Config/Paths.php';

$paths = new Paths();

require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
