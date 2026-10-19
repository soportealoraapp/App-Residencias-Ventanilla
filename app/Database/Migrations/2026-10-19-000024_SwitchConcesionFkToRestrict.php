<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Convierte la FK de solicitudes.concesion_id de ON DELETE CASCADE a RESTRICT.
 *
 * Con CASCADE, borrar una concesión del padrón arrastraba en silencio los
 * expedientes UR-02/03/06 que la referenciaban: el ciudadano perdía su historial
 * sin ningún aviso. El panel ya lo bloquea y esta migración lo prohíbe también en
 * la base de datos, que es la única defensa si alguien opera desde SQL.
 *
 * Equivale a supabase/migrations/0009_concesion_id_sin_cascade.sql: hay que
 * mantener las dos rutas sincronizadas.
 */
class SwitchConcesionFkToRestrict extends Migration
{
    public function up()
    {
        // La migración 2026-08-26-000013 nunca creó estas FK en SQLite, así que
        // aquí tampoco hay nada que reconvertir.
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->dropForeignKey('solicitudes', 'solicitudes_concesion_id_foreign');
        $this->forge->addForeignKey('concesion_id', 'concesiones', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->processIndexes('solicitudes');
    }

    public function down()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            return;
        }

        $this->forge->dropForeignKey('solicitudes', 'solicitudes_concesion_id_foreign');
        $this->forge->addForeignKey('concesion_id', 'concesiones', 'id', 'CASCADE', 'SET NULL');
        $this->forge->processIndexes('solicitudes');
    }
}
