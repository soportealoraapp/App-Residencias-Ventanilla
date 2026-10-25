<?php declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * COB-08: el ciudadano que llega a ventanilla puede identificarse con solo su
 * nombre. Esa persona queda registrada como ciudadano sin acceso al portal:
 * la cuenta existe (es dueña de sus trámites y de su historial), pero no tiene
 * usuario ni contraseña hasta que reclama la cuenta.
 *
 * Para eso `users.password_hash` pasa a admitir NULL. Un hash nulo no se puede
 * verificar, así que el login ya lo rechaza: AuthController cae al hash señuelo
 * cuando la columna viene vacía y responde "Usuario o contraseña incorrectos".
 *
 * En SQLite3 el Forge de CI4 atiende el CHANGE reconstruyendo la tabla. Los
 * índices uq_users_username y uq_users_email se declaran con nombre explícito
 * desde la migración 000002 justamente para sobrevivir a esa reconstrucción.
 *
 * Equivale a supabase/migrations/0014_ciudadano_sin_acceso.sql: hay que
 * mantener las dos rutas sincronizadas.
 */
class PermitirCiudadanoSinAcceso extends Migration
{
    public function up()
    {
        $esSqlite = $this->db->DBDriver === 'SQLite3';

        // SQLite no puede cambiar la nulabilidad con ALTER TABLE: el Forge de
        // CI4 reconstruye la tabla renombrando users a temp_users. Desde SQLite
        // 3.25 ese renombre reescribe las claves foraneas de otras tablas, asi
        // que user_roles.user_id terminaria apuntando a temp_users (una tabla
        // que se borra al final) y cualquier alta de rol fallaria. El modo de
        // renombre anterior evita esa reescritura: las relaciones siguen
        // apuntando a users, que se vuelve a crear en la misma transaccion.
        if ($esSqlite) {
            $this->db->query('PRAGMA legacy_alter_table = ON');
        }

        try {
            $this->forge->modifyColumn('users', [
                'password_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
            ]);
        } finally {
            if ($esSqlite) {
                $this->db->query('PRAGMA legacy_alter_table = OFF');
            }
        }
    }

    public function down()
    {
        // No se revierte: volver a NOT NULL obligaría a inventar contraseñas
        // para las cuentas sin acceso, que es exactamente lo que COB-08 evita.
    }
}
