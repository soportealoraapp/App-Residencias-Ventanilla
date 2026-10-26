<?php declare(strict_types=1);

namespace Tests\Libraries;

use App\Libraries\CobroException;
use App\Libraries\ReferenciaBancariaProvisional;
use CodeIgniter\Test\CIUnitTestCase;

class ReferenciaBancariaProvisionalTest extends CIUnitTestCase
{
    private ReferenciaBancariaProvisional $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ref = new ReferenciaBancariaProvisional();
    }

    public function testFormatoDeDoceDigitosConVerificador(): void
    {
        $this->assertSame('100000000190', $this->ref->generar('solicitud', 1));
        $this->assertSame('200000000185', $this->ref->generar('boleta', 1));
        $this->assertMatchesRegularExpression('/^1\d{11}$/', $this->ref->generar('solicitud', 987654321));
        $this->assertSame(service('referenciaBancaria')->generar('boleta', 42), $this->ref->generar('boleta', 42));
    }

    public function testDetectaErroresDeCaptura(): void
    {
        $buena = $this->ref->generar('solicitud', 1234);
        $this->assertTrue($this->ref->esValida($buena));
        $this->assertTrue($this->ref->esValida('1000 0012 34' . substr($buena, 10)));
        $this->assertTrue($this->ref->esValida(substr($buena, 0, 4) . '-' . substr($buena, 4)));

        // Un dígito cambiado.
        $otroDigito = substr($buena, 0, 9) . (((int) $buena[9] + 1) % 10) . substr($buena, 10);
        $this->assertFalse($this->ref->esValida($otroDigito));
        // Dos dígitos intercambiados.
        $intercambio = substr($buena, 0, 7) . $buena[8] . $buena[7] . substr($buena, 9);
        $this->assertNotSame($buena, $intercambio);
        $this->assertFalse($this->ref->esValida($intercambio));

        $this->assertFalse($this->ref->esValida('12345'));
        $this->assertFalse($this->ref->esValida('ABC000000190'));
    }

    public function testNoGeneraConDatosInvalidos(): void
    {
        $this->expectException(CobroException::class);
        $this->ref->generar('otro', 1);
    }
}
