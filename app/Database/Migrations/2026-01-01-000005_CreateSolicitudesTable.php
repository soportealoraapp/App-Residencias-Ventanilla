<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudesTable extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => [
                'type'           => $idType,
                'auto_increment' => true,
            ],
            'folio' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'tramite' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'ciudadano_id' => [
                'type'     => 'BIGINT',
                'null'     => false,
            ],
            'estatus' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'monto' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'null'       => true,
            ],
            'fecha_solicitud' => [
                'type' => 'TIMESTAMP',
                'null' => false,
            ],
            'fecha_resolucion' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'fecha_pago' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'fecha_vigencia_inicio' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'fecha_vigencia_fin' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'comentario_rechazo' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('folio', false, true);
        $this->forge->addKey('ciudadano_id');
        $this->forge->addKey('tramite');
        $this->forge->addKey('estatus');
        $this->forge->addForeignKey('ciudadano_id', 'users', 'id', 'RESTRICT', 'RESTRICT');

        $this->forge->createTable('solicitudes', true);
    }

    public function down()
    {
        $this->forge->dropTable('solicitudes', true);
    }
}
