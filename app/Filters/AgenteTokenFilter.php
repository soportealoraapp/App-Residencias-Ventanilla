<?php declare(strict_types=1);

namespace App\Filters;

use App\Libraries\AgenteTokenService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Exige un token Bearer válido de un agente activo en los endpoints de la app móvil.
 */
class AgenteTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // El preflight CORS no lleva credenciales; lo responde el controlador.
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return null;
        }

        if ((new AgenteTokenService())->agenteDeSolicitud($request) !== null) {
            return null;
        }

        $origin = $request->getHeaderLine('Origin') ?: '*';

        return Services::response()
            ->setStatusCode(401)
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('WWW-Authenticate', 'Bearer')
            ->setJSON([
                'success' => false,
                'message' => 'Sesión no válida o expirada. Inicia sesión nuevamente.',
            ]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
