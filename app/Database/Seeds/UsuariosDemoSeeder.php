<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = password_hash('12345678', PASSWORD_DEFAULT);

        $usuarios = [
            [
                'id'       => 1,
                'username' => 'admin',
                'email'    => 'admin@uriangato.gob.mx',
                'nombre_completo' => 'Administrador del Sistema',
                'rfc'      => null,
                'telefono' => null,
                'domicilio' => null,
                'password_hash' => $passwordHash,
            ],
            [
                'id'       => 2,
                'username' => 'operador1',
                'email'    => 'operador1@uriangato.gob.mx',
                'nombre_completo' => 'Operador Ventanilla Uno',
                'rfc'      => null,
                'telefono' => null,
                'domicilio' => null,
                'password_hash' => $passwordHash,
            ],
            [
                'id'       => 3,
                'username' => 'ciudadano1',
                'email'    => 'ciudadano@example.com',
                'nombre_completo' => 'Juan Pérez García',
                'rfc'      => 'PEGJ800101XXX',
                'telefono' => '4711234567',
                'domicilio' => 'Calle Madero #123, Uriangato, Gto.',
                'password_hash' => $passwordHash,
            ],
        ];

        $this->db->table('users')->insertBatch($usuarios);

        $userRoles = [
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 2, 'role_id' => 2],
            ['user_id' => 3, 'role_id' => 3],
        ];

        $this->db->table('user_roles')->insertBatch($userRoles);

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1))");
            $this->db->query("SELECT setval(pg_get_serial_sequence('user_roles', 'id'), COALESCE((SELECT MAX(id) FROM user_roles), 1))");
        }
    }
}
