<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHistorialEstatusTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGSERIAL',
                'auto_increment' => true,
            ],
            'solicitud_id' => [
                'type'     => 'BIGINT',
                'null'     => false,
            ],
            'estatus_anterior' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'estatus_nuevo' => [
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
            'comentario' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('solicitud_id');
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('historial_estatus', true);
    }

    public function down()
    {
        $this->forge->dropTable('historial_estatus', true);
    }
}
