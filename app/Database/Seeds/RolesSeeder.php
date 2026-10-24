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

        // Conserva sin cambios el comportamiento histórico de los tres roles
        // iniciales: una coincidencia por id o nombre impide otra inserción.
        foreach ($data as $row) {
            $exists = $this->db->table('roles')->where('id', $row['id'])->orWhere('nombre', $row['nombre'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('roles')->insert($row);
            }
        }

        // COB-09 no reserva un id: la secuencia asigna el disponible y el nombre
        // único hace que ejecutar nuevamente el seeder sea seguro.
        $juezExiste = $this->db->table('roles')->where('nombre', 'juez_calificador')->countAllResults();
        if ($juezExiste === 0) {
            $this->db->table('roles')->insert([
                'nombre'      => 'juez_calificador',
                'descripcion' => 'Consulta y calificación de boletas de infracción',
            ]);
        }

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('roles', 'id'), COALESCE((SELECT MAX(id) FROM roles), 1))");
        }
    }
}
