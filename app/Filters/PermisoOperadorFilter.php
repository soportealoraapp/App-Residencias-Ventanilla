<?php declare(strict_types=1);

namespace App\Filters;

use App\Libraries\PermisosOperador;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Aplica los permisos del operador de ventanilla definidos por el
 * administrador en /admin/permisos-operador.
 *
 * Corre en todas las rutas del panel (admin, admin/*). El administrador nunca
 * se filtra; los usuarios que no son ventanilla se dejan pasar porque los
 * filtros auth/role de la ruta ya se encargan de ellos.
 */
class PermisoOperadorFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = [])
    {
        $session = Services::session();
        $userId = $session->get('user_id');
        if (!$userId) {
            // Sin sesión: dejan pasar; el filtro auth de la ruta resuelve.
            return null;
        }
        $userId = (int) $userId;

        $roles = $session->get('roles');
        if (!is_array($roles) || $roles === []) {
            // Sesiones antiguas sin el dato en sesión: se consulta la BD.
            $roles = (new UserModel())->conRoles($userId);
        }

        if (in_array('administrador', $roles, true)) {
            return null;
        }
        if (!in_array('operador_ventanilla', $roles, true)) {
            return null;
        }

        $clave = PermisosOperador::requeridoParaUri(uri_string());
        if ($clave === null || PermisosOperador::activo($clave)) {
            return null;
        }

        $def = PermisosOperador::definiciones()[$clave] ?? ['nombre' => 'esta sección'];
        $session->setFlashdata('error', 'No tienes permiso para usar "' . $def['nombre'] . '". Pide al administrador habilitarlo en Permisos del Operador.');

        return redirect()->to('/admin/dashboard')->setStatusCode(302);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
