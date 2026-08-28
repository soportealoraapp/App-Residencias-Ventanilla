<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TarifasSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'tramite'          => 'UR-TT-T-04',
                'criterio'         => 'base',
                'monto'            => 156.94,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'Tarifa provisional del prototipo: Permiso Eventual de Transporte',
            ],
            [
                'tramite'          => 'UR-TT-T-05',
                'criterio'         => 'base',
                'monto'            => 287.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'Tarifa provisional del prototipo: Cierre de Calle',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'particular_dia',
                'monto'            => 50.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa particular por día',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'particular_mes',
                'monto'            => 400.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa particular por mes',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'particular_semestre',
                'monto'            => 2200.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa particular por semestre',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'particular_anio',
                'monto'            => 4000.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa particular por año',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'empresa_dia',
                'monto'            => 120.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa empresa por día',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'empresa_mes',
                'monto'            => 1000.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa empresa por mes',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'empresa_semestre',
                'monto'            => 5500.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa empresa por semestre',
            ],
            [
                'tramite'          => 'UR-TT-T-07',
                'criterio'         => 'empresa_anio',
                'monto'            => 10000.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor no verificado - Tarifa empresa por año',
            ],
            [
                'tramite'          => 'UR-TT-T-06',
                'criterio'         => 'cesion_concesion_base',
                'monto'            => 9055.20,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'TODO: valor dudoso, revisar con Movilidad',
            ],
            [
                'tramite'          => 'UR-TT-T-01',
                'criterio'         => 'base',
                'monto'            => 9055.20,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'Valor de prueba editable: revisar con Movilidad',
            ],
            [
                'tramite'          => 'UR-TT-T-02',
                'criterio'         => 'base',
                'monto'            => 64.90,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'Valor de prueba editable: revisar con Movilidad',
            ],
            [
                'tramite'          => 'UR-TT-T-03',
                'criterio'         => 'base',
                'monto'            => 50.00,
                'vigente_desde'    => date('Y-m-d'),
                'placeholder_oficial' => 1,
                'descripcion'      => 'Valor de prueba editable: revisar con Movilidad',
            ],
        ];

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
