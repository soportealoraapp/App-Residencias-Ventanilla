<?php declare(strict_types=1);

namespace Tests\Deploy;

use CodeIgniter\HTTP\SiteURIFactory;
use CodeIgniter\Superglobals;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
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
 * Este examen fija cuatro cosas: el mecanismo que provoca el 404 de la API, que con
 * la corrección las rutas API vuelven a resolverse, que el arreglo sigue escrito en
 * el front controller serverless, y que los otros tres 404 que salieron del mismo
 * console (las fuentes .ttf de la raíz, el HEAD / que NetInfo usa para saber si hay
 * red y las rutas hijas de /agentes) tienen quién los atienda.
 */
final class EnrutadoServerlessTest extends CIUnitTestCase
{
    use FeatureTestTrait;

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

    public function testLaRaizRespondeHeadAlSondeoDeConectividad(): void
    {
        // NetInfo, el detector de red que usa la app de agentes, solo da por buena
        // la conexión si HEAD / responde 200. CI4 no resuelve HEAD con las rutas
        // GET, así que el sondeo caía en el 404: la app se marcaba sin conexión y
        // el auto-sincronizado al recuperar la red no llegaba a dispararse.
        $coleccion = service('routes');
        $coleccion->loadRoutes();

        $this->assertArrayHasKey('/', $coleccion->getRoutes('HEAD'),
            'HEAD / debe estar declarado; sin él el sondeo de NetInfo recibe 404.');

        $respuesta = $this->call('HEAD', '/');
        $respuesta->assertResponseOK();
        $this->assertSame('', (string) $respuesta->response()->getBody(),
            'El sondeo no debe arrastrar el HTML de la portada ni abrir sesión.');
    }

    /** @return array<int, array{src: string, dest: string}> */
    private function reglasVercel(): array
    {
        return json_decode((string) file_get_contents(ROOTPATH . 'vercel.json'), true, 512, JSON_THROW_ON_ERROR)['routes'] ?? [];
    }

    /**
     * Primera regla que casa con la petición, en el orden en que Vercel las evalúa.
     *
     * @param array<int, array{src: string, dest: string}> $rutas
     * @return array{src: string, dest: string}|null
     */
    private function reglaQueAtiende(array $rutas, string $peticion): ?array
    {
        foreach ($rutas as $regla) {
            if (preg_match('#^' . $regla['src'] . '$#', $peticion) === 1) {
                return $regla;
            }
        }

        return null;
    }

    public function testElFallbackDeAgentesNoDependeDeUnaReglaEstaticaConComodin(): void
    {
        // Un enlace directo a /agentes/login, o simplemente un F5 en esa pantalla, es
        // la forma normal de entrar a la app de agentes. La primera idea fue resolver
        // ese fallback en vercel.json y falló dos veces: con `/agentes(/.*)?` y con
        // `/agentes(?:/[^/]*)*` la plataforma buscaba el destino más lo consumido
        // (/public/agentes/index.html/login), respondía su propio 404 con
        // x-vercel-error: NOT_FOUND y no dejaba caer en la regla siguiente.
        // Por eso aquí se verifica lo contrario: ninguna regla estática puede
        // prometer el shell de más de un segmento; las rutas hijas tienen que llegar
        // a la función, que es la única que recibe la URI original intacta.
        $rutas = $this->reglasVercel();

        $portada = $this->reglaQueAtiende($rutas, '/agentes');
        $this->assertNotNull($portada, '/agentes no la atiende ninguna regla de vercel.json.');
        $this->assertSame('/public/agentes/index.html', $portada['dest'],
            'La portada de la app de agentes debe seguir sirviéndose como archivo estático.');

        foreach (['/agentes/', '/agentes/login', '/agentes/infracciones/40'] as $peticion) {
            $regla = $this->reglaQueAtiende($rutas, $peticion);
            $this->assertNotNull($regla, "{$peticion} no la atiende ninguna regla de vercel.json.");
            $this->assertSame('/api/index.php', $regla['dest'],
                "{$peticion} debe caer en el front controller para que el fallback lo resuelva PHP; `dest` {$regla['dest']} lo resolvería como archivo y la plataforma respondería su 404.");
        }

        // Los archivos reales del export siguen ganando al fallback del shell.
        $this->assertSame('/public/agentes/_expo/$1', $this->reglaQueAtiende($rutas, '/agentes/_expo/static/js/web/entry-abcd.js')['dest'] ?? null,
            'Los bundles de _expo no pueden caer en el HTML del shell.');
        $this->assertSame('/public/agentes/fonts/$1', $this->reglaQueAtiende($rutas, '/agentes/fonts/Ionicons.ttf')['dest'] ?? null,
            'Las fuentes del export no pueden caer en el HTML del shell.');
    }

    public function testLasRutasHijasDeAgentesEntreganElShell(): void
    {
        // El fallback vive en AgentesWebController: cualquier ruta hija de /agentes
        // responde el HTML del export para que el enrutador de la app (Expo Router)
        // tome el control desde la URI que ve el navegador.
        $coleccion = service('routes');
        $coleccion->loadRoutes();

        // El mapa interno ya tiene el marcador convertido: (:any) se guarda como (.*)
        // y (:num) como ([0-9]+), así que en esa forma se busca la ruta declarada.
        $declaradas = array_intersect_key(
            $coleccion->getRoutes('GET'),
            array_flip(['agentes', 'agentes/(.*)']),
        );
        $this->assertCount(2, $declaradas,
            'Faltan las rutas GET "agentes" y "agentes/(:any)"; sin la segunda, un F5 en /agentes/login devuelve el 404 de la plataforma.');

        foreach (['/agentes/login', '/agentes/infracciones/40'] as $uri) {
            $respuesta = $this->call('GET', $uri);
            $respuesta->assertResponseOK();
            $this->assertStringContainsString(
                '/agentes/_expo/static/js/web/entry-',
                (string) $respuesta->response()->getBody(),
                "{$uri} no devuelve el shell del export: el navegador cargaría una página sin aplicación.",
            );
        }
    }

    public function testNingunaReglaEstaticaDejaGruposSinReferenciar(): void
    {
        // Vercel añade al destino la parte capturada que el destino no referencia, y
        // así `/agentes/login` se buscaba como `/public/agentes/index.html/login`.
        // Una regla con grupos en `src` debe usarlos todos en `dest`, o no tenerlos.
        $ofensas = [];

        foreach ($this->reglasVercel() as $regla) {
            // Una función sí recibe la URI original, así que ahí el sobrante no daña.
            if (str_ends_with($regla['dest'], '.php')) {
                continue;
            }

            $capturados = preg_match_all('/\((?!\?)/', $regla['src']);
            $usados     = 0;
            if (preg_match_all('/\$([0-9]+)/', $regla['dest'], $marcas) > 0) {
                $usados = count(array_unique($marcas[1]));
            }

            if ($capturados > $usados) {
                $ofensas[] = sprintf('%s (casa %d grupos, el destino usa %d) -> %s', $regla['src'], $capturados, $usados, $regla['dest']);
            }
        }

        $this->assertSame([], $ofensas,
            "Reglas cuyo destino no menciona todos los grupos capturados; Vercel les anexa el sobrante y el archivo deja de existir:\n  "
            . implode("\n  ", $ofensas));
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
        $rutas = $this->reglasVercel();
        $this->assertNotEmpty($rutas, 'vercel.json no define reglas de rutas.');

        $ultima = end($rutas);
        $this->assertSame('/api/index.php', $ultima['dest'] ?? null, 'El catch-all debe ser la última regla de vercel.json.');

        $fuentes = glob(FCPATH . '*.ttf') ?: [];
        $this->assertNotEmpty($fuentes, 'public/ debería contener las fuentes .ttf que pide el bundle de agentes.');

        foreach ($fuentes as $archivo) {
            $peticion = '/' . basename($archivo);
            $resuelta = false;

            foreach ($rutas as $regla) {
                // El catch-all hacia la función nunca es la respuesta correcta para un
                // archivo estático: CI4 respondería su propia página 404.
                if (str_ends_with($regla['dest'], '.php')) {
                    continue;
                }
                if (preg_match('#^' . $regla['src'] . '$#', $peticion) === 1) {
                    $destino = preg_replace('#^' . $regla['src'] . '$#', $regla['dest'], $peticion);
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
