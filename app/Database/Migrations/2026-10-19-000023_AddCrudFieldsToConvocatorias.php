<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Columnas que el CRUD de convocatorias necesita y no estaban en la tabla
 * original: un título legible para listarlas, el archivo de las bases
 * oficiales (PDF) y quién la publicó.
 *
 * Equivale al bloque 1 de supabase/migrations/0008_completar_esquema_pendiente.sql.
 * Las dos rutas deben mantenerse sincronizadas: el panel se prueba contra SQLite
 * construido con migraciones, pero produce contra una base ya existente.
 */
class AddCrudFieldsToConvocatorias extends Migration
{
    public function up()
    {
        $this->forge->addColumn('convocatorias', [
            'titulo' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false, 'default' => ''],
            'bases_archivo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bases_nombre' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'bases_mime' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'bases_tamano' => ['type' => 'INT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'null' => true],
        ]);
    }

    public function down()
    {
        if ($this->db->DBDriver === 'SQLite3') {
            // SQLite no soporta DROP COLUMN multi-columna de forma fiable en esta versión.
            return;
        }

        $this->forge->dropColumn('convocatorias', [
            'titulo', 'bases_archivo', 'bases_nombre', 'bases_mime', 'bases_tamano', 'created_by',
        ]);
    }
}
