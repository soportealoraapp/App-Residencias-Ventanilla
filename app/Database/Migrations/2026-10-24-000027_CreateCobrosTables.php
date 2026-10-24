<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * COB-01: base del módulo de cobros.
 *
 * `cobros` guarda un cobro por trámite (solicitud) o por multa (boleta), con su
 * desglose (base, descuento, recargo, condonado, total) y su estatus
 * (pendiente, aprobado, cancelado). `cobro_movimientos` es su historial.
 *
 * Espejo para Supabase: supabase/migrations/0012_cobros.sql
 */
class CreateCobrosTables extends Migration
{
    public function up()
    {
        $idType = $this->db->DBDriver === 'SQLite3' ? 'INTEGER' : 'BIGSERIAL';
        $dinero = ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => false, 'default' => 0];

        $this->forge->addField([
            'id'                   => ['type' => $idType, 'auto_increment' => true],
            'folio'                => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'origen'               => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'solicitud_id'         => ['type' => 'BIGINT', 'null' => true],
            'boleta_id'            => ['type' => 'BIGINT', 'null' => true],
            'ciudadano_id'         => ['type' => 'BIGINT', 'null' => true],
            'nombre_pagador'       => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => false],
            'concepto'             => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'monto_base'           => $dinero,
            'descuento'            => $dinero,
            'recargo'              => $dinero,
            'condonado'            => $dinero,
            'total'                => $dinero,
            'detalle_calculo'      => ['type' => 'TEXT', 'null' => true],
            'estatus'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false, 'default' => 'pendiente'],
            'pendiente_documentos' => ['type' => 'SMALLINT', 'null' => false, 'default' => 0],
            'motivo'               => ['type' => 'TEXT', 'null' => true],
            'lugar_pago'           => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'referencia_pago'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'folio_recibo'         => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'cobrado_por'          => ['type' => 'BIGINT', 'null' => true],
            'fecha_cobro'          => ['type' => 'TIMESTAMP', 'null' => true],
            'created_by'           => ['type' => 'BIGINT', 'null' => true],
            'created_at'           => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'           => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('folio');
        $this->forge->addUniqueKey('folio_recibo');
        $this->forge->addKey('estatus');
        $this->forge->addKey('origen');
        $this->forge->addKey('solicitud_id');
        $this->forge->addKey('boleta_id');
        $this->forge->addKey('fecha_cobro');
        // Un cobro es un registro contable: borrar la solicitud o la boleta no lo borra.
        $this->forge->addForeignKey('solicitud_id', 'solicitudes', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('boleta_id', 'boletas_infracciones', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('ciudadano_id', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->addForeignKey('cobrado_por', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('cobros', true);

        $this->forge->addField([
            'id'               => ['type' => $idType, 'auto_increment' => true],
            'cobro_id'         => ['type' => 'BIGINT', 'null' => false],
            'accion'           => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => false],
            'estatus_anterior' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'estatus_nuevo'    => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => false],
            'motivo'           => ['type' => 'TEXT', 'null' => true],
            'detalle'          => ['type' => 'TEXT', 'null' => true],
            'usuario_id'       => ['type' => 'BIGINT', 'null' => true],
            'fecha'            => ['type' => 'TIMESTAMP', 'null' => false],
            'created_at'       => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at'       => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('cobro_id');
        $this->forge->addForeignKey('cobro_id', 'cobros', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('usuario_id', 'users', 'id', 'RESTRICT', 'SET NULL');
        $this->forge->createTable('cobro_movimientos', true);
    }

    public function down()
    {
        $this->forge->dropTable('cobro_movimientos', true);
        $this->forge->dropTable('cobros', true);
    }
}
