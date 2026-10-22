<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Mueve la secuencia de id de cada tabla a MAX(id) + 1 (solo Postgres).
 *
 * Al sincronizar datos hacia Supabase con sus id explícitos, las secuencias no
 * avanzan y el siguiente INSERT falla por llave duplicada: desde el panel parece
 * que "no se puede crear" un agente, una solicitud o un usuario.
 *
 * No modifica datos. Equivale a supabase/migrations/0011_resincronizar_secuencias.sql,
 * que conviene volver a ejecutar después de cada sincronización de datos.
 */
class ResyncSecuenciasPostgres extends Migration
{
    public function up()
    {
        if ($this->db->DBDriver !== 'Postgre') {
            return;
        }

        $this->db->query(<<<'SQL'
            DO $$
            DECLARE
                r   record;
                seq text;
            BEGIN
                FOR r IN
                    SELECT c.table_name, c.column_name
                    FROM information_schema.columns c
                    JOIN information_schema.tables t
                      ON t.table_schema = c.table_schema AND t.table_name = c.table_name
                    WHERE c.table_schema = current_schema() AND t.table_type = 'BASE TABLE'
                LOOP
                    seq := pg_get_serial_sequence(format('%I.%I', current_schema(), r.table_name), r.column_name);
                    IF seq IS NOT NULL THEN
                        EXECUTE format(
                            'SELECT setval(%L, COALESCE((SELECT MAX(%I) FROM %I.%I), 0) + 1, false)',
                            seq, r.column_name, current_schema(), r.table_name
                        );
                    END IF;
                END LOOP;
            END $$;
            SQL);
    }

    public function down()
    {
        // Nada que revertir: solo se ajustaron contadores.
    }
}
