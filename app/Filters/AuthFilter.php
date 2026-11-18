<?php declare(strict_types=1);

namespace App\Filters;

use App\Models\AuditoriaModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Database;
use Config\Services;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return self::exigirSesionVigente();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }

    /**
     * Exige una sesión iniciada de una cuenta que siga existiendo y activa
     * (auditoría M3). Si un administrador desactiva o borra la cuenta, la
     * sesión abierta se cierra en la siguiente petición en lugar de seguir
     * viva hasta que caduque. RoleFilter usa esta misma revisión.
     */
    public static function exigirSesionVigente(): ?RedirectResponse
    {
        $session = Services::session();
        $userId  = (int) $session->get('user_id');
        if ($userId <= 0) {
            $session->setFlashdata('error', 'Acceso denegado: debes iniciar sesión');
            return redirect()->to('/auth/login');
        }

        $cuenta = Database::connect()->table('users')->select('activo')->where('id', $userId)->get()->getRow();
        if ($cuenta !== null && (int) $cuenta->activo === 1) {
            return null;
        }

        (new AuditoriaModel())->registrar('users', $userId, 'sesion_cerrada_cuenta_inactiva', $userId, [
            'motivo' => $cuenta === null ? 'cuenta_eliminada' : 'cuenta_desactivada',
        ]);
        // Vacía la sesión y emite un ID nuevo (equivale a destruirla), pero la
        // deja abierta para poder mostrar el aviso en la pantalla de login.
        $session->remove(array_keys((array) $session->get()));
        $session->regenerate(true);
        $session->setFlashdata('error', 'Tu sesión se cerró porque la cuenta está desactivada. Acude a la ventanilla de Tránsito y Transporte.');

        return redirect()->to('/auth/login');
    }
}
