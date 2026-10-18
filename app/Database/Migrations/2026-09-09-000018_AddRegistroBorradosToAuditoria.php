<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegistroBorradosToAuditoria extends Migration
{
    public function up()
    {
        $fields = [
            'registro_borrados' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'detalles',
            ],
        ];

        $this->forge->addColumn('auditoria', $fields);
    }

    public function down()
    {
        // SQLite < 3.35 no soporta ALTER TABLE DROP COLUMN; en tests in-memory
        // el regress completo de todas las migraciones funciona sin este paso.
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }
        $this->forge->dropColumn('auditoria', 'registro_borrados');
    }
}
