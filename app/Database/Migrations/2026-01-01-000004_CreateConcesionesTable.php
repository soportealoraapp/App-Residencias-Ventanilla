<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Estructura provisional, pendiente de sustituir por el padron oficial
 * cuando este disponible. Columnas actuales: id, numero_titulo,
 * titular_actual, vehiculo_placas, vehiculo_num_serie, vigencia_inicio,
 * vigencia_fin, estatus, created_at, updated_at.
 * Ya cubren vigencia_inicio, vigencia_fin y estatus. Falta tipo_persona
 * (se agrega en migracion posterior, sin reemplazar este catalogo stub).
 */
class CreateConcesionesTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => [
                'type'           => $idType,
                'auto_increment' => true,
            ],
            'numero_titulo' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
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
        // Clave nombrada explicita en vez de 'unique'=>true: SQLite crea un
        // indice reservado sqlite_autoindex_* con el inline que rompe los
        // ALTER TABLE (dropColumn/addColumn) de CI4 al reconstruir la tabla.
        $this->forge->addUniqueKey('numero_titulo', 'uq_concesiones_numero_titulo');

        $this->forge->createTable('concesiones', true);
    }

    public function down()
    {
        $this->forge->dropTable('concesiones', true);
    }
}
