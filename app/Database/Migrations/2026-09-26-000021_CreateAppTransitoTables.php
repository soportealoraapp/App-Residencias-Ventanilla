<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppTransitoTables extends Migration
{
    public function up(): void
    {
        // 1. TABLA: agentes_transito
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'placa' => ['type' => 'VARCHAR', 'constraint' => 20],
            'nombre_completo' => ['type' => 'VARCHAR', 'constraint' => 180],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'rol' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'operativo'],
            'sector' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'Sector Centro'],
            'activo' => ['type' => 'TINYINT', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('placa', 'uq_agentes_transito_placa');
        $this->forge->createTable('agentes_transito', true);

        // 2. TABLA: parametros_app
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'clave' => ['type' => 'VARCHAR', 'constraint' => 100],
            'valor' => ['type' => 'TEXT'],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'tipo' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'texto'],
            'editable' => ['type' => 'TINYINT', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('clave', 'uq_parametros_app_clave');
        $this->forge->createTable('parametros_app', true);

        // 3. TABLA: catalogo_infracciones_app
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fundamento_legal' => ['type' => 'VARCHAR', 'constraint' => 100],
            'descripcion' => ['type' => 'TEXT'],
            'categoria' => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => 'GENERAL'],
            'monto_min_uma' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 3.00],
            'monto_max_uma' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'default' => 10.00],
            'activo' => ['type' => 'TINYINT', 'default' => 1],
            'orden' => ['type' => 'INT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('fundamento_legal', 'uq_catalogo_infracciones_app_fundamento');
        $this->forge->createTable('catalogo_infracciones_app', true);

        // 4. TABLA: boletas_infracciones
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'folio' => ['type' => 'VARCHAR', 'constraint' => 50],
            'agente_placa' => ['type' => 'VARCHAR', 'constraint' => 20],
            'agente_nombre' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'fecha_infraccion' => ['type' => 'DATE'],
            'hora_infraccion' => ['type' => 'TIME'],
            'lugar' => ['type' => 'TEXT'],
            'latitud' => ['type' => 'DECIMAL', 'constraint' => '10,7', 'null' => true],
            'longitud' => ['type' => 'DECIMAL', 'constraint' => '11,7', 'null' => true],
            'conductor_ausente' => ['type' => 'TINYINT', 'default' => 0],
            'infractor_nombre' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'infractor_domicilio' => ['type' => 'TEXT', 'null' => true],
            'infractor_licencia' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'vehiculo_placas' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'sin_placas' => ['type' => 'TINYINT', 'default' => 0],
            'vehiculo_marca' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'vehiculo_linea' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'vehiculo_color' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'vehiculo_tipo' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'particular'],
            'falta_fundamento_legal' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'falta_descripcion' => ['type' => 'TEXT', 'null' => true],
            'falta_categoria' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'falta_monto_min_uma' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'falta_monto_max_uma' => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
            'faltas_json' => ['type' => 'TEXT', 'null' => true],
            'hechos' => ['type' => 'TEXT', 'null' => true],
            'garantias_retenidas' => ['type' => 'TEXT', 'null' => true],
            'inventario_grua' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'foto_placa_url' => ['type' => 'TEXT', 'null' => true],
            'foto_contexto_url' => ['type' => 'TEXT', 'null' => true],
            'foto_documento_url' => ['type' => 'TEXT', 'null' => true],
            'estado' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'recibida'],
            'sincronizado_en' => ['type' => 'DATETIME', 'null' => true],
            'creado_en_dispositivo' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folio', 'uq_boletas_infracciones_folio');
        $this->forge->addKey('agente_placa');
        $this->forge->addKey('fecha_infraccion');
        $this->forge->createTable('boletas_infracciones', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('boletas_infracciones', true);
        $this->forge->dropTable('catalogo_infracciones_app', true);
        $this->forge->dropTable('parametros_app', true);
        $this->forge->dropTable('agentes_transito', true);
    }
}
