<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAgenteTokensTable extends Migration
{
    public function up(): void
    {
        // Tokens de acceso de la app móvil. Solo se guarda el hash SHA-256 del token.
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'agente_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expira_en' => ['type' => 'DATETIME'],
            'ultimo_uso_en' => ['type' => 'DATETIME', 'null' => true],
            'revocado_en' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('token_hash', 'uq_agentes_transito_tokens_hash');
        $this->forge->addKey('agente_id', false, false, 'idx_agentes_transito_tokens_agente');
        $this->forge->addForeignKey('agente_id', 'agentes_transito', 'id', '', 'CASCADE');
        $this->forge->createTable('agentes_transito_tokens', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('agentes_transito_tokens', true);
    }
}
