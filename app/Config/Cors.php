<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * CORS de la API (filtro global `cors`).
 *
 * Auditoría B1: lista fija de orígenes, sin comodines de `*.vercel.app` y sin
 * reflejar el `Origin` que mande cualquiera. La app de agentes web se sirve
 * desde el mismo dominio (/agentes) y la app nativa no envía `Origin`, así que
 * ninguna de las dos necesita estar en la lista; solo hacen falta los orígenes
 * de desarrollo (Expo en localhost) y, si se publica la app web en otro
 * dominio, ese dominio en APP_CORS_ORIGINS (separados por coma).
 */
class Cors extends BaseConfig
{
    public array $default = [
        'allowedOrigins'         => [],
        'allowedOriginsPatterns' => [],
        'supportsCredentials'    => false,
        'allowedHeaders'         => ['Accept', 'Content-Type', 'Authorization', 'X-Requested-With', 'Origin'],
        'exposedHeaders'         => [],
        'allowedMethods'         => ['GET', 'POST', 'OPTIONS'],
        'maxAge'                 => 7200,
    ];

    public function __construct()
    {
        parent::__construct();

        $origenes = [];

        // El propio dominio de la app (producción o demo).
        $base = parse_url((string) config(App::class)->baseURL);
        if (isset($base['scheme'], $base['host'])) {
            $origenes[] = $base['scheme'] . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');
        }

        foreach (explode(',', (string) getenv('APP_CORS_ORIGINS')) as $origen) {
            $origen = rtrim(trim($origen), '/');
            if (preg_match('#^https?://[A-Za-z0-9.-]+(:\d+)?$#', $origen) === 1) {
                $origenes[] = $origen;
            }
        }

        // Expo y el servidor de desarrollo, solo fuera de producción.
        if (ENVIRONMENT !== 'production') {
            array_push($origenes, 'http://localhost:8081', 'http://localhost:8080', 'http://localhost:19006');
        }

        $this->default['allowedOrigins'] = array_values(array_unique($origenes));
    }
}
