<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditoriaTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => [
                'type'           => $idType,
                'auto_increment' => true,
            ],
            'entidad' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'entidad_id' => [
                'type'     => 'BIGINT',
                'null'     => true,
            ],
            'accion' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'usuario_id' => [
                'type'     => 'BIGINT',
                'null'     => true,
            ],
            'fecha' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'detalle' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['entidad', 'entidad_id']);
        $this->forge->addKey('fecha');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('auditoria', true);
    }

    public function down()
    {
        $this->forge->dropTable('auditoria', true);
    }
}
