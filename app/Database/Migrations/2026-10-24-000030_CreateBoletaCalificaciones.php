<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** COB-10: calificación judicial de boletas y protección de cobros vigentes. */
class CreateBoletaCalificaciones extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';

        $this->forge->addField([
            'id' => ['type' => $idType, 'auto_increment' => true],
            'boleta_id' => ['type' => 'BIGINT', 'null' => false],
            'juez_id' => ['type' => 'BIGINT', 'null' => true],
            'cobro_id' => ['type' => 'BIGINT', 'null' => true],
            'nombre_infractor' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'valor_uma' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'total_uma' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'monto_base' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
            'calificada_en' => ['type' => 'TIMESTAMP', 'null' => false],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('boleta_id');
        $this->forge->addUniqueKey('cobro_id');
        $this->forge->addKey('juez_id');
        $this->forge->addKey('calificada_en');
        $this->forge->addForeignKey('boleta_id', 'boletas_infracciones', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('juez_id', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('cobro_id', 'cobros', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('boleta_calificaciones', true);

        $this->forge->addField([
            'id' => ['type' => $idType, 'auto_increment' => true],
            'calificacion_id' => ['type' => 'BIGINT', 'null' => false],
            'orden' => ['type' => 'SMALLINT', 'null' => false],
            'falta_id_origen' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'fundamento_legal' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'descripcion' => ['type' => 'TEXT', 'null' => true],
            'categoria' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'monto_min_uma' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'monto_max_uma' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'uma_asignada' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => false],
            'subtotal_pesos' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['calificacion_id', 'orden']);
        $this->forge->addKey('calificacion_id');
        $this->forge->addForeignKey('calificacion_id', 'boleta_calificaciones', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('boleta_calificacion_detalles', true);

        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("ALTER TABLE boleta_calificaciones ADD CONSTRAINT ck_boleta_calificaciones_importes CHECK (btrim(nombre_infractor) <> '' AND valor_uma > 0 AND total_uma >= 0 AND monto_base > 0)");
            $this->db->query('ALTER TABLE boleta_calificacion_detalles ADD CONSTRAINT ck_boleta_calificacion_rango CHECK (orden > 0 AND monto_min_uma >= 0 AND monto_max_uma >= monto_min_uma AND uma_asignada BETWEEN monto_min_uma AND monto_max_uma AND subtotal_pesos >= 0)');
        }
        $this->db->query("CREATE UNIQUE INDEX IF NOT EXISTS uq_cobros_boleta_vigente ON cobros (boleta_id) WHERE boleta_id IS NOT NULL AND estatus IN ('pendiente', 'aprobado')");
    }

    public function down()
    {
        $this->db->query('DROP INDEX IF EXISTS uq_cobros_boleta_vigente');
        $this->forge->dropTable('boleta_calificacion_detalles', true);
        $this->forge->dropTable('boleta_calificaciones', true);
    }
}
