<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTarifasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGSERIAL',
                'auto_increment' => true,
            ],
            'tramite' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'criterio' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'monto' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => false,
            ],
            'vigente_desde' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'vigente_hasta' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'descripcion' => [
                'type'       => 'VARCHAR',
                'constraint' => 250,
                'null'       => true,
            ],
            'placeholder_oficial' => [
                'type'    => 'SMALLINT',
                'default' => 1,
                'null'    => false,
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
        $this->forge->addKey(['tramite', 'criterio', 'vigente_desde']);

        $this->forge->createTable('tarifas', true);
    }

    public function down()
    {
        $this->forge->dropTable('tarifas', true);
    }
}
