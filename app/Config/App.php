<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost:8080/';

    public array $allowedHostnames = [];

    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public string $defaultLocale = 'es';

    public bool $negotiateLocale = false;

    public array $supportedLocales = ['es', 'en'];

    public string $appTimezone = 'America/Mexico_City';

    public string $charset = 'UTF-8';

    public bool $forceGlobalSecureRequests = false;

    public function __construct()
    {
        // ----------------------------------------------------------------
        // Confianza de proxy: Vercel termina TLS en su edge y pasa las
        // peticiones a PHP como HTTP plano, pero incluye el header
        // X-Forwarded-Proto: https. Necesitamos trustear ese header para
        // que CI4 detecte la peticion como HTTPS y NO emita un redirect.
        // En un entorno serverless de Vercel toda la infraestructura es de
        // confianza, por lo que aceptamos cualquier IP de proxy.
        // ----------------------------------------------------------------
        $this->proxyIPs = [
            '0.0.0.0/0' => 'X-Forwarded-For',
        ];

        $envBaseUrl = getenv('APP_BASEURL') !== false ? getenv('APP_BASEURL') : getenv('app.baseURL');
        if (is_string($envBaseUrl) && $envBaseUrl !== '') {
            $this->baseURL = rtrim($envBaseUrl, '/') . '/';
        }

        $host = parse_url($this->baseURL, PHP_URL_HOST);
        if (is_string($host) && $host !== '' && ! in_array($host, $this->allowedHostnames, true)) {
            $this->allowedHostnames[] = $host;
        }

        $hostnamesEnv = getenv('APP_ALLOWED_HOSTNAMES') !== false ? getenv('APP_ALLOWED_HOSTNAMES') : getenv('app.allowedHostnames');
        $extraHosts = array_filter(array_map('trim', explode(',', (string) $hostnamesEnv)));
        foreach ($extraHosts as $h) {
            if ($h !== '' && ! in_array($h, $this->allowedHostnames, true)) {
                $this->allowedHostnames[] = $h;
            }
        }

        // ----------------------------------------------------------------
        // forceGlobalSecureRequests: solo activar si el entorno NO es
        // serverless (Vercel/Lambda). En serverless, el edge ya garantiza
        // HTTPS; activar force_https() aqui causaria un redirect loop
        // porque PHP ve la peticion como HTTP aunque el cliente use HTTPS.
        //
        // En su lugar, solo marcamos las cookies como 'secure' cuando el
        // baseURL es https://, lo cual es suficiente para produccion.
        // ----------------------------------------------------------------
        $isServerless = (getenv('VERCEL') !== false || getenv('VERCEL_ENV') !== false);

        $envForceHttps = getenv('APP_FORCE_HTTPS') !== false ? getenv('APP_FORCE_HTTPS') : getenv('app.forceHTTPS');

        if ($isServerless) {
            // En Vercel: nunca forzar redirect HTTPS (ya viene por HTTPS),
            // pero si asegurar las cookies si el baseURL usa HTTPS.
            $scheme = parse_url($this->baseURL, PHP_URL_SCHEME);
            if ($scheme === 'https') {
                $this->cookie['secure'] = true;
                $this->CSRFSameSite     = 'Lax';
            }
        } else {
            // Entorno local o servidor tradicional: comportamiento normal.
            if ($envForceHttps === false || $envForceHttps === '') {
                $scheme        = parse_url($this->baseURL, PHP_URL_SCHEME);
                $envForceHttps = ($scheme === 'https') ? 'true' : 'false';
            }
            if (filter_var($envForceHttps, FILTER_VALIDATE_BOOLEAN)) {
                $this->forceGlobalSecureRequests = true;
                $this->cookie['secure']          = true;
                $this->CSRFSameSite              = 'Lax';
            }
        }
    }

    public array $proxyIPs = [];

    public bool $CSPEnabled = false;
    public bool $CSPReportOnly = false;

    public array $CSP = [
        'default-src' => [
            'self' => true,
        ],
        'script-src' => [
            'self' => true,
        ],
        'style-src' => [
            'self' => true,
        ],
        'img-src' => [
            'self' => true,
        ],
    ];

    public string $CSRFTokenName = 'csrf_token_name';
    public string $CSRFHeaderName = 'X-CSRF-TOKEN';
    public string $CSRFCookieName = 'csrf_cookie_name';
    public int $CSRFExpire = 7200;
    public bool $CSRFRegenerate = true;
    public bool $CSRFRedirect = true;
    public string $CSRFSameSite = 'Lax';

    public array $cookie = [
        'prefix'   => '',
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    public string $defaultNamespace = 'App';
    public string $defaultController = 'Home';
    public string $defaultMethod = 'index';
    public bool $translateURIDashes = false;
    public $CGIMode = null;
    public bool $safeHeaderParsing = false;
    public int $bodyLimit = 0;
}
