<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'id'          => 1,
                'nombre'      => 'administrador',
                'descripcion' => 'Acceso total al panel administrativo',
            ],
            [
                'id'          => 2,
                'nombre'      => 'operador_ventanilla',
                'descripcion' => 'Revisión y cobro de solicitudes',
            ],
            [
                'id'          => 3,
                'nombre'      => 'ciudadano',
                'descripcion' => 'Usuario público que solicita trámites',
            ],
        ];

        $this->db->table('roles')->insertBatch($data);

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('roles', 'id'), COALESCE((SELECT MAX(id) FROM roles), 1))");
        }
    }
}
