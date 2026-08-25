<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Session\Handlers\BaseHandler;
use CodeIgniter\Session\Handlers\DatabaseHandler;
use CodeIgniter\Session\Handlers\FileHandler;

class Session extends BaseConfig
{
    /**
     * The session storage driver to use.
     */
    public string $driver = FileHandler::class;

    /**
     * The session cookie name.
     */
    public string $cookieName = 'ci_session';

    /**
     * Session expiration in seconds. 0 = expire when browser is closed.
     */
    public int $expiration = 7200;

    /**
     * The location to save sessions to.
     * For 'files' driver: a writable path.
     * For 'database' driver: a table name.
     */
    public string $savePath = WRITEPATH . 'session';

    /**
     * Whether to match the user's IP address when reading session data.
     */
    public bool $matchIP = false;

    /**
     * How many seconds between CI regenerating the session ID.
     */
    public int $timeToUpdate = 300;

    /**
     * Whether to destroy session data associated with the old session ID
     * when auto-regenerating the session ID.
     */
    public bool $regenerateDestroy = false;

    /**
     * DB Group for the database session.
     */
    public ?string $DBGroup = null;

    /**
     * Lock Retry Interval (microseconds) — RedisHandler only.
     */
    public int $lockRetryInterval = 100_000;

    /**
     * Lock Max Retries — RedisHandler only.
     */
    public int $lockMaxRetries = 300;

    public function __construct()
    {
        parent::__construct();

        if ($this->isVercel()) {
            $this->driver    = DatabaseHandler::class;
            $this->savePath  = 'ci_sessions';
            $this->DBGroup   = null; // usa el DBGroup 'default'
        }
    }

    private function isVercel(): bool
    {
        $vercel = getenv('VERCEL');
        if (is_string($vercel) && ($vercel === '1' || strtolower($vercel) === 'true')) {
            return true;
        }

        $vercelEnv = getenv('VERCEL_ENV');
        if (is_string($vercelEnv) && $vercelEnv !== '') {
            return true;
        }

        return false;
    }
}
