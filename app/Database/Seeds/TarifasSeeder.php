<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TarifasSeeder extends Seeder
{
    public function run(): void
    {
        // Montos de las fichas oficiales del Registro de Trámites y Servicios de
        // Uriangato (actualización 29-ene-26). T-07 particular es por 1 camión;
        // T-07 empresa es cuota fija para empresas establecidas, hasta 15 camiones.
        $tarifas = [
            ['UR-TT-T-01', 'base', 9055.20, 'Concesión de transporte público de ruta fija'],
            ['UR-TT-T-02', 'base', 64.90, 'Constancia de despintado'],
            ['UR-TT-T-03', 'base', 0.00, 'Orden de plaqueo para cambio de unidad (sin costo)'],
            ['UR-TT-T-04', 'base', 156.94, 'Permiso eventual de transporte'],
            ['UR-TT-T-05', 'base', 287.00, 'Permiso para cierre parcial o total de calle'],
            ['UR-TT-T-06', 'cesion_concesion_base', 9055.20, 'Cesión de concesión'],
            ['UR-TT-T-07', 'particular_dia', 191.00, 'Carga y descarga: por día y por 1 camión'],
            ['UR-TT-T-07', 'particular_mes', 1151.00, 'Carga y descarga: por mes y por 1 camión'],
            ['UR-TT-T-07', 'particular_semestre', 5752.00, 'Carga y descarga: por semestre y por 1 camión'],
            ['UR-TT-T-07', 'particular_anio', 11503.00, 'Carga y descarga: por año y por 1 camión'],
            ['UR-TT-T-07', 'empresa_dia', 573.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por día'],
            ['UR-TT-T-07', 'empresa_mes', 5732.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por mes'],
            ['UR-TT-T-07', 'empresa_semestre', 28662.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por semestre'],
            ['UR-TT-T-07', 'empresa_anio', 57325.00, 'Carga y descarga: empresa establecida, hasta 15 camiones por año'],
        ];
        $data = array_map(static fn (array $t) => [
            'tramite'             => $t[0],
            'criterio'            => $t[1],
            'monto'               => $t[2],
            'vigente_desde'       => date('Y-m-d'),
            'placeholder_oficial' => 0,
            'descripcion'         => 'Ficha oficial ' . $t[0] . ' (29-ene-26): ' . $t[3],
        ], $tarifas);

        foreach ($data as $row) {
            $exists = $this->db->table('tarifas')
                ->where('tramite', $row['tramite'])
                ->where('criterio', $row['criterio'])
                ->countAllResults();
            if ($exists === 0) {
                $this->db->table('tarifas')->insert($row);
            }
        }

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('tarifas', 'id'), COALESCE((SELECT MAX(id) FROM tarifas), 1))");
        }
    }
}
