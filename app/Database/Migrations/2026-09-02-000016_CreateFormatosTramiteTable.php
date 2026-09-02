<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFormatosTramiteTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => [
                'type'           => $idType,
                'auto_increment' => true,
            ],
            'tramite' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'nombre' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'nombre_archivo' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'ruta_interna' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'tamano_bytes' => [
                'type'   => 'BIGINT',
                'null'   => true,
            ],
            'usuario_id' => [
                'type'   => 'BIGINT',
                'null'   => false,
            ],
            'activo' => [
                'type'    => 'SMALLINT',
                'default' => 1,
                'null'    => false,
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
        $this->forge->addUniqueKey('tramite');
        $this->forge->createTable('formatos_tramite', true);
    }

    public function down()
    {
        $this->forge->dropTable('formatos_tramite', true);
    }
}
