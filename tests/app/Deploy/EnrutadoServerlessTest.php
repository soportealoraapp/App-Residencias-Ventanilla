<?php declare(strict_types=1);

namespace Tests\Deploy;

use CodeIgniter\HTTP\SiteURIFactory;
use CodeIgniter\Superglobals;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Detección de la ruta cuando el front controller no vive en la raíz.
 *
 * El 19-10-2026 la app de agentes desplegada avisaba "El servicio de autenticación
 * no está disponible": en https://app-residencias.vercel.app los endpoints
 * (/api/parametros, /api/checklist-infracciones, /api/agentes/login,
 * /api/infracciones/sincronizar, /api/boletas/sincronizar y
 * /api/infracciones/evidencias) respondían 404 con la página de error propia de
 * CI4, es decir el PHP sí corría pero el enrutador no encontraba nada. La causa
 * no estaba en el mapa de rutas: vercel.json reescribe todo hacia
 * /api/index.php y CodeIgniter recorta el SCRIPT_NAME segmento a segmento del
 * REQUEST_URI (SiteURIFactory::parseRequestURI), así que el prefijo
 * `api` se consumía como si fuera el directorio del script y `/api/parametros`
 * llegaba al enrutador como `parametros`. Ninguna prueba lo detectaba porque en
 * local el front controller es public/index.php, donde el recorte no borra nada.
 *
 * Este examen fija tres cosas: el mecanismo que provoca el 404, que con la
 * corrección las rutas API vuelven a resolverse, y que el arreglo sigue escrito
 * en el front controller serverless.
 */
final class EnrutadoServerlessTest extends CIUnitTestCase
{
    /** @var array{server: array<string, mixed>, get: array<string, mixed>, post: array<string, mixed>, cookie: array<string, mixed>, files: array<string, mixed>, request: array<string, mixed>} */
    private array $superglobalesOriginales = [];

    protected function setUp(): void
    {
        parent::setUp();

        // SiteURIFactory se construye con un Superglobals que escribe en los
        // superglobales reales: se restauran todos al terminar cada prueba.
        $this->superglobalesOriginales = [
            'server'  => $_SERVER,
            'get'     => $_GET,
            'post'    => $_POST,
            'cookie'  => $_COOKIE,
            'files'   => $_FILES,
            'request' => $_REQUEST,
        ];
    }

    protected function tearDown(): void
    {
        // Asignaciones literales: con `$$nombre` PHP crearía una variable local
        // que sombra al superglobal y el estado real nunca se recuperaría.
        $_SERVER  = $this->superglobalesOriginales['server'];
        $_GET     = $this->superglobalesOriginales['get'];
        $_POST    = $this->superglobalesOriginales['post'];
        $_COOKIE  = $this->superglobalesOriginales['cookie'];
        $_FILES   = $this->superglobalesOriginales['files'];
        $_REQUEST = $this->superglobalesOriginales['request'];

        parent::tearDown();
    }

    /**
     * Ruta que CodeIgniter vería para el REQUEST_URI dado, con el SCRIPT_NAME
     * que envía la plataforma de despliegue.
     */
    private function rutaDetectada(string $requestUri, string $scriptName): string
    {
        $factoria = new SiteURIFactory(
            config(App::class),
            new Superglobals([
                'REQUEST_URI' => $requestUri,
                'SCRIPT_NAME' => $scriptName,
                'HTTP_HOST'   => 'app-residencias.vercel.app',
                'SERVER_PORT' => '443',
                'HTTPS'       => 'on',
            ], [], [], [], [], [])
        );

        return $factoria->detectRoutePath();
    }

    /** @return array<string, string> uri de producción => verbo declarado */
    private function endpointsMoviles(): array
    {
        return [
            '/api/parametros'                => 'GET',
            '/api/checklist-infracciones'    => 'GET',
            '/api/agentes/login'             => 'POST',
            '/api/infracciones/sincronizar'  => 'POST',
            '/api/boletas/sincronizar'       => 'POST',
            '/api/infracciones/evidencias'   => 'POST',
        ];
    }

    public function testElPrefijoApiDesapareceSiElScriptSigueEnElDirectorioApi(): void
    {
        // Reproducción del fallo: así veía Vercel las peticiones antes de la
        // corrección. El segmento `api` se descarta junto con el nombre del
        // script, y el enrutador se queda con una ruta que nadie declaró.
        $this->assertSame('parametros', $this->rutaDetectada('/api/parametros', '/api/index.php'));
        $this->assertSame('agentes/login', $this->rutaDetectada('/api/agentes/login', '/api/index.php'));

        $coleccion = service('routes');
        $coleccion->loadRoutes();

        $this->assertArrayNotHasKey('parametros', $coleccion->getRoutes('GET'),
            'Si `parametros` existiera como ruta suelta, el 404 tendría otra explicación.');
    }

    public function testConElFrontControllerEnLaRaizLasRutasApiSeResuelven(): void
    {
        $coleccion = service('routes');
        $coleccion->loadRoutes();

        foreach ($this->endpointsMoviles() as $uri => $verbo) {
            $detectada = $this->rutaDetectada($uri, '/index.php');

            $this->assertSame(ltrim($uri, '/'), $detectada, "REQUEST_URI {$uri} debe detectarse sin recortar el prefijo api/.");
            $this->assertArrayHasKey($detectada, $coleccion->getRoutes($verbo),
                "La ruta {$detectada} no está declarada para {$verbo}; el endpoint volvería a 404 en producción.");
        }
    }

    public function testLasRutasDeNavegacionNoCambianConLaCorreccion(): void
    {
        // La corrección no debe alterar lo que ya funcionaba: las rutas fuera de
        // /api no pierden ni ganan segmentos.
        $this->assertSame('login', $this->rutaDetectada('/login', '/index.php'));
        $this->assertSame('portal/tramites', $this->rutaDetectada('/portal/tramites', '/index.php'));
        $this->assertSame('/', $this->rutaDetectada('/', '/index.php'));
    }

    public function testElFrontControllerServerlessDejaSCRIPTNameEnLaRaiz(): void
    {
        $archivo = (string) file_get_contents(ROOTPATH . 'api/index.php');

        $asignado = strpos($archivo, "['SCRIPT_NAME'] = '/index.php'");
        $arranque = strpos($archivo, 'Boot::bootWeb');

        $this->assertNotFalse($asignado,
            'api/index.php debe fijar $_SERVER["SCRIPT_NAME"] = "/index.php"; sin esa línea, CI4 recorta "api" del REQUEST_URI y todos los endpoints móviles devuelven 404 en Vercel.');
        $this->assertNotFalse($arranque, 'No se encontró el arranque de CI4 en api/index.php.');
        $this->assertLessThan($arranque, $asignado,
            'El SCRIPT_NAME debe corregirse antes de arrancar el framework; después ya se habría detectado la ruta.');
    }

    public function testVercelSirveLasFuentesDeIconosDeLaRaiz(): void
    {
        // La app de agentes se sirve en /agentes y también en rutas hijas como
        // /agentes/login; desde ahí una fuente relativa pide /Ionicons.<hash>.ttf
        // a la raíz del dominio. Si vercel.json no la lista, la petición cae en el
        // catch-all y CI4 responde su 404, así que los iconos desaparecen.
        $rutas = json_decode((string) file_get_contents(ROOTPATH . 'vercel.json'), true, 512, JSON_THROW_ON_ERROR)['routes'] ?? [];
        $this->assertNotEmpty($rutas, 'vercel.json no define reglas de rutas.');

        $ultima = end($rutas);
        $this->assertSame('/api/index.php', $ultima['dest'] ?? null, 'El catch-all debe ser la última regla de vercel.json.');

        $fuentes = glob(FCPATH . '*.ttf') ?: [];
        $this->assertNotEmpty($fuentes, 'public/ debería contener las fuentes .ttf que pide el bundle de agentes.');

        foreach ($fuentes as $archivo) {
            $peticion = '/' . basename($archivo);
            $resuelta = false;

            foreach ($rutas as $regla) {
                if (($regla['dest'] ?? '') === '/api/index.php') {
                    continue; // el catch-all nunca es la respuesta correcta para un estático
                }
                if (preg_match('#' . $regla['src'] . '#', $peticion) === 1) {
                    $destino = preg_replace('#' . $regla['src'] . '#', $regla['dest'], $peticion);
                    $this->assertFileExists(ROOTPATH . ltrim((string) $destino, '/'),
                        "{$peticion} se sirve desde {$destino}, que no existe en el repositorio.");
                    $resuelta = true;
                    break;
                }
            }

            $this->assertTrue($resuelta, "{$peticion} no la atiende ninguna regla estática: caería en CI4 y respondería 404.");
        }
    }
}
