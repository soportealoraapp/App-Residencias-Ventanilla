<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Auditoría de Félix, hallazgo A1: reclamar una cuenta registrada en ventanilla
 * exige el código de activación impreso en el acuse, no solo la CURP.
 *
 * - users.activacion_hash: SHA-256 del código (nunca el código en claro).
 * - users.activacion_expira: vigencia del código.
 *
 * Equivale a supabase/migrations/0021_codigo_activacion_y_evidencias.sql.
 */
class CodigoActivacionCiudadano extends Migration
{
    public function up()
    {
        $campos = [];
        if (! $this->db->fieldExists('activacion_hash', 'users')) {
            $campos['activacion_hash'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true];
        }
        if (! $this->db->fieldExists('activacion_expira', 'users')) {
            $campos['activacion_expira'] = ['type' => 'DATETIME', 'null' => true];
        }
        if ($campos !== []) {
            $this->forge->addColumn('users', $campos);
        }

        $this->db->query('CREATE INDEX IF NOT EXISTS idx_users_activacion_hash ON users (activacion_hash)');
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS idx_users_activacion_hash');
        foreach (['activacion_hash', 'activacion_expira'] as $campo) {
            if ($this->db->fieldExists($campo, 'users')) {
                $this->forge->dropColumn('users', $campo);
            }
        }
    }
}
