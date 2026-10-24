<?php declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminRedirectFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = \Config\Services::session();
        $roles = (array) $session->get('roles');

        if (in_array('administrador', $roles, true) || in_array('operador_ventanilla', $roles, true)) {
            return redirect()->to('/admin/dashboard');
        }
        if (in_array('juez_calificador', $roles, true)) {
            return redirect()->to('/juez');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
