<?php declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Email;

/**
 * Envía un correo de prueba con la configuración del .env (EMAIL_*), para
 * confirmar antes de abrir el sistema que la recuperación de contraseña podrá
 * enviar sus enlaces (GUIA_MIGRACION.md, sección 4).
 *
 *     php spark correo:probar persona@uriangato.gob.mx
 *
 * Muestra la configuración usada (sin la contraseña) y, si falla, la respuesta
 * del servidor de correo.
 */
class ProbarCorreo extends BaseCommand
{
    protected $group       = 'Correo';
    protected $name        = 'correo:probar';
    protected $description = 'Envía un correo de prueba con la configuración EMAIL_* del .env.';
    protected $usage       = 'correo:probar <destinatario>';
    protected $arguments   = ['destinatario' => 'Correo que recibirá la prueba.'];

    public function run(array $params)
    {
        $destino = trim((string) ($params[0] ?? ''));
        if (filter_var($destino, FILTER_VALIDATE_EMAIL) === false) {
            CLI::error('Uso: php spark correo:probar <destinatario>');

            return EXIT_USER_INPUT;
        }

        $config = config(Email::class);
        CLI::write('Protocolo: ' . $config->protocol);
        if ($config->protocol === 'smtp') {
            CLI::write(sprintf(
                'Servidor:  %s:%d · cifrado %s · usuario %s',
                $config->SMTPHost !== '' ? $config->SMTPHost : '(vacío)',
                $config->SMTPPort,
                $config->SMTPCrypto !== '' ? $config->SMTPCrypto : 'ninguno',
                $config->SMTPUser !== '' ? $config->SMTPUser : '(sin usuario)'
            ));
        }
        CLI::write('Remitente: ' . ($config->fromEmail !== '' ? $config->fromName . ' <' . $config->fromEmail . '>' : '(vacío)'));

        if ($config->fromEmail === '' || ($config->protocol === 'smtp' && $config->SMTPHost === '')) {
            CLI::error('Faltan EMAIL_FROM o EMAIL_SMTP_HOST en el .env.');

            return EXIT_ERROR;
        }

        $correo = service('email', $config, false);
        $correo->setTo($destino);
        $correo->setSubject('Prueba de correo - Ventanilla Digital Uriangato');
        $correo->setMessage('<p>Este es un correo de prueba de la Ventanilla Digital de Movilidad de Uriangato.</p>'
            . '<p>Si lo recibiste, la recuperación de contraseña podrá enviar sus enlaces.</p>');

        if (! $correo->send(false)) {
            CLI::error('No se pudo enviar. Respuesta del servidor de correo:');
            CLI::write(strip_tags((string) $correo->printDebugger(['headers'])));

            return EXIT_ERROR;
        }

        CLI::write('Correo enviado a ' . $destino . '. Revisa la bandeja de entrada (y la de spam).', 'green');

        return EXIT_SUCCESS;
    }
}
