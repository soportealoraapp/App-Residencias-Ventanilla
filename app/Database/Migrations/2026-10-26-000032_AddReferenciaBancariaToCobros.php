<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * COB-04: referencia bancaria de cada cobro y datos del comprobante de BanBajío.
 *
 * - referencia_bancaria: la que se imprime en la hoja de pago para pagar en el banco.
 * - fecha_pago_banco / importe_pagado: lo que dice el comprobante al registrarlo.
 *   (El folio del comprobante ya se guarda en referencia_pago.)
 *
 * Asigna la referencia a los cobros que ya existían.
 * Espejo para Supabase: supabase/migrations/0017_referencia_bancaria.sql
 */
class AddReferenciaBancariaToCobros extends Migration
{
    public function up()
    {
        $this->forge->addColumn('cobros', [
            'referencia_bancaria' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'fecha_pago_banco'    => ['type' => 'DATE', 'null' => true],
            'importe_pagado'      => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
        ]);
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS ' . $this->db->prefixTable('cobros_referencia_bancaria') . ' ON ' . $this->db->prefixTable('cobros') . ' (referencia_bancaria)');

        $referencias = service('referenciaBancaria');
        foreach ($this->db->table('cobros')->select('id, origen')->where('referencia_bancaria', null)->get()->getResult() as $cobro) {
            $this->db->table('cobros')->where('id', $cobro->id)
                ->update(['referencia_bancaria' => $referencias->generar((string) $cobro->origen, (int) $cobro->id)]);
        }
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS ' . $this->db->prefixTable('cobros_referencia_bancaria'));
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }
        $this->forge->dropColumn('cobros', ['referencia_bancaria', 'fecha_pago_banco', 'importe_pagado']);
    }
}
