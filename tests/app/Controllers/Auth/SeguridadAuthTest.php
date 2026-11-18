<?php declare(strict_types=1);

namespace Tests\Controllers\Auth;

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\DatabaseTestCase;

/**
 * Auditoría de Félix, Fase 1: hallazgos C2 (botones demo), A2 (restablecer
 * contraseña) y M5 (cuentas sin contraseña).
 */
class SeguridadAuthTest extends DatabaseTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        // Apagada explícitamente: el .env local puede encenderla para la demo.
        putenv('APP_ENABLE_DEMO_LOGIN=false');
        $_ENV['APP_ENABLE_DEMO_LOGIN'] = 'false';
    }

    protected function tearDown(): void
    {
        putenv('APP_ENABLE_DEMO_LOGIN');
        unset($_ENV['APP_ENABLE_DEMO_LOGIN']);
        parent::tearDown();
    }

    private function crearUsuario(string $username, ?string $hash, array $extra = []): int
    {
        $this->db->table('users')->insert(array_merge([
            'username'        => $username,
            'email'           => $username . '@example.com',
            'nombre_completo' => 'Usuario ' . $username,
            'password_hash'   => $hash,
            'activo'          => 1,
        ], $extra));

        return (int) $this->db->insertID();
    }

    public function testLosBotonesDemoNoAparecenSinLaBandera(): void
    {
        $cuerpo = $this->get('/auth/login')->getBody();

        $this->assertStringNotContainsString('fillLogin(\'admin\'', $cuerpo);
        $this->assertStringNotContainsString('Acceso R', $cuerpo);
    }

    public function testLosBotonesDemoAparecenConLaBandera(): void
    {
        putenv('APP_ENABLE_DEMO_LOGIN=true');
        $_ENV['APP_ENABLE_DEMO_LOGIN'] = 'true';

        $cuerpo = $this->get('/auth/login')->getBody();

        $this->assertStringContainsString("fillLogin('admin'", $cuerpo);
    }

    public function testRestablecerContrasenaCambiaLaContrasena(): void
    {
        $token = 'token-de-prueba-123';
        $id = $this->crearUsuario('olvidadizo', password_hash('Anterior123', PASSWORD_DEFAULT), [
            'reset_token'  => hash('sha256', $token),
            'reset_expira' => date('Y-m-d H:i:s', time() + 3600),
        ]);

        $respuesta = $this->post('/auth/reset/' . $token, [
            'password'         => 'Nueva12345',
            'password_confirm' => 'Nueva12345',
        ]);

        $respuesta->assertRedirectTo(site_url('auth/login'));
        $usuario = $this->db->table('users')->where('id', $id)->get()->getRow();
        $this->assertTrue(password_verify('Nueva12345', (string) $usuario->password_hash));
        $this->assertNull($usuario->reset_token);
    }

    public function testRestablecerConTokenVencidoNoCambiaNada(): void
    {
        $token = 'token-vencido-456';
        $id = $this->crearUsuario('tarde', password_hash('Anterior123', PASSWORD_DEFAULT), [
            'reset_token'  => hash('sha256', $token),
            'reset_expira' => date('Y-m-d H:i:s', time() - 60),
        ]);

        $this->post('/auth/reset/' . $token, [
            'password'         => 'Nueva12345',
            'password_confirm' => 'Nueva12345',
        ]);

        $usuario = $this->db->table('users')->where('id', $id)->get()->getRow();
        $this->assertTrue(password_verify('Anterior123', (string) $usuario->password_hash));
    }

    public function testUnaCuentaSinContrasenaNoPuedeIniciarSesion(): void
    {
        $this->crearUsuario('sinacceso', null);

        $respuesta = $this->post('/auth/attempt-login', [
            'username' => 'sinacceso',
            'password' => 'cualquiera123',
        ]);

        $this->assertNull(session('user_id'));
        $this->assertTrue($respuesta->isRedirect());
    }
}
