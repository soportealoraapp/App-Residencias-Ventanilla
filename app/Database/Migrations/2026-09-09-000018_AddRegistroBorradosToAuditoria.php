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
        $this->forge->dropColumn('auditoria', 'registro_borrados');
    }
}
