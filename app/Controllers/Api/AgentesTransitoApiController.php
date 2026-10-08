<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\AgenteTransitoModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class AgentesTransitoApiController extends Controller
{
    public function registrar(): ResponseInterface
    {
        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'La solicitud no contiene datos válidos.',
            ]);
        }

        $placaInput = $payload['placa'] ?? null;
        $nombreInput = $payload['nombre_completo'] ?? null;
        $passwordInput = $payload['password'] ?? null;

        if (
            ! is_string($placaInput)
            || ! is_string($nombreInput)
            || ! is_string($passwordInput)
            || trim($placaInput) === ''
            || trim($nombreInput) === ''
            || $passwordInput === ''
        ) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'Completa todos los campos requeridos.',
            ]);
        }

        $placa = strtoupper(trim($placaInput));
        $nombre = trim($nombreInput);
        $password = $passwordInput;

        if (strlen($placa) > 20 || strlen($nombre) < 3 || strlen($nombre) > 180) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'La placa o el nombre no cumplen con el formato permitido.',
            ]);
        }

        if (strlen($password) < 3 || strlen($password) > 50) {
            return $this->response->setStatusCode(422)->setJSON([
                'success' => false,
                'message' => 'La contraseña debe tener entre 3 y 50 caracteres.',
            ]);
        }

        $agenteModel = new AgenteTransitoModel();
        if ($agenteModel->getPorPlaca($placa) !== null) {
            return $this->response->setStatusCode(409)->setJSON([
                'success' => false,
                'message' => 'Ya existe un agente registrado con esa placa.',
            ]);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        if ($passwordHash === false) {
            log_message('error', 'No se pudo generar el hash de contraseña para el registro de agente.');
            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No fue posible completar el registro.',
            ]);
        }

        try {
            $inserted = $agenteModel->insert([
                'placa' => $placa,
                'nombre_completo' => $nombre,
                'password_hash' => $passwordHash,
                'rol' => 'operativo',
                'sector' => 'Sector Centro',
                'activo' => 1,
            ]);

            if ($inserted === false) {
                if ($agenteModel->getPorPlaca($placa) !== null) {
                    return $this->response->setStatusCode(409)->setJSON([
                        'success' => false,
                        'message' => 'Ya existe un agente registrado con esa placa.',
                    ]);
                }

                log_message('error', 'Falló la inserción del agente móvil en agentes_transito.');
                return $this->response->setStatusCode(500)->setJSON([
                    'success' => false,
                    'message' => 'No fue posible completar el registro.',
                ]);
            }
        } catch (Throwable $error) {
            log_message('error', 'Error al registrar agente móvil: ' . $error->getMessage());

            try {
                if ($agenteModel->getPorPlaca($placa) !== null) {
                    return $this->response->setStatusCode(409)->setJSON([
                        'success' => false,
                        'message' => 'Ya existe un agente registrado con esa placa.',
                    ]);
                }
            } catch (Throwable $lookupError) {
                log_message('error', 'No se pudo verificar la placa luego de fallar el registro: ' . $lookupError->getMessage());
            }

            return $this->response->setStatusCode(500)->setJSON([
                'success' => false,
                'message' => 'No fue posible completar el registro.',
            ]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'success' => true,
            'message' => 'Cuenta creada correctamente. Ya puedes iniciar sesión.',
        ]);
    }
}
