<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConvocatoriasTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => ['type' => $idType, 'auto_increment' => true],
            'fecha_publicacion' => ['type' => 'DATE', 'null' => false],
            'periodo_registro_inicio' => ['type' => 'DATE', 'null' => false],
            'periodo_registro_fin' => ['type' => 'DATE', 'null' => false],
            'bases' => ['type' => 'TEXT', 'null' => true],
            'estatus' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false, 'default' => 'Vigente'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('estatus');
        $this->forge->createTable('convocatorias', true);
    }

    public function down()
    {
        $this->forge->dropTable('convocatorias', true);
    }
}
