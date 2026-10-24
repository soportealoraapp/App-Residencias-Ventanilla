<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJuezCalificadorRole extends Migration
{
    private const ROLE = 'juez_calificador';

    public function up(): void
    {
        $roles = $this->db->table('roles');
        if ($roles->where('nombre', self::ROLE)->countAllResults() > 0) {
            return;
        }

        $roles->insert([
            'nombre'      => self::ROLE,
            'descripcion' => 'Consulta y calificación de boletas de infracción',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function down(): void
    {
        $rol = $this->db->table('roles')->where('nombre', self::ROLE)->get()->getRow();
        if ($rol === null) {
            return;
        }

        // Nunca elimina asignaciones existentes. Si el rol ya está en uso, se
        // conserva para no dejar cuentas sin su relación de autorización.
        $asignaciones = $this->db->table('user_roles')->where('role_id', $rol->id)->countAllResults();
        if ($asignaciones === 0) {
            $this->db->table('roles')->where('id', $rol->id)->delete();
        }
    }
}