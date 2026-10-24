<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Crea una cuenta demo solo cuando las credenciales se proporcionan al ejecutar.
 *
 * Variables requeridas:
 * JUEZ_DEMO_USERNAME, JUEZ_DEMO_EMAIL, JUEZ_DEMO_PASSWORD
 * Variable opcional: JUEZ_DEMO_NOMBRE
 */
class JuezCalificadorDemoSeeder extends Seeder
{
    public function run(): void
    {
        $username = trim((string) getenv('JUEZ_DEMO_USERNAME'));
        $email = trim((string) getenv('JUEZ_DEMO_EMAIL'));
        $password = (string) getenv('JUEZ_DEMO_PASSWORD');
        $nombre = trim((string) getenv('JUEZ_DEMO_NOMBRE')) ?: 'Juez Calificador Demo';

        if ($username === '' || $email === '' || $password === '') {
            throw new RuntimeException('Define JUEZ_DEMO_USERNAME, JUEZ_DEMO_EMAIL y JUEZ_DEMO_PASSWORD antes de ejecutar.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('JUEZ_DEMO_EMAIL no es un correo válido.');
        }
        if (strlen($password) < 8 || preg_match('/[A-Za-z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1) {
            throw new RuntimeException('JUEZ_DEMO_PASSWORD debe tener al menos 8 caracteres, una letra y un número.');
        }

        $rol = $this->db->table('roles')->where('nombre', 'juez_calificador')->get()->getRow();
        if ($rol === null) {
            throw new RuntimeException('El rol juez_calificador no existe. Ejecuta primero la migración COB-09.');
        }

        $porUsername = $this->db->table('users')->where('username', $username)->get()->getRow();
        $porEmail = $this->db->table('users')->where('email', $email)->get()->getRow();
        if ($porUsername !== null || $porEmail !== null) {
            if ($porUsername === null || $porEmail === null || (int) $porUsername->id !== (int) $porEmail->id) {
                throw new RuntimeException('El usuario o correo ya pertenece a otra cuenta; no se modificó ningún usuario.');
            }
            $asignado = $this->db->table('user_roles')
                ->where('user_id', $porUsername->id)
                ->where('role_id', $rol->id)
                ->countAllResults();
            if ($asignado === 0) {
                throw new RuntimeException('La cuenta ya existe sin el rol de juez; no se modificó automáticamente.');
            }
            return;
        }

        $this->db->transStart();
        $this->db->table('users')->insert([
            'username'        => $username,
            'email'           => $email,
            'password_hash'   => password_hash($password, PASSWORD_DEFAULT),
            'nombre_completo' => $nombre,
            'activo'          => 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        $userId = (int) $this->db->insertID();
        $this->db->table('user_roles')->insert([
            'user_id'    => $userId,
            'role_id'    => (int) $rol->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new RuntimeException('No fue posible crear la cuenta demo del juez.');
        }
    }
}