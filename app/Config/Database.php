<?php declare(strict_types=1);

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    public string $defaultGroup = 'default';

    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'database.sqlite',
        'DBDriver'     => 'SQLite3',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8',
        'DBCollat'     => '',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 5432,
        'numberNative' => false,
        'foundRows'    => false,
        'foreignKeys'  => true,
        'busyTimeout'  => 1000,
        'schema'       => 'public',
        'sslmode'      => 'require',
    ];

    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 5432,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'schema'      => 'public',
    ];

    /**
     * Resuelve una variable de entorno con orden de prioridad:
     *  1. POSTGRES_*       (estandar Supabase)
     *  2. DATABASE_DEFAULT_*  (formato Vercel compatible)
     *  3. database.default.* (formato legacy CodeIgniter con puntos)
     *
     * @param list<string> $candidates Lista de nombres de variable a probar en orden
     * @return false|string false si no existe, string con el valor si existe
     */
    private function resolveEnv(array $candidates)
    {
        foreach ($candidates as $name) {
            $val = getenv($name);
            if ($val !== false && $val !== '') {
                return $val;
            }
        }
        return false;
    }

    public function __construct()
    {
        parent::__construct();

        $hostname = $this->resolveEnv(['POSTGRES_HOST', 'DATABASE_DEFAULT_HOSTNAME', 'database.default.hostname']);
        if (is_string($hostname)) {
            $this->default['hostname'] = $hostname;
        }

        $database = $this->resolveEnv(['POSTGRES_DATABASE', 'DATABASE_DEFAULT_DATABASE', 'database.default.database']);
        if (is_string($database)) {
            $this->default['database'] = $database;
        }

        $username = $this->resolveEnv(['POSTGRES_USER', 'DATABASE_DEFAULT_USERNAME', 'database.default.username']);
        if (is_string($username)) {
            $this->default['username'] = $username;
        }

        $password = $this->resolveEnv(['POSTGRES_PASSWORD', 'DATABASE_DEFAULT_PASSWORD', 'database.default.password']);
        if (is_string($password)) {
            $this->default['password'] = $password;
        }

        $dbdriver = $this->resolveEnv(['DATABASE_DEFAULT_DBDRIVER', 'database.default.DBDriver']);
        if (is_string($dbdriver)) {
            $this->default['DBDriver'] = $dbdriver;
        }

        $dbprefix = $this->resolveEnv(['DATABASE_DEFAULT_DBPREFIX', 'database.default.DBPrefix']);
        if ($dbprefix !== false) {
            $this->default['DBPrefix'] = $dbprefix;
        }

        $port = $this->resolveEnv(['POSTGRES_PORT', 'DATABASE_DEFAULT_PORT', 'database.default.port']);
        if (is_string($port)) {
            $this->default['port'] = (int) $port;
        }

        $charset = $this->resolveEnv(['DATABASE_DEFAULT_CHARSET', 'database.default.charset']);
        if (is_string($charset)) {
            $this->default['charset'] = $charset;
        }

        $schema = $this->resolveEnv(['DATABASE_DEFAULT_SCHEMA', 'database.default.schema']);
        if (is_string($schema)) {
            $this->default['schema'] = $schema;
        }

        $sslmode = $this->resolveEnv(['DATABASE_DEFAULT_SSLMODE', 'database.default.sslmode']);
        if (is_string($sslmode)) {
            $this->default['sslmode'] = $sslmode;
        }

        $foreignKeys = $this->resolveEnv(['DATABASE_DEFAULT_FOREIGNKEYS', 'database.default.foreignKeys']);
        if (is_string($foreignKeys)) {
            $this->default['foreignKeys'] = filter_var($foreignKeys, FILTER_VALIDATE_BOOLEAN);
        }

        $busyTimeout = $this->resolveEnv(['DATABASE_DEFAULT_BUSYTIMEOUT', 'database.default.busyTimeout']);
        if (is_string($busyTimeout)) {
            $this->default['busyTimeout'] = (int) $busyTimeout;
        }
    }
}
