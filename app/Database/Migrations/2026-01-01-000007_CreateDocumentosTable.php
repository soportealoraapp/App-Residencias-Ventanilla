<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDocumentosTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => [
                'type'           => $idType,
                'auto_increment' => true,
            ],
            'solicitud_id' => [
                'type'     => 'BIGINT',
                'null'     => false,
            ],
            'tipo_documento' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'nombre_original' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'ruta_interna' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'tamano_bytes' => [
                'type' => 'BIGINT',
                'null' => true,
            ],
            'hash_sha256' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'usuario_id' => [
                'type'     => 'BIGINT',
                'null'     => false,
            ],
            'fecha_carga' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'validado' => [
                'type'    => 'SMALLINT',
                'default' => 0,
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
        $this->forge->addKey('solicitud_id');
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('documentos', true);
    }

    public function down()
    {
        $this->forge->dropTable('documentos', true);
    }
}
