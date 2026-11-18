<?php declare(strict_types=1);

namespace App\Controllers\Api;

use App\Libraries\AgenteTokenService;
use App\Models\AgenteTransitoModel;
use App\Models\AuditoriaModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class AgentesTransitoApiController extends Controller
{
    /**
     * Límite de intentos fallidos (auditoría M6). Se cuentan en la tabla de
     * auditoría, igual que en el login web, porque en Vercel no hay caché
     * persistente. Por placa frena adivinar la contraseña de un agente; por IP,
     * probar muchas placas desde el mismo equipo.
     */
    private const VENTANA_MINUTOS = 15;
    private const MAX_FALLOS_POR_PLACA = 5;
    private const MAX_FALLOS_POR_IP = 15;
    private const MENSAJE_CREDENCIALES = 'Placa o contraseña incorrectas.';
    private const HASH_SENUELO = '$2y$10$EHgK/vUWPycfFKsgTmpnZehdoMfpOuqjcMUd8T9wOebEyWhBKT3GO';

    /**
     * Inicio de sesión de agentes de tránsito desde la aplicación móvil.
     */
    public function login(): ResponseInterface
    {
        // Los encabezados CORS los pone el filtro global `cors` (Config\Cors, auditoría B1).
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

        $placa     = mb_substr(strtoupper(trim($placaInput)), 0, 30);
        $ip        = $this->request->getIPAddress();
        $auditoria = new AuditoriaModel();

        if ($auditoria->contarRecientes('login_agente_fallido', 'placa', $placa, self::VENTANA_MINUTOS) >= self::MAX_FALLOS_POR_PLACA
            || $auditoria->contarRecientes('login_agente_fallido', 'ip', $ip, self::VENTANA_MINUTOS) >= self::MAX_FALLOS_POR_IP) {
            $auditoria->registrar('agentes_transito', null, 'login_agente_bloqueado', null, ['placa' => $placa, 'ip' => $ip]);

            return $this->response->setStatusCode(429)
                ->setHeader('Retry-After', (string) (self::VENTANA_MINUTOS * 60))
                ->setJSON([
                    'success' => false,
                    'message' => 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.',
                ]);
        }

        $agente = (new AgenteTransitoModel())->getPorPlaca($placa);

        // Siempre se verifica contra un hash (real o señuelo) para que el tiempo
        // de respuesta no delate qué placas existen, y el mensaje es el mismo
        // para placa inexistente, contraseña incorrecta o agente inactivo.
        $hash        = (string) ($agente?->password_hash ?? '');
        $claveValida = password_verify($passwordInput, $hash !== '' ? $hash : self::HASH_SENUELO) && $hash !== '';

        if ($agente === null || ! $claveValida || (int) $agente->activo !== 1) {
            $motivo = match (true) {
                $agente === null => 'placa_inexistente',
                ! $claveValida   => 'contrasena_incorrecta',
                default          => 'agente_inactivo',
            };
            $auditoria->registrar('agentes_transito', $agente?->id !== null ? (int) $agente->id : null, 'login_agente_fallido', null, [
                'placa' => $placa, 'ip' => $ip, 'motivo' => $motivo,
            ]);

            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => self::MENSAJE_CREDENCIALES,
            ]);
        }

        $acceso = (new AgenteTokenService())->emitir((int) $agente->id);
        $auditoria->registrar('agentes_transito', (int) $agente->id, 'login_agente_exitoso', null, ['placa' => $placa, 'ip' => $ip]);

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

    /**
     * Cierre de sesión desde la app (auditoría B2): revoca el token en el
     * servidor para que una copia robada deje de servir de inmediato, en vez
     * de seguir viva hasta que caduque. Siempre responde 200: cerrar sesión
     * con un token ya revocado o vencido no es un error para la app.
     */
    public function logout(): ResponseInterface
    {
        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        $agenteId = (new AgenteTokenService())->revocar($this->request);
        if ($agenteId !== null) {
            (new AuditoriaModel())->registrar('agentes_transito', $agenteId, 'logout_agente', null, [
                'ip' => $this->request->getIPAddress(),
            ]);
        }

        return $this->response->setJSON(['success' => true]);
    }
}
