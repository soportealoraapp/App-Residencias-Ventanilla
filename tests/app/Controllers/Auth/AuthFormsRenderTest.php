<?php declare(strict_types=1);

namespace Tests\Controllers\Auth;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use DOMDocument;
use DOMXPath;

/**
 * Formularios de acceso.
 *
 * Estas vistas fueron las unicas tocadas por la pasada de ergonomica movil que
 * la suite nunca renderizaba, asi que aqui se comprueba que el HTML sigue
 * integro: si un atributo se hubiera insertado fuera de su etiqueta, el
 * analizador DOM perderia un <input> y el recuento dejaria de cuadrar.
 *
 * Los atributos se leen por nombre, nunca con expresiones que dependan del
 * orden: la reescritura los dejo delante de `type` en unas vistas y detras de
 * `name` en otras, y ambas formas son igual de validas para el navegador.
 */
class AuthFormsRenderTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * @return array{dom:int, html:int, inputs:array<string,array<string,string>>, ids:array<int,string>}
     */
    private function inspeccionar(string $body): array
    {
        $documento = new DOMDocument();
        $previo    = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>' . $body);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $xpath  = new DOMXPath($documento);
        $nodos  = $xpath->query('//input');
        $inputs = [];
        $ids    = [];

        /** @var \DOMElement $nodo */
        foreach ($nodos as $nodo) {
            $nombre = (string) $nodo->getAttribute('name');
            $atributos = [];
            foreach ($nodo->attributes as $attr) {
                $atributos[$attr->nodeName] = $attr->nodeValue ?? '';
            }
            $inputs[$nombre] = $atributos;
            $ids[]           = (string) $nodo->getAttribute('id');
        }

        return [
            'dom'    => $nodos === false ? 0 : $nodos->length,
            'html'   => preg_match_all('/<input\b/i', $body),
            'inputs' => $inputs,
            'ids'    => $ids,
        ];
    }

    /** @param array<string,string> $atributos */
    private function assertAtributos(array $atributos, array $esperado, string $campo): void
    {
        foreach ($esperado as $clave => $valor) {
            self::assertArrayHasKey($clave, $atributos, 'El campo ' . $campo . ' perdio ' . $clave . '.');
            self::assertSame($valor, $atributos[$clave], 'El campo ' . $campo . ' declara ' . $clave . ' equivocado.');
        }
    }

    public function testElLoginSigueIntegroYListoParaElTeclado(): void
    {
        $respuesta = $this->get('/auth/login');
        $respuesta->assertOK();
        $cuerpo = $respuesta->getBody();
        $datos  = $this->inspeccionar($cuerpo);

        // Ningun <input> perdido por una etiqueta mal cerrada.
        self::assertSame($datos['html'], $datos['dom'], 'El HTML anuncia mas <input> de los que el DOM encuentra.');
        self::assertGreaterThanOrEqual(2, $datos['dom']);

        // Quien escribe sus propias credenciales debe poder autocompletarlas.
        $this->assertAtributos($datos['inputs']['username'] ?? [], [
            'autocomplete' => 'username',
            'spellcheck'   => 'false',
            'enterkeyhint' => 'next',
        ], 'username');
        $this->assertAtributos($datos['inputs']['password'] ?? [], [
            'autocomplete' => 'current-password',
            'enterkeyhint' => 'go',
        ], 'password');
    }

    public function testElRegistroSigueIntegroYConSusCampos(): void
    {
        $respuesta = $this->get('/auth/register');
        $respuesta->assertOK();
        $cuerpo = $respuesta->getBody();
        $datos  = $this->inspeccionar($cuerpo);

        self::assertSame($datos['html'], $datos['dom'], 'El HTML anuncia mas <input> de los que el DOM encuentra.');

        foreach (['curp', 'nombre', 'apellido', 'telefono', 'email', 'username', 'password'] as $campo) {
            self::assertArrayHasKey($campo, $datos['inputs'], 'Falta el campo ' . $campo . ' del registro.');
        }

        $this->assertAtributos($datos['inputs']['nombre'], ['autocomplete' => 'given-name'], 'nombre');
        $this->assertAtributos($datos['inputs']['apellido'], ['autocomplete' => 'family-name'], 'apellido');
        $this->assertAtributos($datos['inputs']['telefono'], ['inputmode' => 'tel'], 'telefono');
        $this->assertAtributos($datos['inputs']['email'], ['inputmode' => 'email'], 'email');
        // Contraseña nueva: el navegador no debe reutilizar la anterior.
        $this->assertAtributos($datos['inputs']['password'], ['autocomplete' => 'new-password'], 'password');
        // La CURP no tiene token estandar de autocompletado: se desactiva expreso.
        $this->assertAtributos($datos['inputs']['curp'], [
            'autocomplete'   => 'off',
            'autocapitalize' => 'characters',
        ], 'curp');
    }

    public function testLaRecuperacionSigueIntegra(): void
    {
        $respuesta = $this->get('/auth/forgot');
        $respuesta->assertOK();
        $cuerpo = $respuesta->getBody();
        $datos  = $this->inspeccionar($cuerpo);

        self::assertSame($datos['html'], $datos['dom'], 'El HTML anuncia mas <input> de los que el DOM encuentra.');
        self::assertArrayHasKey('email', $datos['inputs']);
        $this->assertAtributos($datos['inputs']['email'], [
            'type'      => 'email',
            'inputmode' => 'email',
        ], 'email');
    }

    public function testCadaCampoVisibleTieneSuEtiqueta(): void
    {
        $respuesta = $this->get('/auth/register');
        $respuesta->assertOK();
        $datos = $this->inspeccionar($respuesta->getBody());

        $documento = new DOMDocument();
        $previo    = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="utf-8"?>' . $respuesta->getBody());
        libxml_clear_errors();
        libxml_use_internal_errors($previo);

        $para = [];
        /** @var \DOMElement $etiqueta */
        foreach ((new DOMXPath($documento))->query('//label[@for]') as $etiqueta) {
            $para[] = $etiqueta->getAttribute('for');
        }

        foreach ($datos['inputs'] as $nombre => $atributos) {
            if (($atributos['type'] ?? 'text') === 'hidden') {
                continue;
            }
            $id = $atributos['id'] ?? '';
            self::assertNotSame('', $id, 'El campo ' . $nombre . ' no tiene id.');
            self::assertContains($id, $para, 'El campo ' . $nombre . ' se queda sin etiqueta: en el movil no se sabe que pide.');
        }
    }

    public function testNingunFormularioArrastraRestosDeLaReescritura(): void
    {
        foreach (['/auth/login', '/auth/register', '/auth/forgot'] as $ruta) {
            $respuesta = $this->get($ruta);
            $respuesta->assertOK();
            $cuerpo = $respuesta->getBody();

            // Secuencias que solo podrian venir de un transformo torpe.
            self::assertStringNotContainsString('XXXX', $cuerpo, $ruta . ' conserva restos del enmascarado.');

            // Comillas pareadas: una etiqueta truncada dejaria el HTML chueco.
            self::assertSame(0, substr_count($cuerpo, '"') % 2, $ruta . ' deja comillas dobles sin cerrar.');
        }
    }

    /**
     * Los atajos /login y /register estuvieron definidos desde el primer commit
     * pero CI4 los descartaba en silencio: su ruta coincidia con el nombre que ya
     * traia ['as' => 'login'] en auth/login. La pagina 404 propia enlaza a
     * /login, asi que el boton "Iniciar sesion" llevaba a otro 404.
     */
    public function testLosAtajosDeAccesoResponden(): void
    {
        foreach (['/login', '/register'] as $atajo) {
            $respuesta = $this->get($atajo);
            self::assertSame(200, $respuesta->response()->getStatusCode(), $atajo . ' no tiene ruta registrada.');
        }
    }

    public function testCerrarSesionPorAtajoRedirige(): void
    {
        $this->get('/logout')->assertRedirect();
    }
}
