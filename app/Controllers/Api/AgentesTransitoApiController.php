<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\AgenteTokenService;
use App\Models\AgenteTransitoModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class AgentesTransitoApiController extends Controller
{
    /**
     * Inicio de sesión de agentes de tránsito desde la aplicación móvil.
     */
    public function login(): ResponseInterface
    {
        $this->configureCors();
        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        try {
            $payload = $this->request->getJSON(true);
        } catch (\Throwable) {
            $payload = null;
        }

        if (! is_array($payload) || empty($payload)) {
            $payload = $this->request->getPost();
        }

        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'La solicitud no contiene datos válidos.',
            ]);
        }

        $placaInput = $payload['placa'] ?? $payload['usuario'] ?? null;
        $passwordInput = $payload['password'] ?? null;

        if (
            ! is_string($placaInput)
            || ! is_string($passwordInput)
            || trim($placaInput) === ''
            || $passwordInput === ''
        ) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Ingresa tu placa/usuario y contraseña.',
            ]);
        }

        $placa = strtoupper(trim($placaInput));
        $agenteModel = new AgenteTransitoModel();
        $agente = $agenteModel->getPorPlaca($placa);

        if ($agente === null || (int)$agente->activo !== 1) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Credenciales inválidas o agente no activo.',
            ]);
        }

        if (! password_verify($passwordInput, $agente->password_hash)) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Credenciales inválidas.',
            ]);
        }

        $acceso = (new AgenteTokenService())->emitir((int) $agente->id);

        return $this->response->setStatusCode(200)->setJSON([
            'success'    => true,
            'token'      => $acceso['token'],
            'token_type' => 'Bearer',
            'expira_en'  => $acceso['expira_en'],
            'agente'  => [
                'id'              => (int) $agente->id,
                'placa'           => $agente->placa,
                'nombre_completo' => $agente->nombre_completo,
                'rol'             => $agente->rol,
                'sector'          => $agente->sector,
                'activo'          => (int) $agente->activo,
            ],
            'user'    => [
                'id'     => (int) $agente->id,
                'nombre' => $agente->nombre_completo,
                'rol'    => $agente->rol,
            ],
        ]);
    }

    private function configureCors(): void
    {
        $origin = $this->request->getHeaderLine('Origin') ?: '*';
        $this->response
            ->setHeader('Access-Control-Allow-Origin', $origin)
            ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin')
            ->setHeader('Access-Control-Max-Age', '7200');
    }

}
