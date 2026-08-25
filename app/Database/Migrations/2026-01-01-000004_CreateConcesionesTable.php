<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConcesionesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGSERIAL',
                'auto_increment' => true,
            ],
            'numero_titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'unique'     => true,
                'null'       => false,
            ],
            'titular_actual' => [
                'type'       => 'VARCHAR',
                'constraint' => 180,
                'null'       => false,
            ],
            'vehiculo_placas' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'vehiculo_num_serie' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'vigencia_inicio' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'vigencia_fin' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'estatus' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'default'    => 'vigente',
                'null'       => false,
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

        $this->forge->createTable('concesiones', true);
    }

    public function down()
    {
        $this->forge->dropTable('concesiones', true);
    }
}
