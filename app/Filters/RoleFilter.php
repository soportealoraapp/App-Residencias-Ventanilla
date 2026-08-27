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

        if (empty($arguments)) {
            $session->setFlashdata('error', 'No tienes permiso para acceder a esta sección');
            return redirect()->to('/');
        }

        $rolesPermitidos = [];
        foreach ($arguments as $arg) {
            if (is_string($arg)) {
                foreach (explode(',', $arg) as $r) {
                    $trimmed = trim($r);
                    if ($trimmed !== '') {
                        $rolesPermitidos[] = $trimmed;
                    }
                }
            }
        }

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
