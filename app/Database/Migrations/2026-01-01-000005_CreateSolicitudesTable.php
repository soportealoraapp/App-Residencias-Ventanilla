<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSolicitudesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'folio' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'unique'     => true,
                'null'       => false,
            ],
            'tramite' => [
                'type'    => 'ENUM',
                'values'  => ['UR-TT-T-07', 'UR-TT-T-06'],
                'null'    => false,
            ],
            'ciudadano_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
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
                'type' => 'DATETIME',
                'null' => false,
            ],
            'fecha_resolucion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fecha_pago' => [
                'type' => 'DATETIME',
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
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
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
