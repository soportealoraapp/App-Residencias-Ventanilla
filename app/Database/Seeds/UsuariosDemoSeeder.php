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

        foreach ($usuarios as $row) {
            $exists = $this->db->table('users')->where('id', $row['id'])->orWhere('email', $row['email'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('users')->insert($row);
            }
        }

        $userRoles = [
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 2, 'role_id' => 2],
            ['user_id' => 3, 'role_id' => 3],
        ];

        foreach ($userRoles as $row) {
            $exists = $this->db->table('user_roles')->where('user_id', $row['user_id'])->where('role_id', $row['role_id'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('user_roles')->insert($row);
            }
        }

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1))");
            $this->db->query("SELECT setval(pg_get_serial_sequence('user_roles', 'id'), COALESCE((SELECT MAX(id) FROM user_roles), 1))");
        }

        $this->juezDemo($passwordHash);
    }

    /**
     * Cuenta juez1 para el acceso rápido del login. El rol no tiene id fijo
     * (COB-09), así que se busca por nombre; sin el rol no se crea la cuenta.
     */
    private function juezDemo(string $passwordHash): void
    {
        $rol = $this->db->table('roles')->where('nombre', 'juez_calificador')->get()->getRow();
        if ($rol === null) {
            return;
        }

        $usuario = $this->db->table('users')->where('username', 'juez1')->get()->getRow();
        if ($usuario === null) {
            if ($this->db->table('users')->where('email', 'juez1@uriangato.gob.mx')->countAllResults() > 0) {
                return;
            }
            $this->db->table('users')->insert([
                'username'        => 'juez1',
                'email'           => 'juez1@uriangato.gob.mx',
                'nombre_completo' => 'Juez Calificador Demo',
                'password_hash'   => $passwordHash,
                'activo'          => 1,
            ]);
            $userId = (int) $this->db->insertID();
        } else {
            $userId = (int) $usuario->id;
        }

        if ($this->db->table('user_roles')->where('user_id', $userId)->where('role_id', $rol->id)->countAllResults() === 0) {
            $this->db->table('user_roles')->insert(['user_id' => $userId, 'role_id' => (int) $rol->id]);
        }
    }
}
