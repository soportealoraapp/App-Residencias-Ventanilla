<?php declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'auth'          => \App\Filters\AuthFilter::class,
        'role'          => \App\Filters\RoleFilter::class,
        'adminRedirect' => \App\Filters\AdminRedirectFilter::class,
        'agenteToken'   => \App\Filters\AgenteTokenFilter::class,
    ];

    public array $globals = [
        'before' => [
            'cors',
            'csrf' => ['except' => ['api/*']],
            'invalidchars',
        ],
        'after' => [
            'toolbar',
            'secureheaders',
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        if (ENVIRONMENT === 'testing') {
            unset($this->globals['before']['csrf']);
        }
    }

    public array $methods = [];

    public array $filters = [
        'adminRedirect' => [
            'before' => [
                'portal',
                'portal/dashboard',
                'portal/mis-solicitudes',
                'portal/solicitud/*',
                'portal/mi-perfil',
            ],
        ],
    ];
}
