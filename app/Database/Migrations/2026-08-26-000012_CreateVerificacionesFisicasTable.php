<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVerificacionesFisicasTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => ['type' => $idType, 'auto_increment' => true],
            'solicitud_id' => ['type' => 'BIGINT', 'null' => false],
            'fecha_cita' => ['type' => 'TIMESTAMP', 'null' => false],
            'resultado' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'observaciones' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('solicitud_id');
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('verificaciones_fisicas', true);
    }

    public function down()
    {
        $this->forge->dropTable('verificaciones_fisicas', true);
    }
}
