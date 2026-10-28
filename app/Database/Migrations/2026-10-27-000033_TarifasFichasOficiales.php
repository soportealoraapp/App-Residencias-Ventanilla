<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tarifas de las fichas oficiales del Registro de Trámites y Servicios de
 * Uriangato (actualización 29-ene-26).
 *
 * - UR-TT-T-03 no tiene costo ($0.00).
 * - UR-TT-T-07: $191 / $1,151 / $5,752 / $11,503 por 1 camión (día, mes,
 *   semestre, año) y $573 / $5,732 / $28,662 / $57,325 para empresas
 *   establecidas, hasta 15 camiones.
 * - El resto ya coincidía; deja de marcarse como tarifa provisional.
 *
 * No borra tarifas: la anterior se cierra (vigente_hasta = ayer) y se abre la
 * nueva desde hoy, para conservar el historial del tarifario.
 *
 * Espejo para Supabase: supabase/migrations/0018_tarifas_fichas_oficiales.sql
 */
class TarifasFichasOficiales extends Migration
{
    private const TARIFAS = [
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

    public function up()
    {
        $hoy   = date('Y-m-d');
        $ayer  = date('Y-m-d', strtotime('-1 day'));
        $ahora = date('Y-m-d H:i:s');

        foreach (self::TARIFAS as [$tramite, $criterio, $monto, $descripcion]) {
            $descripcion = 'Ficha oficial ' . $tramite . ' (29-ene-26): ' . $descripcion;
            $abiertas = $this->db->table('tarifas')
                ->where('tramite', $tramite)->where('criterio', $criterio)
                ->groupStart()->where('vigente_hasta', null)->orWhere('vigente_hasta >=', $hoy)->groupEnd()
                ->get()->getResult();

            $igual = null;
            foreach ($abiertas as $t) {
                if (abs((float) $t->monto - $monto) < 0.005) {
                    $igual = $t;
                    continue;
                }
                $this->db->table('tarifas')->where('id', $t->id)->update(['vigente_hasta' => $ayer, 'updated_at' => $ahora]);
            }

            if ($igual !== null) {
                $this->db->table('tarifas')->where('id', $igual->id)
                    ->update(['placeholder_oficial' => 0, 'descripcion' => $descripcion, 'updated_at' => $ahora]);
                continue;
            }

            $this->db->table('tarifas')->insert([
                'tramite'             => $tramite,
                'criterio'            => $criterio,
                'monto'               => $monto,
                'vigente_desde'       => $hoy,
                'placeholder_oficial' => 0,
                'descripcion'         => $descripcion,
                'created_at'          => $ahora,
                'updated_at'          => $ahora,
            ]);
        }
    }

    public function down()
    {
        // El tarifario conserva su historial: no se revierte.
    }
}
