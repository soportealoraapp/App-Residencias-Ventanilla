<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoleViews extends Migration
{
    public function up()
    {
        $this->db->query("DROP VIEW IF EXISTS ciudadanos");
        $this->db->query("
            CREATE VIEW ciudadanos AS
            SELECT u.*
            FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE r.nombre = 'ciudadano'
        ");

        $this->db->query("DROP VIEW IF EXISTS administradores");
        $this->db->query("
            CREATE VIEW administradores AS
            SELECT u.*
            FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE r.nombre IN ('administrador', 'operador_ventanilla')
        ");
    }

    public function down()
    {
        $this->db->query("DROP VIEW IF EXISTS ciudadanos");
        $this->db->query("DROP VIEW IF EXISTS administradores");
    }
}
