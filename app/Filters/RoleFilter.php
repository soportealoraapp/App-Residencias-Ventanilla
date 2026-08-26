<?php declare(strict_types=1);

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Services;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = [])
    {
        $session = Services::session();
        $userId = $session->get('user_id');
        if (!$userId) {
            $session->setFlashdata('error', 'Acceso denegado: debes iniciar sesión');
            return redirect()->to('/auth/login');
        }
        $userId = (int) $userId;

        $userModel = new UserModel();
        $rolesArgument = $arguments[0] ?? null;

        if ($rolesArgument === null) {
            $session->setFlashdata('error', 'No tienes permiso para acceder a esta sección');
            return redirect()->to('/');
        }

        $rolesPermitidos = array_map('trim', explode(',', $rolesArgument));
        $tieneAlgunRol = false;
        foreach ($rolesPermitidos as $rol) {
            if ($userModel->tieneRol($userId, $rol)) {
                $tieneAlgunRol = true;
                break;
            }
        }

        if (!$tieneAlgunRol) {
            $session->setFlashdata('error', 'No tienes permiso para acceder a esta sección');
            return redirect()->to('/');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
