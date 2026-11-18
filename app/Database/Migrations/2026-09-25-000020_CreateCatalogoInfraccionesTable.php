<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCatalogoInfraccionesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fundamento_legal' => ['type' => 'VARCHAR', 'constraint' => 60],
            'descripcion' => ['type' => 'TEXT'],
            // ENUM es de MySQL: en PostgreSQL rompía `php spark migrate` en una
            // base limpia. Texto con CHECK, igual que supabase/migrations/0008.
            'categoria_actor' => ['type' => 'VARCHAR', 'constraint' => 30],
            'monto_min_uma' => ['type' => 'DECIMAL', 'constraint' => '8,2'],
            'monto_max_uma' => ['type' => 'DECIMAL', 'constraint' => '8,2'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('fundamento_legal', 'uq_catalogo_infracciones_fundamento');
        $this->forge->createTable('catalogo_infracciones');
        if ($this->db->DBDriver === 'Postgre') {
            $this->db->query("ALTER TABLE catalogo_infracciones ADD CONSTRAINT catalogo_infracciones_categoria_actor_check CHECK (categoria_actor IN ('conductor_general', 'motociclista', 'ciclista', 'concesionario_operador'))");
        }

        // Tabla de configuración del sistema (para el valor de UMA, nunca hardcodeado)
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'clave' => ['type' => 'VARCHAR', 'constraint' => 60],
            'valor' => ['type' => 'VARCHAR', 'constraint' => 60],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'actualizado_en' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('clave', 'uq_parametros_clave');
        $this->forge->createTable('parametros_sistema');

        // Tabla de mapeo boleta física -> catálogo oficial (pendiente de llenar con las casillas reales)
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'categoria_boleta' => ['type' => 'VARCHAR', 'constraint' => 60],
            'etiqueta_casilla' => ['type' => 'VARCHAR', 'constraint' => 150],
            'catalogo_infraccion_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('catalogo_infraccion_id', 'catalogo_infracciones', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('boleta_checklist_item');
    }

    public function down()
    {
        $this->forge->dropTable('boleta_checklist_item', true);
        $this->forge->dropTable('parametros_sistema', true);
        $this->forge->dropTable('catalogo_infracciones', true);
    }
}
