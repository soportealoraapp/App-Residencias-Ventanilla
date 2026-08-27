<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Estructura provisional, pendiente de sustituir por el padron oficial
 * cuando este disponible. Agrega tipo_persona (unica columna requerida
 * que no existia en concesiones).
 */
class AddTipoPersonaToConcesiones extends Migration
{
    public function up()
    {
        $this->forge->addColumn('concesiones', [
            'tipo_persona' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->dropColumn('concesiones', 'tipo_persona');
    }
}
