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
}
