<?php declare(strict_types=1);

namespace Tests\Controllers\Views;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Enlaces estáticos de las vistas contra el mapa de rutas real.
 *
 * La página 404 propia enlazaba a /login, y /login no existía: CI4 descartaba la
 * definición porque su ruta coincidía con el nombre que ya traía
 * ['as' => 'login'] en auth/login. El resultado era un botón "Iniciar sesión"
 * que llevaba a otro 404, y ninguna prueba lo veía porque los enlaces escritos a
 * mano dentro del HTML nunca se cotejaron.
 *
 * Este examen solo revisa los href escritos como cadena literal (los construidos
 * con site_url() o variables dependen de datos y se comprueban en su propia
 * prueba de controller).
 */
final class EnlacesEstaticosTest extends CIUnitTestCase
{
    /**
     * Rutas registradas convertidas a expresiones regulares.
     *
     * @return array<int, string>
     */
    private function patrones(): array
    {
        $coleccion = service('routes');

        // El servicio `routes` llega sin cargar app/Config/Routes.php: quien lo hace
        // es el ciclo de la petición. Sin esta llamada el mapa vendría vacío y el
        // examen acusaría de muertos todos los enlaces. Es idempotente.
        $coleccion->loadRoutes();

        $rutas = [];

        // Las claves del mapa están en mayúsculas (Method::GET); con `get` el
        // isset() falla y getRoutes() devuelve [] sin avisar.
        foreach (['GET', 'POST'] as $verbo) {
            foreach ($coleccion->getRoutes($verbo) as $ruta => $manejador) {
                if (! is_string($ruta)) {
                    continue;
                }
                $rutas[] = $ruta;
            }
        }

        $marcas = [
            '(:num)'           => '@@NUM@@',
            '(:segment)'       => '@@SEG@@',
            '(:any)'           => '@@ANY@@',
            '(:alphanumeric)'  => '@@ALNUM@@',
            '(:hash)'          => '@@HASH@@',
            '(:uri)'           => '@@URI@@',
        ];
        $expresiones = [
            '@@NUM@@'   => '[0-9]+',
            '@@SEG@@'   => '[^/]+',
            '@@ANY@@'   => '.+',
            '@@ALNUM@@' => '[a-zA-Z0-9]+',
            '@@HASH@@'  => '[a-f0-9]{40}',
            '@@URI@@'   => '.+',
        ];

        $patrones = [];
        foreach (array_unique($rutas) as $ruta) {
            $normalizada = strtr('/' . trim($ruta, '/'), $marcas);
            $escapada    = preg_quote($normalizada, '#');
            $patrones[]  = '#^' . strtr($escapada, $expresiones) . '$#';
        }

        return $patrones;
    }

    /** @return array<int, array{archivo: string, enlace: string}> */
    private function enlacesEnVistas(): array
    {
        $encontrados = [];
        $archivos    = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(APPPATH . 'Views', \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($archivos as $archivo) {
            if (! $archivo instanceof \SplFileInfo || strtolower($archivo->getExtension()) !== 'php') {
                continue;
            }

            // Las plantillas de errores de CI4 (errors/html y errors/php) incluyen
            // vistas del framework con enlaces de depuración que no son del sitio.
            if (str_contains(str_replace('\\', '/', $archivo->getRealPath()), '/Views/errors/html/error_exception')) {
                continue;
            }

            $contenido = (string) file_get_contents($archivo->getRealPath());

            if (preg_match_all('/href="(\/[^"?#]*)"/', $contenido, $coincidencias)) {
                foreach ($coincidencias[1] as $enlace) {
                    if (str_contains($enlace, '<?') || str_contains($enlace, '$') || str_contains($enlace, '{')) {
                        continue; // construido en runtime: no es un literal
                    }
                    $encontrados[] = [
                        'archivo' => str_replace(APPPATH, '', $archivo->getRealPath()),
                        'enlace'  => $enlace,
                    ];
                }
            }
        }

        return $encontrados;
    }

    public function testTodoEnlaceLiteralTieneRutaOOArchivo(): void
    {
        $patrones = $this->patrones();
        $publico  = realpath(FCPATH) ?: FCPATH;
        $huerfanos = [];

        foreach ($this->enlacesEnVistas() as $dato) {
            $enlace = rtrim($dato['enlace'], '/');
            if ($enlace === '') {
                continue;
            }

            $rutaFisica    = $publico . str_replace('/', DIRECTORY_SEPARATOR, $enlace);
            $esArchivoFisico = is_file($rutaFisica)
                // /agentes no es una ruta CI4: es el export del Expo web y se sirve
                // como directorio con su propio index.html.
                || is_file($rutaFisica . DIRECTORY_SEPARATOR . 'index.html');
            $coincide       = false;
            foreach ($patrones as $patron) {
                if (preg_match($patron, $enlace)) {
                    $coincide = true;
                    break;
                }
            }

            if (! $coincide && ! $esArchivoFisico) {
                $huerfanos[] = $dato['archivo'] . ' → ' . $dato['enlace'];
            }
        }

        $this->assertSame([], $huerfanos,
            "Enlaces que no corresponden a ninguna ruta registrada ni a un archivo de public/:\n  "
            . implode("\n  ", array_unique($huerfanos)));
    }

    public function testElMapaDeRutasNoEstaVacio(): void
    {
        // Salvaguarda del propio examen: si el mapa viniera vacío, el examen
        // anterior acusaría de muerto cada enlace y daría falso positivo.
        $this->assertNotEmpty($this->patrones(), 'El mapa de rutas no está cargado en pruebas.');
        $this->assertContains('#^/login$#', $this->patrones(), 'El atajo /login debería estar en el mapa.');
    }
}
