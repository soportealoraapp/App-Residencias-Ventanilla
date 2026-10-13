<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AgentesTransitoSeeder extends Seeder
{
    public function run(): void
    {
        $hash = password_hash('transito2026', PASSWORD_BCRYPT);

        $agentes = [
            [
                'placa'           => 'AGT-204',
                'nombre_completo' => 'Oficial Carlos Mendoza Ruiz',
                'password_hash'   => $hash,
                'rol'             => 'operativo',
                'sector'          => 'Sector Centro',
                'activo'          => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'placa'           => 'AGT-107',
                'nombre_completo' => 'Oficial María Hernández López',
                'password_hash'   => $hash,
                'rol'             => 'operativo',
                'sector'          => 'Sector Norte',
                'activo'          => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'placa'           => 'JEF-001',
                'nombre_completo' => 'Coordinador Javier Reyes Cruz',
                'password_hash'   => $hash,
                'rol'             => 'coordinador',
                'sector'          => 'Dirección de Movilidad',
                'activo'          => 1,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($agentes as $agente) {
            $existe = $this->db->table('agentes_transito')
                ->where('placa', $agente['placa'])
                ->get()
                ->getFirstRow();

            if (! $existe) {
                $this->db->table('agentes_transito')->insert($agente);
            }
        }
    }
}
