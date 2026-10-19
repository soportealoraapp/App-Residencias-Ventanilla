<?php declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Shell de la app de agentes (el export web de Expo) para las rutas profundas del
 * enrutador del cliente.
 *
 * /agentes se sirve como archivo estático, pero un enlace directo a
 * /agentes/login, o simplemente refrescar esa pantalla, necesita el fallback del
 * SPA. Ese fallback no puede darlo vercel.json: cuando la regla consume segmentos
 * extra y el destino es un archivo, la plataforma los anexa al destino, busca
 * /public/agentes/index.html/login y responde su propio 404 (x-vercel-error:
 * NOT_FOUND) sin dejar caer en la regla siguiente. Aquí el fallback vive en PHP,
 * que sí recibe la URI original intacta.
 *
 * Servir el mismo HTML desde cualquier profundidad es seguro porque el export usa
 * rutas absolutas (/agentes/_expo/..., /agentes/favicon.ico) y esos archivos los
 * entrega Vercel con sus propias reglas estáticas.
 */
class AgentesWebController extends Controller
{
    public function shell(): ResponseInterface
    {
        $archivo = FCPATH . 'agentes' . DIRECTORY_SEPARATOR . 'index.html';

        if (! is_file($archivo)) {
            // Sin export no hay aplicación que servir: es un problema del despliegue
            // y debe gritar, no responder una página vacía.
            throw new \RuntimeException(
                'Falta el export de la app de agentes en public/agentes/index.html. '
                . 'Genérelo con scripts/exportar_agentes_web.ps1 y vuelva a desplegar.'
            );
        }

        return $this->response
            ->setContentType('text/html')
            // El HTML referencia el bundle por su hash: cachearlo sería quedar
            // apuntando a una versión vieja después del siguiente export.
            ->setHeader('Cache-Control', 'no-store')
            ->setBody((string) file_get_contents($archivo));
    }
}
