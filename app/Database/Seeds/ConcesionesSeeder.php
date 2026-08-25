<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConcesionesSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'numero_titulo'      => 'CONC-URI-2024-0001',
                'titular_actual'     => 'María González López',
                'vehiculo_placas'    => 'GTO-123-45',
                'vehiculo_num_serie' => '3VWSK7AN1RM000001',
                'vigencia_inicio'    => '2024-01-15',
                'vigencia_fin'       => '2029-01-15',
                'estatus'            => 'vigente',
            ],
            [
                'numero_titulo'      => 'CONC-URI-2024-0002',
                'titular_actual'     => 'José Martínez Ruiz',
                'vehiculo_placas'    => 'GTO-678-90',
                'vehiculo_num_serie' => '3VWSK7AN1RM000002',
                'vigencia_inicio'    => '2023-06-01',
                'vigencia_fin'       => '2028-06-01',
                'estatus'            => 'vigente',
            ],
            [
                'numero_titulo'      => 'CONC-URI-2022-0099',
                'titular_actual'     => 'Carlos Rodríguez Hernández',
                'vehiculo_placas'    => 'GTO-000-99',
                'vehiculo_num_serie' => '3VWSK7AN1RM000099',
                'vigencia_inicio'    => '2022-03-10',
                'vigencia_fin'       => '2025-03-10',
                'estatus'            => 'vencida',
            ],
        ];

        $this->db->table('concesiones')->insertBatch($data);

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('concesiones', 'id'), COALESCE((SELECT MAX(id) FROM concesiones), 1))");
        }
    }
}
