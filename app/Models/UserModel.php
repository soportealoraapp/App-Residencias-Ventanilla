<?php declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'username',
        'email',
        'password_hash',
        'nombre_completo',
        'rfc',
        'telefono',
        'domicilio',
        'activo',
        'reset_token',
        'reset_expira',
    ];
    protected $useTimestamps    = true;
    protected $dateFormat       = 'datetime';
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $deletedField     = '';

    public function findByEmail(string $email)
    {
        return $this->where('email', $email)->first();
    }

    public function findByUsername(string $username)
    {
        return $this->where('username', $username)->first();
    }

    public function conRoles(int $userId): array
    {
        $builder = $this->db->table('user_roles ur');
        $builder->select('r.nombre');
        $builder->join('roles r', 'r.id = ur.role_id');
        $builder->where('ur.user_id', $userId);
        $query = $builder->get();

        $result = [];
        foreach ($query->getResult() as $row) {
            $result[] = $row->nombre;
        }

        return $result;
    }

    public function tieneRol(int $userId, string $rolNombre): bool
    {
        $roles = $this->conRoles($userId);

        return in_array($rolNombre, $roles, true);
    }

    /**
     * Asigna un rol a un usuario por nombre del rol.
     * Busca el rol en la tabla roles y crea el registro en user_roles.
     */
    public function asignarRol(int $userId, string $rolNombre): bool
    {
        $rol = $this->db->table('roles')
            ->where('nombre', $rolNombre)
            ->get()
            ->getRow();

        if ($rol === null) {
            return false;
        }

        $exists = $this->db->table('user_roles')
            ->where('user_id', $userId)
            ->where('role_id', $rol->id)
            ->countAllResults();

        if ($exists > 0) {
            return true; // ya tiene el rol
        }

        return (bool) $this->db->table('user_roles')->insert([
            'user_id'    => $userId,
            'role_id'    => $rol->id,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
