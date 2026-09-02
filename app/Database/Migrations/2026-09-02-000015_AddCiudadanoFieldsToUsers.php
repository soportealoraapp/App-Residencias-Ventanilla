<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCiudadanoFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'curp' => [
                'type'       => 'VARCHAR',
                'constraint' => 18,
                'null'       => true,
            ],
            'apellido' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
            ],
            'estado' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'ciudad' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['curp', 'apellido', 'estado', 'ciudad']);
    }
}
