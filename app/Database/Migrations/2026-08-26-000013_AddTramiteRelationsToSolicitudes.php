<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTramiteRelationsToSolicitudes extends Migration
{
    public function up()
    {
        $this->forge->addColumn('solicitudes', [
            'concesion_id' => ['type' => 'BIGINT', 'null' => true],
            'convocatoria_id' => ['type' => 'BIGINT', 'null' => true],
        ]);

        // SQLite no aplica bien FK con ALTER TABLE; en Postgre/MySQL si.
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->forge->addForeignKey('concesion_id', 'concesiones', 'id', 'CASCADE', 'SET NULL');
            $this->forge->addForeignKey('convocatoria_id', 'convocatorias', 'id', 'CASCADE', 'SET NULL');
            $this->forge->processIndexes('solicitudes');
        }
    }

    public function down()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->dropForeignKey('solicitudes', 'solicitudes_concesion_id_foreign');
        $this->forge->dropForeignKey('solicitudes', 'solicitudes_convocatoria_id_foreign');
        $this->forge->dropColumn('solicitudes', ['concesion_id', 'convocatoria_id']);
    }
}
