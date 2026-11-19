<?php declare(strict_types=1);

namespace Tests\Controllers\Auth;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockEmail;
use Config\Email;
use Config\Services;
use Tests\Support\DatabaseTestCase;

/**
 * Recuperación de contraseña lista para el servidor municipal: el cifrado
 * SMTP corresponde al puerto, el correo saluda con el nombre real y un envío
 * fallido queda registrado en vez de perderse en silencio.
 */
class RecuperacionCorreoTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    private array $envOriginal = [];
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['EMAIL_SMTP_PORT', 'EMAIL_SMTP_CRYPTO', 'EMAIL_PROTOCOL'] as $k) {
            $this->envOriginal[$k] = $_ENV[$k] ?? null;
        }
        $this->db->table('users')->insert([
            'username' => 'recupera01', 'email' => 'recupera@municipio.test', 'password_hash' => password_hash('Inicial2026', PASSWORD_DEFAULT),
            'nombre_completo' => 'María López Recupera', 'activo' => 1,
        ]);
        $this->userId = (int) $this->db->insertID();
    }

    protected function tearDown(): void
    {
        foreach ($this->envOriginal as $k => $v) {
            if ($v === null) {
                unset($_ENV[$k]);
            } else {
                $_ENV[$k] = $v;
            }
        }
        Services::reset();
        parent::tearDown();
    }

    private function correoSimulado(bool $resultado): MockEmail
    {
        $correo = new MockEmail(config(Email::class));
        $correo->returnValue = $resultado;
        Services::injectMock('email', $correo);

        return $correo;
    }

    public function testElCifradoCorrespondeAlPuerto(): void
    {
        $casos = [['587', '', 'tls'], ['465', '', 'ssl'], ['25', 'none', ''], ['587', 'ssl', 'ssl']];
        foreach ($casos as [$puerto, $cifrado, $esperado]) {
            $_ENV['EMAIL_SMTP_PORT']   = $puerto;
            $_ENV['EMAIL_SMTP_CRYPTO'] = $cifrado;
            $this->assertSame($esperado, (new Email())->SMTPCrypto, "puerto {$puerto}, cifrado '{$cifrado}'");
        }
    }

    public function testElCorreoLlevaElNombreYUnEnlaceDeUnaHora(): void
    {
        $correo = $this->correoSimulado(true);

        $this->post('auth/attempt-forgot', ['email' => 'recupera@municipio.test'])->assertRedirect();

        $this->assertStringContainsString('María López Recupera', $correo->archive['body'] ?? '');
        $this->assertMatchesRegularExpression('#/auth/reset/[0-9a-f]{40}#', $correo->archive['body'] ?? '');
        $this->assertStringContainsString('1 hora', $correo->archive['body'] ?? '');
        $this->assertSame(0, $this->db->table('auditoria')->where('accion', 'recuperacion_correo_fallido')->countAllResults());
    }

    public function testUnEnvioFallidoQuedaRegistrado(): void
    {
        $this->correoSimulado(false);

        $this->post('auth/attempt-forgot', ['email' => 'recupera@municipio.test'])
            ->assertSessionHas('message', 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.');

        $this->assertSame(1, $this->db->table('auditoria')->where('accion', 'recuperacion_correo_fallido')
            ->where('entidad_id', $this->userId)->countAllResults());
    }
}
