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
        $envBaseUrl = getenv('app.baseURL');
        if (is_string($envBaseUrl) && $envBaseUrl !== '') {
            $this->baseURL = rtrim($envBaseUrl, '/') . '/';
        }

        $host = parse_url($this->baseURL, PHP_URL_HOST);
        if (is_string($host) && $host !== '' && ! in_array($host, $this->allowedHostnames, true)) {
            $this->allowedHostnames[] = $host;
        }

        $extraHosts = array_filter(array_map('trim', explode(',', (string) getenv('app.allowedHostnames'))));
        foreach ($extraHosts as $h) {
            if ($h !== '' && ! in_array($h, $this->allowedHostnames, true)) {
                $this->allowedHostnames[] = $h;
            }
        }

        $isHttpsEnv = getenv('app.forceHTTPS');
        if ($isHttpsEnv === false || $isHttpsEnv === '') {
            $scheme = parse_url($this->baseURL, PHP_URL_SCHEME);
            $isHttpsEnv = ($scheme === 'https') ? 'true' : 'false';
        }
        $forceSecure = filter_var($isHttpsEnv, FILTER_VALIDATE_BOOLEAN);
        if ($forceSecure) {
            $this->forceGlobalSecureRequests = true;
            $this->cookie['secure'] = true;
            $this->CSRFSameSite = 'Lax';
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
