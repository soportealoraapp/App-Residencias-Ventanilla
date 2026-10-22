<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Crea las tarifas base de UR-TT-T-01 a UR-TT-T-05 cuando no existen.
 *
 * El esquema inicial de Supabase (0001_init_schema.sql) solo sembró T-06 y T-07,
 * y las de T-01 a T-05 venían únicamente del TarifasSeeder local. En la base
 * hosteada esos trámites no tenían tarifa vigente y el registro presencial dejaba
 * deshabilitado el botón "Registrar solicitud".
 *
 * Solo inserta lo que falta: si el municipio ya capturó una tarifa para el
 * trámite (vigente o no), no la toca. Los montos son los provisionales del
 * prototipo y se corrigen desde Panel → Tarifario.
 *
 * Equivale a supabase/migrations/0010_tarifas_base_faltantes.sql: hay que
 * mantener las dos rutas sincronizadas.
 */
class SeedTarifasBaseFaltantes extends Migration
{
    private const TARIFAS = [
        ['UR-TT-T-01', 9055.20, 'Tarifa provisional del prototipo: Concesión de Transporte'],
        ['UR-TT-T-02', 64.90, 'Tarifa provisional del prototipo: Constancia de Despintado'],
        ['UR-TT-T-03', 50.00, 'Tarifa provisional del prototipo: Orden de Plaqueo'],
        ['UR-TT-T-04', 156.94, 'Tarifa provisional del prototipo: Permiso Eventual de Transporte'],
        ['UR-TT-T-05', 287.00, 'Tarifa provisional del prototipo: Cierre de Calle'],
    ];

    public function up()
    {
        // Si los datos se importaron con ids explícitos, la secuencia puede ir
        // atrasada y el INSERT chocaría con un id existente.
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("SELECT setval(pg_get_serial_sequence('tarifas', 'id'), COALESCE((SELECT MAX(id) FROM tarifas), 0) + 1, false)");
        }

        $ahora = date('Y-m-d H:i:s');
        foreach (self::TARIFAS as [$tramite, $monto, $descripcion]) {
            $existe = $this->db->table('tarifas')
                ->where('tramite', $tramite)
                ->where('criterio', 'base')
                ->countAllResults();
            if ($existe > 0) {
                continue;
            }
            $this->db->table('tarifas')->insert([
                'tramite'             => $tramite,
                'criterio'            => 'base',
                'monto'               => $monto,
                'vigente_desde'       => '2026-01-01',
                'placeholder_oficial' => 1,
                'descripcion'         => $descripcion,
                'created_at'          => $ahora,
                'updated_at'          => $ahora,
            ]);
        }
    }

    public function down()
    {
        // No se borran tarifas: pudieron editarse desde el panel después de sembrarlas.
    }
}
