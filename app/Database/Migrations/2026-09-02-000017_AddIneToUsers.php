<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIneToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'ine_frente' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'ciudad',
            ],
            'ine_reverso' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'ine_frente',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['ine_frente', 'ine_reverso']);
    }
}
