<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ConvocatoriasSeeder extends Seeder
{
    public function run(): void
    {
        $exists = $this->db->table('convocatorias')->countAllResults();
        if ($exists === 0) {
            $this->db->table('convocatorias')->insert([
                'fecha_publicacion' => date('Y-m-d', strtotime('-7 days')),
                'periodo_registro_inicio' => date('Y-m-d', strtotime('-2 days')),
                'periodo_registro_fin' => date('Y-m-d', strtotime('+30 days')),
                'bases' => 'Convocatoria ficticia para pruebas locales de UR-01.',
                'estatus' => 'Vigente',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
