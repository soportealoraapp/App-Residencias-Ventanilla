<?php declare(strict_types=1);

namespace App\Commands;

use App\Models\AuditoriaModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Crea la primera cuenta de administrador en una instalación limpia
 * (GUIA_MIGRACION.md, sección 10). Sin ella nadie puede entrar al panel: el
 * registro público solo crea ciudadanos y los seeders de catálogos no crean
 * usuarios.
 *
 *     php spark usuarios:crear-admin --usuario admin01 --correo ti@uriangato.gob.mx --nombre "Nombre Apellido"
 *
 * La contraseña se genera al azar y se muestra una sola vez en la terminal de
 * quien instala; no queda en el historial de comandos. Se cambia después en
 * Admin → Usuarios. Los demás usuarios (operadores, juez) se dan de alta desde
 * el panel.
 */
class CrearAdministrador extends BaseCommand
{
    protected $group       = 'Usuarios';
    protected $name        = 'usuarios:crear-admin';
    protected $description = 'Crea una cuenta de administrador con contraseña aleatoria.';
    protected $usage       = 'usuarios:crear-admin --usuario <usuario> --correo <correo> --nombre "<nombre completo>"';
    protected $options     = [
        '--usuario' => 'Nombre de usuario (letras y números, 5 a 100).',
        '--correo'  => 'Correo para recuperar la contraseña.',
        '--nombre'  => 'Nombre completo.',
    ];

    public function run(array $params)
    {
        $usuario = trim((string) ($params['usuario'] ?? CLI::getOption('usuario') ?? ''));
        $correo  = strtolower(trim((string) ($params['correo'] ?? CLI::getOption('correo') ?? '')));
        $nombre  = trim((string) ($params['nombre'] ?? CLI::getOption('nombre') ?? ''));

        if (preg_match('/^[A-Za-z0-9]{5,100}$/', $usuario) !== 1) {
            CLI::error('--usuario: solo letras y números, de 5 a 100 caracteres.');

            return EXIT_USER_INPUT;
        }
        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            CLI::error('--correo: escribe un correo válido.');

            return EXIT_USER_INPUT;
        }
        if (mb_strlen($nombre) < 3) {
            CLI::error('--nombre: escribe el nombre completo.');

            return EXIT_USER_INPUT;
        }

        $db = Database::connect();
        if ($db->table('users')->groupStart()->where('username', $usuario)->orWhere('email', $correo)->groupEnd()->countAllResults() > 0) {
            CLI::error('Ya existe una cuenta con ese usuario o correo.');

            return EXIT_ERROR;
        }
        $rol = $db->table('roles')->where('nombre', 'administrador')->get()->getRow();
        if ($rol === null) {
            CLI::error('No existe el rol administrador. Corre antes: php spark db:seed RolesSeeder');

            return EXIT_ERROR;
        }

        $password = self::generarPassword();

        $db->transStart();
        $db->table('users')->insert([
            'username'        => $usuario,
            'email'           => $correo,
            'password_hash'   => password_hash($password, PASSWORD_DEFAULT),
            'nombre_completo' => $nombre,
            'activo'          => 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $db->insertID();
        $db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => (int) $rol->id]);
        $db->transComplete();

        if (! $db->transStatus()) {
            CLI::error('No se pudo crear la cuenta.');

            return EXIT_ERROR;
        }

        (new AuditoriaModel())->registrar('users', $userId, 'administrador_creado_consola', null, ['usuario' => $usuario]);

        CLI::write('Administrador creado: ' . $usuario, 'green');
        CLI::write('Contraseña temporal (no se vuelve a mostrar): ' . $password, 'yellow');
        CLI::write('Cámbiala al entrar, en Admin → Usuarios.');

        return EXIT_SUCCESS;
    }

    /** 16 caracteres sin letras ni números que se confundan, con al menos una letra y un número. */
    private static function generarPassword(): string
    {
        $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        do {
            $password = '';
            for ($i = 0; $i < 16; $i++) {
                $password .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while (preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1);

        return $password;
    }
}
